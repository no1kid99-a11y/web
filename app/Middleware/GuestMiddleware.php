<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;

/** Chỉ cho khách chưa đăng nhập (trang đăng nhập, đăng ký). */
class GuestMiddleware
{
    public function handle(?string $arg, string $path): void
    {
        if (Auth::check()) {
            redirect(Auth::home());
        }
    }
}
