<?php $u = \Core\Auth::user(); ?>
<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <p class="text-sm text-ink-500"><?= e(weekday_vn(date('Y-m-d'))) ?>, <?= e(vn_date(date('Y-m-d'))) ?></p>
    <h1 class="page-title">Chào <?= e(mb_substr($u['name'], mb_strrpos($u['name'], ' ') + 1)) ?>, hôm nay có <?= count($today) ?> nhà</h1>
  </div>
  <dl class="flex gap-6 text-sm">
    <div><dt class="text-ink-500">30 ngày tới</dt><dd class="font-display text-2xl font-bold"><?= (int)$upcomingCount ?> đơn</dd></div>
    <div><dt class="text-ink-500">Hoàn thành tháng này</dt><dd class="font-display text-2xl font-bold"><?= (int)$monthDone ?></dd></div>
  </dl>
</div>

<section class="mt-8">
  <h2 class="font-display text-xl font-semibold">Hôm nay</h2>
  <?php if (!$today): ?>
    <p class="mt-3 rounded-xl border border-dashed border-ink-200 bg-white px-5 py-8 text-center text-sm text-ink-500">Hôm nay bạn không có lịch nào.</p>
  <?php else: ?>
    <ol class="mt-3 space-y-3">
      <?php foreach ($today as $b): $done = in_array($b['status'], ['completed', 'cancelled'], true); ?>
        <li class="flex flex-col gap-4 rounded-2xl border bg-white p-5 sm:flex-row sm:items-center <?= $b['status'] === 'in_progress' ? 'border-tag-400 ring-2 ring-tag-100' : 'border-ink-100' ?> <?= $done ? 'opacity-60' : '' ?>">
          <div class="w-28 shrink-0">
            <p class="font-display text-2xl font-bold tabular-nums"><?= e(hm($b['start_time'])) ?></p>
            <p class="text-xs text-ink-500">đến <?= e(hm($b['end_time'])) ?></p>
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-semibold"><?= species_icon($b['pet_species']) ?> <?= e($b['pet_name']) ?> · <?= e($b['combo_name'] ?? 'Dịch vụ lẻ') ?></p>
            <p class="mt-0.5 text-sm text-ink-600">
              <a class="hover:underline" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($b['address']) ?>"><?= e($b['address']) ?></a>
            </p>
            <p class="text-sm text-ink-500"><?= e($b['client_name']) ?> · <a class="hover:underline" href="tel:<?= e($b['client_phone']) ?>"><?= e($b['client_phone']) ?></a></p>
          </div>
          <div class="flex items-center gap-3">
            <?= status_badge($b['status']) ?>
            <a href="<?= url('/staff/bookings/' . $b['id']) ?>" class="btn <?= in_array($b['status'], ['confirmed', 'in_progress'], true) ? 'btn-primary' : 'btn-outline' ?> btn-sm">
              <?= ['confirmed' => 'Mở để bắt đầu', 'in_progress' => 'Ghi sổ và hoàn thành'][$b['status']] ?? 'Xem đơn' ?>
            </a>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</section>

<section class="mt-10">
  <h2 class="font-display text-xl font-semibold">Sắp tới</h2>
  <?php if (!$grouped): ?>
    <p class="mt-3 text-sm text-ink-500">Chưa có đơn nào được giao trong 30 ngày tới.</p>
  <?php else: ?>
    <div class="mt-3 space-y-5">
      <?php foreach ($grouped as $date => $list): ?>
        <div class="panel overflow-hidden">
          <div class="border-b border-ink-100 bg-ink-50/60 px-5 py-2.5 text-sm font-semibold"><?= e(weekday_vn($date)) ?>, <?= e(vn_date($date)) ?> <span class="font-normal text-ink-500">· <?= count($list) ?> đơn</span></div>
          <ul class="divide-y divide-ink-100">
            <?php foreach ($list as $b): ?>
              <li>
                <a href="<?= url('/staff/bookings/' . $b['id']) ?>" class="flex flex-wrap items-center gap-4 px-5 py-3 hover:bg-ink-50/60">
                  <span class="w-24 font-semibold tabular-nums"><?= e(hm($b['start_time'])) ?>–<?= e(hm($b['end_time'])) ?></span>
                  <span class="min-w-0 flex-1"><?= species_icon($b['pet_species']) ?> <?= e($b['pet_name']) ?> · <span class="text-ink-500"><?= e($b['address']) ?></span></span>
                  <?= status_badge($b['status']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="mt-3 text-xs text-ink-500">Đơn Chờ duyệt có thể còn thay đổi. Chỉ đơn Đã xác nhận mới chắc chắn diễn ra.</p>
  <?php endif; ?>
</section>

<?php if ($recent): ?>
<section class="mt-10">
  <h2 class="font-display text-xl font-semibold">14 ngày qua</h2>
  <div class="panel mt-3 overflow-x-auto">
    <table class="tbl">
      <thead><tr><th>Ngày</th><th>Thú cưng</th><th>Khách</th><th>Trạng thái</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($recent as $b): ?>
          <tr>
            <td class="tabular-nums"><?= e(vn_date($b['booking_date'])) ?> <?= e(hm($b['start_time'])) ?></td>
            <td><?= e($b['pet_name']) ?></td>
            <td><?= e($b['client_name']) ?></td>
            <td><?= status_badge($b['status']) ?></td>
            <td class="text-right"><a class="link text-xs" href="<?= url('/staff/bookings/' . $b['id']) ?>">Xem</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>
