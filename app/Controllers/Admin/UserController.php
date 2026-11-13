<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\HealthRecord;
use App\Models\Pet;
use App\Models\User;
use App\Models\Vaccination;
use App\Services\UserService;
use Core\Session;

/**
 * Quản lý người dùng. Mọi quy tắc (không tự khóa, Admin cuối cùng, nhân viên còn đơn...) nằm trong UserService.
 */
class UserController extends AdminController
{
    private function find(int $id): array
    {
        $u = User::find($id);
        $this->notFoundUnless($u, 'Không tìm thấy tài khoản.');
        return $u;
    }

    public function index(): void
    {
        $role = in_array($this->query('role'), ['client', 'staff', 'admin'], true) ? $this->query('role') : null;
        $status = in_array($this->query('status'), ['active', 'locked'], true) ? $this->query('status') : null;
        $q = $this->query('q');
        $this->render('admin/users/index', [
            'title' => 'Người dùng',
            'users' => User::search($role, $status, $q),
            'role' => $role, 'status' => $status, 'q' => $q,
            'counts' => User::countByRole(),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/users/create', ['title' => 'Tạo tài khoản nội bộ']);
    }

    public function store(): void
    {
        $data = [
            'name' => $this->input('name'), 'email' => mb_strtolower((string)$this->input('email', '')),
            'phone' => $this->input('phone'), 'address' => $this->input('address'), 'role' => $this->input('role'),
        ];
        $v = $this->validate($data, [
            'name' => 'required|min:2|max:100', 'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'required|phone', 'address' => 'nullable|max:255', 'role' => 'required|in:staff,admin',
        ], '/admin/users/create');
        [$id, $temp] = $this->attempt(fn() => (new UserService())->createInternal($this->user(), $v), '/admin/users/create', null, $data);
        Session::set('_temp_password', ['user_id' => $id, 'password' => $temp]);
        flash('success', 'Đã tạo tài khoản ' . e($v['name']) . '.');
        redirect('/admin/users/' . $id);
    }

    public function show(int $id): void
    {
        $u = $this->find($id);
        $data = [
            'title' => $u['name'],
            'u' => $u,
            'logs' => ActivityLog::forTarget('user', $id),
            'temp' => null,
            'isSelf' => $id === (int)$this->user()['id'],
        ];
        $temp = Session::pull('_temp_password');
        if ($temp && (int)$temp['user_id'] === $id) {
            $data['temp'] = $temp['password']; // hiển thị đúng một lần
        }
        if ($u['role'] === 'client') {
            $data['pets'] = \Core\Database::fetchAll('SELECT * FROM pets WHERE user_id = ? ORDER BY is_active DESC, created_at', [$id]);
            $data['bookings'] = Booking::list(['user_id' => $id], [], 100);
        } elseif ($u['role'] === 'staff') {
            $data['bookings'] = Booking::futureHoldingForStaff($id);
            $data['done'] = Booking::list(['staff_id' => $id], ['status' => 'completed'], 10);
        }
        if ($u['created_by']) {
            $data['creator'] = User::find((int)$u['created_by']);
        }
        $this->render('admin/users/show', $data);
    }

    public function update(int $id): void
    {
        $this->find($id);
        $data = ['name' => $this->input('name'), 'email' => mb_strtolower((string)$this->input('email', '')), 'phone' => $this->input('phone'), 'address' => $this->input('address')];
        $v = $this->validate($data, [
            'name' => 'required|min:2|max:100', 'email' => 'required|email|max:150|unique:users,email,' . $id,
            'phone' => 'required|phone', 'address' => 'nullable|max:255',
        ], "/admin/users/$id");
        $this->attempt(fn() => (new UserService())->update($this->user(), $id, $v), "/admin/users/$id", 'Đã lưu thông tin tài khoản.');
        redirect("/admin/users/$id");
    }

    public function lock(int $id): void
    {
        $u = $this->find($id);
        $n = $this->attempt(fn() => (new UserService())->lock($this->user(), $id), "/admin/users/$id");
        flash('success', 'Đã khóa tài khoản ' . e($u['name']) . '.' . ($n ? " Đã tự hủy $n đơn chưa thực hiện của khách." : ''));
        redirect("/admin/users/$id");
    }

    public function unlock(int $id): void
    {
        $u = $this->find($id);
        $this->attempt(fn() => (new UserService())->unlock($this->user(), $id), "/admin/users/$id", 'Đã mở khóa tài khoản ' . e($u['name']) . '.');
        redirect("/admin/users/$id");
    }

    public function role(int $id): void
    {
        $role = (string)$this->input('role');
        $this->attempt(fn() => (new UserService())->changeRole($this->user(), $id, $role), "/admin/users/$id", 'Đã đổi vai trò thành ' . e(role_label($role)) . '.');
        redirect("/admin/users/$id");
    }

    public function resetPassword(int $id): void
    {
        $this->find($id);
        $temp = $this->attempt(fn() => (new UserService())->resetPassword($this->user(), $id), "/admin/users/$id");
        Session::set('_temp_password', ['user_id' => $id, 'password' => $temp]);
        flash('success', 'Đã đặt lại mật khẩu. Người dùng phải đổi mật khẩu ở lần đăng nhập tới.');
        redirect("/admin/users/$id");
    }

    /** Admin xem hồ sơ thú cưng (chỉ đọc). */
    public function pet(int $id): void
    {
        $pet = Pet::find($id);
        $this->notFoundUnless($pet, 'Không tìm thấy thú cưng.');
        $this->render('admin/users/pet', [
            'title' => $pet['name'],
            'pet' => $pet,
            'owner' => User::find((int)$pet['user_id']),
            'vaccinations' => Vaccination::forPet($id),
            'records' => HealthRecord::forPet($id),
            'bookings' => array_values(array_filter(Booking::list(['user_id' => (int)$pet['user_id']], [], 200), fn($b) => (int)$b['pet_id'] === $id)),
        ]);
    }
}
