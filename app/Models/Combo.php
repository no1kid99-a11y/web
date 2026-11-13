<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class Combo extends Model
{
    protected static string $table = 'combos';

    /** Danh sách combo kèm dịch vụ con, tổng giá lẻ và tổng thời lượng. */
    public static function all(bool $activeOnly = false): array
    {
        $combos = Database::fetchAll('SELECT * FROM combos' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY is_active DESC, price');
        foreach ($combos as &$c) {
            $c['services'] = self::services((int)$c['id']);
            $c['retail_total'] = array_sum(array_column($c['services'], 'price'));
            $c['duration'] = array_sum(array_column($c['services'], 'duration_minutes'));
        }
        return $combos;
    }

    public static function services(int $comboId): array
    {
        return Database::fetchAll(
            'SELECT s.* FROM combo_service cs JOIN services s ON s.id = cs.service_id WHERE cs.combo_id = ? ORDER BY s.id',
            [$comboId]
        );
    }

    public static function syncServices(int $comboId, array $serviceIds): void
    {
        Database::query('DELETE FROM combo_service WHERE combo_id = ?', [$comboId]);
        foreach (array_unique(array_map('intval', $serviceIds)) as $sid) {
            Database::insert('combo_service', ['combo_id' => $comboId, 'service_id' => $sid]);
        }
    }
}
