<?php
/** @var array $b  @var bool $showClient */
$showClient = $showClient ?? false;
$retail = array_sum(array_column($b['services'], 'price'));
?>
<dl class="grid gap-x-6 gap-y-4 px-5 py-5 text-sm sm:grid-cols-2">
  <div>
    <dt class="text-ink-500">Thời gian</dt>
    <dd class="mt-0.5 font-semibold"><?= e(weekday_vn($b['booking_date'])) ?>, <?= e(vn_date($b['booking_date'])) ?> · <?= e(hm($b['start_time'])) ?>–<?= e(hm($b['end_time'])) ?></dd>
  </div>
  <div>
    <dt class="text-ink-500">Thú cưng</dt>
    <dd class="mt-0.5 font-semibold"><?= species_icon($b['pet_species']) ?> <?= e($b['pet_name']) ?><?= $b['pet_breed'] ? ' · ' . e($b['pet_breed']) : '' ?> · <?= e(kg($b['pet_weight'])) ?></dd>
  </div>
  <div class="sm:col-span-2">
    <dt class="text-ink-500">Địa chỉ</dt>
    <dd class="mt-0.5 font-semibold"><?= e($b['address']) ?></dd>
  </div>
  <?php if ($showClient): ?>
    <div>
      <dt class="text-ink-500">Khách hàng</dt>
      <dd class="mt-0.5 font-semibold"><?= e($b['client_name']) ?> · <a class="link" href="tel:<?= e($b['client_phone']) ?>"><?= e($b['client_phone']) ?></a></dd>
    </div>
  <?php endif; ?>
  <div>
    <dt class="text-ink-500">Nhân viên phụ trách</dt>
    <dd class="mt-0.5 font-semibold"><?= e($b['staff_name']) ?><?php if (in_array($b['status'], ['confirmed', 'in_progress'], true) || $showClient): ?> · <a class="link" href="tel:<?= e($b['staff_phone']) ?>"><?= e($b['staff_phone']) ?></a><?php endif; ?></dd>
  </div>
  <?php if ($b['note']): ?>
    <div class="sm:col-span-2">
      <dt class="text-ink-500">Ghi chú của khách</dt>
      <dd class="mt-0.5 rounded-lg bg-tag-50 px-3 py-2"><?= nl2br(e($b['note'])) ?></dd>
    </div>
  <?php endif; ?>
</dl>
<div class="border-t border-ink-100">
  <table class="tbl">
    <thead><tr><th><?= $b['combo_name'] ? e($b['combo_name']) : 'Dịch vụ lẻ' ?></th><th class="text-right">Thời gian</th><th class="text-right">Giá lẻ</th></tr></thead>
    <tbody>
      <?php foreach ($b['services'] as $s): ?>
        <tr><td><?= e($s['name']) ?></td><td class="text-right tabular-nums"><?= (int)$s['duration_minutes'] ?> phút</td><td class="text-right tabular-nums"><?= money($s['price']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="space-y-1 border-t border-ink-100 px-5 py-4 text-sm">
    <?php if ((int)$b['discount_amount'] > 0): ?>
      <div class="flex justify-between text-ink-600"><span>Tổng giá lẻ</span><span class="tabular-nums"><?= money($retail) ?></span></div>
      <div class="flex justify-between text-ink-600"><span>Giảm giá combo</span><span class="tabular-nums">−<?= money($b['discount_amount']) ?></span></div>
    <?php endif; ?>
    <div class="flex items-baseline justify-between"><span class="font-semibold">Thanh toán tại nhà</span><span class="font-display text-2xl font-bold tabular-nums"><?= money($b['total_price']) ?></span></div>
  </div>
</div>
