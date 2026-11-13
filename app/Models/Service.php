<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class Service extends Model
{
    protected static string $table = 'services';

    public static function all(bool $activeOnly = false): array
    {
        return Database::fetchAll('SELECT * FROM services' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY is_active DESC, price');
    }

    /** Lấy các dịch vụ đang bật theo danh sách id. */
    public static function activeByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        return Database::fetchAll("SELECT * FROM services WHERE is_active = 1 AND id IN ($in) ORDER BY id", $ids);
    }
}
