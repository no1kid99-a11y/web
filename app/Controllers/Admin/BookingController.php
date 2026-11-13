<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\HealthRecord;
use App\Models\User;
use App\Services\BookingPhotoService;
use App\Services\BookingService;
use Core\AppException;
use Core\Database;

/**
 * Admin quản lý lịch hẹn: duyệt, từ chối/hủy (lý do bắt buộc), đổi nhân viên, dời lịch, xử lý sự cố.
 * Controller chỉ gọi BookingService, không tự viết quy tắc.
 */
class BookingController extends AdminController
{
    private function booking(int $id): array
    {
        $b = Booking::detail($id);
        $this->notFoundUnless($b, 'Không tìm thấy đơn.');
        return $b;
    }

    private const PER_PAGE = 10;

    /** Các khoảng thời gian của bộ lọc ngày. */
    public const RANGES = [
        'upcoming' => 'Từ hôm nay trở đi',
        'today'    => 'Hôm nay',
        'tomorrow' => 'Ngày mai',
        'week'     => '7 ngày tới',
        'past'     => 'Đã qua',
        'all'      => 'Tất cả thời gian',
        'date'     => 'Chọn ngày…',
    ];

    /** Đọc bộ lọc từ query string (dùng chung cho danh sách và xuất CSV). */
    private function filters(): array
    {
        $range = array_key_exists((string)$this->query('range'), self::RANGES) ? (string)$this->query('range') : 'upcoming';
        $date = (string)$this->query('date', '');
        if ($range === 'date' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $range = 'upcoming';
        }
        $today = date('Y-m-d');
        $f = ['staff_id' => $this->query('staff_id'), 'q' => $this->query('q')];
        $f['order'] = 'b.booking_date ASC, t.start_time ASC';
        switch ($range) {
            case 'upcoming': $f['from'] = $today; break;
            case 'today':    $f['date'] = $today; break;
            case 'tomorrow': $f['date'] = date('Y-m-d', strtotime('+1 day')); break;
            case 'week':     $f['from'] = $today; $f['to'] = date('Y-m-d', strtotime('+6 days')); break;
            case 'past':     $f['to'] = date('Y-m-d', strtotime('-1 day')); $f['order'] = 'b.booking_date DESC, t.start_time DESC'; break;
            case 'all':      $f['order'] = 'b.booking_date DESC, t.start_time DESC'; break;
            case 'date':     $f['date'] = $date; break;
        }
        return [$f, $range, $date];
    }

    public function index(): void
    {
        $svc = new BookingService();
        $svc->expirePending();
        [$filters, $range, $date] = $this->filters();
        $status = in_array($this->query('status'), Booking::STATUSES, true) ? (string)$this->query('status') : null;

        // Lọc theo thời gian/nhân viên/từ khóa trước, đếm theo trạng thái, rồi mới lọc trạng thái và phân trang.
        $rows = Booking::list([], $filters, 2000);
        $counts = array_count_values(array_column($rows, 'status'));
        if ($status) {
            $rows = array_values(array_filter($rows, fn($b) => $b['status'] === $status));
        }
        $total = count($rows);
        $pages = max(1, (int)ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, (int)$this->query('page', 1)));
        $rows = array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        // Dịch vụ của từng đơn và danh sách nhân viên có thể đổi sang (chỉ đơn Chờ duyệt / Đã xác nhận)
        foreach ($rows as &$b) {
            $b['services'] = Booking::services((int)$b['id']);
            $b['free_staff'] = in_array($b['status'], ['pending', 'confirmed'], true)
                ? array_values(array_filter(
                    $svc->availableStaff($b['booking_date'], (int)$b['time_slot_id'], (int)$b['id']),
                    fn($s) => (int)$s['id'] !== (int)$b['staff_id']
                ))
                : [];
        }
        unset($b);

