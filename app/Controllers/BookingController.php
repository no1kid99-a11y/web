<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\Combo;
use App\Models\HealthRecord;
use App\Models\Pet;
use App\Models\Review;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Policies\BookingPolicy;
use App\Services\BookingService;
use Core\Controller;
use Core\Database;
use Core\Session;

/**
 * Đặt lịch và theo dõi đơn của khách hàng.
 */
class BookingController extends Controller
{
    private function ownBooking(int $id): array
    {
        $b = Booking::detail($id);
        $this->notFoundUnless($b && BookingPolicy::view($this->user(), $b), 'Không tìm thấy lịch hẹn.');
        return $b;
    }

    public function wizard(): void
    {
        $u = $this->user();
        $pets = Pet::forUser((int)$u['id']);
        if (!$pets) {
            flash('info', 'Hãy thêm hồ sơ thú cưng trước khi đặt lịch.');
            redirect('/pets/create');
        }
        $svc = new BookingService();
        $old = Session::get('_old_input', []);
        $this->render('client/bookings/wizard', [
            'title' => 'Đặt lịch chăm sóc',
            'config' => [
                'pets' => array_map(fn($p) => [
                    'id' => (int)$p['id'], 'name' => $p['name'], 'species' => $p['species'],
                    'speciesLabel' => species_label($p['species']), 'breed' => $p['breed'], 'weight' => kg($p['weight']),
                    'age' => $p['age'] !== null ? (int)$p['age'] : null,
                    'vaccinated' => (int)\Core\Database::value('SELECT COUNT(*) FROM vaccinations WHERE pet_id = ?', [(int)$p['id']]) > 0,
                    'photo' => $p['photo'] ? upload_url($p['photo']) : photo($p['species'] === 'cat' ? 'cat' : 'puppy', 200),
                    'fallback' => asset('img/fallback-' . ($p['species'] === 'cat' ? 'cat' : 'dog') . '.svg'),
                ], $pets),
                'combos' => array_map(fn($c) => [
                    'id' => (int)$c['id'], 'name' => $c['name'], 'description' => $c['description'],
                    'price' => (int)$c['price'], 'retail' => (int)$c['retail_total'], 'duration' => (int)$c['duration'],
                    'services' => array_column($c['services'], 'name'),
                    'photo' => photo(service_photo_key($c['name']), 300),
                ], Combo::all(true)),
                'services' => array_map(fn($s) => [
                    'id' => (int)$s['id'], 'name' => $s['name'], 'description' => $s['description'],
                    'price' => (int)$s['price'], 'duration' => (int)$s['duration_minutes'],
                ], Service::all(true)),
                'travel' => $svc->travelMinutes(),
                'slotMinutes' => TimeSlot::maxActiveMinutes(),
                'minDate' => date('Y-m-d'),
                'maxDate' => date('Y-m-d', strtotime('+' . (int)config('booking.max_days_ahead') . ' days')),
                'address' => $old['address'] ?? ($u['address'] ?? ''),
                'note' => $old['note'] ?? '',
                'petId' => (int)($old['pet_id'] ?? $this->query('pet', $pets[0]['id'])),
                'comboId' => isset($old['combo_id']) ? (int)$old['combo_id'] : ($this->query('combo') ? (int)$this->query('combo') : null),
                'serviceIds' => array_map('intval', (array)($old['service_ids'] ?? ($this->query('service') ? [(int)$this->query('service')] : []))),
                'date' => $old['booking_date'] ?? date('Y-m-d', strtotime('+1 day')),
                'slotsUrl' => url('/api/slots'),
                'fallbackDog' => asset('img/fallback-dog.svg'),
            ],
        ]);
    }

    public function store(): void
    {
        $u = $this->user();
        $input = [
            'pet_id' => (int)$this->input('pet_id'),
            'booking_date' => (string)$this->input('booking_date'),
            'time_slot_id' => (int)$this->input('time_slot_id'),
            'combo_id' => $this->input('combo_id') ? (int)$this->input('combo_id') : null,
            'service_ids' => array_map('intval', (array)($_POST['service_ids'] ?? [])),
            'address' => (string)$this->input('address'),
            'note' => (string)$this->input('note', ''),
        ];
        $id = $this->attempt(fn() => (new BookingService())->create($u, $input), '/book', null, $input);
        Session::forget('_old_input');
        flash('success', 'Đã gửi yêu cầu đặt lịch ' . booking_code($id) . '. Cửa hàng sẽ duyệt đơn sớm, bạn theo dõi trạng thái ngay tại đây.');
        redirect('/bookings/' . $id);
    }

    public function index(): void
    {
        $u = $this->user();
        (new BookingService())->expirePending();
        $status = $this->query('status');
        $all = Booking::list(['user_id' => (int)$u['id']], [], 500);
        $counts = array_count_values(array_column($all, 'status'));
        $list = $status ? array_values(array_filter($all, fn($b) => $b['status'] === $status)) : $all;
        $today = date('Y-m-d');
        $upcoming = array_values(array_filter($list, fn($b) => in_array($b['status'], Booking::HOLDING, true) && $b['booking_date'] >= $today));
        usort($upcoming, fn($a, $b) => [$a['booking_date'], $a['start_time']] <=> [$b['booking_date'], $b['start_time']]);
        $past = array_values(array_filter($list, fn($b) => !in_array($b, $upcoming, true)));
        $this->render('client/bookings/index', [
            'title' => 'Lịch hẹn của tôi',
            'upcoming' => $upcoming,
            'past' => $past,
            'counts' => $counts,
            'total' => count($all),
            'status' => $status,
        ]);
    }

