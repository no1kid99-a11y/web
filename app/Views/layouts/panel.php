<?php
use Core\Auth;
use Core\Database;

$u = Auth::user();
$isAdmin = $u['role'] === 'admin';
$today = date('Y-m-d');
$icons = [
    'home'     => 'M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v4H4zM14 15h6v4h-6z',
    'calendar' => 'M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm3.5 8.5h2m3 0h2m-7 3h2',
    'grid'     => 'M4 5h16M4 12h16M4 19h16M9 5v14M15 5v14',
    'users'    => 'M16 19v-1a4 4 0 00-4-4H6a4 4 0 00-4 4v1m14-12a3 3 0 11-6 0 3 3 0 016 0zm6 12v-1a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8',
    'box'      => 'M5 8h14v11a1 1 0 01-1 1H6a1 1 0 01-1-1zM3 5h18v3H3zm9 6v6m-3-3h6',
    'clock'    => 'M12 7v5l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z',
    'star'     => 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z',
    'log'      => 'M9 5h10M9 12h10M9 19h10M5 5h.01M5 12h.01M5 19h.01',
    'scissors' => 'M6 9a3 3 0 100-6 3 3 0 000 6zm0 12a3 3 0 100-6 3 3 0 000 6zM20 4L8.1 15.9M14.5 14.5 20 20M8.1 8.1 12 12',
];

// Chỉ số cho thanh trên cùng (Admin: toàn hệ thống, Nhân viên: của chính mình)
$scope = $isAdmin ? '' : ' AND staff_id = ' . (int)$u['id'];
$todayCount = (int)Database::value("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND status <> 'cancelled'$scope", [$today]);
$todayRunning = (int)Database::value("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND status = 'in_progress'$scope", [$today]);
$rate = Database::fetch(
    "SELECT SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS done,
            SUM(CASE WHEN status IN ('completed','cancelled') THEN 1 ELSE 0 END) AS closed
     FROM bookings WHERE booking_date BETWEEN ? AND ?$scope",
    [date('Y-m-d', strtotime('-30 days')), $today]
);
$rateText = (int)$rate['closed'] > 0 ? number_format($rate['done'] / $rate['closed'] * 100, 1, ',', '') . '%' : '—';

