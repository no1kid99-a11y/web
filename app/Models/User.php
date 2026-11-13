<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    }

    /** Danh sách có lọc vai trò, trạng thái, từ khóa. */
    public static function search(?string $role, ?string $status, ?string $q, int $limit = 200): array
    {
        $where = ['1=1'];
        $p = [];
        if ($role) {
            $where[] = 'u.role = ?';
            $p[] = $role;
        }
        if ($status) {
            $where[] = 'u.status = ?';
            $p[] = $status;
        }
        if ($q) {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            array_push($p, "%$q%", "%$q%", "%$q%");
        }
        $sql = 'SELECT u.*,
                    (SELECT COUNT(*) FROM pets pt WHERE pt.user_id = u.id AND pt.is_active = 1) AS pet_count,
                    (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id OR b.staff_id = u.id) AS booking_count
                FROM users u WHERE ' . implode(' AND ', $where) . '
                ORDER BY FIELD_ORDER, u.created_at DESC LIMIT ' . (int)$limit;
        $order = "CASE u.role WHEN 'admin' THEN 0 WHEN 'staff' THEN 1 ELSE 2 END";
        return Database::fetchAll(str_replace('FIELD_ORDER', $order, $sql), $p);
    }

    public static function staffList(bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM users WHERE role = 'staff'" . ($activeOnly ? " AND status = 'active'" : '') . ' ORDER BY name';
        return Database::fetchAll($sql);
    }

    public static function countActiveAdmins(): int
    {
        return (int)Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
    }

    public static function countByRole(): array
    {
        $rows = Database::fetchAll("SELECT role, COUNT(*) AS n FROM users WHERE status = 'active' GROUP BY role");
        return array_column($rows, 'n', 'role');
    }
}
