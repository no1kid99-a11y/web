<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Core\AppException;
use Core\Auth;
use Core\Database;

/**
 * Quản lý người dùng (chỉ Admin). Không xóa cứng tài khoản; mọi thao tác ghi nhật ký trong cùng giao dịch.
 */
class UserService
{
    /** Tạo tài khoản nội bộ (staff/admin) với mật khẩu tạm. Trả về [id, mật khẩu tạm]. */
    public function createInternal(array $admin, array $data): array
    {
        $this->assertAdmin($admin);
        if (!in_array($data['role'] ?? '', ['staff', 'admin'], true)) {
            throw new AppException('Chỉ tạo được tài khoản Nhân viên hoặc Quản trị viên. Khách hàng tự đăng ký.');
        }
        $email = mb_strtolower(trim((string)$data['email']));
        if (User::findByEmail($email)) {
            throw new AppException('Email đã được sử dụng.');
        }
        $temp = self::tempPassword();
        Database::begin();
        try {
            $id = Database::insert('users', [
                'name' => trim((string)$data['name']),
                'email' => $email,
                'password' => password_hash($temp, PASSWORD_DEFAULT),
                'phone' => trim((string)$data['phone']),
                'address' => ($data['address'] ?? '') !== '' ? trim((string)$data['address']) : null,
                'role' => $data['role'],
                'status' => 'active',
                'must_change_password' => 1,
                'created_by' => (int)$admin['id'],
                'created_at' => Database::now(),
                'updated_at' => Database::now(),
            ]);
            ActivityLogger::log($admin, 'user.create', 'user', $id, ['email' => $email, 'role' => role_label($data['role'])]);
            Database::commit();
            return [$id, $temp];
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public function update(array $admin, int $userId, array $data): void
    {
        $this->assertAdmin($admin);
        $u = User::find($userId);
        if (!$u) {
            throw new AppException('Không tìm thấy tài khoản.');
        }
        $email = mb_strtolower(trim((string)$data['email']));
        $other = User::findByEmail($email);
        if ($other && (int)$other['id'] !== $userId) {
            throw new AppException('Email đã được sử dụng.');
        }
        $new = [
            'name' => trim((string)$data['name']),
            'email' => $email,
            'phone' => trim((string)$data['phone']),
            'address' => ($data['address'] ?? '') !== '' ? trim((string)$data['address']) : null,
        ];
        $changes = [];
        foreach ($new as $k => $v) {
            if ((string)$u[$k] !== (string)$v) {
                $changes[$k] = ['old' => $u[$k], 'new' => $v];
            }
        }
        if (!$changes) {
            return;
        }
        Database::begin();
        try {
            Database::update('users', $new + ['updated_at' => Database::now()], 'id = :id', ['id' => $userId]);
            ActivityLogger::log($admin, 'user.update', 'user', $userId, $changes);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Khóa tài khoản.
     * - Không tự khóa mình, không khóa Admin cuối cùng.
     * - Nhân viên còn đơn giữ chỗ trong tương lai: chặn, trả danh sách để chuyển trước.
     * - Khách hàng: tự hủy đơn Chờ duyệt / Đã xác nhận với lý do "Tài khoản bị khóa".
     */
    public function lock(array $admin, int $userId): int
    {
        $this->assertAdmin($admin);
        if ($userId === (int)$admin['id']) {
            throw new AppException('Bạn không thể tự khóa tài khoản của mình.');
        }
        Database::begin();
        try {
            $u = Database::lockRow('users', $userId);
            if (!$u) {
                throw new AppException('Không tìm thấy tài khoản.');
            }
            if ($u['status'] === 'locked') {
                throw new AppException('Tài khoản đã bị khóa từ trước.');
            }
            if ($u['role'] === 'admin' && User::countActiveAdmins() <= 1) {
                throw new AppException('Không thể khóa quản trị viên cuối cùng còn hoạt động.');
            }
            $cancelled = 0;
            if ($u['role'] === 'staff') {
                $future = Booking::futureHoldingForStaff($userId);
                if ($future) {
                    throw new AppException(
                        'Nhân viên còn ' . count($future) . ' đơn giữ chỗ. Hãy chuyển các đơn này cho nhân viên khác trước khi khóa.',
                        ['bookings' => Booking::brief($future)],
                        'warning'
                    );
                }
            }
            if ($u['role'] === 'client') {
                $cancelled = Database::query(
                    "UPDATE bookings SET status = 'cancelled', cancel_reason = 'Tài khoản bị khóa', cancelled_by = ?, updated_at = ?
                     WHERE user_id = ? AND status IN ('pending','confirmed')",
                    [(int)$admin['id'], Database::now(), $userId]
                )->rowCount();
            }
            Database::update('users', ['status' => 'locked', 'updated_at' => Database::now()], 'id = :id', ['id' => $userId]);
            ActivityLogger::log($admin, 'user.lock', 'user', $userId, array_filter([
                'email' => $u['email'], 'cancelled_bookings' => $cancelled ?: null,
            ]));
            Database::commit();
            return $cancelled;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public function unlock(array $admin, int $userId): void
    {
        $this->assertAdmin($admin);
        Database::begin();
        try {
            $rows = Database::update('users', ['status' => 'active', 'updated_at' => Database::now()], "id = :id AND status = 'locked'", ['id' => $userId]);
            if ($rows === 0) {
                throw new AppException('Tài khoản không ở trạng thái bị khóa.');
            }
            ActivityLogger::log($admin, 'user.unlock', 'user', $userId);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Đổi vai trò: chỉ qua lại giữa staff và admin.
     * Client không nâng lên nội bộ; staff còn đơn giữ chỗ không thành admin; không hạ quyền Admin cuối cùng.
     */
    public function changeRole(array $admin, int $userId, string $role): void
    {
        $this->assertAdmin($admin);
        if (!in_array($role, ['staff', 'admin'], true)) {
            throw new AppException('Vai trò không hợp lệ.');
        }
        if ($userId === (int)$admin['id']) {
            throw new AppException('Bạn không thể tự đổi vai trò của mình.');
        }
        Database::begin();
        try {
            $u = Database::lockRow('users', $userId);
            if (!$u) {
                throw new AppException('Không tìm thấy tài khoản.');
            }
            if ($u['role'] === 'client') {
                throw new AppException('Không thể đổi tài khoản khách hàng thành tài khoản nội bộ. Hãy tạo tài khoản mới.');
            }
            if ($u['role'] === $role) {
                throw new AppException('Tài khoản đã có vai trò này.');
            }
            if ($u['role'] === 'staff' && Booking::futureHoldingForStaff($userId)) {
                throw new AppException('Nhân viên còn đơn giữ chỗ. Quản trị viên không nhận lịch, hãy chuyển các đơn này trước.');
            }
            if ($u['role'] === 'admin' && $u['status'] === 'active' && User::countActiveAdmins() <= 1) {
                throw new AppException('Không thể hạ quyền quản trị viên cuối cùng còn hoạt động.');
            }
            Database::update('users', ['role' => $role, 'updated_at' => Database::now()], 'id = :id', ['id' => $userId]);
            ActivityLogger::log($admin, 'user.role_change', 'user', $userId, ['from' => role_label($u['role']), 'to' => role_label($role)]);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /** Đặt lại mật khẩu: tạo mật khẩu tạm mới, bắt đổi ở lần đăng nhập sau. */
    public function resetPassword(array $admin, int $userId): string
    {
        $this->assertAdmin($admin);
        $u = User::find($userId);
        if (!$u) {
            throw new AppException('Không tìm thấy tài khoản.');
        }
        $temp = self::tempPassword();
        Database::begin();
        try {
            Database::update('users', [
                'password' => password_hash($temp, PASSWORD_DEFAULT),
                'must_change_password' => 1,
                'updated_at' => Database::now(),
            ], 'id = :id', ['id' => $userId]);
            ActivityLogger::log($admin, 'user.reset_password', 'user', $userId, ['email' => $u['email']]);
            Database::commit();
            return $temp;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function tempPassword(int $len = 10): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }

    private function assertAdmin(array $admin): void
    {
        if (!Auth::can('user.manage', $admin)) {
            throw new AppException('Chỉ quản trị viên mới quản lý người dùng.');
        }
    }
}
