<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Booking;
use App\Models\Pet;
use App\Models\Review;
use App\Models\User;
use App\Services\BookingService;
use Core\Database;

class DashboardController extends AdminController
{
    public function index(): void
    {
        (new BookingService())->expirePending();
        $today = Database::today();
        $monthStart = date('Y-m-01');
        $revenue = Booking::revenueByMonth(6);
        $thisMonth = $revenue[date('Y-m')]['revenue'] ?? 0;
        $lastMonthKey = date('Y-m', strtotime('first day of last month'));
        $lastMonth = $revenue[$lastMonthKey]['revenue'] ?? 0;

        $this->render('admin/dashboard', [
            'title' => 'Tổng quan',
            'revenue' => $revenue,
            'thisMonth' => $thisMonth,
            'lastMonth' => $lastMonth,
            'statusCounts' => Booking::countByStatus(),
            'pendingCount' => (int)Database::value("SELECT COUNT(*) FROM bookings WHERE status = 'pending'"),
            'newToday' => (int)Database::value('SELECT COUNT(*) FROM bookings WHERE created_at >= ?', [$today . ' 00:00:00']),
            'petCount' => Pet::countActive(),
            'roleCounts' => User::countByRole(),
            'todayBookings' => Booking::list([], ['date' => $today, 'order' => 't.start_time ASC']),
            'pending' => Booking::list([], ['status' => 'pending', 'order' => 'b.booking_date ASC, t.start_time ASC'], 8),
            'perStaff' => Booking::perStaff($monthStart, date('Y-m-t')),
            'reviewStats' => Review::stats(),
        ]);
    }
}