        $today = date('Y-m-d');
        $this->render('admin/bookings/index', [
            'title' => 'Quản lý lịch hẹn',
            'bookings' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'counts' => $counts,
            'allCount' => array_sum($counts),
            'status' => $status,
            'range' => $range,
            'date' => $date,
            'filters' => $filters,
            'staff' => User::staffList(false),
            'stats' => $this->stats($svc),
            'todayBoard' => $this->todayBoard($svc, $today),
        ]);
    }

    /** Bốn thẻ chỉ số đầu trang (toàn hệ thống, không phụ thuộc bộ lọc). */
    private function stats(BookingService $svc): array
    {
        $today = date('Y-m-d');
        $pending = (int)Database::value("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
        $soon = 0;
        foreach (Booking::list([], ['status' => 'pending', 'from' => $today, 'to' => date('Y-m-d', strtotime('+1 day'))], 500) as $b) {
            $soon += strtotime($b['booking_date'] . ' ' . $b['start_time']) - time() <= 86400 ? 1 : 0;
        }
        // Nhân viên sẵn sàng = đang hoạt động và còn ít nhất một khung trống chưa qua trong hôm nay
        $active = User::staffList(true);
        $ready = [];
        foreach (\App\Models\TimeSlot::all(true) as $slot) {
            if ($svc->slotStart($today, $slot) > time()) {
                foreach ($svc->availableStaff($today, (int)$slot['id']) as $s) {
                    $ready[(int)$s['id']] = true;
                }
            }
        }
        $ready = count($ready);
        return [
            'pending' => $pending,
            'pending_soon' => $soon,
            'running' => (int)Database::value("SELECT COUNT(*) FROM bookings WHERE status = 'in_progress'"),
            'confirmed' => (int)Database::value("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND booking_date >= ?", [$today]),
            'confirmed_today' => (int)Database::value("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND booking_date = ?", [$today]),
            'ready' => $ready,
            'staff_total' => count($active),
        ];
    }

    /** Bảng hôm nay: mỗi nhân viên × mỗi khung giờ là đơn, còn trống hay đã qua. */
    private function todayBoard(BookingService $svc, string $today): array
    {
        $slots = \App\Models\TimeSlot::all(true);
        $cells = [];
        foreach (Booking::holdingOn($today) as $b) {
            $cells[(int)$b['staff_id']][(int)$b['time_slot_id']] = $b;
        }
        foreach (Booking::list([], ['date' => $today, 'status' => 'completed'], 200) as $b) {
            $cells[(int)$b['staff_id']][(int)$b['time_slot_id']] ??= $b;
        }
        $board = [];
        foreach (User::staffList(true) as $s) {
            $row = ['staff' => $s, 'cells' => []];
            foreach ($slots as $slot) {
                $sid = (int)$slot['id'];
                if (isset($cells[(int)$s['id']][$sid])) {
                    $row['cells'][] = ['type' => 'booking', 'b' => $cells[(int)$s['id']][$sid], 'slot' => $slot];
                } else {
                    $row['cells'][] = ['type' => $svc->slotStart($today, $slot) > time() ? 'free' : 'past', 'slot' => $slot];
                }
            }
            $board[] = $row;
        }
        return ['slots' => $slots, 'rows' => $board];
    }

    /** Xuất danh sách đang lọc ra file CSV (mở được bằng Excel). */
    public function export(): void
    {
        [$filters] = $this->filters();
        $status = in_array($this->query('status'), Booking::STATUSES, true) ? (string)$this->query('status') : null;
        $rows = Booking::list([], $filters, 5000);
        if ($status) {
            $rows = array_filter($rows, fn($b) => $b['status'] === $status);
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lich-hen-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM để Excel đọc đúng tiếng Việt
        fputcsv($out, ['Mã đơn', 'Ngày', 'Khung giờ', 'Thú cưng', 'Loài', 'Khách hàng', 'Số điện thoại', 'Địa chỉ', 'Gói dịch vụ', 'Nhân viên', 'Trạng thái', 'Tổng tiền (đ)']);
        foreach ($rows as $b) {
            fputcsv($out, [
                booking_code($b['id']), vn_date($b['booking_date']), hm($b['start_time']) . '-' . hm($b['end_time']),
                $b['pet_name'], species_label($b['pet_species']), $b['client_name'], $b['client_phone'], $b['address'],
                $b['combo_name'] ?? 'Dịch vụ lẻ', $b['staff_name'], status_label($b['status']), (int)$b['total_price'],
            ]);
        }
        fclose($out);
    }

    public function show(int $id): void
    {
        $b = $this->booking($id);
        $svc = new BookingService();
        $reassignable = in_array($b['status'], ['pending', 'confirmed'], true);
        $this->render('admin/bookings/show', [
            'title' => 'Đơn ' . booking_code($id),
            'b' => $b,
            'freeStaff' => $reassignable ? array_values(array_filter(
                $svc->availableStaff($b['booking_date'], (int)$b['time_slot_id'], $id),
                fn($s) => (int)$s['id'] !== (int)$b['staff_id']
            )) : [],
            'records' => HealthRecord::forBooking($id),
            'photos' => array_map([BookingPhoto::class, 'present'], BookingPhoto::forBooking($id)),
            'logs' => ActivityLog::forTarget('booking', $id),
            'reassignable' => $reassignable,
        ]);
    }

    public function approve(int $id): void
    {
        $this->booking($id);
        $this->attempt(fn() => (new BookingService())->transition($this->user(), $id, 'confirmed'), "/admin/bookings/$id", "Đã duyệt đơn " . booking_code($id) . ".");
        $this->back($id);
    }

    public function cancel(int $id): void
    {
        $b = $this->booking($id);
        $label = $b['status'] === 'pending' ? 'từ chối' : 'hủy';
        $this->attempt(fn() => (new BookingService())->transition($this->user(), $id, 'cancelled', (string)$this->input('reason', '')),
            "/admin/bookings/$id", "Đã $label đơn " . booking_code($id) . ". Khung giờ đã được trả lại.");
        $this->back($id);
    }

    public function start(int $id): void
    {
        $this->booking($id);
        $this->attempt(fn() => (new BookingService())->transition($this->user(), $id, 'in_progress'), "/admin/bookings/$id", "Đơn " . booking_code($id) . " đã chuyển sang Đang tiến hành.");
        $this->back($id);
    }

    public function complete(int $id): void
    {
        $this->booking($id);
        $this->attempt(fn() => (new BookingService())->transition($this->user(), $id, 'completed'), "/admin/bookings/$id", "Đơn " . booking_code($id) . " đã hoàn thành.");
        $this->back($id);
    }

    public function reassign(int $id): void
    {
        $this->booking($id);
        $this->attempt(fn() => (new BookingService())->reassign($this->user(), $id, (int)$this->input('staff_id')),
            "/admin/bookings/$id", "Đã đổi nhân viên phụ trách đơn " . booking_code($id) . ".");
        redirect("/admin/bookings/$id");
    }

    public function reschedule(int $id): void
    {
        $this->booking($id);
        $staff = $this->input('staff_id') ? (int)$this->input('staff_id') : null;
        $this->attempt(fn() => (new BookingService())->reschedule($this->user(), $id, (string)$this->input('booking_date'), (int)$this->input('time_slot_id'), $staff),
            "/admin/bookings/$id", "Đã dời lịch đơn " . booking_code($id) . ".");
        redirect("/admin/bookings/$id");
    }

    public function health(int $id): void
    {
        $this->booking($id);
        $w = trim((string)$this->input('weight', ''));
        $this->attempt(fn() => (new BookingService())->addHealthRecord($this->user(), $id, $w === '' ? null : (float)str_replace(',', '.', $w), (string)$this->input('note', '')),
            "/admin/bookings/$id", 'Đã ghi sổ sức khỏe.');
        redirect("/admin/bookings/$id");
    }

    /** Admin gửi ảnh bổ sung (đang làm hoặc đã hoàn thành), ví dụ nhân viên nhờ gửi hộ. */
    public function uploadPhoto(int $id): void
    {
        $this->booking($id);
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
        $this->attempt($add, "/admin/bookings/$id", 'Đã gửi ảnh cho khách.');
        redirect("/admin/bookings/$id#anh");
    }

    /** Gỡ ảnh không phù hợp (chụp nhầm, lộ thông tin nhà khách...). Có ghi nhật ký. */
    public function deletePhoto(int $id): void
    {
        $bookingId = $this->attempt(fn() => (new BookingPhotoService())->delete($this->user(), $id), '/admin/bookings', 'Đã gỡ ảnh.');
        redirect("/admin/bookings/$bookingId#anh");
    }

    /** Quay lại danh sách nếu thao tác từ danh sách, ngược lại về chi tiết đơn. */
    private function back(int $id): never
    {
        $ret = (string)$this->input('return', '');
        if ($ret !== '' && str_starts_with($ret, '/admin')) {
            redirect($ret);
        }
        redirect("/admin/bookings/$id");
    }
}
