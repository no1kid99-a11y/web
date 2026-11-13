<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class HealthRecord extends Model
{
    protected static string $table = 'health_records';
    protected static bool $timestamps = false;

    public static function forPet(int $petId): array
    {
        return Database::fetchAll(
            'SELECT h.*, u.name AS recorder_name, b.booking_date
             FROM health_records h
             JOIN users u ON u.id = h.recorded_by
             LEFT JOIN bookings b ON b.id = h.booking_id
             WHERE h.pet_id = ? ORDER BY h.recorded_at DESC',
            [$petId]
        );
    }

    public static function forBooking(int $bookingId): array
    {
        return Database::fetchAll(
            'SELECT h.*, u.name AS recorder_name FROM health_records h JOIN users u ON u.id = h.recorded_by
             WHERE h.booking_id = ? ORDER BY h.recorded_at DESC',
            [$bookingId]
        );
    }

    public static function existsForBooking(int $bookingId): bool
    {
        return (int)Database::value('SELECT COUNT(*) FROM health_records WHERE booking_id = ?', [$bookingId]) > 0;
    }
}
