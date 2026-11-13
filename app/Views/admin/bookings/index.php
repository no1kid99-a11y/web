<?php
use App\Controllers\Admin\BookingController as BC;

$base = array_filter(['range' => $range !== 'upcoming' ? $range : null, 'date' => $range === 'date' ? $date : null,
    'staff_id' => $filters['staff_id'] ?? null, 'q' => $filters['q'] ?? null]);
$link = fn(array $over) => url('/admin/bookings', array_merge($base, ['status' => $status], $over));
$return = current_path() . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
$today = date('Y-m-d');
$tabCount = [
    'pending' => 'bg-red-500 text-white', 'confirmed' => 'bg-ink-800 text-white', 'in_progress' => 'bg-mint-100 text-mint-700',
    'completed' => 'bg-ink-100 text-ink-600', 'cancelled' => 'bg-ink-100 text-ink-600',
];
$svgIcon = fn(string $d, string $cls = 'h-5 w-5') => '<svg class="' . $cls . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="' . $d . '"/></svg>';

ob_start(); ?>
  <a href="<?= url('/admin/bookings/export', array_merge($base, ['status' => $status])) ?>" class="btn btn-outline bg-white py-2.5">
    <?= $svgIcon('M12 4v11m0 0l-4-4m4 4l4-4M5 20h14', 'h-4 w-4') ?> Xuất danh sách (CSV)
  </a>
  <a href="<?= url('/admin/schedule') ?>" class="btn btn-primary py-2.5">
    <?= $svgIcon('M4 5h16M4 12h16M4 19h16M9 5v14M15 5v14', 'h-4 w-4') ?> Lịch 7 ngày nhân viên
  </a>
<?php $actions = ob_get_clean(); ?>

<?= \Core\View::partial('partials/page_header', [
    'title' => 'Điều phối & phê duyệt lịch chăm sóc tại nhà',
    'badge' => 'Chống trùng lịch',
    'subtitle' => 'Duyệt đơn, phân công nhân viên, dời lịch và theo dõi tiến độ từng ca tại nhà khách.',
    'actions' => $actions,
]) ?>

<!-- Thẻ chỉ số -->
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <a href="<?= url('/admin/bookings', ['status' => 'pending']) ?>" class="stat-card hover:border-tag-300">
    <div>
      <p class="stat-label">Chờ duyệt</p>
      <p class="stat-value text-tag-600"><?= sprintf('%02d', $stats['pending']) ?> <span class="text-sm font-normal text-ink-400">đơn</span></p>
      <p class="mt-2 text-xs text-ink-500"><?= $stats['pending_soon'] ?> đơn hẹn trong 24 giờ tới</p>
    </div>
    <span class="stat-icon bg-red-50 text-red-500"><?= $svgIcon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7v3m0 2h.01') ?></span>
  </a>
  <a href="<?= url('/admin/bookings', ['status' => 'in_progress', 'range' => 'all']) ?>" class="stat-card hover:border-mint-300">
    <div>
      <p class="stat-label">Đang thực hiện</p>
      <p class="stat-value text-mint-600"><?= sprintf('%02d', $stats['running']) ?> <span class="text-sm font-normal text-ink-400">ca</span></p>
      <p class="mt-2 text-xs text-ink-500">Khách đang nhận ảnh trực tiếp</p>
    </div>
    <span class="stat-icon bg-mint-100 text-mint-600"><?= $svgIcon('M15 10l4.6-2.3A1 1 0 0121 8.6v6.8a1 1 0 01-1.4.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z') ?></span>
  </a>
  <a href="<?= url('/admin/bookings', ['status' => 'confirmed']) ?>" class="stat-card hover:border-ink-300">
    <div>
      <p class="stat-label">Đã xác nhận</p>
      <p class="stat-value text-ink-900"><?= sprintf('%02d', $stats['confirmed']) ?> <span class="text-sm font-normal text-ink-400">lịch</span></p>
      <p class="mt-2 text-xs text-ink-500"><?= $stats['confirmed_today'] ?> lịch diễn ra hôm nay</p>
    </div>
    <span class="stat-icon bg-tag-50 text-tag-600"><?= $svgIcon('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z') ?></span>
  </a>
  <a href="<?= url('/admin/schedule') ?>" class="stat-card hover:border-ink-300">
    <div>
      <p class="stat-label">Nhân viên sẵn sàng hôm nay</p>
      <p class="stat-value text-tag-600"><?= $stats['ready'] ?>/<?= $stats['staff_total'] ?> <span class="text-sm font-normal text-ink-400">người</span></p>
      <p class="mt-2 text-xs text-ink-500">Còn khung trống trong hôm nay</p>
    </div>
    <span class="stat-icon bg-ink-100 text-ink-600"><?= $svgIcon('M16 19v-1a4 4 0 00-4-4H6a4 4 0 00-4 4v1m14-12a3 3 0 11-6 0 3 3 0 016 0zm6 12v-1a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8') ?></span>
  </a>