if ($isAdmin) {
    $pendingBookings = (int)Database::value("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $bell = $pendingBookings;
    $bellUrl = url('/admin/bookings', ['status' => 'pending']);
    $groups = [
        [
            ['/admin', 'Tổng quan & Doanh thu', 'home', 0, true],
            ['/admin/bookings', 'Quản lý lịch hẹn', 'calendar', $pendingBookings, false],
            ['/admin/schedule', 'Lịch 7 ngày nhân viên', 'grid', 0, false],
        ],
        [
            ['/admin/services', 'Dịch vụ', 'scissors', 0, false],
            ['/admin/combos', 'Combo', 'box', 0, false],
            ['/admin/slots', 'Khung giờ', 'clock', 0, false],
            ['/admin/users', 'Người dùng', 'users', 0, false],
            ['/admin/reviews', 'Đánh giá & Phản hồi', 'star', 0, false],
            ['/admin/logs', 'Nhật ký hoạt động', 'log', 0, false],
        ],
    ];
} else {
    $bell = $todayCount - (int)Database::value("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND status = 'completed' AND staff_id = ?", [$today, (int)$u['id']]);
    $bellUrl = url('/staff');
    $groups = [[
        ['/staff', 'Lịch làm việc của tôi', 'calendar', 0, true],
        ['/staff/reviews', 'Đánh giá của khách', 'star', 0, false],
    ]];
}
$initial = mb_strtoupper(mb_substr($u['name'], mb_strrpos($u['name'], ' ') !== false ? mb_strrpos($u['name'], ' ') + 1 : 0, 1));
?>
<!doctype html>
<html lang="vi">
<head>
<?= \Core\View::partial('partials/head', ['title' => $title ?? '']) ?>
</head>
<body class="admin-theme min-h-screen bg-ink-50 font-lexend text-ink-900 antialiased" x-data="{side:false}">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Bỏ qua tới nội dung</a>

<!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-ink-100 bg-[#FCFAF7] transition-transform lg:translate-x-0" :class="side && 'translate-x-0'">
  <a href="<?= url(Auth::home()) ?>" class="flex items-center gap-3 px-5 pb-4 pt-6">
    <span class="grid h-11 w-11 place-items-center rounded-xl bg-tag-400 shadow-sm shadow-tag-400/40">
      <svg class="h-6 w-6" viewBox="0 0 32 32" aria-hidden="true"><g fill="#241F1B"><ellipse cx="16" cy="20" rx="6" ry="5"/><circle cx="9" cy="13" r="2.6"/><circle cx="23" cy="13" r="2.6"/><circle cx="13" cy="8.5" r="2.4"/><circle cx="19" cy="8.5" r="2.4"/></g></svg>
    </span>
    <span>
      <span class="block text-lg font-semibold leading-tight text-ink-900">PetCare</span>
      <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-tag-600"><?= $isAdmin ? 'Home care admin' : 'Khu vực nhân viên' ?></span>
    </span>
  </a>

  <div class="mx-4 mb-4 flex items-center justify-between gap-2 rounded-xl bg-mint-50 px-3 py-2 text-xs ring-1 ring-inset ring-mint-100">
    <span class="flex min-w-0 items-center gap-1.5 text-mint-700">
      <span class="h-2 w-2 shrink-0 rounded-full bg-mint-500"></span>
      <span class="truncate"><?= $isAdmin ? 'Điều phối' : 'Ca làm' ?>: <?= e($u['name']) ?></span>
    </span>
    <span class="shrink-0 font-medium text-mint-600">Trực tuyến</span>
  </div>

  <nav class="flex-1 space-y-4 overflow-y-auto px-3 pb-4" aria-label="Menu quản lý">
    <?php foreach ($groups as $gi => $items): ?>
      <div class="space-y-1 <?= $gi > 0 ? 'border-t border-ink-100 pt-4' : '' ?>">
        <?php foreach ($items as [$href, $label, $icon, $badge, $exact]): $active = nav_active($href, $exact); ?>
          <a href="<?= url($href) ?>" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm <?= $active ? 'bg-tag-400 font-semibold text-ink-900 shadow-sm shadow-tag-400/40' : 'font-medium text-ink-600 hover:bg-white hover:text-ink-900' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
            <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="<?= $icons[$icon] ?>"/></svg>
            <span class="flex-1"><?= e($label) ?></span>
            <?php if ($badge): ?><span class="rounded-full <?= $active ? 'bg-white/80' : 'bg-red-500 text-white' ?> px-1.5 text-[11px] font-semibold leading-5"><?= $badge ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </nav>

  <div class="m-4 rounded-2xl bg-ink-100/70 p-4">
    <div class="flex items-start justify-between">
      <p class="text-xs font-medium text-ink-600">Hỗ trợ khách hàng</p>
      <span class="text-lg leading-none text-red-500" aria-hidden="true">*</span>
    </div>
    <p class="mt-1 text-2xl font-semibold tabular-nums text-tag-600">1900 6868</p>
    <p class="mt-1 text-[11px] text-ink-500">Nhận lịch 8:00–17:00 mỗi ngày</p>
  </div>
</aside>
<div x-cloak x-show="side" class="fixed inset-0 z-30 bg-ink-900/30 lg:hidden" @click="side=false"></div>

<div class="lg:pl-64">
  <!-- Thanh trên cùng -->
  <header class="sticky top-0 z-20 border-b border-ink-100 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-[1400px] items-center gap-3 px-4 sm:px-6 lg:px-8">
      <button type="button" class="rounded-lg p-2 hover:bg-ink-100 lg:hidden" @click="side=true" aria-label="Mở menu">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
      <div class="hidden items-center gap-2 md:flex">
        <span class="inline-flex items-center gap-2 rounded-full border border-tag-200 bg-tag-50 px-3 py-1.5 text-xs text-ink-700">
          <svg class="h-3.5 w-3.5 text-tag-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="<?= $icons['clock'] ?>"/></svg>
          Hôm nay: <strong class="font-semibold text-ink-900"><?= $todayCount ?> lịch tại nhà</strong> (<?= $todayRunning ?> đang thực hiện)
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-mint-50 px-3 py-1.5 text-xs text-mint-700 ring-1 ring-inset ring-mint-100" title="Đơn hoàn thành trên tổng số đơn đã kết thúc trong 30 ngày qua">
          <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Tỷ lệ hoàn thành 30 ngày: <strong class="font-semibold"><?= $rateText ?></strong>
        </span>
      </div>
      <div class="ml-auto flex items-center gap-4">
        <a href="<?= $bellUrl ?>" class="relative grid h-10 w-10 place-items-center rounded-full border border-ink-100 bg-white text-ink-600 hover:text-ink-900" aria-label="<?= $isAdmin ? 'Việc cần xử lý' : 'Lịch còn lại hôm nay' ?>: <?= max(0, $bell) ?>">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
          <?php if ($bell > 0): ?><span class="absolute -right-1 -top-1 grid h-5 min-w-[20px] place-items-center rounded-full bg-red-500 px-1 text-[11px] font-semibold text-white ring-2 ring-white"><?= $bell ?></span><?php endif; ?>
        </a>
        <div class="relative" x-data="{m:false}" @click.outside="m=false">
          <button type="button" class="flex items-center gap-3 text-right" @click="m=!m" :aria-expanded="m">
            <span class="hidden sm:block">
              <span class="block text-sm font-semibold leading-tight"><?= e($u['name']) ?></span>
              <span class="block text-[11px] text-ink-500"><?= e(role_label($u['role'])) ?></span>
            </span>
            <span class="grid h-10 w-10 place-items-center rounded-full bg-tag-100 font-semibold text-tag-700 ring-2 ring-white"><?= e($initial) ?></span>
          </button>
          <div x-cloak x-show="m" class="absolute right-0 mt-2 w-52 rounded-xl border border-ink-100 bg-white p-1.5 text-sm shadow-lg shadow-ink-900/5">
            <a href="<?= url('/profile') ?>" class="block rounded-lg px-3 py-2 hover:bg-ink-50">Thông tin cá nhân</a>
            <a href="<?= url('/password') ?>" class="block rounded-lg px-3 py-2 hover:bg-ink-50">Đổi mật khẩu</a>
            <form method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?><button class="w-full rounded-lg px-3 py-2 text-left text-red-600 hover:bg-red-50">Đăng xuất</button></form>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main id="main" class="mx-auto max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <?php if (trim($flash = \Core\View::partial('partials/flash')) !== ''): ?><div class="mb-6"><?= $flash ?></div><?php endif; ?>
    <?= $content ?>
  </main>
</div>
</body>
</html>
