<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class Booking extends Model
{
    protected static string $table = 'bookings';

    public const HOLDING = ['pending', 'confirmed', 'in_progress'];
    public const STATUSES = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];

    private const SELECT = 'SELECT b.*,
            p.name AS pet_name, p.species AS pet_species, p.breed AS pet_breed, p.photo AS pet_photo, p.weight AS pet_weight, p.age AS pet_age,
            c.name AS client_name, c.phone AS client_phone, c.email AS client_email,
            s.name AS staff_name, s.phone AS staff_phone,
            t.start_time, t.end_time,
            cb.name AS combo_name,
            r.id AS review_id, r.rating AS review_rating
        FROM bookings b
        JOIN pets p ON p.id = b.pet_id
        JOIN users c ON c.id = b.user_id
        JOIN users s ON s.id = b.staff_id
        JOIN time_slots t ON t.id = b.time_slot_id
        LEFT JOIN combos cb ON cb.id = b.combo_id
        LEFT JOIN reviews r ON r.booking_id = b.id';

    public static function detail(int $id): ?array
    {
        $b = Database::fetch(self::SELECT . ' WHERE b.id = ?', [$id]);
        if ($b) {
            $b['services'] = self::services($id);
        }
        return $b;
    }

    public static function services(int $bookingId): array
    {
        return Database::fetchAll(
            'SELECT bs.price, s.id, s.name, s.duration_minutes FROM booking_services bs
             JOIN services s ON s.id = bs.service_id WHERE bs.booking_id = ? ORDER BY s.id',
            [$bookingId]
        );
    }

    /**
     * Danh sách đơn có lọc. $scope giới hạn theo vai trò:
     * ['user_id' => x] cho Client, ['staff_id' => x] cho Nhân viên, [] cho Admin.
     */
    public static function list(array $scope, array $filters = [], int $limit = 300): array
    {
        $where = ['1=1'];
        $p = [];
        foreach (['user_id', 'staff_id'] as $k) {
            if (isset($scope[$k])) {
                $where[] = "b.$k = ?";
                $p[] = $scope[$k];
            }
        }
        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = 'b.status = ?';
            $p[] = $filters['status'];
        }
        if (!empty($filters['staff_id'])) {
            $where[] = 'b.staff_id = ?';
            $p[] = (int)$filters['staff_id'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'b.booking_date >= ?';
            $p[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'b.booking_date <= ?';
            $p[] = $filters['to'];
        }
        if (!empty($filters['date'])) {
            $where[] = 'b.booking_date = ?';
            $p[] = $filters['date'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(c.name LIKE ? OR c.phone LIKE ? OR p.name LIKE ? OR b.id = ?)';
            $q = $filters['q'];
            array_push($p, "%$q%", "%$q%", "%$q%", ctype_digit($q) ? (int)$q : 0);
        }
        $order = $filters['order'] ?? 'b.booking_date DESC, t.start_time DESC';
        return Database::fetchAll(self::SELECT . ' WHERE ' . implode(' AND ', $where) . " ORDER BY $order LIMIT " . (int)$limit, $p);
    }

    public static function holdingForStaff(int $staffId, string $date, ?int $slotId = null): array
    {
        $sql = self::SELECT . ' WHERE b.staff_id = ? AND b.booking_date = ? AND b.active_flag = 1';
        $p = [$staffId, $date];
        if ($slotId) {
            $sql .= ' AND b.time_slot_id = ?';
            $p[] = $slotId;
        }
        return Database::fetchAll($sql . ' ORDER BY t.start_time', $p);
    }

    public static function futureHoldingForStaff(int $staffId): array
    {
        return Database::fetchAll(
            self::SELECT . ' WHERE b.staff_id = ? AND b.active_flag = 1 AND b.booking_date >= ? ORDER BY b.booking_date, t.start_time',
            [$staffId, Database::today()]
        );
    }

    /** Thông tin rút gọn của đơn để hiển thị danh sách "đơn cần xử lý trước". */
    public static function brief(array $bookings): array
    {
        return array_map(fn($b) => [
            'id' => (int)$b['id'],
            'date' => $b['booking_date'],
            'slot' => hm($b['start_time']) . '–' . hm($b['end_time']),
            'pet' => $b['pet_name'],
            'client' => $b['client_name'],
            'staff' => $b['staff_name'],
            'status' => $b['status'],
        ], $bookings);
    }

    public static function holdingOn(string $date, ?int $slotId = null): array
    {
        $sql = self::SELECT . ' WHERE b.booking_date = ? AND b.active_flag = 1';
        $p = [$date];
        if ($slotId) {
            $sql .= ' AND b.time_slot_id = ?';
            $p[] = $slotId;
        }
        return Database::fetchAll($sql . ' ORDER BY t.start_time', $p);
    }

    public static function countByStatus(array $scope = []): array
    {
        $where = '1=1';
        $p = [];
        foreach ($scope as $k => $v) {
            $where .= " AND $k = ?";
            $p[] = $v;
        }
        $rows = Database::fetchAll("SELECT status, COUNT(*) AS n FROM bookings WHERE $where GROUP BY status", $p);
        return array_column($rows, 'n', 'status');
    }

    /** Doanh thu theo tháng (chỉ đơn Hoàn thành), dạng ['2026-05' => 1200000]. */
    public static function revenueByMonth(int $months = 6): array
    {
        $start = date('Y-m-01', strtotime('-' . ($months - 1) . ' months', strtotime(date('Y-m-01'))));
        $rows = Database::fetchAll(
            "SELECT SUBSTR(booking_date, 1, 7) AS ym, SUM(total_price) AS revenue, COUNT(*) AS n
             FROM bookings WHERE status = 'completed' AND booking_date >= ? GROUP BY ym ORDER BY ym",
            [$start]
        );
        $map = [];
        for ($i = 0; $i < $months; $i++) {
            $ym = date('Y-m', strtotime("+$i months", strtotime($start)));
            $map[$ym] = ['revenue' => 0, 'n' => 0];
        }
        foreach ($rows as $r) {
            $map[$r['ym']] = ['revenue' => (int)$r['revenue'], 'n' => (int)$r['n']];
        }
        return $map;
    }

    public static function perStaff(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT u.id, u.name,
                SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN b.active_flag = 1 THEN 1 ELSE 0 END) AS holding,
                AVG(r.rating) AS avg_rating
             FROM users u
             LEFT JOIN bookings b ON b.staff_id = u.id AND b.booking_date BETWEEN ? AND ?
             LEFT JOIN reviews r ON r.booking_id = b.id AND r.is_hidden = 0
             WHERE u.role = 'staff' GROUP BY u.id, u.name ORDER BY u.name",
            [$from, $to]
        );
    }
}
