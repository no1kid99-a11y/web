<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class ActivityLog extends Model
{
    protected static string $table = 'activity_logs';
    protected static bool $timestamps = false;

    public static function search(array $f, int $page = 1, int $perPage = 50): array
    {
        $where = ['1=1'];
        $p = [];
        if (!empty($f['action'])) {
            $where[] = 'l.action LIKE ?';
            $p[] = $f['action'] . '%';
        }
        if (!empty($f['user_id'])) {
            $where[] = 'l.user_id = ?';
            $p[] = (int)$f['user_id'];
        }
        if (!empty($f['target_type']) && !empty($f['target_id'])) {
            $where[] = 'l.target_type = ? AND l.target_id = ?';
            $p[] = $f['target_type'];
            $p[] = (int)$f['target_id'];
        }
        $w = implode(' AND ', $where);
        $total = (int)Database::value("SELECT COUNT(*) FROM activity_logs l WHERE $w", $p);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::fetchAll(
            "SELECT l.*, u.name AS user_name, u.role AS user_role FROM activity_logs l
             LEFT JOIN users u ON u.id = l.user_id WHERE $w ORDER BY l.id DESC LIMIT $perPage OFFSET $offset",
            $p
        );
        return ['rows' => $rows, 'total' => $total, 'pages' => max(1, (int)ceil($total / $perPage))];
    }

    public static function forTarget(string $type, int $id): array
    {
        return Database::fetchAll(
            'SELECT l.*, u.name AS user_name, u.role AS user_role FROM activity_logs l
             LEFT JOIN users u ON u.id = l.user_id WHERE l.target_type = ? AND l.target_id = ? ORDER BY l.id DESC',
            [$type, $id]
        );
    }
}