    public function show(int $id): void
    {
        $b = $this->ownBooking($id);
        $slot = ['start_time' => $b['start_time']];
        $svc = new BookingService();
        $canCancel = in_array($b['status'], ['pending', 'confirmed'], true)
            && $svc->slotStart($b['booking_date'], $slot) - time() >= (int)config('booking.min_lead_hours') * 3600;
        $this->render('client/bookings/show', [
            'title' => 'Lịch hẹn ' . booking_code($id),
            'b' => $b,
            'canCancel' => $canCancel,
            'review' => Review::forBooking($id),
            'records' => HealthRecord::forBooking($id),
            'photos' => array_map([BookingPhoto::class, 'present'], BookingPhoto::forBooking($id)),
        ]);
    }

    public function cancel(int $id): void
    {
        $this->ownBooking($id);
        $this->attempt(
            fn() => (new BookingService())->transition($this->user(), $id, 'cancelled', (string)$this->input('reason', '')),
            '/bookings/' . $id,
            'Đã hủy lịch hẹn ' . booking_code($id) . '. Khung giờ đã được trả lại.'
        );
        redirect('/bookings/' . $id);
    }

    public function review(int $id): void
    {
        $b = $this->ownBooking($id);
        if (!BookingPolicy::review($this->user(), $b) || $b['status'] !== 'completed') {
            flash('error', 'Chỉ đánh giá được lịch hẹn đã hoàn thành.');
            redirect('/bookings/' . $id);
        }
        if (Review::forBooking($id)) {
            flash('error', 'Bạn đã đánh giá lịch hẹn này rồi.');
            redirect('/bookings/' . $id);
        }
        $v = $this->validate(
            ['rating' => $this->input('rating'), 'comment' => $this->input('comment')],
            ['rating' => 'required|int|between:1,5', 'comment' => 'nullable|max:1000'],
            '/bookings/' . $id
        );
        try {
            Review::create(['booking_id' => $id, 'user_id' => (int)$this->user()['id'], 'rating' => $v['rating'], 'comment' => $v['comment'], 'is_hidden' => 0, 'created_at' => Database::now()]);
        } catch (\PDOException $e) {
            if (Database::uniqueViolation($e) === null) {
                throw $e;
            }
            flash('error', 'Bạn đã đánh giá lịch hẹn này rồi.');
            redirect('/bookings/' . $id);
        }
        flash('success', 'Cảm ơn bạn đã đánh giá!');
        redirect('/bookings/' . $id);
    }

    /**
     * Lối tắt "Theo dõi trực tiếp" trên menu: mở ca đang làm; nếu chưa có thì ca đã xác nhận gần nhất hôm nay.
     */
    public function cameraEntry(): void
    {
        $scope = ['user_id' => (int)$this->user()['id']];
        $running = Booking::list($scope, ['status' => 'in_progress'], 1)
            ?: Booking::list($scope, ['status' => 'confirmed', 'date' => Database::today(), 'order' => 't.start_time ASC'], 1);
        if ($running) {
            redirect('/bookings/' . $running[0]['id'] . '/camera');
        }
        flash('info', 'Hôm nay bé chưa có ca chăm sóc nào. Trang theo dõi mở từ khi lịch hẹn được xác nhận, ảnh hiện lên ngay khi nhân viên chụp.');
        redirect('/bookings');
    }

    /** Trang theo dõi trực tiếp: ảnh nhân viên chụp theo từng mốc, tự cập nhật vài giây một lần. */
    public function camera(int $id): void
    {
        $b = $this->ownBooking($id);
        if (!BookingPolicy::camera($this->user(), $b)) {
            flash('warning', 'Trang theo dõi chỉ mở khi lịch hẹn đã được xác nhận.');
            redirect('/bookings/' . $id);
        }
        $this->render('client/bookings/camera', [
            'title' => 'Theo dõi trực tiếp · ' . $b['pet_name'],
            'b' => $b,
            'photos' => array_map([BookingPhoto::class, 'present'], BookingPhoto::forBooking($id)),
            'records' => HealthRecord::forBooking($id),
            'poll' => max(2, (int)config('booking_photo.poll_seconds', 5)),
        ]);
    }

    /**
     * JSON cho trang theo dõi: trạng thái đơn + ảnh mới hơn ?after=<id ảnh cuối đã có>.
     * Trình duyệt gọi lại sau mỗi vài giây (polling) nên chạy được trên XAMPP, không cần máy chủ video.
     */
    public function photoFeed(int $id): void
    {
        $b = $this->ownBooking($id);
        if (!BookingPolicy::camera($this->user(), $b)) {
            $this->json(['error' => 'Lịch hẹn chưa mở theo dõi.'], 403);
            return;
        }
        $after = max(0, (int)$this->query('after', 0));
        header('Cache-Control: no-store');
        $this->json([
            'status'       => $b['status'],
            'status_label' => status_label($b['status']),
            'photos'       => array_map([BookingPhoto::class, 'present'], BookingPhoto::forBooking($id, $after)),
            'ids'          => BookingPhoto::idsForBooking($id),
            'has_record'   => HealthRecord::existsForBooking($id),
            'server_time'  => date('H:i:s'),
        ]);
    }
}
