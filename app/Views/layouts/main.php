<?php
use Core\Auth;
use Core\Database;

$u = Auth::user();
$isClient = $u && $u['role'] === 'client';
$wide = $wide ?? false;
$ic = fn(string $d, string $cls = 'h-4 w-4') => '<svg class="' . $cls . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $d . '"/></svg>';
$I = [
    'home'   => 'M3 11l9-7 9 7v9a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z',
    'cal'    => 'M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z',
    'cam'    => 'M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z',
    'heart'  => 'M12 20s-7-4.4-7-10a4 4 0 017-2.6A4 4 0 0119 10c0 5.6-7 10-7 10z',
    'user'   => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    'phone'  => 'M3 5a2 2 0 012-2h3.3a1 1 0 01.9.7l1.5 4.5a1 1 0 01-.5 1.2l-2.3 1.1a11 11 0 005.5 5.5l1.1-2.3a1 1 0 011.2-.5l4.5 1.5a1 1 0 01.7.9V19a2 2 0 01-2 2h-1C9.7 21 3 14.3 3 6z',
    'pin'    => 'M12 21s-7-6.3-7-12a7 7 0 1114 0c0 5.7-7 12-7 12zm0-9a3 3 0 100-6 3 3 0 000 6z',
    'clock'  => 'M12 7v5l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z',
    'shield' => 'M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6z',
    'bell'   => 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0',
    'list'   => 'M9 5h10M9 12h10M9 19h10M5 5h.01M5 12h.01M5 19h.01',
];
$nav = $isClient
    ? [['/', 'Trang chủ', true], ['/book', 'Đặt lịch hẹn', false], ['/services', 'Dịch vụ & Combo', false], ['/camera', 'Ảnh trực tiếp', false], ['/pets', 'Sổ sức khỏe', false], ['/bookings', 'Lịch hẹn của tôi', false]]
    : [['/', 'Trang chủ', true], ['/services', 'Dịch vụ & Combo', false], ['/#camera', 'Ảnh trực tiếp', false], ['/#danh-gia', 'Đánh giá', false]];
$upcoming = $isClient ? (int)Database::value('SELECT COUNT(*) FROM bookings WHERE user_id = ? AND active_flag = 1', [(int)$u['id']]) : 0;
?>
<!doctype html>
<html lang="vi">
<head>
<?= \Core\View::partial('partials/head', ['title' => $title ?? '']) ?>
</head>
<body class="min-h-screen bg-paper font-sans text-ink-900 antialiased <?= $isClient ? 'pb-20 lg:pb-0' : '' ?>">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Bỏ qua tới nội dung</a>

<!-- Thanh thông tin -->
<div class="hidden border-b border-ink-100 bg-ink-50 text-[12px] text-ink-600 md:block">
  <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-2">
    <span class="flex items-center gap-1.5"><span class="text-tag-500"><?= $ic($I['heart'], 'h-3.5 w-3.5') ?></span> Tắm, tỉa lông, dắt đi dạo cho chó mèo ngay tại nhà</span>
    <span class="flex items-center gap-1.5"><span class="text-tag-500"><?= $ic($I['pin'], 'h-3.5 w-3.5') ?></span> Khu vực phục vụ: nội thành Hà Nội</span>
    <span class="flex items-center gap-1.5"><span class="text-tag-500"><?= $ic($I['clock'], 'h-3.5 w-3.5') ?></span> Nhận lịch 8:00–17:00 mỗi ngày</span>
    <span class="hidden items-center gap-1.5 lg:flex"><span class="text-forest-500"><?= $ic($I['shield'], 'h-3.5 w-3.5') ?></span> Khử khuẩn dụng cụ trước mỗi ca</span>
  </div>
</div>

