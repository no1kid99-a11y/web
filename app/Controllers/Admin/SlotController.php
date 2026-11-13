<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\TimeSlot;
use App\Services\ActivityLogger;
use App\Services\BookingService;
use Core\Database;

/**
 * Khung giờ. Không sửa giờ hay ẩn khung đang có đơn giữ chỗ trong tương lai; khung mới không được chồng lên khung cũ.
 */
class SlotController extends AdminController
{
    public function index(): void
    {
        $slots = TimeSlot::all();
        foreach ($slots as &$s) {
            $s['future'] = (int)Database::value('SELECT COUNT(*) FROM bookings WHERE time_slot_id = ? AND active_flag = 1 AND booking_date >= ?', [$s['id'], Database::today()]);
        }
        $this->render('admin/slots/index', ['title' => 'Khung giờ', 'slots' => $slots, 'travel' => (new BookingService())->travelMinutes()]);
    }

    public function store(): void
    {
        [$start, $end] = $this->times('/admin/slots');
        $this->assertNoOverlap($start, $end, null);
        Database::begin();
        $id = TimeSlot::create(['start_time' => $start, 'end_time' => $end, 'is_active' => 1]);
        ActivityLogger::log($this->user(), 'slot.save', 'slot', $id, ['to' => hm($start) . '–' . hm($end)]);
        Database::commit();
        flash('success', 'Đã thêm khung ' . hm($start) . '–' . hm($end) . '.');
        redirect('/admin/slots');
    }

    public function update(int $id): void
    {
        $slot = TimeSlot::find($id);
        $this->notFoundUnless($slot);
        [$start, $end] = $this->times('/admin/slots');
        if (TimeSlot::hasFutureHolding($id) && ($start !== $slot['start_time'] || $end !== $slot['end_time'])) {
            flash('error', 'Khung này đang có đơn giữ chỗ trong tương lai nên không đổi giờ được. Hãy thêm khung mới và ẩn khung cũ sau khi các đơn hoàn thành.');
            redirect('/admin/slots');
        }
        $this->assertNoOverlap($start, $end, $id);
        Database::begin();
        Database::update('time_slots', ['start_time' => $start, 'end_time' => $end], 'id = :id', ['id' => $id]);
        ActivityLogger::log($this->user(), 'slot.save', 'slot', $id, ['from' => TimeSlot::label($slot), 'to' => hm($start) . '–' . hm($end)]);
        Database::commit();
        flash('success', 'Đã lưu khung giờ.');
        redirect('/admin/slots');
    }

    public function toggle(int $id): void
    {
        $slot = TimeSlot::find($id);
        $this->notFoundUnless($slot);
        if ((int)$slot['is_active'] && TimeSlot::hasFutureHolding($id)) {
            flash('error', 'Khung ' . TimeSlot::label($slot) . ' đang có đơn giữ chỗ trong tương lai. Hãy dời hoặc hoàn thành các đơn đó trước khi ẩn.');
            redirect('/admin/slots');
        }
        Database::update('time_slots', ['is_active' => (int)$slot['is_active'] ? 0 : 1], 'id = :id', ['id' => $id]);
        flash('success', ((int)$slot['is_active'] ? 'Đã ẩn' : 'Đã bật lại') . ' khung ' . TimeSlot::label($slot) . '.');
        redirect('/admin/slots');
    }

    private function times(string $back): array
    {
        $v = $this->validate(
            ['start_time' => $this->input('start_time'), 'end_time' => $this->input('end_time')],
            ['start_time' => 'required|time', 'end_time' => 'required|time'],
            $back
        );
        $start = substr($v['start_time'], 0, 5) . ':00';
        $end = substr($v['end_time'], 0, 5) . ':00';
        $minutes = (strtotime("2000-01-01 $end") - strtotime("2000-01-01 $start")) / 60;
        if ($minutes < 60) {
            flash('error', 'Khung giờ phải dài ít nhất 60 phút và giờ kết thúc phải sau giờ bắt đầu.');
            redirect($back);
        }
        return [$start, $end];
    }

    private function assertNoOverlap(string $start, string $end, ?int $ignore): void
    {
        $sql = 'SELECT COUNT(*) FROM time_slots WHERE start_time < ? AND end_time > ?' . ($ignore ? ' AND id <> ?' : '');
        $p = $ignore ? [$end, $start, $ignore] : [$end, $start];
        if ((int)Database::value($sql, $p) > 0) {
            flash('error', 'Khung giờ bị chồng lên một khung đã có. Mỗi nhân viên chỉ nhận một đơn mỗi khung nên các khung không được giao nhau.');
            redirect('/admin/slots');
        }
    }
}
