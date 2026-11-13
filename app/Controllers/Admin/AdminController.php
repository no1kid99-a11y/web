<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use Core\Controller;

/** Controller cơ sở cho khu vực quản trị (route đã chặn bằng role:admin + can:...). */
abstract class AdminController extends Controller
{
    protected string $layout = 'layouts/panel';
}
