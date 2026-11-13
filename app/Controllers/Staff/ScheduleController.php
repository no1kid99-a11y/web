<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\HealthRecord;
use App\Models\Review;
use App\Models\Vaccination;
use App\Policies\BookingPolicy;
use App\Services\BookingPhotoService;
use App\Services\BookingService;
use Core\AppException;
use Core\Controller;
use Core\Database;

/**
 * Lịch làm việc của nhân viên: chỉ thấy và xử lý đơn có staff_id là mình.
 */
class ScheduleController extends Controller
{
    protected string $layout = 'layouts/panel';

    private function assigned(int $id): array
    {
        $b = Booking::detail($id);
        // Không tiết lộ đơn của người khác: trả 404 thay vì 403.
        $this->notFoundUnless($b && BookingPolicy::view($this->user(), $b), 'Không tìm thấy đơn được giao cho bạn.');
        return $b;
    }

    public function index(): void
    {
        $u = $this->user();
        (new BookingService())->expirePending();
        $today = Database::today();
        $scope = ['staff_id' => (int)$u['id']];
        $order = 'b.booking_date ASC, t.start_time ASC';

        $todayList = Booking::list($scope, ['date' => $today, 'order' => $order]);
        $upcoming = array_values(array_filter(
            Booking::list($scope, ['from' => date('Y-m-d', strtotime('+1 day')), 'to' => date('Y-m-d', strtotime('+30 days')), 'order' => $order]),
            fn($b) => in_array($b['status'], Booking::HOLDING, true)
        ));
        $recent = Booking::list($scope, ['from' => date('Y-m-d', strtotime('-14 days')), 'to' => date('Y-m-d', strtotime('-1 day'))], 50);

        $grouped = [];
        foreach ($upcoming as $b) {
            $grouped[$b['booking_date']][] = $b;
        }
        $monthDone = (int)Database::value(
            "SELECT COUNT(*) FROM bookings WHERE staff_id = ? AND status = 'completed' AND booking_date >= ?",
            [(int)$u['id'], date('Y-m-01')]
        );
        $this->render('staff/schedule', [
            'title' => 'Lịch làm việc',
            'today' => $todayList,
            'grouped' => $grouped,
            'recent' => $recent,
            'monthDone' => $monthDone,
            'upcomingCount' => count($upcoming),
        ]);
    }

    public function show(int $id): void
    {
        $b = $this->assigned($id);
        $this->render('staff/booking', [
            'title' => 'Đơn ' . booking_code($id),
            'b' => $b,
            'vaccinations' => Vaccination::forPet((int)$b['pet_id']),
            'petRecords' => HealthRecord::forPet((int)$b['pet_id']),
            'bookingRecords' => HealthRecord::forBooking($id),
            'photos' => array_map([BookingPhoto::class, 'present'], BookingPhoto::forBooking($id)),
            'isToday' => $b['booking_date'] === Database::today(),
        ]);
    }

    public function start(int $id): void
    {
        $this->assigned($id);
        $this->attempt(fn() => (new BookingService())->transition($this->user(), $id, 'in_progress'), "/staff/bookings/$id",
            'Đã bắt đầu dịch vụ. Khách đang mở trang theo dõi sẽ thấy ngay; hãy chụp ảnh gửi khách ở từng mốc.');
        redirect("/staff/bookings/$id");
    }

    public function complete(int $id): void
    {
        $this->assigned($id);
        $this->attempt(fn() => (new BookingService())->transition($this->user(), $id, 'completed'), "/staff/bookings/$id",
            'Đã hoàn thành đơn ' . booking_code($id) . '. Nhớ nhận thanh toán từ khách.');
        redirect("/staff/bookings/$id");
    }

    public function health(int $id): void
    {
        $this->assigned($id);
        $w = trim((string)$this->input('weight', ''));
        $weight = $w === '' ? null : (float)str_replace(',', '.', $w);
        $this->attempt(fn() => (new BookingService())->addHealthRecord($this->user(), $id, $weight, (string)$this->input('note', '')),
            "/staff/bookings/$id", 'Đã ghi sổ sức khỏe.', ['weight' => $w, 'note' => $this->input('note')]);
        redirect("/staff/bookings/$id");
    }

    /**
     * Nhân viên chụp ảnh gửi khách. Form gửi bằng fetch() nhận JSON để ở lại trang chụp tiếp;
     * nếu trình duyệt tắt JavaScript thì form gửi thường và quay về trang đơn.
     */
    public function uploadPhoto(int $id): void
    {
        $this->assigned($id);
        $add = fn() => (new BookingPhotoService())->add(
            $this->user(), $id, $_FILES['photo'] ?? null,
            (string)$this->input('stage', 'other'), (string)$this->input('caption', '')
        );
        if ($this->wantsJson()) {
            try {
                $this->json(['ok' => true, 'photo' => BookingPhoto::present($add())]);
            } catch (AppException $e) {
                $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
            }
            return;
        }
        $this->attempt($add, "/staff/bookings/$id", 'Đã gửi ảnh, khách sẽ thấy trong vài giây.');
        redirect("/staff/bookings/$id#anh");
    }

    public function deletePhoto(int $id): void
    {
        $photo = BookingPhoto::find($id);
        $this->notFoundUnless($photo, 'Không tìm thấy ảnh.');
        $this->assigned((int)$photo['booking_id']);
        $bookingId = (int)$photo['booking_id'];
        $this->attempt(fn() => (new BookingPhotoService())->delete($this->user(), $id), "/staff/bookings/$bookingId#anh", 'Đã gỡ ảnh.');
        redirect("/staff/bookings/$bookingId#anh");
    }

    public function reviews(): void
    {
        $u = $this->user();
        $list = Review::list(true, 200, (int)$u['id']);
        $avg = $list ? array_sum(array_column($list, 'rating')) / count($list) : null;
        $this->render('staff/reviews', ['title' => 'Đánh giá của khách', 'reviews' => $list, 'avg' => $avg]);
    }
}
