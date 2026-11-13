<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Booking;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\BookingService;

/**
 * Lịch tuần dạng lưới: hàng = nhân viên, cột = ngày; mỗi ô hiển thị các khung đã có đơn.
 */
class ScheduleController extends AdminController
{
    public function index(): void
    {
        (new BookingService())->expirePending();
        $start = (string)$this->query('week', '');
        $ts = $start && strtotime($start) ? strtotime($start) : time();
        // Mặc định: 7 ngày tính từ hôm nay (hữu ích cho điều phối hơn tuần T2–CN cố định)
        $monday = date('Y-m-d', $ts);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = date('Y-m-d', strtotime("$monday +$i days"));
        }
        $sunday = end($days);

        $bookings = array_filter(
            Booking::list([], ['from' => $monday, 'to' => $sunday, 'order' => 'b.booking_date, t.start_time'], 1000),
            fn($b) => $b['status'] !== 'cancelled'
        );
        $cells = [];
        foreach ($bookings as $b) {
            $cells[(int)$b['staff_id']][$b['booking_date']][] = $b;
        }
        $slots = TimeSlot::all(true);
        $staff = User::staffList(true);

        // Số khung còn trống mỗi ngày (cho hàng tổng ở cuối lưới)
        $svc = new BookingService();
        $free = [];
        foreach ($days as $d) {
            $n = 0;
            foreach ($slots as $s) {
                if ($svc->slotStart($d, $s) > time()) {
                    $n += count($svc->availableStaff($d, (int)$s['id']));
                }
            }
            $free[$d] = $n;
        }

        $this->render('admin/schedule', [
            'title' => 'Lịch tuần nhân viên',
            'days' => $days,
            'staff' => $staff,
            'cells' => $cells,
            'slots' => $slots,
            'free' => $free,
            'prev' => date('Y-m-d', strtotime("$monday -7 days")),
            'next' => date('Y-m-d', strtotime("$monday +7 days")),
            'monday' => $monday,
        ]);
    }
}