<!-- Header -->
<header class="sticky top-0 z-30 border-b border-ink-100 bg-white/95 backdrop-blur" x-data="{open:false}">
  <div class="mx-auto flex h-[72px] max-w-7xl items-center gap-4 px-4">
    <a href="<?= url('/') ?>" class="flex shrink-0 items-center gap-2.5" aria-label="PetCare Home, trang chủ">
      <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-tag-400 to-tag-600 shadow-md shadow-tag-500/30">
        <svg class="h-6 w-6" viewBox="0 0 32 32" aria-hidden="true"><g fill="#fff"><ellipse cx="16" cy="20" rx="6" ry="5"/><circle cx="9" cy="13" r="2.6"/><circle cx="23" cy="13" r="2.6"/><circle cx="13" cy="8.5" r="2.4"/><circle cx="19" cy="8.5" r="2.4"/></g></svg>
      </span>
      <span class="leading-tight">
        <span class="block font-display text-xl font-extrabold tracking-tight text-ink-900">PetCare</span>
        <span class="block text-[11px] font-medium text-tag-600">Tận tâm tại gia</span>
      </span>
    </a>

    <nav class="ml-4 hidden flex-1 items-center gap-1 lg:flex" aria-label="Điều hướng chính">
      <?php foreach ($nav as [$href, $label, $exact]): $active = !str_contains($href, '#') && nav_active($href, $exact); ?>
        <a href="<?= url($href) ?>" class="rounded-xl px-3.5 py-2 text-sm font-semibold <?= $active ? 'bg-tag-50 text-tag-600' : 'text-ink-600 hover:bg-ink-50 hover:text-ink-900' ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="ml-auto flex items-center gap-2.5">
      <a href="tel:19006868" class="hidden items-center gap-2 rounded-xl border border-tag-200 bg-tag-50 px-3 py-2 text-sm font-bold text-tag-700 xl:flex">
        <?= $ic($I['phone']) ?> 1900 6868
      </a>
      <?php if (!$u): ?>
        <a href="<?= url('/login') ?>" class="btn btn-ghost hidden sm:inline-flex">Đăng nhập</a>
        <a href="<?= url('/register') ?>" class="btn btn-accent">Tạo tài khoản</a>
      <?php else: ?>
        <?php if ($isClient): ?>
          <a href="<?= url('/bookings') ?>" class="relative grid h-10 w-10 place-items-center rounded-full border border-ink-100 bg-white text-ink-600 hover:text-ink-900" aria-label="Lịch hẹn đang chờ: <?= $upcoming ?>">
            <?= $ic($I['bell'], 'h-5 w-5') ?>
            <?php if ($upcoming): ?><span class="absolute -right-1 -top-1 grid h-5 min-w-[20px] place-items-center rounded-full bg-tag-500 px-1 text-[11px] font-bold text-white ring-2 ring-white"><?= $upcoming ?></span><?php endif; ?>
          </a>
        <?php else: ?>
          <a href="<?= url(Auth::home()) ?>" class="btn btn-outline hidden sm:inline-flex">Vào trang <?= $u['role'] === 'admin' ? 'quản trị' : 'nhân viên' ?></a>
        <?php endif; ?>
        <div class="relative" x-data="{m:false}" @click.outside="m=false">
          <button type="button" class="flex items-center gap-2.5 rounded-full border border-ink-100 bg-white py-1 pl-1 pr-3 hover:bg-ink-50" @click="m=!m" :aria-expanded="m">
            <span class="grid h-8 w-8 place-items-center rounded-full bg-gradient-to-br from-tag-300 to-tag-500 text-sm font-bold text-white"><?= e(mb_strtoupper(mb_substr($u['name'], mb_strrpos($u['name'], ' ') !== false ? mb_strrpos($u['name'], ' ') + 1 : 0, 1))) ?></span>
            <span class="hidden text-left leading-tight sm:block">
              <span class="block max-w-[9rem] truncate text-sm font-semibold"><?= e($u['name']) ?></span>
              <span class="block text-[11px] text-forest-600"><?= e(role_label($u['role'])) ?></span>
            </span>
          </button>
          <div x-cloak x-show="m" class="absolute right-0 mt-2 w-56 rounded-2xl border border-ink-100 bg-white p-1.5 text-sm shadow-xl shadow-ink-900/5">
            <?php if ($isClient): ?>
              <a href="<?= url('/bookings') ?>" class="block rounded-xl px-3 py-2 hover:bg-ink-50">Lịch hẹn của tôi</a>
              <a href="<?= url('/pets') ?>" class="block rounded-xl px-3 py-2 hover:bg-ink-50">Thú cưng và sổ sức khỏe</a>
            <?php endif; ?>
            <a href="<?= url('/profile') ?>" class="block rounded-xl px-3 py-2 hover:bg-ink-50">Thông tin cá nhân</a>
            <a href="<?= url('/password') ?>" class="block rounded-xl px-3 py-2 hover:bg-ink-50">Đổi mật khẩu</a>
            <form method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?><button class="w-full rounded-xl px-3 py-2 text-left text-red-600 hover:bg-red-50">Đăng xuất</button></form>
          </div>
        </div>
      <?php endif; ?>
      <button type="button" class="rounded-xl p-2 hover:bg-ink-50 lg:hidden" @click="open=!open" aria-label="Mở menu">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
    </div>
  </div>
  <div x-cloak x-show="open" class="border-t border-ink-100 bg-white px-4 py-3 lg:hidden">
    <?php foreach ($nav as [$href, $label]): ?>
      <a href="<?= url($href) ?>" class="block rounded-xl px-3 py-2.5 font-semibold text-ink-700 hover:bg-ink-50"><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php if (!$u): ?><a href="<?= url('/login') ?>" class="block rounded-xl px-3 py-2.5 font-semibold text-ink-700">Đăng nhập</a><?php endif; ?>
    <a href="tel:19006868" class="mt-2 block rounded-xl bg-tag-50 px-3 py-2.5 font-bold text-tag-700">Hotline 1900 6868</a>
  </div>
</header>

<main id="main" class="<?= $wide ? '' : 'mx-auto max-w-7xl px-4 py-8 lg:py-10' ?>">
  <?php if (!$wide && trim($flash = \Core\View::partial('partials/flash')) !== ''): ?><div class="mb-6"><?= $flash ?></div><?php endif; ?>
  <?= $content ?>
</main>

