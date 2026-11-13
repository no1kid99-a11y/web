<?php
$months = array_keys($revenue);
$labels = array_map(fn($ym) => 'Th' . (int)substr($ym, 5, 2) . '/' . substr($ym, 2, 2), $months);
$values = array_map(fn($r) => $r['revenue'], array_values($revenue));
$delta = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : null;
$statusTotal = array_sum($statusCounts) ?: 1;
?>
<?php ob_start(); ?>
  <a href="<?= url('/admin/bookings', ['status' => 'pending']) ?>" class="btn btn-outline bg-white py-2.5">Duyệt đơn chờ (<?= (int)$pendingCount ?>)</a>
  <a href="<?= url('/admin/schedule') ?>" class="btn btn-primary py-2.5">Lịch 7 ngày nhân viên</a>
<?php $actions = ob_get_clean(); ?>
<?= \Core\View::partial('partials/page_header', [
    'title' => 'Tổng quan & doanh thu',
    'badge' => weekday_vn(date('Y-m-d')) . ', ' . vn_date(date('Y-m-d')),
    'subtitle' => 'Doanh thu tính theo giá chốt của các đơn đã hoàn thành. Bấm vào từng thẻ để xem chi tiết.',
    'icon' => 'M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v4H4zM14 15h6v4h-6z',
    'actions' => $actions,
]) ?>

