<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class TimeSlot extends Model
{
    protected static string $table = 'time_slots';
    protected static bool $timestamps = false;

    public static function all(bool $activeOnly = false): array
    {
        return Database::fetchAll('SELECT * FROM time_slots' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY start_time');
    }

    public static function minutes(array $slot): int
    {
        return (int)((strtotime('2000-01-01 ' . $slot['end_time']) - strtotime('2000-01-01 ' . $slot['start_time'])) / 60);
    }

    public static function label(array $slot): string
    {
        return hm($slot['start_time']) . '–' . hm($slot['end_time']);
    }

    public static function hasFutureHolding(int $slotId): bool
    {
        return (int)Database::value(
            'SELECT COUNT(*) FROM bookings WHERE time_slot_id = ? AND active_flag = 1 AND booking_date >= ?',
            [$slotId, Database::today()]
        ) > 0;
    }

    public static function maxActiveMinutes(): int
    {
        $max = 0;
        foreach (self::all(true) as $s) {
            $max = max($max, self::minutes($s));
        }
        return $max;
    }
}