<!-- Footer -->
<footer class="mt-16 border-t border-ink-100 bg-white">
  <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 text-sm text-ink-600 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
    <div>
      <span class="flex items-center gap-2.5">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-tag-400 to-tag-600"><svg class="h-5 w-5" viewBox="0 0 32 32" aria-hidden="true"><g fill="#fff"><ellipse cx="16" cy="20" rx="6" ry="5"/><circle cx="9" cy="13" r="2.6"/><circle cx="23" cy="13" r="2.6"/><circle cx="13" cy="8.5" r="2.4"/><circle cx="19" cy="8.5" r="2.4"/></g></svg></span>
        <span class="font-display text-lg font-extrabold text-ink-900">PetCare Home</span>
      </span>
      <p class="mt-4 max-w-xs leading-relaxed">Nhân viên chăm sóc thú cưng đến tận nhà: tắm sấy, cắt tỉa, vệ sinh và dắt đi dạo, kèm sổ sức khỏe sau mỗi lần.</p>
      <ul class="mt-4 space-y-2 text-[13px]">
        <li class="flex items-center gap-2"><span class="text-forest-500"><?= $ic($I['shield']) ?></span> Mỗi nhân viên chỉ nhận một nhà trong một khung giờ</li>
        <li class="flex items-center gap-2"><span class="text-forest-500"><?= $ic($I['cam']) ?></span> Nhận ảnh từng bước khi dịch vụ đang diễn ra</li>
        <li class="flex items-center gap-2"><span class="text-forest-500"><?= $ic($I['heart']) ?></span> Thanh toán tại nhà sau khi làm xong</li>
      </ul>
    </div>
    <div>
      <p class="font-bold text-ink-900">Dịch vụ tại gia</p>
      <ul class="mt-4 space-y-2.5">
        <li><a class="hover:text-tag-600" href="<?= url('/services') ?>">Tắm và sấy khô</a></li>
        <li><a class="hover:text-tag-600" href="<?= url('/services') ?>">Cắt tỉa lông tạo kiểu</a></li>
        <li><a class="hover:text-tag-600" href="<?= url('/services') ?>">Vệ sinh tai, răng, móng</a></li>
        <li><a class="hover:text-tag-600" href="<?= url('/services') ?>">Dắt đi dạo</a></li>
        <li><a class="hover:text-tag-600" href="<?= url('/services') ?>">Combo tiết kiệm</a></li>
      </ul>
    </div>
    <div>
      <p class="font-bold text-ink-900">Hỗ trợ khách hàng</p>
      <ul class="mt-4 space-y-2.5">
        <li><a class="hover:text-tag-600" href="<?= url('/#cach-dat-lich') ?>">Hướng dẫn đặt lịch</a></li>
        <li><a class="hover:text-tag-600" href="<?= url('/services') ?>#chinh-sach">Chính sách hủy lịch</a></li>
        <li><a class="hover:text-tag-600" href="<?= url('/camera') ?>">Theo dõi trực tiếp</a></li>
        <li><a class="hover:text-tag-600" href="<?= url('/pets') ?>">Sổ sức khỏe thú cưng</a></li>
      </ul>
    </div>
    <div>
      <p class="font-bold text-ink-900">Liên hệ</p>
      <ul class="mt-4 space-y-2.5">
        <li class="flex gap-2"><span class="mt-0.5 text-tag-500"><?= $ic($I['pin']) ?></span> 12 Láng Hạ, Đống Đa, Hà Nội</li>
        <li class="flex gap-2"><span class="mt-0.5 text-tag-500"><?= $ic($I['phone']) ?></span> <span><strong class="text-ink-900">Hotline: 1900 6868</strong></span></li>
        <li class="flex gap-2"><span class="mt-0.5 text-tag-500"><?= $ic($I['clock']) ?></span> 8:00–17:00, thứ Hai đến Chủ nhật</li>
      </ul>
    </div>
  </div>
  <div class="border-t border-ink-100">
    <div class="mx-auto flex max-w-7xl flex-wrap justify-between gap-2 px-4 py-4 text-xs text-ink-400">
      <span>© <?= date('Y') ?> PetCare Home. Đồ án môn học.</span>
      <span>Thanh toán trực tiếp cho nhân viên sau khi hoàn thành dịch vụ.</span>
    </div>
  </div>
</footer>

<?php if ($isClient): ?>
<!-- Thanh điều hướng dưới đáy (điện thoại) -->
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-ink-100 bg-white/95 backdrop-blur lg:hidden" aria-label="Điều hướng nhanh">
  <div class="mx-auto grid max-w-md grid-cols-5">
    <?php foreach ([['/', 'Trang chủ', 'home', true], ['/book', 'Đặt lịch', 'cal', false], ['/camera', 'Theo dõi', 'cam', false], ['/pets', 'Sức khỏe', 'heart', false], ['/profile', 'Hồ sơ', 'user', false]] as [$href, $label, $icon, $exact]): $active = nav_active($href, $exact); ?>
      <a href="<?= url($href) ?>" class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold <?= $active ? 'text-tag-600' : 'text-ink-500' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
        <?= $ic($I[$icon], 'h-5 w-5') ?><?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>
</body>
</html>
