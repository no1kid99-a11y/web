<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Combo;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Services\ActivityLogger;
use App\Services\BookingService;
use Core\Database;

/**
 * Dịch vụ lẻ và combo. Không xóa cứng: ẩn bằng is_active. Đơn cũ không bị ảnh hưởng vì giá đã sao chép.
 */
class CatalogController extends AdminController
{
    // ---------------------------------------------------------------- Dịch vụ
    public function services(): void
    {
        $this->render('admin/services/index', ['title' => 'Dịch vụ', 'services' => Service::all()]);
    }

    public function createService(): void
    {
        $this->render('admin/services/form', ['title' => 'Thêm dịch vụ', 'service' => null, 'limit' => $this->durationLimit()]);
    }

    public function storeService(): void
    {
        $v = $this->validateService('/admin/services/create');
        Database::begin();
        $id = Service::create($v + ['is_active' => 1, 'updated_at' => Database::now()]);
        ActivityLogger::log($this->user(), 'service.save', 'service', $id, ['name' => $v['name'], 'price' => money($v['price'])]);
        Database::commit();
        flash('success', 'Đã thêm dịch vụ ' . e($v['name']) . '.');
        redirect('/admin/services');
    }

    public function editService(int $id): void
    {
        $s = Service::find($id);
        $this->notFoundUnless($s);
        $this->render('admin/services/form', ['title' => 'Sửa dịch vụ', 'service' => $s, 'limit' => $this->durationLimit()]);
    }

    public function updateService(int $id): void
    {
        $old = Service::find($id);
        $this->notFoundUnless($old);
        $v = $this->validateService("/admin/services/$id/edit");
        Database::begin();
        Service::updateById($id, $v);
        $changes = [];
        foreach (['name', 'price', 'duration_minutes'] as $k) {
            if ((string)$old[$k] !== (string)$v[$k]) {
                $changes[$k] = ['old' => $old[$k], 'new' => $v[$k]];
            }
        }
        ActivityLogger::log($this->user(), 'service.save', 'service', $id, $changes ?: ['name' => $v['name']]);
        Database::commit();
        flash('success', 'Đã lưu dịch vụ. Giá mới chỉ áp dụng cho đơn đặt sau thời điểm này.');
        redirect('/admin/services');
    }

    public function toggleService(int $id): void
    {
        $s = Service::find($id);
        $this->notFoundUnless($s);
        Service::updateById($id, ['is_active' => (int)$s['is_active'] ? 0 : 1]);
        flash('success', ((int)$s['is_active'] ? 'Đã ẩn ' : 'Đã hiện lại ') . e($s['name']) . '. Đơn cũ không bị ảnh hưởng.');
        redirect('/admin/services');
    }

    private function validateService(string $back): array
    {
        $data = [
            'name' => $this->input('name'), 'description' => $this->input('description'),
            'price' => preg_replace('/\D/', '', (string)$this->input('price')), 'duration_minutes' => $this->input('duration_minutes'),
        ];
        $v = $this->validate($data, [
            'name' => 'required|max:150', 'description' => 'nullable|max:1000',
            'price' => 'required|int|between:1000,100000000', 'duration_minutes' => 'required|int|between:5,600',
        ], $back);
        $limit = $this->durationLimit();
        if ($v['duration_minutes'] > $limit) {
            $this->withOld($data);
            flash('error', "Thời lượng tối đa là $limit phút để vừa một khung giờ (đã trừ thời gian di chuyển).");
            redirect($back);
        }
        return $v;
    }

    private function durationLimit(): int
    {
        return max(0, TimeSlot::maxActiveMinutes() - (new BookingService())->travelMinutes());
    }

    // ---------------------------------------------------------------- Combo
    public function combos(): void
    {
        $this->render('admin/combos/index', ['title' => 'Combo', 'combos' => Combo::all(), 'limit' => $this->durationLimit()]);
    }

    public function createCombo(): void
    {
        $this->render('admin/combos/form', ['title' => 'Thêm combo', 'combo' => null, 'selected' => [], 'services' => Service::all(true), 'limit' => $this->durationLimit()]);
    }

    public function storeCombo(): void
    {
        [$v, $ids] = $this->validateCombo('/admin/combos/create');
        Database::begin();
        $id = Combo::create($v + ['is_active' => 1, 'updated_at' => Database::now()]);
        Combo::syncServices($id, $ids);
        ActivityLogger::log($this->user(), 'combo.save', 'combo', $id, ['name' => $v['name'], 'price' => money($v['price'])]);
        Database::commit();
        flash('success', 'Đã thêm ' . e($v['name']) . '.');
        redirect('/admin/combos');
    }

    public function editCombo(int $id): void
    {
        $c = Combo::find($id);
        $this->notFoundUnless($c);
        $this->render('admin/combos/form', [
            'title' => 'Sửa combo', 'combo' => $c,
            'selected' => array_map('intval', array_column(Combo::services($id), 'id')),
            'services' => Service::all(), 'limit' => $this->durationLimit(),
        ]);
    }

    public function updateCombo(int $id): void
    {
        $old = Combo::find($id);
        $this->notFoundUnless($old);
        [$v, $ids] = $this->validateCombo("/admin/combos/$id/edit");
        Database::begin();
        Combo::updateById($id, $v);
        Combo::syncServices($id, $ids);
        $changes = (string)$old['price'] !== (string)$v['price'] ? ['price' => ['old' => $old['price'], 'new' => $v['price']]] : ['name' => $v['name']];
        ActivityLogger::log($this->user(), 'combo.save', 'combo', $id, $changes);
        Database::commit();
        flash('success', 'Đã lưu combo. Đơn đã đặt giữ nguyên giá cũ.');
        redirect('/admin/combos');
    }

    public function toggleCombo(int $id): void
    {
        $c = Combo::find($id);
        $this->notFoundUnless($c);
        Combo::updateById($id, ['is_active' => (int)$c['is_active'] ? 0 : 1]);
        flash('success', ((int)$c['is_active'] ? 'Đã ẩn ' : 'Đã hiện lại ') . e($c['name']) . '.');
        redirect('/admin/combos');
    }

    private function validateCombo(string $back): array
    {
        $data = ['name' => $this->input('name'), 'description' => $this->input('description'), 'price' => preg_replace('/\D/', '', (string)$this->input('price'))];
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['service_ids'] ?? [])))));
        $v = $this->validate($data, ['name' => 'required|max:150', 'description' => 'nullable|max:1000', 'price' => 'required|int|between:1000,100000000'], $back);
        $fail = function (string $msg) use ($data, $ids, $back) {
            $this->withOld($data + ['service_ids' => $ids]);
            flash('error', $msg);
            redirect($back);
        };
        if (count($ids) < 2) {
            $fail('Combo phải gồm ít nhất 2 dịch vụ.');
        }
        $services = Service::activeByIds($ids);
        if (count($services) !== count($ids)) {
            $fail('Có dịch vụ đã bị ẩn hoặc không tồn tại.');
        }
        $duration = array_sum(array_column($services, 'duration_minutes'));
        if ($duration > $this->durationLimit()) {
            $fail("Tổng thời lượng $duration phút vượt giới hạn {$this->durationLimit()} phút của một khung giờ.");
        }
        $retail = array_sum(array_column($services, 'price'));
        if ($v['price'] > $retail) {
            $fail('Giá combo (' . money($v['price']) . ') không nên cao hơn tổng giá lẻ (' . money($retail) . ').');
        }
        return [$v, $ids];
    }
}
