<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\HttpException;

/**
 * Chặn sai vai trò theo nhóm route. Ví dụ 'role:admin' hoặc 'role:staff,admin'.
 * Luôn đặt sau 'auth'.
 */
class RoleMiddleware
{
    public function handle(?string $arg, string $path): void
    {
        $roles = array_map('trim', explode(',', (string)$arg));
        if (!Auth::is(...$roles)) {
            throw new HttpException(403, 'Trang này dành cho ' . implode(', ', array_map('role_label', $roles)) . '.');
        }
    }
}