</div>

<!-- Bộ lọc -->
<section class="panel mt-6 p-4 sm:p-5">
  <nav class="flex flex-wrap gap-2" aria-label="Lọc theo trạng thái">
    <a href="<?= $link(['status' => null]) ?>" class="pill-tab <?= !$status ? 'pill-tab-active' : '' ?>">Tất cả (<?= (int)$allCount ?>)</a>
    <?php foreach (\App\Models\Booking::STATUSES as $s): $n = (int)($counts[$s] ?? 0); ?>
      <a href="<?= $link(['status' => $s]) ?>" class="pill-tab <?= $status === $s ? 'pill-tab-active' : '' ?>">
        <?php if ($s === 'in_progress'): ?><span class="h-2 w-2 rounded-full bg-mint-500"></span><?php endif; ?>
        <?= e(status_label($s)) ?>
        <span class="pill-count <?= $status === $s ? 'bg-white/80 text-ink-900' : $tabCount[$s] ?>"><?= $n ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <form method="get" action="<?= url('/admin/bookings') ?>" class="mt-4 grid gap-3 lg:grid-cols-[1.6fr_1fr_1fr_auto_auto]" x-data="{range: '<?= e($range) ?>'}">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="relative block">
      <span class="sr-only">Tìm kiếm</span>
      <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-4.3-4.3M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
      <input class="input py-2.5 pl-10" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Tìm theo mã đơn, tên thú cưng, chủ nuôi, số điện thoại">
    </label>
    <div class="flex gap-2">
      <label class="sr-only" for="range">Thời gian</label>
      <select class="input py-2.5" id="range" name="range" x-model="range">
        <?php foreach (BC::RANGES as $k => $l): ?><option value="<?= $k ?>" <?= selected($range, $k) ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
      <input x-cloak x-show="range === 'date'" class="input w-40 py-2.5" type="date" name="date" value="<?= e($date ?: $today) ?>" aria-label="Ngày">
    </div>
    <div>
      <label class="sr-only" for="staff_id">Nhân viên</label>
      <select class="input py-2.5" id="staff_id" name="staff_id">
        <option value="">Tất cả nhân viên</option>
        <?php foreach ($staff as $s): ?><option value="<?= (int)$s['id'] ?>" <?= selected($filters['staff_id'] ?? '', $s['id']) ?>><?= e($s['name']) ?><?= $s['status'] === 'locked' ? ' (đã khóa)' : '' ?></option><?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-outline bg-white py-2.5"><?= $svgIcon('M4 6h16M7 12h10M10 18h4', 'h-4 w-4') ?> Lọc</button>
    <a href="<?= url('/admin/bookings') ?>" class="btn btn-outline bg-white px-3 py-2.5" title="Xóa bộ lọc" aria-label="Xóa bộ lọc"><?= $svgIcon('M4 4v5h5M20 20v-5h-5M5.5 15a7 7 0 0012 2.5M18.5 9A7 7 0 006.5 6.5', 'h-4 w-4') ?></a>
  </form>
</section>

