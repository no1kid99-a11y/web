<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class Review extends Model
{
    protected static string $table = 'reviews';
    protected static bool $timestamps = false;

    public static function forBooking(int $bookingId): ?array
    {
        return Database::fetch('SELECT * FROM reviews WHERE booking_id = ?', [$bookingId]);
    }

    public static function list(bool $visibleOnly = false, int $limit = 200, ?int $staffId = null): array
    {
        $where = ['1=1'];
        $p = [];
        if ($visibleOnly) {
            $where[] = 'r.is_hidden = 0';
        }
        if ($staffId) {
            $where[] = 'b.staff_id = ?';
            $p[] = $staffId;
        }
        return Database::fetchAll(
            'SELECT r.*, u.name AS client_name, p.name AS pet_name, p.species AS pet_species, s.name AS staff_name,
                    b.booking_date, cb.name AS combo_name
             FROM reviews r
             JOIN bookings b ON b.id = r.booking_id
             JOIN users u ON u.id = r.user_id
             JOIN pets p ON p.id = b.pet_id
             JOIN users s ON s.id = b.staff_id
             LEFT JOIN combos cb ON cb.id = b.combo_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY r.created_at DESC LIMIT ' . (int)$limit,
            $p
        );
    }

    public static function stats(): array
    {
        return Database::fetch('SELECT COUNT(*) AS n, AVG(rating) AS avg FROM reviews WHERE is_hidden = 0') ?? ['n' => 0, 'avg' => null];
    }
}
