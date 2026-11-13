<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\Combo;
use App\Models\HealthRecord;
use App\Models\Pet;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Policies\BookingPolicy;
use Core\AppException;
use Core\Auth;
use Core\Database;
use PDOException;

/**
 * Toàn bộ nghiệp vụ lịch hẹn và chống trùng lịch.
 *
 * Lớp 1 (giao diện) gọi availableSlots() qua API, lớp 2 (server) là các hàm create/transition/
 * reassign/reschedule bên dưới, lớp 3 là hai chỉ mục duy nhất uq_staff_slot/uq_pet_slot trong database,
 * lớp 4 là vòng lặp giao dịch: bắt lỗi trùng khóa, deadlock, khóa dòng nhân viên, đổi trạng thái có điều kiện.
 */
class BookingService
{
    /** Đường chuyển trạng thái hợp lệ. */
    public const TRANSITIONS = [
        'pending'     => ['confirmed', 'cancelled'],
        'confirmed'   => ['in_progress', 'cancelled'],
        'in_progress' => ['completed'],
        'completed'   => [],
        'cancelled'   => [],
    ];

    protected int $travel;
    protected int $leadHours;
    protected int $maxDays;
    protected int $attempts;

    public function __construct()
    {
        $this->travel = (int)config('booking.travel_minutes', 30);
        $this->leadHours = (int)config('booking.min_lead_hours', 2);
        $this->maxDays = (int)config('booking.max_days_ahead', 30);
        $this->attempts = (int)config('booking.retry_attempts', 3);
    }

    public function travelMinutes(): int
    {
        return $this->travel;
    }

    // ------------------------------------------------------------------
    // Truy vấn tính khung trống
    // ------------------------------------------------------------------

    /**
     * Đơn Chờ duyệt đã quá giờ hẹn tự bị hủy với lý do "Hết hạn", để không giữ chỉ mục duy nhất.
     * Không cần cron: được gọi khi mở API khung giờ, danh sách đơn, lịch nhân viên và trước khi lưu đơn mới.
     */
    public function expirePending(): int
    {
        $today = Database::today();
        return Database::query(
            "UPDATE bookings SET status = 'cancelled', cancel_reason = 'Hết hạn', updated_at = ?
             WHERE status = 'pending'
               AND (booking_date < ? OR (booking_date = ? AND time_slot_id IN (SELECT id FROM time_slots WHERE start_time <= ?)))",
            [Database::now(), $today, $today, date('H:i:s')]
        )->rowCount();
    }

    public function slotStart(string $date, array $slot): int
    {
        return (int)strtotime($date . ' ' . $slot['start_time']);
    }

