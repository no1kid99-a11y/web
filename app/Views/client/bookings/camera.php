<?php
/**
 * Trang "Theo dõi trực tiếp" của khách: ảnh nhân viên chụp theo từng mốc, tự cập nhật (public/assets/js/photos.js).
 * @var array $b  @var array $photos  @var array $records  @var int $poll
 */
use App\Models\BookingPhoto;

$services = $b['combo_name'] ?: implode(', ', array_column($b['services'], 'name'));
$feed = [
    'url' => url('/bookings/' . $b['id'] . '/photos'),
    'poll' => $poll,
    'status' => $b['status'],
    'has_record' => (bool)$records,
    'photos' => $photos,
];
?>
<a href="<?= url('/bookings/' . $b['id']) ?>" class="text-sm text-ink-500 hover:text-ink-900">← Lịch hẹn <?= booking_code($b['id']) ?></a>

<div data-live-feed>
  <script type="application/json"><?= json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

  <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
    <div class="flex items-center gap-4">
      <?= \Core\View::partial('partials/pet_avatar', ['photo' => $b['pet_photo'], 'species' => $b['pet_species'], 'size' => 'h-16 w-16 text-3xl']) ?>
      <div>
        <p class="eyebrow">Theo dõi trực tiếp</p>
        <h1 class="page-title mt-1"><?= e($b['pet_name']) ?> hôm nay</h1>
        <p class="mt-1 text-sm text-ink-500"><?= e(hm($b['start_time'])) ?>–<?= e(hm($b['end_time'])) ?> · <?= e($services) ?></p>
      </div>
    </div>
    <div class="flex flex-col items-start gap-1.5 sm:items-end">
      <span data-status-pill class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold ring-1"><?= e(status_label($b['status'])) ?></span>
      <p class="text-xs text-ink-500" data-live-note>Tự cập nhật mỗi <?= (int)$poll ?> giây · lần cuối <span data-updated class="tabular-nums"><?= date('H:i:s') ?></span></p>
    </div>
  </div>

  <p data-offline hidden class="mt-4 rounded-xl bg-amber-50 px-4 py-2.5 text-sm text-amber-800 ring-1 ring-amber-200" role="status"></p>

  <div data-done hidden class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-forest-50 px-5 py-4 ring-1 ring-forest-100">
    <div>
      <p class="font-display text-lg font-bold text-forest-800"><?= e($b['pet_name']) ?> đã xong buổi chăm sóc</p>
      <p class="text-sm text-forest-700">Ảnh được lưu trong lịch hẹn và album của bé trong sổ sức khỏe.</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a data-record-link hidden href="<?= url('/bookings/' . $b['id']) ?>" class="btn btn-primary">Xem ghi chú sức khỏe và đánh giá</a>
      <a href="<?= url('/pets/' . $b['pet_id']) ?>#album" class="btn btn-outline">Album của <?= e($b['pet_name']) ?></a>
    </div>
  </div>

  <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
    <div class="space-y-6">
      <div data-hero></div>

      <section class="panel">
        <div class="panel-head">
          <h2 class="panel-title">Dòng thời gian</h2>
          <span class="text-xs text-ink-500"><span data-count><?= count($photos) ?></span> ảnh · mới nhất ở trên</span>
        </div>
        <ul data-timeline data-photo-list class="px-4 py-5 sm:px-5" aria-live="polite"></ul>
      </section>
    </div>

    <aside class="space-y-6">
      <section class="panel p-5">
        <h2 class="panel-title">Các mốc chăm sóc</h2>
        <ol class="mt-4 space-y-3 text-sm">
          <?php foreach (BookingPhoto::STAGES as $k => $label): if ($k === 'other') continue; ?>
            <li data-stage="<?= e($k) ?>" class="flex items-center gap-3">
              <span data-stage-dot class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-ink-100"></span>
              <span class="flex-1 font-medium"><?= e($label) ?></span>
              <span data-stage-time class="text-xs tabular-nums text-ink-500"></span>
            </li>
          <?php endforeach; ?>
        </ol>
        <p class="mt-4 text-xs leading-relaxed text-ink-500">Tùy dịch vụ bạn đặt, nhân viên có thể bỏ qua vài mốc.</p>
      </section>

      <section class="panel p-5 text-sm">
        <h2 class="panel-title">Nhân viên phụ trách</h2>
        <p class="mt-3 font-semibold text-ink-900"><?= e($b['staff_name']) ?></p>
        <?php if ($b['status'] !== 'completed'): ?>
          <a class="link mt-1 inline-block" href="tel:<?= e($b['staff_phone']) ?>"><?= e($b['staff_phone']) ?></a>
        <?php endif; ?>
        <p class="mt-3 text-xs leading-relaxed text-ink-500">Ảnh chỉ bạn, nhân viên phụ trách và quản trị viên xem được. Ảnh lưu trên máy chủ PetCare, không đăng công khai.</p>
      </section>
    </aside>
  </div>

  <div data-toast hidden class="fixed bottom-24 left-1/2 z-50 -translate-x-1/2 rounded-full bg-ink-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg lg:bottom-8" role="status"></div>
  <noscript><p class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Trang này cần JavaScript để tự cập nhật ảnh. Bạn vẫn xem được ảnh trong trang lịch hẹn.</p></noscript>
</div>
