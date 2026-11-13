<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\HttpException;

/** Chặn theo quyền chức năng trong config/permissions.php, ví dụ 'can:user.manage'. */
class PermissionMiddleware
{
    public function handle(?string $arg, string $path): void
    {
        if (!Auth::can((string)$arg)) {
            throw new HttpException(403, 'Bạn không có quyền truy cập chức năng này.');
        }
    }
}
