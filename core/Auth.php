<?php
declare(strict_types=1);

namespace Core;

/**
 * Xác thực và phân quyền.
 * user() luôn đọc lại tài khoản từ database theo user_id trong session,
 * không tin vai trò lưu trong session: bị khóa thì đăng xuất ngay, đổi vai trò có hiệu lực ngay.
 */
class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;
    private static ?array $permissions = null;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            if ($id) {
                $u = Database::fetch('SELECT * FROM users WHERE id = ?', [(int)$id]);
                if (!$u || $u['status'] !== 'active') {
                    self::logout();
                    if ($u) {
                        flash('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
                    }
                    return null;
                }
                self::$user = $u;
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int)$user['id']);
        self::$user = null;
        self::$loaded = false;
        Database::update('users', ['last_login_at' => Database::now()], 'id = :id', ['id' => $user['id']]);
    }

    public static function logout(): void
    {
        Session::forget('user_id');
        Session::regenerate();
        self::$user = null;
        self::$loaded = true;
    }

    /** Làm mới thông tin trong request hiện tại (sau khi sửa hồ sơ). */
    public static function refresh(): void
    {
        self::$loaded = false;
        self::$user = null;
    }

    public static function permissions(): array
    {
        return self::$permissions ??= require BASE_PATH . '/config/permissions.php';
    }

    /** Kiểm tra quyền chức năng theo bảng config/permissions.php. */
    public static function can(string $permission, ?array $user = null): bool
    {
        $user ??= self::user();
        if (!$user) {
            return false;
        }
        $roles = self::permissions()[$permission] ?? [];
        return in_array($user['role'], $roles, true);
    }

    public static function home(?array $user = null): string
    {
        $user ??= self::user();
        return match ($user['role'] ?? null) {
            'admin' => '/admin',
            'staff' => '/staff',
            'client' => '/bookings',
            default => '/',
        };
    }

    /** Chỉ dùng trong kiểm thử. */
    public static function actAs(?array $user): void
    {
        self::$user = $user;
        self::$loaded = true;
    }
}