<!-- Chỉ số -->
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <div class="stat-card">
    <div class="min-w-0">
      <p class="stat-label">Doanh thu tháng <?= (int)date('m') ?></p>
      <p class="stat-value text-3xl text-tag-600"><?= money($thisMonth) ?></p>
      <p class="mt-2 text-xs text-ink-500">
        <?php if ($delta !== null): ?><span class="font-semibold <?= $delta >= 0 ? 'text-mint-600' : 'text-red-600' ?>"><?= $delta >= 0 ? '+' : '' ?><?= number_format($delta, 0, ',', '.') ?>%</span> so với tháng trước<?php else: ?>Chỉ tính đơn hoàn thành<?php endif; ?>
      </p>
    </div>
    <span class="stat-icon bg-tag-50 text-tag-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 8c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2m0-8c1.1 0 2.1.4 2.6 1M12 8V7m0 1v8m0 0v1m0-1c-1.1 0-2.1-.4-2.6-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
  </div>
  <a href="<?= url('/admin/bookings', ['status' => 'pending']) ?>" class="stat-card hover:border-tag-300">
    <div>
      <p class="stat-label">Chờ duyệt</p>
      <p class="stat-value text-tag-600"><?= sprintf('%02d', (int)$pendingCount) ?> <span class="text-sm font-normal text-ink-400">đơn</span></p>
      <p class="mt-2 text-xs text-ink-500"><?= (int)$newToday ?> đơn mới hôm nay</p>
    </div>
    <span class="stat-icon bg-red-50 text-red-500"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7v3m0 2h.01"/></svg></span>
  </a>
  <a href="<?= url('/admin/bookings', ['range' => 'today']) ?>" class="stat-card hover:border-mint-300">
    <div>
      <p class="stat-label">Lịch hôm nay</p>
      <p class="stat-value text-mint-600"><?= sprintf('%02d', count(array_filter($todayBookings, fn($b) => $b['status'] !== 'cancelled'))) ?> <span class="text-sm font-normal text-ink-400">ca</span></p>
      <p class="mt-2 text-xs text-ink-500"><?= (int)($roleCounts['staff'] ?? 0) ?> nhân viên đang hoạt động</p>
    </div>
    <span class="stat-icon bg-mint-100 text-mint-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 9l2 2 4-4"/></svg></span>
  </a>
  <a href="<?= url('/admin/users', ['role' => 'client']) ?>" class="stat-card hover:border-ink-300">
    <div>
      <p class="stat-label">Thú cưng</p>
      <p class="stat-value text-ink-900"><?= (int)$petCount ?> <span class="text-sm font-normal text-ink-400">bé</span></p>
      <p class="mt-2 text-xs text-ink-500"><?= (int)($roleCounts['client'] ?? 0) ?> khách hàng · <?= $reviewStats['avg'] ? number_format((float)$reviewStats['avg'], 1, ',', '') . '/5 sao' : 'chưa có đánh giá' ?></p>
    </div>
    <span class="stat-icon bg-ink-100 text-ink-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 21c-3.5 0-6-2-6-4.5S9 12 12 12s6 2 6 4.5-2.5 4.5-6 4.5zM5 10a2 2 0 100-4 2 2 0 000 4zm14 0a2 2 0 100-4 2 2 0 000 4zM9 6a2 2 0 100-4 2 2 0 000 4zm6 0a2 2 0 100-4 2 2 0 000 4z"/></svg></span>
  </a>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title">Doanh thu 6 tháng</h2>
      <span class="text-xs text-ink-500">Đơn hoàn thành, theo giá chốt</span>
    </div>
    <div class="h-72 p-5"><canvas id="revenueChart" role="img" aria-label="Biểu đồ doanh thu 6 tháng gần nhất"></canvas></div>
    <details class="border-t border-ink-100 px-5 py-3 text-sm">
      <summary class="cursor-pointer text-ink-600">Xem dạng bảng</summary>
      <table class="tbl mt-2">
        <thead><tr><th>Tháng</th><th class="text-right">Số đơn</th><th class="text-right">Doanh thu</th></tr></thead>
        <tbody>
          <?php foreach ($revenue as $ym => $r): ?>
            <tr><td><?= (int)substr($ym, 5, 2) ?>/<?= substr($ym, 0, 4) ?></td><td class="text-right tabular-nums"><?= (int)$r['n'] ?></td><td class="text-right tabular-nums"><?= money($r['revenue']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </details>
  </section>

  <section class="panel">
    <div class="panel-head"><h2 class="panel-title">Đơn theo trạng thái</h2><span class="text-xs text-ink-500">Toàn thời gian</span></div>
    <ul class="space-y-4 p-5">
      <?php foreach (\App\Models\Booking::STATUSES as $s): $n = (int)($statusCounts[$s] ?? 0); ?>
        <li>
          <div class="flex items-center justify-between text-sm">
            <a href="<?= url('/admin/bookings', ['status' => $s, 'sort' => 'date_desc']) ?>" class="hover:underline"><?= e(status_label($s)) ?></a>
            <span class="font-semibold tabular-nums"><?= $n ?></span>
          </div>
          <div class="mt-1.5 h-2 rounded-full bg-ink-100"><div class="h-2 rounded-full bg-tag-400" style="width: <?= round($n / $statusTotal * 100, 1) ?>%"></div></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
  <section class="panel overflow-hidden">
    <div class="panel-head">
      <h2 class="panel-title">Chờ duyệt</h2>
      <a class="link text-xs" href="<?= url('/admin/bookings', ['status' => 'pending']) ?>">Xem tất cả</a>
    </div>
    <?php if (!$pending): ?>
      <p class="px-5 py-6 text-sm text-ink-500">Không có đơn nào đang chờ.</p>
    <?php else: ?>
      <ul class="divide-y divide-ink-100">
        <?php foreach ($pending as $b): ?>
          <li class="flex flex-wrap items-center gap-3 px-5 py-3">
            <a href="<?= url('/admin/bookings/' . $b['id']) ?>" class="min-w-0 flex-1">
              <p class="text-sm font-semibold"><?= booking_code($b['id']) ?> · <?= e($b['pet_name']) ?> · <?= e($b['client_name']) ?></p>
              <p class="text-xs text-ink-500"><?= e(weekday_vn($b['booking_date'])) ?> <?= e(vn_date($b['booking_date'])) ?>, <?= e(hm($b['start_time'])) ?> · NV <?= e($b['staff_name']) ?> · <?= money($b['total_price']) ?></p>
            </a>
            <form method="post" action="<?= url('/admin/bookings/' . $b['id'] . '/approve') ?>">
              <?= csrf_field() ?><input type="hidden" name="return" value="/admin">
              <button class="btn btn-sm bg-mint-500 text-white hover:bg-mint-600">Duyệt</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="panel overflow-hidden">
    <div class="panel-head"><h2 class="panel-title">Hôm nay</h2><span class="text-xs text-ink-500"><?= count($todayBookings) ?> đơn</span></div>
    <?php if (!$todayBookings): ?>
      <p class="px-5 py-6 text-sm text-ink-500">Hôm nay không có lịch hẹn.</p>
    <?php else: ?>
      <ul class="divide-y divide-ink-100">
        <?php foreach ($todayBookings as $b): ?>
          <li>
            <a href="<?= url('/admin/bookings/' . $b['id']) ?>" class="flex items-center gap-3 px-5 py-3 hover:bg-ink-50/60">
              <span class="w-12 font-semibold tabular-nums"><?= e(hm($b['start_time'])) ?></span>
              <span class="min-w-0 flex-1 truncate text-sm"><?= e($b['pet_name']) ?> · <?= e($b['client_name']) ?> · <span class="text-ink-500">NV <?= e($b['staff_name']) ?></span></span>
              <?= status_badge($b['status']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<div class="mt-6">
  <section class="panel overflow-hidden">
    <div class="panel-head"><h2 class="panel-title">Nhân viên tháng <?= (int)date('m') ?></h2></div>
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead><tr><th>Nhân viên</th><th class="text-right">Hoàn thành</th><th class="text-right">Đang giữ lịch</th><th class="text-right">Điểm đánh giá</th></tr></thead>
        <tbody>
          <?php foreach ($perStaff as $s): ?>
            <tr>
              <td><a class="hover:underline" href="<?= url('/admin/users/' . $s['id']) ?>"><?= e($s['name']) ?></a></td>
              <td class="text-right tabular-nums"><?= (int)$s['completed'] ?></td>
              <td class="text-right tabular-nums"><?= (int)$s['holding'] ?></td>
              <td class="text-right tabular-nums"><?= $s['avg_rating'] ? number_format((float)$s['avg_rating'], 1, ',', '') . ' ★' : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  const el = document.getElementById('revenueChart');
  if (!el || !window.Chart) return;
  const values = <?= json_encode($values) ?>;
  const last = values.length - 1;
  new Chart(el, {
    type: 'bar',
    data: {
      labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
      datasets: [{
        label: 'Doanh thu',
        data: values,
        backgroundColor: values.map((_, i) => i === last ? '#F7A42C' : '#FCD49A'),
        borderRadius: { topLeft: 4, topRight: 4 },
        borderSkipped: 'bottom',
        maxBarThickness: 44,
      }],
    },
    options: {
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (c) => PetCare.money(c.parsed.y) } },
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#7D7062' } },
        y: {
          beginAtZero: true, border: { display: false },
          grid: { color: '#F3EFE9' },
          ticks: { color: '#A39684', callback: (v) => v >= 1e6 ? (v / 1e6).toLocaleString('vi-VN') + ' tr' : (v / 1e3).toLocaleString('vi-VN') + 'k' },
        },
      },
    },
  });
})();
</script>
