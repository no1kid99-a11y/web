<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class Pet extends Model
{
    protected static string $table = 'pets';

    /** Lấy thú cưng thuộc đúng chủ (chống IDOR). */
    public static function findOwned(int $id, int $userId): ?array
    {
        return Database::fetch('SELECT * FROM pets WHERE id = ? AND user_id = ? AND is_active = 1', [$id, $userId]);
    }

    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT p.*,
                (SELECT MAX(b.booking_date) FROM bookings b WHERE b.pet_id = p.id AND b.status = \'completed\') AS last_care
             FROM pets p WHERE p.user_id = ? AND p.is_active = 1 ORDER BY p.created_at',
            [$userId]
        );
    }

    public static function hasHoldingBookings(int $petId): bool
    {
        return (int)Database::value('SELECT COUNT(*) FROM bookings WHERE pet_id = ? AND active_flag = 1', [$petId]) > 0;
    }

    public static function countActive(): int
    {
        return (int)Database::value('SELECT COUNT(*) FROM pets WHERE is_active = 1');
    }
}
