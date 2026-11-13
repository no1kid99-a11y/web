<?php
use Core\Session;

$messages = Session::pull('_flash', []);
$affected = Session::pull('_affected_bookings', []);
$styles = [
    'success' => ['border-emerald-200 bg-emerald-50 text-emerald-900', 'M5 13l4 4L19 7'],
    'error'   => ['border-red-200 bg-red-50 text-red-900', 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z'],
    'warning' => ['border-tag-300 bg-tag-50 text-ink-900', 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z'],
    'info'    => ['border-ink-200 bg-ink-50 text-ink-900', 'M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 110-18 9 9 0 010 18z'],
];
$isAdmin = \Core\Auth::is('admin');
?>
<?php if ($messages || $affected): ?>
<div class="space-y-3" role="status">
  <?php foreach ($messages as $m): [$cls, $icon] = $styles[$m['type']] ?? $styles['info']; ?>
    <div class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm <?= $cls ?>" x-data="{show:true}" x-show="show">
      <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/></svg>
      <div class="flex-1 leading-relaxed"><?= $m['message'] /* đã escape khi tạo */ ?></div>
      <button type="button" class="opacity-60 hover:opacity-100" @click="show=false" aria-label="Đóng">✕</button>
    </div>
  <?php endforeach; ?>

  <?php if ($affected): ?>
    <div class="rounded-lg border border-tag-300 bg-white">
      <div class="border-b border-tag-200 bg-tag-50 px-4 py-2.5 text-sm font-semibold text-ink-900">Các đơn cần xử lý trước</div>
      <ul class="divide-y divide-ink-100 text-sm">
        <?php foreach ($affected as $b): ?>
          <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
            <span>
              <span class="font-semibold"><?= booking_code($b['id']) ?></span>
              <?= e(vn_date($b['date'])) ?>, <?= e($b['slot']) ?> · <?= e($b['pet']) ?> (<?= e($b['client']) ?>) · NV: <?= e($b['staff']) ?>
            </span>
            <?php if ($isAdmin): ?>
              <a class="link text-xs" href="<?= url('/admin/bookings/' . (int)$b['id']) ?>">Mở đơn để chuyển, dời hoặc hủy</a>
            <?php else: ?>
              <?= status_badge($b['status']) ?>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
