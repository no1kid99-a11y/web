<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\ActivityLog;
use Core\Database;

class LogController extends AdminController
{
    public function index(): void
    {
        $f = ['action' => $this->query('action'), 'user_id' => $this->query('user_id')];
        $page = max(1, (int)$this->query('page', 1));
        $result = ActivityLog::search($f, $page);
        $this->render('admin/logs/index', [
            'title' => 'Nhật ký hoạt động',
            'logs' => $result['rows'],
            'pages' => $result['pages'],
            'total' => $result['total'],
            'page' => $page,
            'f' => $f,
            'actors' => Database::fetchAll("SELECT id, name, role FROM users WHERE role IN ('admin','staff') ORDER BY role, name"),
        ]);
    }
}