    /**
     * Nhân viên khả dụng và còn rảnh ở ngày + khung giờ:
     * role = staff, đang hoạt động (không bị khóa), chưa có đơn giữ chỗ ở khung đó.
     * Admin không bao giờ được tính. Sắp xếp người ít đơn nhất trong ngày lên trước, hòa thì id nhỏ hơn.
     */
    public function availableStaff(string $date, int $slotId, ?int $excludeBookingId = null): array
    {
        $ex = $excludeBookingId ? ' AND x.id <> ' . (int)$excludeBookingId : '';
        $exb = $excludeBookingId ? ' AND b.id <> ' . (int)$excludeBookingId : '';
        return Database::fetchAll(
            "SELECT u.id, u.name,
                (SELECT COUNT(*) FROM bookings x WHERE x.staff_id = u.id AND x.booking_date = ? AND x.active_flag = 1 $ex) AS day_load
             FROM users u
             WHERE u.role = 'staff' AND u.status = 'active'
               AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.staff_id = u.id AND b.booking_date = ?
                               AND b.time_slot_id = ? AND b.active_flag = 1 $exb)
             ORDER BY day_load ASC, u.id ASC",
            [$date, $date, $slotId]
        );
    }

    public function isStaffAvailable(int $staffId, string $date, int $slotId, ?int $excludeBookingId = null): bool
    {
        foreach ($this->availableStaff($date, $slotId, $excludeBookingId) as $s) {
            if ((int)$s['id'] === $staffId) {
                return true;
            }
        }
        return false;
    }

    public function petBusy(int $petId, string $date, int $slotId, ?int $excludeBookingId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM bookings WHERE pet_id = ? AND booking_date = ? AND time_slot_id = ? AND active_flag = 1';
        $p = [$petId, $date, $slotId];
        if ($excludeBookingId) {
            $sql .= ' AND id <> ?';
            $p[] = $excludeBookingId;
        }
        return (int)Database::value($sql, $p) > 0;
    }

    /**
     * Danh sách khung giờ của một ngày kèm trạng thái còn trống. Dùng chung cho Booking Wizard (API)
     * và màn hình dời lịch của Admin, nên giao diện và server không bao giờ lệch quy tắc.
     */
    public function availableSlots(string $date, ?int $petId = null, ?int $excludeBookingId = null, bool $enforceLead = true): array
    {
        $this->expirePending();
        $valid = $this->isValidDate($date);
        $out = [];
        foreach (TimeSlot::all(true) as $slot) {
            $sid = (int)$slot['id'];
            $start = $this->slotStart($date, $slot);
            $reason = null;
            $free = [];
            if (!$valid) {
                $reason = 'Ngày không hợp lệ';
            } elseif ($start <= time()) {
                $reason = 'Đã qua';
            } elseif ($enforceLead && $start - time() < $this->leadHours * 3600) {
                $reason = 'Cần đặt trước ' . $this->leadHours . ' giờ';
            } elseif ($enforceLead && $date > date('Y-m-d', strtotime("+{$this->maxDays} days"))) {
                $reason = 'Quá xa (tối đa ' . $this->maxDays . ' ngày)';
            } elseif ($petId && $this->petBusy($petId, $date, $sid, $excludeBookingId)) {
                $reason = 'Thú cưng đã có lịch';
            } else {
                $free = $this->availableStaff($date, $sid, $excludeBookingId);
                if (!$free) {
                    $reason = 'Đã kín lịch';
                }
            }
            $out[] = [
                'id'         => $sid,
                'label'      => TimeSlot::label($slot),
                'start_time' => hm($slot['start_time']),
                'end_time'   => hm($slot['end_time']),
                'minutes'    => TimeSlot::minutes($slot),
                'available'  => $reason === null,
                'reason'     => $reason,
                'free_staff' => count($free),
                'staff'      => array_map(fn($s) => ['id' => (int)$s['id'], 'name' => $s['name']], $free),
            ];
        }
        return $out;
    }

    // ------------------------------------------------------------------
    // Kiểm tra dữ liệu đặt lịch
    // ------------------------------------------------------------------

    public function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Chọn một combo HOẶC các dịch vụ lẻ (không trộn). Trả về danh sách dịch vụ, giá chốt, giảm giá, thời lượng.
     */
    public function resolveItems(?int $comboId, array $serviceIds): array
    {
        $serviceIds = array_values(array_filter(array_map('intval', $serviceIds)));
        if ($comboId && $serviceIds) {
            throw new AppException('Chỉ chọn một combo hoặc các dịch vụ lẻ, không trộn cả hai.');
        }
        if ($comboId) {
            $combo = Combo::find($comboId);
            if (!$combo || !(int)$combo['is_active']) {
                throw new AppException('Combo không tồn tại hoặc đã ngừng cung cấp.');
            }
            $services = Combo::services($comboId);
            if (!$services) {
                throw new AppException('Combo chưa có dịch vụ nào.');
            }
            $retail = array_sum(array_column($services, 'price'));
            $total = (int)$combo['price'];
            return [
                'combo_id' => $comboId,
                'combo'    => $combo,
                'services' => $services,
                'total'    => $total,
                'discount' => max(0, $retail - $total),
                'duration' => array_sum(array_column($services, 'duration_minutes')),
            ];
        }
        if (!$serviceIds) {
            throw new AppException('Vui lòng chọn một combo hoặc ít nhất một dịch vụ.');
        }
        $services = Service::activeByIds($serviceIds);
        if (count($services) !== count(array_unique($serviceIds))) {
            throw new AppException('Có dịch vụ không tồn tại hoặc đã ngừng cung cấp.');
        }
        return [
            'combo_id' => null,
            'combo'    => null,
            'services' => $services,
            'total'    => array_sum(array_column($services, 'price')),
            'discount' => 0,
            'duration' => array_sum(array_column($services, 'duration_minutes')),
        ];
    }

    /** Tổng thời lượng + thời gian di chuyển không được vượt độ dài khung. */
    public function checkDuration(int $duration, array $slot): void
    {
        $len = TimeSlot::minutes($slot);
        if ($duration + $this->travel > $len) {
            throw new AppException(sprintf(
                'Tổng thời gian dịch vụ (%d phút) cộng di chuyển (%d phút) vượt độ dài khung giờ (%d phút). Hãy bớt dịch vụ hoặc chia thành hai lịch.',
                $duration,
                $this->travel,
                $len
            ));
        }
    }

    protected function assertBookableTime(string $date, array $slot, bool $enforceLead): void
    {
        if (!$this->isValidDate($date)) {
            throw new AppException('Ngày hẹn không hợp lệ.');
        }
        if (!(int)$slot['is_active']) {
            throw new AppException('Khung giờ này đã ngừng nhận lịch.');
        }
        $start = $this->slotStart($date, $slot);
        if ($start <= time()) {
            throw new AppException('Không thể chọn ngày giờ đã qua.');
        }
        if ($enforceLead && $start - time() < $this->leadHours * 3600) {
            throw new AppException("Cần đặt trước giờ hẹn ít nhất {$this->leadHours} giờ.");
        }
        if ($enforceLead && $date > date('Y-m-d', strtotime("+{$this->maxDays} days"))) {
            throw new AppException("Chỉ nhận đặt lịch trong vòng {$this->maxDays} ngày tới.");
        }
    }

    // ------------------------------------------------------------------
    // Tạo đơn
    // ------------------------------------------------------------------

    /**
     * Tạo đơn trong giao dịch, tự gán nhân viên. Thử lại khi nhân viên vừa bị người khác nhận
     * (uq_staff_slot) hoặc deadlock, tối đa 2 lần.
     */
    public function create(array $client, array $input): int
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                Database::begin();
                $this->expirePending();
                $id = $this->createOnce($client, $input);
                Database::commit();
                return $id;
            } catch (PDOException $e) {
                Database::rollBack();
                $index = Database::uniqueViolation($e);
                if ($index === 'uq_pet_slot') {
                    throw new AppException('Thú cưng này đã có lịch ở khung giờ đã chọn.');
                }
                if ($index === 'uq_staff_slot' || Database::isDeadlock($e)) {
                    if ($attempt < $this->attempts) {
                        continue;
                    }
                    throw new AppException('Khung giờ này vừa có người đặt, vui lòng chọn khung khác.');
                }
                throw $e;
            } catch (RetryException) {
                Database::rollBack();
                if ($attempt < $this->attempts) {
                    continue;
                }
                throw new AppException('Khung giờ này vừa có người đặt, vui lòng chọn khung khác.');
            } catch (\Throwable $e) {
                Database::rollBack();
                throw $e;
            }
        }
    }

    protected function createOnce(array $client, array $in): int
    {
        if (!Auth::can('booking.create', $client)) {
            throw new AppException('Chỉ khách hàng mới được đặt lịch.');
        }
        $pet = Pet::findOwned((int)($in['pet_id'] ?? 0), (int)$client['id']);
        if (!$pet) {
            throw new AppException('Thú cưng không hợp lệ.');
        }
        $date = (string)($in['booking_date'] ?? '');
        $slot = TimeSlot::find((int)($in['time_slot_id'] ?? 0));
        if (!$slot) {
            throw new AppException('Khung giờ không hợp lệ.');
        }
        $this->assertBookableTime($date, $slot, true);
        $items = $this->resolveItems(
            !empty($in['combo_id']) ? (int)$in['combo_id'] : null,
            (array)($in['service_ids'] ?? [])
        );
        $this->checkDuration($items['duration'], $slot);

        $address = trim((string)($in['address'] ?? ''));
        if (mb_strlen($address) < 5) {
            throw new AppException('Vui lòng nhập địa chỉ đầy đủ.');
        }
        if ($this->petBusy((int)$pet['id'], $date, (int)$slot['id'])) {
            throw new AppException('Thú cưng này đã có lịch ở khung giờ đã chọn.');
        }

        $staffId = $this->pickStaff($date, (int)$slot['id']);
        if ($staffId === null) {
            throw new AppException('Khung giờ này đã kín lịch, vui lòng chọn khung khác.');
        }
        // Khóa đúng dòng của nhân viên (dòng đã tồn tại, không sinh gap lock) rồi kiểm tra lại:
        // khóa tài khoản nhân viên cũng khóa dòng này nên không thể chen ngang.
        Database::lockRow('users', $staffId);
        if (!$this->isStaffAvailable($staffId, $date, (int)$slot['id'])) {
            throw new RetryException();
        }

        $now = Database::now();
        $id = Database::insert('bookings', [
            'user_id'         => (int)$client['id'],
            'pet_id'          => (int)$pet['id'],
            'combo_id'        => $items['combo_id'],
            'time_slot_id'    => (int)$slot['id'],
            'staff_id'        => $staffId,
            'booking_date'    => $date,
            'address'         => mb_substr($address, 0, 255),
            'total_price'     => $items['total'],
            'discount_amount' => $items['discount'],
            'status'          => 'pending',
            'note'            => ($in['note'] ?? '') !== '' ? mb_substr((string)$in['note'], 0, 1000) : null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        // Sao chép giá lẻ tại thời điểm đặt để đổi giá về sau không làm sai đơn cũ.
        foreach ($items['services'] as $s) {
            Database::insert('booking_services', ['booking_id' => $id, 'service_id' => (int)$s['id'], 'price' => (int)$s['price']]);
        }
        return $id;
    }

    /** Chọn nhân viên khả dụng ít việc nhất. Tách riêng để kiểm thử có thể mô phỏng tranh chấp. */
    protected function pickStaff(string $date, int $slotId, ?int $excludeBookingId = null): ?int
    {
        $list = $this->availableStaff($date, $slotId, $excludeBookingId);
        return $list ? (int)$list[0]['id'] : null;
    }

    // ------------------------------------------------------------------
    // Chuyển trạng thái
    // ------------------------------------------------------------------

    public function transition(array $actor, int $bookingId, string $to, ?string $reason = null): void
    {
        $plan = $this->prepareTransition($actor, $bookingId, $to, $reason);
        $this->applyTransition($actor, $plan);
    }

    /**
     * Bước 1: kiểm tra quyền và quy tắc, trả về kế hoạch cập nhật (gồm trạng thái đã đọc).
     */
    public function prepareTransition(array $actor, int $bookingId, string $to, ?string $reason = null): array
    {
        $b = Booking::find($bookingId);
        if (!$b) {
            throw new AppException('Không tìm thấy đơn.');
        }
        $from = $b['status'];
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new AppException(sprintf('Không thể chuyển đơn từ "%s" sang "%s".', status_label($from), status_label($to)));
        }
        $slot = TimeSlot::find((int)$b['time_slot_id']);
        $reason = $reason !== null ? trim($reason) : null;
        $action = null;
        $data = ['status' => $to, 'updated_at' => Database::now()];

        switch ($to) {
            case 'confirmed':
                if (!Auth::can('booking.approve', $actor)) {
                    throw new AppException('Chỉ quản trị viên mới được duyệt đơn.');
                }
                $action = 'booking.approve';
                break;

            case 'cancelled':
                if (!BookingPolicy::cancel($actor, $b)) {
                    throw new AppException('Bạn không có quyền hủy đơn này.');
                }
                if ($actor['role'] === 'admin') {
                    if (!$reason) {
                        throw new AppException('Vui lòng nhập lý do hủy hoặc từ chối.');
                    }
                    $action = $from === 'pending' ? 'booking.reject' : 'booking.cancel';
                } else {
                    if ($this->slotStart($b['booking_date'], $slot) - time() < $this->leadHours * 3600) {
                        throw new AppException("Chỉ được hủy trước giờ hẹn ít nhất {$this->leadHours} giờ. Vui lòng liên hệ cửa hàng.");
                    }
                    $reason = $reason ?: 'Khách hàng hủy';
                }
                $data['cancel_reason'] = mb_substr($reason, 0, 255);
                $data['cancelled_by'] = (int)$actor['id'];
                break;

            case 'in_progress':
                if (!BookingPolicy::progress($actor, $b)) {
                    throw new AppException('Bạn không phụ trách đơn này.');
                }
                $today = Database::today();
                if ($actor['role'] === 'staff' && $b['booking_date'] !== $today) {
                    throw new AppException('Chỉ được bắt đầu dịch vụ trong ngày hẹn.');
                }
                if ($b['booking_date'] > $today) {
                    throw new AppException('Chưa đến ngày hẹn.');
                }
                $action = 'booking.start';
                break;

            case 'completed':
                if (!BookingPolicy::progress($actor, $b)) {
                    throw new AppException('Bạn không phụ trách đơn này.');
                }
                if ($actor['role'] === 'staff' && !HealthRecord::existsForBooking($bookingId)) {
                    throw new AppException('Hãy ghi sổ sức khỏe cho thú cưng trước khi hoàn thành đơn.');
                }
                $action = 'booking.complete';
                break;
        }
        return ['booking_id' => $bookingId, 'from' => $from, 'to' => $to, 'data' => $data, 'action' => $action];
    }

    /**
     * Bước 2: ghi thay đổi. Đổi trạng thái có điều kiện (WHERE status = trạng thái đã đọc):
     * nếu người khác vừa đổi trước thì rowCount = 0 và báo tải lại, không ghi đè.
     */
    public function applyTransition(array $actor, array $plan): void
    {
        ['booking_id' => $bookingId, 'from' => $from, 'to' => $to, 'data' => $data, 'action' => $action] = $plan;
        Database::begin();
        try {
            $rows = Database::update('bookings', $data, 'id = :id AND status = :from', ['id' => $bookingId, 'from' => $from]);
            if ($rows === 0) {
                throw new AppException('Đơn đã được cập nhật bởi người khác, vui lòng tải lại trang.');
            }
            if ($action && $actor['role'] !== 'client') {
                ActivityLogger::log($actor, $action, 'booking', $bookingId, array_filter([
                    'from' => status_label($from),
                    'to' => status_label($to),
                    'reason' => $data['cancel_reason'] ?? null,
                ]));
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Đổi nhân viên, dời lịch (chỉ Admin)
    // ------------------------------------------------------------------

    public function reassign(array $admin, int $bookingId, int $newStaffId): void
    {
        if (!Auth::can('booking.reassign', $admin)) {
            throw new AppException('Chỉ quản trị viên mới được đổi nhân viên.');
        }
        $this->inTransaction(function () use ($admin, $bookingId, $newStaffId) {
            $b = Database::lockRow('bookings', $bookingId);
            if (!$b) {
                throw new AppException('Không tìm thấy đơn.');
            }
            if (!in_array($b['status'], ['pending', 'confirmed'], true)) {
                throw new AppException('Chỉ đổi nhân viên cho đơn Chờ duyệt hoặc Đã xác nhận.');
            }
            if ((int)$b['staff_id'] === $newStaffId) {
                throw new AppException('Nhân viên mới trùng với nhân viên hiện tại.');
            }
            $staff = Database::lockRow('users', $newStaffId);
            if (!$staff || $staff['role'] !== 'staff' || $staff['status'] !== 'active') {
                throw new AppException('Nhân viên không hợp lệ hoặc đã bị khóa.');
            }
            if (!$this->isStaffAvailable($newStaffId, $b['booking_date'], (int)$b['time_slot_id'], $bookingId)) {
                throw new AppException('Nhân viên này không rảnh ở khung giờ của đơn (bị khóa hoặc đã có lịch).');
            }
            $old = Database::fetch('SELECT name FROM users WHERE id = ?', [(int)$b['staff_id']]);
            $rows = Database::update(
                'bookings',
                ['staff_id' => $newStaffId, 'updated_at' => Database::now()],
                'id = :id AND staff_id = :old AND status = :st',
                ['id' => $bookingId, 'old' => (int)$b['staff_id'], 'st' => $b['status']]
            );
            if ($rows === 0) {
                throw new AppException('Đơn đã được cập nhật bởi người khác, vui lòng tải lại trang.');
            }
            ActivityLogger::log($admin, 'booking.reassign', 'booking', $bookingId, [
                'from_staff' => $old['name'] ?? '?',
                'to_staff' => $staff['name'],
            ]);
        }, 'Nhân viên này vừa được giao lịch khác ở cùng khung giờ.');
    }

    public function reschedule(array $admin, int $bookingId, string $date, int $slotId, ?int $staffId = null): void
    {
        if (!Auth::can('booking.reschedule', $admin)) {
            throw new AppException('Chỉ quản trị viên mới được dời lịch.');
        }
        $this->inTransaction(function () use ($admin, $bookingId, $date, $slotId, $staffId) {
            $b = Database::lockRow('bookings', $bookingId);
            if (!$b) {
                throw new AppException('Không tìm thấy đơn.');
            }
            if (!in_array($b['status'], ['pending', 'confirmed'], true)) {
                throw new AppException('Chỉ dời lịch cho đơn Chờ duyệt hoặc Đã xác nhận.');
            }
            $slot = TimeSlot::find($slotId);
            if (!$slot) {
                throw new AppException('Khung giờ không hợp lệ.');
            }
            $this->assertBookableTime($date, $slot, false);
            $duration = array_sum(array_column(Booking::services($bookingId), 'duration_minutes'));
            $this->checkDuration((int)$duration, $slot);
            if ($this->petBusy((int)$b['pet_id'], $date, $slotId, $bookingId)) {
                throw new AppException('Thú cưng đã có lịch khác ở khung giờ mới.');
            }

            $current = (int)$b['staff_id'];
            if ($staffId) {
                if (!$this->isStaffAvailable($staffId, $date, $slotId, $bookingId)) {
                    throw new AppException('Nhân viên đã chọn không rảnh ở khung giờ mới.');
                }
                $newStaff = $staffId;
            } elseif ($this->isStaffAvailable($current, $date, $slotId, $bookingId)) {
                $newStaff = $current; // giữ nhân viên cũ nếu vẫn rảnh
            } else {
                $newStaff = $this->pickStaff($date, $slotId, $bookingId);
                if ($newStaff === null) {
                    throw new AppException('Khung giờ mới đã kín lịch.');
                }
            }
            if ($date === $b['booking_date'] && $slotId === (int)$b['time_slot_id'] && $newStaff === $current) {
                throw new AppException('Thời gian mới trùng với thời gian hiện tại.');
            }
            Database::lockRow('users', $newStaff);
            if (!$this->isStaffAvailable($newStaff, $date, $slotId, $bookingId)) {
                throw new AppException('Nhân viên vừa được giao lịch khác, vui lòng thử lại.');
            }

            $oldSlot = TimeSlot::find((int)$b['time_slot_id']);
            $rows = Database::update(
                'bookings',
                ['booking_date' => $date, 'time_slot_id' => $slotId, 'staff_id' => $newStaff, 'updated_at' => Database::now()],
                'id = :id AND status = :st AND booking_date = :od AND time_slot_id = :os',
                ['id' => $bookingId, 'st' => $b['status'], 'od' => $b['booking_date'], 'os' => (int)$b['time_slot_id']]
            );
            if ($rows === 0) {
                throw new AppException('Đơn đã được cập nhật bởi người khác, vui lòng tải lại trang.');
            }
            $names = Database::fetchAll('SELECT id, name FROM users WHERE id IN (?, ?)', [$current, $newStaff]);
            $names = array_column($names, 'name', 'id');
            ActivityLogger::log($admin, 'booking.reschedule', 'booking', $bookingId, array_filter([
                'from' => vn_date($b['booking_date']) . ' ' . TimeSlot::label($oldSlot),
                'to' => vn_date($date) . ' ' . TimeSlot::label($slot),
                'from_staff' => $newStaff !== $current ? ($names[$current] ?? null) : null,
                'to_staff' => $newStaff !== $current ? ($names[$newStaff] ?? null) : null,
            ]));
        }, 'Nhân viên hoặc thú cưng vừa có lịch khác ở khung giờ mới, vui lòng chọn lại.');
    }

    // ------------------------------------------------------------------
    // Sổ sức khỏe
    // ------------------------------------------------------------------

    public function addHealthRecord(array $actor, int $bookingId, ?float $weight, string $note): void
    {
        $b = Booking::find($bookingId);
        if (!$b) {
            throw new AppException('Không tìm thấy đơn.');
        }
        if (!BookingPolicy::writeHealth($actor, $b)) {
            throw new AppException('Bạn không phụ trách đơn này.');
        }
        $allowed = $actor['role'] === 'admin' ? ['in_progress', 'completed'] : ['in_progress'];
        if (!in_array($b['status'], $allowed, true)) {
            throw new AppException('Chỉ ghi sổ sức khỏe khi dịch vụ đang tiến hành.');
        }
        if ($weight !== null && ($weight <= 0 || $weight > 150)) {
            throw new AppException('Cân nặng không hợp lệ.');
        }
        $note = trim($note);
        if ($note === '') {
            throw new AppException('Vui lòng nhập ghi chú sức khỏe.');
        }
        Database::begin();
        try {
            $id = Database::insert('health_records', [
                'pet_id'      => (int)$b['pet_id'],
                'booking_id'  => $bookingId,
                'recorded_by' => (int)$actor['id'],
                'weight'      => $weight,
                'note'        => mb_substr($note, 0, 2000),
                'recorded_at' => Database::now(),
            ]);
            if ($weight !== null) {
                // Cập nhật luôn hồ sơ thú cưng để hai nơi không lệch nhau.
                Database::update('pets', ['weight' => $weight, 'updated_at' => Database::now()], 'id = :id', ['id' => (int)$b['pet_id']]);
            }
            ActivityLogger::log($actor, 'health.record', 'booking', $bookingId, array_filter(['weight' => $weight, 'record_id' => $id]));
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------

    /**
     * Chạy thao tác trong giao dịch; thử lại khi deadlock, đổi lỗi trùng khóa thành thông báo dễ hiểu.
     */
    protected function inTransaction(callable $fn, string $duplicateMessage): void
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                Database::begin();
                $fn();
                Database::commit();
                return;
            } catch (PDOException $e) {
                Database::rollBack();
                if (Database::isDeadlock($e) && $attempt < $this->attempts) {
                    continue;
                }
                if (Database::uniqueViolation($e) !== null) {
                    throw new AppException($duplicateMessage);
                }
                throw $e;
            } catch (\Throwable $e) {
                Database::rollBack();
                throw $e;
            }
        }
    }
}
