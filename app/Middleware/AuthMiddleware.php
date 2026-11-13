<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Session;

/**
 * Bắt buộc đăng nhập. Tài khoản phải đổi mật khẩu tạm thì bị chuyển tới trang đổi mật khẩu
 * trước mọi trang khác.
 */
class AuthMiddleware
{
    public function handle(?string $arg, string $path): void
    {
        $user = Auth::user();
        if (!$user) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                Session::set('_intended', $path . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
            }
            flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
            redirect('/login');
        }
        if ((int)$user['must_change_password'] === 1 && !in_array($path, ['/password', '/logout'], true)) {
            flash('warning', 'Bạn đang dùng mật khẩu tạm. Hãy đặt mật khẩu mới trước khi tiếp tục.');
            redirect('/password');
        }
    }
}
