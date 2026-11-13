<?php /** @var array $b */ ?>
<a href="<?= url('/bookings/' . $b['id']) ?>" class="flex flex-wrap items-center gap-4 px-5 py-4 hover:bg-ink-50/70 focus:outline-none focus-visible:bg-ink-50">
  <div class="w-16 shrink-0 text-center">
    <p class="text-xs text-ink-500"><?= e(weekday_vn($b['booking_date'])) ?></p>
    <p class="font-display text-2xl font-bold leading-none"><?= date('d', strtotime($b['booking_date'])) ?></p>
    <p class="text-xs text-ink-500">th<?= (int)date('m', strtotime($b['booking_date'])) ?></p>
  </div>
  <?= \Core\View::partial('partials/pet_avatar', ['photo' => $b['pet_photo'], 'species' => $b['pet_species'], 'size' => 'h-11 w-11 text-xl']) ?>
  <div class="min-w-0 flex-1">
    <p class="font-semibold"><?= e($b['pet_name']) ?> · <?= e($b['combo_name'] ?? 'Dịch vụ lẻ') ?></p>
    <p class="text-sm text-ink-500"><?= e(hm($b['start_time'])) ?>–<?= e(hm($b['end_time'])) ?> · Nhân viên <?= e($b['staff_name']) ?> · <?= booking_code($b['id']) ?></p>
  </div>
  <div class="flex items-center gap-4">
    <span class="font-semibold tabular-nums"><?= money($b['total_price']) ?></span>
    <?= status_badge($b['status']) ?>
  </div>
</a>
