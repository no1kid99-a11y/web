<?php
$id = (int)$b['id'];
$st = $b['status'];
$today = date('Y-m-d');
?>
<a href="<?= url('/admin/bookings') ?>" class="text-sm text-ink-500 hover:text-ink-900">Lịch hẹn</a>
<div class="mt-2 flex flex-wrap items-center justify-between gap-4">
  <h1 class="page-title">Đơn <?= booking_code($id) ?> · <?= e($b['pet_name']) ?></h1>
  <?= status_badge($st) ?>
</div>
<div class="mt-5"><?= \Core\View::partial('partials/status_tracker', ['b' => $b]) ?></div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1fr_400px]">
  <div class="space-y-6">
    <section class="panel overflow-hidden">
      <div class="panel-head">
        <h2 class="panel-title">Thông tin đơn</h2>
        <span class="text-xs text-ink-500">Đặt lúc <?= e(vn_datetime($b['created_at'])) ?></span>
      </div>
      <?= \Core\View::partial('partials/booking_summary', ['b' => $b, 'showClient' => true]) ?>
      <div class="flex flex-wrap gap-4 border-t border-ink-100 px-5 py-3 text-sm">
        <a class="link" href="<?= url('/admin/users/' . $b['user_id']) ?>">Hồ sơ khách hàng</a>
        <a class="link" href="<?= url('/admin/pets/' . $b['pet_id']) ?>">Hồ sơ thú cưng</a>
        <?php if ($b['review_id']): ?><a class="link" href="<?= url('/admin/reviews') ?>">Đánh giá: <?= (int)$b['review_rating'] ?>/5</a><?php endif; ?>
      </div>
    </section>

    <?php if ($records): ?>
      <section class="panel">
        <div class="panel-head"><h2 class="panel-title">Sổ sức khỏe của đơn</h2></div>
        <?= \Core\View::partial('partials/health_timeline', ['records' => $records]) ?>
      </section>
    <?php endif; ?>

    <?php if ($photos || in_array($st, ['in_progress', 'completed'], true)): ?>
      <section class="panel" id="anh">
        <div class="panel-head">
          <h2 class="panel-title">Ảnh gửi khách <span class="text-sm font-normal text-ink-500">(<span id="admin-photo-count"><?= count($photos) ?></span>)</span></h2>
          <span class="text-xs text-ink-500">Gỡ ảnh chụp nhầm hoặc lộ thông tin nhà khách</span>
        </div>
        <div class="space-y-5 p-5">
          <?= \Core\View::partial('partials/photo_gallery', [
              'photos' => $photos, 'id' => 'admin-photos', 'cols' => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4', 'deleteBase' => '/admin/photos',
              'empty' => 'Nhân viên chưa gửi ảnh nào cho đơn này.',
          ]) ?>
          <?php if (in_array($st, ['in_progress', 'completed'], true)): ?>
            <details class="rounded-2xl border border-ink-100 p-4">
              <summary class="cursor-pointer text-sm font-semibold text-ink-800">Gửi ảnh bổ sung thay nhân viên</summary>
              <div class="mt-4 max-w-md">
                <?= \Core\View::partial('partials/photo_uploader', [
                    'action' => url('/admin/bookings/' . $id . '/photos'), 'listId' => 'admin-photos',
                    'counterId' => 'admin-photo-count', 'deleteBase' => '/admin/photos', 'photos' => $photos,
                ]) ?>
              </div>
            </details>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="panel">
      <div class="panel-head"><h2 class="panel-title">Nhật ký đơn</h2></div>
      <?php if (!$logs): ?>
        <p class="px-5 py-6 text-sm text-ink-500">Chưa có thao tác nào của quản trị viên hoặc nhân viên.</p>
      <?php else: ?>
        <ul class="divide-y divide-ink-100 text-sm">
          <?php foreach ($logs as $l): $d = $l['detail'] ? json_decode($l['detail'], true) : []; ?>
            <li class="px-5 py-3">
              <div class="flex flex-wrap justify-between gap-2">
                <span class="font-semibold"><?= e(action_label($l['action'])) ?></span>
                <span class="text-xs text-ink-500"><?= e(vn_datetime($l['created_at'])) ?></span>
              </div>
              <p class="text-ink-600"><?= e($l['user_name'] ?? 'Hệ thống') ?> (<?= e(role_label($l['user_role'] ?? '')) ?>)<?= \Core\View::partial('partials/log_detail', ['d' => $d]) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <div class="space-y-6">
    <!-- Xử lý trạng thái -->
    <section class="panel p-5">
      <h2 class="panel-title">Xử lý đơn</h2>
      <?php if ($st === 'pending'): ?>
        <form method="post" action="<?= url("/admin/bookings/$id/approve") ?>" class="mt-4">
          <?= csrf_field() ?><button class="btn btn-accent w-full py-2.5">Duyệt đơn</button>
        </form>
      <?php elseif ($st === 'confirmed'): ?>
        <p class="mt-2 text-sm text-ink-600">Nhân viên sẽ bấm bắt đầu khi đến nơi. Chỉ dùng nút dưới đây khi nhân viên gặp sự cố.</p>
        <?php if ($b['booking_date'] <= $today): ?>
          <form method="post" action="<?= url("/admin/bookings/$id/start") ?>" class="mt-3" data-confirm="Chuyển đơn sang Đang tiến hành thay nhân viên?">
            <?= csrf_field() ?><button class="btn btn-outline w-full">Chuyển sang Đang tiến hành</button>
          </form>
        <?php endif; ?>
      <?php elseif ($st === 'in_progress'): ?>
        <form method="post" action="<?= url("/admin/bookings/$id/health") ?>" class="mt-4 space-y-3">
          <?= csrf_field() ?>
          <div class="grid grid-cols-[100px_1fr] gap-3">
            <div><label class="label text-xs" for="weight">Cân nặng</label><input class="input" id="weight" name="weight" inputmode="decimal" placeholder="kg"></div>
            <div><label class="label text-xs" for="note">Ghi chú sức khỏe</label><input class="input" id="note" name="note" required></div>
          </div>
          <button class="btn btn-outline w-full">Ghi sổ sức khỏe</button>
        </form>
        <form method="post" action="<?= url("/admin/bookings/$id/complete") ?>" class="mt-3" data-confirm="Đánh dấu đơn đã hoàn thành?">
          <?= csrf_field() ?><button class="btn btn-primary w-full">Hoàn thành đơn</button>
        </form>
      <?php elseif ($st === 'completed'): ?>
        <p class="mt-2 text-sm text-ink-600">Đơn đã hoàn thành và được tính vào doanh thu.</p>
      <?php else: ?>
        <p class="mt-2 text-sm text-ink-600">Đơn đã hủy. Lý do: <?= e($b['cancel_reason']) ?></p>
      <?php endif; ?>

      <?php if (in_array($st, ['pending', 'confirmed'], true)): ?>
        <form method="post" action="<?= url("/admin/bookings/$id/cancel") ?>" class="mt-5 space-y-2 border-t border-ink-100 pt-4" data-confirm="<?= $st === 'pending' ? 'Từ chối' : 'Hủy' ?> đơn <?= booking_code($id) ?>?">
          <?= csrf_field() ?>
          <label class="label" for="reason"><?= $st === 'pending' ? 'Từ chối đơn' : 'Hủy đơn' ?> (bắt buộc nhập lý do)</label>
          <input class="input" id="reason" name="reason" required maxlength="255" placeholder="Ví dụ: Khách báo bận, nhân viên ốm...">
          <button class="btn btn-danger-outline w-full"><?= $st === 'pending' ? 'Từ chối đơn' : 'Hủy đơn' ?></button>
        </form>
      <?php endif; ?>
    </section>

    <?php if ($reassignable): ?>
      <!-- Đổi nhân viên -->
      <section class="panel p-5">
        <h2 class="panel-title">Đổi nhân viên</h2>
        <p class="mt-1 text-sm text-ink-600">Chỉ hiện nhân viên đang hoạt động và còn trống khung <?= e(hm($b['start_time'])) ?> ngày <?= e(vn_date($b['booking_date'])) ?>.</p>
        <?php if (!$freeStaff): ?>
          <p class="mt-3 rounded-lg bg-ink-50 px-3 py-2 text-sm text-ink-600">Không còn nhân viên nào rảnh ở khung này. Hãy dời lịch sang khung khác.</p>
        <?php else: ?>
          <form method="post" action="<?= url("/admin/bookings/$id/reassign") ?>" class="mt-3 flex gap-2">
            <?= csrf_field() ?>
            <label class="sr-only" for="staff_id">Nhân viên mới</label>
            <select class="input" id="staff_id" name="staff_id" required>
              <?php foreach ($freeStaff as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?> · <?= (int)$s['day_load'] ?> đơn trong ngày</option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-primary">Đổi</button>
          </form>
        <?php endif; ?>
      </section>

      <!-- Dời lịch -->
      <section class="panel p-5" x-data="reschedule(<?= e(json_encode([
          'url' => url('/admin/api/slots'), 'bookingId' => $id, 'date' => $b['booking_date'],
          'currentSlot' => (int)$b['time_slot_id'], 'currentStaff' => (int)$b['staff_id'],
      ])) ?>)" x-init="load()">
        <h2 class="panel-title">Dời lịch</h2>
        <p class="mt-1 text-sm text-ink-600">Giữ nhân viên cũ nếu họ còn rảnh ở khung mới, nếu không hệ thống chọn người ít việc nhất.</p>
        <form method="post" action="<?= url("/admin/bookings/$id/reschedule") ?>" class="mt-3 space-y-3">
          <?= csrf_field() ?>
          <input type="hidden" name="time_slot_id" :value="slotId">
          <div>
            <label class="label text-xs" for="booking_date">Ngày mới</label>
            <input class="input" id="booking_date" name="booking_date" type="date" min="<?= $today ?>" x-model="date" @change="slotId = null; load()">
          </div>
          <p x-show="loading" class="text-sm text-ink-500">Đang kiểm tra…</p>
          <div class="grid grid-cols-2 gap-2" x-show="!loading">
            <template x-for="s in slots" :key="s.id">
              <button type="button" class="rounded-lg border-2 px-3 py-2 text-left text-sm"
                      :class="!s.available ? 'cursor-not-allowed border-dashed border-ink-100 text-ink-400' : (slotId === s.id ? 'border-ink-900' : 'border-ink-100 hover:border-ink-300')"
                      :disabled="!s.available" @click="slotId = s.id; staffId = ''">
                <span class="block font-semibold" x-text="s.label"></span>
                <span class="block text-xs" x-text="s.available ? s.free_staff + ' người rảnh' : s.reason"></span>
              </button>
            </template>
          </div>
          <div x-show="slot">
            <label class="label text-xs" for="re_staff">Nhân viên</label>
            <select class="input" id="re_staff" name="staff_id" x-model="staffId">
              <option value="">Tự động (ưu tiên người cũ)</option>
              <template x-for="p in (slot ? slot.staff : [])" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
            </select>
          </div>
          <button class="btn btn-primary w-full" :disabled="!slotId">Dời lịch</button>
        </form>
      </section>
    <?php endif; ?>
  </div>
</div>

<script>
function reschedule(cfg) {
  return {
    date: cfg.date, slots: [], slotId: null, staffId: '', loading: false,
    get slot() { return this.slots.find(s => s.id === this.slotId); },
    async load() {
      if (!this.date) return;
      this.loading = true;
      try {
        const data = await PetCare.getJson(cfg.url + '?booking_id=' + cfg.bookingId + '&date=' + this.date);
        this.slots = data.slots.map(s => (this.date === cfg.date && s.id === cfg.currentSlot)
          ? Object.assign({}, s, { available: false, reason: 'Khung hiện tại' }) : s);
      } catch (e) { this.slots = []; }
      this.loading = false;
    },
  };
}
</script>