<!-- Bảng lịch hẹn -->
<section class="panel mt-6 overflow-hidden">
  <?php if (!$bookings): ?>
    <div class="px-6 py-14 text-center">
      <p class="font-semibold text-ink-800">Không có lịch hẹn nào khớp bộ lọc</p>
      <p class="mt-1 text-sm text-ink-500">Thử chọn "Tất cả thời gian" hoặc xóa từ khóa tìm kiếm.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="tbl min-w-[980px]">
        <thead><tr><th>Mã đơn</th><th>Thú cưng & khách hàng</th><th>Gói dịch vụ</th><th>Lịch hẹn & địa chỉ</th><th>Phân công nhân viên</th><th>Trạng thái</th></tr></thead>
        <tbody>
          <?php foreach ($bookings as $b): $code = booking_code($b['id']); ?>
            <tr class="hover:bg-ink-50/50">
              <td class="whitespace-nowrap">
                <a href="<?= url('/admin/bookings/' . $b['id']) ?>" class="font-semibold text-tag-600 hover:underline">#<?= $code ?></a>
              </td>
              <td class="min-w-[200px]">
                <div class="flex items-center gap-3">
                  <span class="relative">
                    <?= \Core\View::partial('partials/pet_avatar', ['photo' => $b['pet_photo'], 'species' => $b['pet_species'], 'size' => 'h-11 w-11 text-xl']) ?>
                    <?php if ($b['status'] === 'in_progress'): ?><span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-mint-500 ring-2 ring-white"></span><?php endif; ?>
                  </span>
                  <div class="min-w-0">
                    <p class="font-semibold text-ink-900">Bé <?= e($b['pet_name']) ?> <span class="font-normal text-ink-400">(<?= e($b['pet_breed'] ?: species_label($b['pet_species'])) ?><?= $b['pet_age'] !== null ? ' · ' . (int)$b['pet_age'] . ' tuổi' : '' ?>)</span></p>
                    <p class="mt-0.5 flex items-center gap-1 text-xs text-ink-500">
                      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                      <?= e($b['client_name']) ?> · <?= e($b['client_phone']) ?>
                    </p>
                  </div>
                </div>
              </td>
              <td class="min-w-[150px] max-w-[12rem]">
                <p class="font-semibold text-ink-900"><?= e($b['combo_name'] ?? 'Dịch vụ lẻ') ?></p>
                <p class="text-sm font-semibold text-tag-600"><?= money($b['total_price']) ?></p>
                <p class="mt-0.5 text-xs leading-relaxed text-ink-500"><?= e(implode(' + ', array_column($b['services'], 'name'))) ?></p>
              </td>
              <td class="min-w-[170px] max-w-[14rem]">
                <p class="flex items-center gap-1.5 font-medium text-ink-900">
                  <svg class="h-4 w-4 text-tag-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 7v5l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <?= e(hm($b['start_time'])) ?>–<?= e(hm($b['end_time'])) ?>
                  <span class="font-normal <?= $b['booking_date'] === $today ? 'text-tag-600' : 'text-ink-500' ?>">(<?= e(day_label($b['booking_date'])) ?>)</span>
                </p>
                <p class="mt-0.5 truncate text-xs text-ink-500" title="<?= e($b['address']) ?>"><?= e($b['address']) ?></p>
              </td>
              <td>
                <?php if (in_array($b['status'], ['pending', 'confirmed'], true)): ?>
                  <form method="post" action="<?= url('/admin/bookings/' . $b['id'] . '/reassign') ?>">
                    <?= csrf_field() ?>
                    <label class="sr-only" for="st<?= (int)$b['id'] ?>">Nhân viên phụ trách đơn <?= $code ?></label>
                    <select id="st<?= (int)$b['id'] ?>" name="staff_id" class="input w-40 py-2 text-[13px]" data-current="<?= (int)$b['staff_id'] ?>"
                            onchange="if (confirm('Đổi nhân viên phụ trách đơn <?= $code ?>?')) { this.form.submit(); } else { this.value = this.dataset.current; }">
                      <option value="<?= (int)$b['staff_id'] ?>" selected><?= e($b['staff_name']) ?></option>
                      <?php foreach ($b['free_staff'] as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?> · <?= (int)$s['day_load'] ?> đơn/ngày</option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (!$b['free_staff']): ?><p class="mt-1 text-[11px] text-ink-400">Không còn người rảnh khung này</p><?php endif; ?>
                  </form>
                <?php else: ?>
                  <p class="font-medium text-ink-800"><?= e($b['staff_name']) ?></p>
                <?php endif; ?>
              </td>
              <td>
                <?= status_badge($b['status']) ?>
                <div class="mt-2 flex gap-2">
                  <?php if ($b['status'] === 'pending'): ?>
                    <form method="post" action="<?= url('/admin/bookings/' . $b['id'] . '/approve') ?>">
                      <?= csrf_field() ?><input type="hidden" name="return" value="<?= e($return) ?>">
                      <button class="grid h-8 w-8 place-items-center rounded-lg bg-mint-500 text-white shadow-sm hover:bg-mint-600" title="Duyệt đơn <?= $code ?>" aria-label="Duyệt đơn <?= $code ?>">
                        <?= $svgIcon('M5 13l4 4L19 7', 'h-4 w-4') ?>
                      </button>
                    </form>
                  <?php endif; ?>
                  <a href="<?= url('/admin/bookings/' . $b['id']) ?>" class="grid h-8 w-8 place-items-center rounded-lg border border-ink-100 bg-white text-ink-600 hover:border-ink-300 hover:text-ink-900" title="Xem chi tiết" aria-label="Xem chi tiết đơn <?= $code ?>">
                    <?= $svgIcon('M15 12a3 3 0 11-6 0 3 3 0 016 0zm-3-7C7 5 3.3 8 2 12c1.3 4 5 7 10 7s8.7-3 10-7c-1.3-4-5-7-10-7z', 'h-4 w-4') ?>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 bg-ink-50/60 px-5 py-3 text-xs text-ink-500">
      <span>Hiển thị <?= count($bookings) ?> trong tổng số <?= (int)$total ?> lịch hẹn</span>
      <?php if ($pages > 1): ?>
        <nav class="flex items-center gap-1" aria-label="Phân trang">
          <a href="<?= $link(['page' => max(1, $page - 1)]) ?>" class="grid h-8 w-8 place-items-center rounded-lg <?= $page === 1 ? 'pointer-events-none text-ink-300' : 'hover:bg-white' ?>" aria-label="Trang trước">‹</a>
          <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
            <a href="<?= $link(['page' => $i]) ?>" class="grid h-8 w-8 place-items-center rounded-lg font-semibold <?= $i === $page ? 'bg-tag-400 text-ink-900' : 'text-ink-600 hover:bg-white' ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
          <?php endfor; ?>
          <a href="<?= $link(['page' => min($pages, $page + 1)]) ?>" class="grid h-8 w-8 place-items-center rounded-lg <?= $page === $pages ? 'pointer-events-none text-ink-300' : 'hover:bg-white' ?>" aria-label="Trang sau">›</a>
        </nav>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Hôm nay theo nhân viên + quy trình chuẩn -->
<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
  <section class="panel min-w-0 overflow-hidden">
    <div class="panel-head">
      <h2 class="panel-title flex items-center gap-2"><?= $svgIcon('M4 5h16M4 12h16M4 19h16M9 5v14M15 5v14', 'h-5 w-5 text-tag-600') ?> Ca làm hôm nay theo nhân viên</h2>
      <span class="flex items-center gap-1.5 text-xs text-mint-600"><span class="h-2 w-2 rounded-full bg-mint-500"></span> Cập nhật theo đơn</span>
    </div>
    <?php if (!$todayBoard['rows']): ?>
      <p class="px-6 py-6 text-sm text-ink-500">Chưa có nhân viên nào đang hoạt động.</p>
    <?php else: ?>
      <div class="overflow-x-auto p-4 sm:p-5">
        <table class="w-full min-w-[640px] border-separate border-spacing-1.5 text-sm">
          <thead>
            <tr>
              <th class="w-40 text-left text-[11px] font-semibold uppercase tracking-wider text-ink-500">Nhân viên</th>
              <?php foreach ($todayBoard['slots'] as $s): ?><th class="text-left text-[11px] font-semibold uppercase tracking-wider text-ink-500"><?= e(\App\Models\TimeSlot::label($s)) ?></th><?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($todayBoard['rows'] as $row): ?>
              <tr>
                <td class="pr-2 font-medium text-ink-800"><?= e($row['staff']['name']) ?></td>
                <?php foreach ($row['cells'] as $c): ?>
                  <td>
                    <?php if ($c['type'] === 'booking'): $bb = $c['b'];
                        $tone = ['pending' => 'bg-orange-50 ring-orange-200', 'confirmed' => 'bg-sky-50 ring-sky-200', 'in_progress' => 'bg-mint-50 ring-mint-300', 'completed' => 'bg-ink-50 ring-ink-100'][$bb['status']] ?? 'bg-ink-50 ring-ink-100'; ?>
                      <a href="<?= url('/admin/bookings/' . $bb['id']) ?>" class="block rounded-lg px-2.5 py-2 ring-1 ring-inset hover:ring-ink-400 <?= $tone ?>">
                        <span class="block truncate text-xs font-semibold text-ink-900"><?= species_icon($bb['pet_species']) ?> <?= e($bb['pet_name']) ?></span>
                        <span class="block text-[11px] text-ink-500"><?= e(status_label($bb['status'])) ?></span>
                      </a>
                    <?php elseif ($c['type'] === 'free'): ?>
                      <span class="block rounded-lg border border-dashed border-ink-200 px-2.5 py-2 text-xs text-ink-400">Còn trống</span>
                    <?php else: ?>
                      <span class="block rounded-lg px-2.5 py-2 text-xs text-ink-300">Đã qua</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="panel p-5">
    <h2 class="panel-title flex items-center gap-2"><?= $svgIcon('M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6z', 'h-5 w-5 text-mint-600') ?> Quy trình chuẩn tại nhà</h2>
    <p class="mt-1 text-xs text-ink-500">Các bước nhân viên phải làm ở mỗi ca, hệ thống kiểm tra ở bước 2 và 3.</p>
    <ol class="mt-4 space-y-3">
      <?php foreach ([
          ['M9 12l2 2 4-4M7.8 4.7a3.4 3.4 0 001.9-.8 3.4 3.4 0 014.6 0c.5.5 1.2.7 1.9.8a3.4 3.4 0 013.2 3.2c0 .7.3 1.4.8 1.9a3.4 3.4 0 010 4.6c-.5.5-.7 1.2-.8 1.9a3.4 3.4 0 01-3.2 3.2c-.7 0-1.4.3-1.9.8a3.4 3.4 0 01-4.6 0c-.5-.5-1.2-.7-1.9-.8a3.4 3.4 0 01-3.2-3.2c0-.7-.3-1.4-.8-1.9a3.4 3.4 0 010-4.6c.5-.5.7-1.2.8-1.9a3.4 3.4 0 013.2-3.2z', 'Chuẩn bị và sát khuẩn dụng cụ', 'Đọc ghi chú của khách và lịch sử sức khỏe của bé trước khi đến.'],
          ['M15 10l4.6-2.3A1 1 0 0121 8.6v6.8a1 1 0 01-1.4.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'Bấm "Bắt đầu dịch vụ" khi đến nơi', 'Chỉ bấm được trong ngày hẹn; sau đó chụp ảnh từng mốc gửi khách.'],
          ['M3 6l3 1m0 0l-3 9a5 5 0 006 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5 5 0 006 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3', 'Cân và ghi sổ sức khỏe', 'Bắt buộc trước khi bấm "Hoàn thành đơn"; cân nặng cập nhật vào hồ sơ.'],
      ] as $i => [$ic, $h, $p]): ?>
        <li class="flex gap-3 rounded-xl bg-ink-50 p-3 ring-1 ring-inset ring-ink-100">
          <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-mint-600 ring-1 ring-ink-100"><?= $svgIcon($ic, 'h-4 w-4') ?></span>
          <span>
            <span class="block text-sm font-semibold text-ink-900"><?= $i + 1 ?>. <?= e($h) ?></span>
            <span class="block text-xs leading-relaxed text-ink-500"><?= e($p) ?></span>
          </span>
        </li>
      <?php endforeach; ?>
    </ol>
    <div class="mt-4 flex items-center justify-between rounded-xl bg-mint-50 px-4 py-3 text-xs ring-1 ring-inset ring-mint-100">
      <span class="text-ink-600">Nhân viên sẵn sàng hôm nay</span>
      <span class="rounded-full bg-mint-500 px-2.5 py-0.5 font-semibold text-white"><?= $stats['ready'] ?>/<?= $stats['staff_total'] ?></span>
    </div>
  </section>
</div>
