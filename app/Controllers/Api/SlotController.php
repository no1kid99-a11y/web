<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Booking;
use App\Models\Pet;
use App\Services\BookingService;
use Core\Controller;

/**
 * API khung giờ. Dùng chung BookingService::availableSlots() với server khi lưu đơn,
 * nên giao diện không bao giờ lệch quy tắc.
 */
class SlotController extends Controller
{
    /** GET /api/slots?date=YYYY-MM-DD&pet_id=  (khách hàng) */
    public function forClient(): void
    {
        $date = (string)$this->query('date', '');
        $petId = (int)$this->query('pet_id', 0);
        if ($petId && !Pet::findOwned($petId, (int)$this->user()['id'])) {
            $this->json(['error' => 'Thú cưng không hợp lệ.'], 404);
            return;
        }
        $slots = (new BookingService())->availableSlots($date, $petId ?: null);
        // Khách không cần biết tên nhân viên
        foreach ($slots as &$s) {
            unset($s['staff']);
        }
        $this->json(['date' => $date, 'slots' => $slots]);
    }

    /** GET /admin/api/slots?date=&booking_id=  (Admin dời lịch: bỏ qua chính đơn đang dời) */
    public function forAdmin(): void
    {
        $date = (string)$this->query('date', '');
        $b = Booking::find((int)$this->query('booking_id', 0));
        if (!$b) {
            $this->json(['error' => 'Không tìm thấy đơn.'], 404);
            return;
        }
        $slots = (new BookingService())->availableSlots($date, (int)$b['pet_id'], (int)$b['id'], false);
        $this->json(['date' => $date, 'slots' => $slots, 'current' => ['date' => $b['booking_date'], 'slot' => (int)$b['time_slot_id'], 'staff' => (int)$b['staff_id']]]);
    }
}
