<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Combo;
use App\Models\Pet;
use App\Models\Review;
use App\Models\Service;
use App\Services\BookingService;
use Core\Auth;
use Core\Controller;
use Core\Database;

class HomeController extends Controller
{
    public function index(): void
    {
        $svc = new BookingService();
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $tomorrowSlots = $svc->availableSlots($tomorrow);
        $u = Auth::user();

        // Hồ sơ gia đình: thú cưng của khách đang đăng nhập + mũi tiêm sắp đến hạn
        $pets = [];
        if ($u && $u['role'] === 'client') {
            foreach (Pet::forUser((int)$u['id']) as $p) {
                $p['next_vaccine'] = Database::fetch(
                    'SELECT vaccine_name, next_due_at FROM vaccinations WHERE pet_id = ? AND next_due_at IS NOT NULL ORDER BY next_due_at LIMIT 1',
                    [(int)$p['id']]
                );
                $p['upcoming'] = Database::fetch(
                    'SELECT b.id, b.booking_date, t.start_time FROM bookings b JOIN time_slots t ON t.id = b.time_slot_id
                     WHERE b.pet_id = ? AND b.active_flag = 1 ORDER BY b.booking_date, t.start_time LIMIT 1',
                    [(int)$p['id']]
                );
                $pets[] = $p;
            }
        }

        $this->render('home/index', [
            'title' => 'Chăm sóc thú cưng tại nhà',
            'wide' => true,
            'combos' => Combo::all(true),
            'services' => Service::all(true),
            'reviews' => Review::list(true, 3),
            'reviewStats' => Review::stats(),
            'doneCount' => (int)Database::value("SELECT COUNT(*) FROM bookings WHERE status = 'completed'"),
            'tomorrow' => $tomorrow,
            'tomorrowFree' => count(array_filter($tomorrowSlots, fn($s) => $s['available'])),
            'tomorrowSlots' => $tomorrowSlots,
            'pets' => $pets,
            'user' => $u,
        ]);
    }

    public function services(): void
    {
        $this->render('home/services', [
            'title' => 'Dịch vụ và bảng giá',
            'combos' => Combo::all(true),
            'services' => Service::all(true),
            'travel' => (new BookingService())->travelMinutes(),
        ]);
    }
}
