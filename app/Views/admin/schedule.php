<?php
$today = date('Y-m-d');
$capacity = count($slots);
$statusDot = ['pending' => 'bg-amber-400', 'confirmed' => 'bg-sky-500', 'in_progress' => 'bg-violet-500', 'completed' => 'bg-emerald-500'];
?>
<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="page-title">Lịch tuần nhân viên</h1>
    <p class="mt-1 text-sm text-ink-600">7 ngày từ <?= e(weekday_vn($days[0])) ?> <?= e(vn_date($days[0])) ?> đến <?= e(weekday_vn($days[6])) ?> <?= e(vn_date($days[6])) ?>. Mỗi nhân viên nhận tối đa <?= $capacity ?> đơn mỗi ngày (một đơn mỗi khung).</p>
  </div>
  <div class="flex gap-2">
    <a href="<?= url('/admin/schedule', ['week' => $prev]) ?>" class="btn btn-outline">7 ngày trước</a>
    <a href="<?= url('/admin/schedule') ?>" class="btn btn-ghost">Hôm nay</a>
    <a href="<?= url('/admin/schedule', ['week' => $next]) ?>" class="btn btn-outline">7 ngày sau</a>
  </div>
</div>

<div class="mt-4 flex flex-wrap gap-4 text-xs text-ink-600">
  <?php foreach ($statusDot as $s => $cls): ?><span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full <?= $cls ?>"></span><?= e(status_label($s)) ?></span><?php endforeach; ?>
</div>

<div class="panel mt-4 overflow-x-auto">
  <table class="w-full min-w-[960px] table-fixed border-collapse text-sm">
    <thead>
      <tr>
        <th class="w-44 border-b border-ink-100 bg-ink-50/60 px-3 py-2.5 text-left text-xs font-semibold text-ink-500">Nhân viên</th>
        <?php foreach ($days as $d): ?>
          <th class="border-b border-l border-ink-100 px-2 py-2.5 text-left <?= $d === $today ? 'bg-tag-50' : 'bg-ink-50/60' ?>">
            <span class="block text-xs font-medium text-ink-500"><?= e(weekday_vn($d)) ?></span>
            <span class="font-display text-base font-semibold <?= $d === $today ? 'text-ink-900' : 'text-ink-700' ?>"><?= date('d/m', strtotime($d)) ?></span>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($staff as $s): $sid = (int)$s['id']; ?>
        <tr>
          <td class="border-b border-ink-100 px-3 py-2 align-top">
            <a class="font-semibold hover:underline" href="<?= url('/admin/bookings', ['staff_id' => $sid, 'from' => $days[0], 'to' => $days[6]]) ?>"><?= e($s['name']) ?></a>
            <p class="text-xs text-ink-500"><?= e($s['phone']) ?></p>
          </td>
          <?php foreach ($days as $d):
              $items = $cells[$sid][$d] ?? [];
          ?>
            <td class="h-24 border-b border-l border-ink-100 p-1.5 align-top">
              <?php foreach ($items as $b): ?>
                <a href="<?= url('/admin/bookings/' . $b['id']) ?>" class="mb-1 flex items-center gap-1.5 rounded-md bg-white px-1.5 py-1 text-[11px] leading-tight ring-1 ring-ink-100 hover:ring-ink-400" title="<?= booking_code($b['id']) ?> · <?= e($b['client_name']) ?> · <?= e(status_label($b['status'])) ?>">
                  <span class="h-2 w-2 shrink-0 rounded-full <?= $statusDot[$b['status']] ?? 'bg-ink-300' ?>"></span>
                  <span class="font-semibold tabular-nums"><?= e(hm($b['start_time'])) ?></span>
                  <span class="truncate"><?= e($b['pet_name']) ?></span>
                </a>
              <?php endforeach; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      <tr>
        <td class="px-3 py-2.5 text-xs font-semibold text-ink-500">Lượt còn nhận</td>
        <?php foreach ($days as $d): ?>
          <td class="border-l border-ink-100 px-2 py-2.5 text-xs">
            <span class="font-semibold tabular-nums <?= $free[$d] === 0 ? 'text-red-700' : 'text-ink-900' ?>"><?= (int)$free[$d] ?></span>
            <span class="text-ink-500">/ <?= count($staff) * $capacity ?></span>
          </td>
        <?php endforeach; ?>
      </tr>
    </tbody>
  </table>
</div>
<p class="mt-2 text-xs text-ink-500">"Lượt còn nhận" là tổng số cặp nhân viên × khung giờ còn trống trong ngày (đã trừ khung đã có đơn và giờ đã qua).</p>
