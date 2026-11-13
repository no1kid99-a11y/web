<?php
use Core\Auth;

$isClient = $user && $user['role'] === 'client';
$bookUrl = $user ? ($isClient ? url('/book') : url(Auth::home())) : url('/register');
$ic = fn(string $d, string $cls = 'h-5 w-5') => '<svg class="' . $cls . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $d . '"/></svg>';
$I = [
    'shield' => 'M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6zm-3 9l2 2 4-4',
    'cam'    => 'M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z',
    'cal'    => 'M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 9l2 2 4-4',
    'book'   => 'M12 6.5C10.5 5.5 8.5 5 6 5H4v13h2c2.5 0 4.5.5 6 1.5m0-13C13.5 5.5 15.5 5 18 5h2v13h-2c-2.5 0-4.5.5-6 1.5m0-13v13',
    'check'  => 'M5 13l4 4L19 7',
    'play'   => 'M14.8 11.2l-4.6-2.9A1 1 0 008.7 9.2v5.6a1 1 0 001.5.9l4.6-2.9a1 1 0 000-1.6zM21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    'arrow'  => 'M5 12h14m-6-6l6 6-6 6',
    'clock'  => 'M12 7v5l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z',
    'syringe'=> 'M18 2l4 4M17 7l3-3M19 9L9 19l-4 1 1-4L16 6zM14 8l2 2M11 11l2 2',
    'plus'   => 'M12 5v14M5 12h14',
    'scale'  => 'M12 3v18M5 7h14M7 7l-3 7a3 3 0 006 0zm10 0l-3 7a3 3 0 006 0z',
];
$stars = fn(int $n) => str_repeat('★', $n) . '<span class="text-ink-200">' . str_repeat('★', 5 - $n) . '</span>';
$initials = fn(string $name) => mb_strtoupper(mb_substr($name, mb_strrpos($name, ' ') !== false ? mb_strrpos($name, ' ') + 1 : 0, 1));
$avg = $reviewStats['avg'] ? number_format((float)$reviewStats['avg'], 1, ',', '') : null;
?>
<style>
  @keyframes spin-slow { to { transform: rotate(360deg); } }
  .sticker-spin { animation: spin-slow 22s linear infinite; }
  @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
  .hero-in > * { animation: rise .7s cubic-bezier(.2,.7,.2,1) both; }
  .hero-in > *:nth-child(2) { animation-delay: .06s } .hero-in > *:nth-child(3) { animation-delay: .12s }
  .hero-in > *:nth-child(4) { animation-delay: .18s } .hero-in > *:nth-child(5) { animation-delay: .24s } .hero-in > *:nth-child(6) { animation-delay: .3s }
  .paw-bg { background-image: radial-gradient(circle at 20px 20px, rgba(255,255,255,.07) 6px, transparent 7px), radial-gradient(circle at 10px 8px, rgba(255,255,255,.06) 3px, transparent 4px), radial-gradient(circle at 30px 8px, rgba(255,255,255,.06) 3px, transparent 4px); background-size: 64px 64px; }
</style>

<!-- ============ HERO ============ -->
<section class="relative overflow-hidden bg-gradient-to-b from-[#FFF4E4] via-paper to-paper">
  <div class="pointer-events-none absolute -right-40 -top-40 h-[520px] w-[520px] rounded-full bg-tag-200/40 blur-3xl"></div>
  <div class="pointer-events-none absolute -left-32 top-64 h-72 w-72 rounded-full bg-forest-100/60 blur-3xl"></div>
  <div class="relative mx-auto max-w-7xl px-4 pb-16 pt-8 lg:pb-24 lg:pt-12">
    <?php if (trim($flash = \Core\View::partial('partials/flash')) !== ''): ?><div class="mb-8"><?= $flash ?></div><?php endif; ?>
    <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
      <div class="hero-in">
        <span class="inline-flex items-center gap-2 rounded-full border border-tag-200 bg-white/80 px-3.5 py-1.5 text-xs font-bold text-tag-700 shadow-sm">
          <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true"><g fill="currentColor"><ellipse cx="16" cy="20" rx="6" ry="5"/><circle cx="9" cy="13" r="2.6"/><circle cx="23" cy="13" r="2.6"/><circle cx="13" cy="8.5" r="2.4"/><circle cx="19" cy="8.5" r="2.4"/></g></svg>
          Chăm sóc chuẩn spa ngay tại nhà · Hà Nội
        </span>
        <h1 class="mt-5 font-display text-[2.3rem] font-extrabold leading-[1.08] tracking-tight text-ink-900 [text-wrap:balance] sm:text-[3.2rem]">
          Spa, tắm &amp; chăm sóc thú cưng
          <span class="relative mt-1 block text-tag-500">
            tận cửa nhà bạn
            <svg class="absolute -bottom-2 left-0 h-3 w-64 text-tag-300" viewBox="0 0 260 12" fill="none" aria-hidden="true"><path d="M2 9c40-6 90-8 130-5s80 3 126-2" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>
          </span>
        </h1>
        <p class="mt-6 max-w-xl text-[17px] leading-relaxed text-ink-600">
          Nhân viên đến tận nhà tắm sấy, cắt tỉa, vệ sinh và dắt bé đi dạo. Bé không phải ngồi xe, không chờ ở tiệm.
          Bạn nhận ảnh của bé trong lúc làm và sổ sức khỏe sau mỗi lần.
        </p>
        <dl class="mt-7 grid max-w-xl grid-cols-3 gap-3">
          <div class="rounded-2xl border border-ink-100 bg-white/90 p-3.5 shadow-sm">
            <dt class="flex items-center gap-1.5 text-forest-600"><?= $ic($I['shield'], 'h-4 w-4') ?><span class="font-display text-lg font-extrabold text-ink-900">100%</span></dt>
            <dd class="mt-0.5 text-[12px] leading-snug text-ink-500">Dụng cụ khử khuẩn trước mỗi ca</dd>
          </div>
          <div class="rounded-2xl border border-ink-100 bg-white/90 p-3.5 shadow-sm">
            <dt class="flex items-center gap-1.5 text-red-500"><?= $ic($I['cam'], 'h-4 w-4') ?><span class="font-display text-lg font-extrabold text-ink-900">Ảnh thật</span></dt>
            <dd class="mt-0.5 text-[12px] leading-snug text-ink-500">Gửi từng bước khi đang làm</dd>
          </div>
          <div class="rounded-2xl border border-ink-100 bg-white/90 p-3.5 shadow-sm">
            <dt class="flex items-center gap-1.5 text-tag-500"><?= $ic($I['clock'], 'h-4 w-4') ?><span class="font-display text-lg font-extrabold text-ink-900"><?= (int)$tomorrowFree ?></span></dt>
            <dd class="mt-0.5 text-[12px] leading-snug text-ink-500">Khung giờ còn trống ngày mai</dd>
          </div>
        </dl>
        <div class="mt-8 flex flex-wrap gap-3">
          <a href="<?= $bookUrl ?>" class="btn btn-accent px-6 py-3.5 text-base"><?= $ic($I['cal']) ?> Đặt lịch ngay, chỉ mất 1 phút</a>
          <a href="#camera" class="btn btn-outline px-5 py-3.5 text-base"><?= $ic($I['play']) ?> Xem cách theo dõi bé</a>
        </div>
        <?php if ($avg): ?>
          <div class="mt-8 flex items-center gap-3">
            <div class="flex -space-x-2.5">
              <?php foreach (array_slice($reviews, 0, 3) as $i => $r): ?>
                <span class="grid h-9 w-9 place-items-center rounded-full text-xs font-bold text-white ring-2 ring-paper <?= ['bg-tag-400', 'bg-forest-500', 'bg-ink-500'][$i] ?>"><?= e($initials($r['client_name'])) ?></span>
              <?php endforeach; ?>
            </div>
            <p class="text-sm text-ink-600">
              <span class="text-tag-500"><?= $stars((int)round((float)$reviewStats['avg'])) ?></span>
              <strong class="ml-1 text-ink-900"><?= $avg ?>/5</strong> từ <?= (int)$reviewStats['n'] ?> lượt đánh giá sau <?= (int)$doneCount ?> ca đã hoàn thành
            </p>
          </div>
        <?php endif; ?>
      </div>

      <div class="relative mx-auto w-full max-w-md lg:max-w-none">
        <div class="absolute -inset-3 rotate-2 rounded-[2.4rem] bg-gradient-to-br from-tag-200 to-forest-100"></div>
        <div class="relative overflow-hidden rounded-[2.2rem] bg-tag-100 shadow-2xl shadow-ink-900/15">
          <?= photo_img('hero', 'aspect-[4/5] w-full object-cover', 'Chú chó vui vẻ sau khi được tắm tại nhà', 'dog', 1000) ?>
        </div>
        <div class="absolute -left-4 top-8 flex items-center gap-3 rounded-2xl bg-white/95 px-4 py-3 shadow-xl shadow-ink-900/10 backdrop-blur sm:-left-8">
          <span class="relative flex h-3 w-3"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-forest-400 opacity-60"></span><span class="relative inline-flex h-3 w-3 rounded-full bg-forest-500"></span></span>
          <span class="leading-tight">
            <span class="block text-sm font-bold text-ink-900">Ngày mai còn <?= (int)$tomorrowFree ?> khung giờ</span>
            <span class="block text-xs text-ink-500"><?= e(weekday_vn($tomorrow)) ?>, <?= e(vn_date($tomorrow)) ?></span>
          </span>
        </div>
        <div class="absolute -right-3 bottom-10 flex max-w-[15rem] items-center gap-3 rounded-2xl bg-white/95 p-3 shadow-xl shadow-ink-900/10 backdrop-blur sm:-right-6">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-forest-50 text-forest-600"><?= $ic($I['shield']) ?></span>
          <span class="leading-tight">
            <span class="block text-sm font-bold text-ink-900">Không lo trùng lịch</span>
            <span class="block text-xs text-ink-500">Mỗi nhân viên chỉ nhận một nhà trong một khung giờ</span>
          </span>
        </div>
        <div class="absolute -bottom-8 left-6 h-24 w-24 sm:-left-6" aria-hidden="true">
          <svg viewBox="0 0 100 100" class="sticker-spin h-full w-full">
            <defs><path id="circ" d="M50,50 m-36,0 a36,36 0 1,1 72,0 a36,36 0 1,1 -72,0"/></defs>
            <circle cx="50" cy="50" r="48" fill="#F7941D"/>
            <text font-size="10.5" font-weight="800" letter-spacing="2.4" fill="#fff"><textPath href="#circ">PETCARE · TẬN TÂM · TẠI GIA ·</textPath></text>
          </svg>
          <svg viewBox="0 0 32 32" class="absolute inset-0 m-auto h-8 w-8"><g fill="#fff"><ellipse cx="16" cy="20" rx="6" ry="5"/><circle cx="9" cy="13" r="2.6"/><circle cx="23" cy="13" r="2.6"/><circle cx="13" cy="8.5" r="2.4"/><circle cx="19" cy="8.5" r="2.4"/></g></svg>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($isClient): ?>
<!-- ============ HỒ SƠ GIA ĐÌNH ============ -->
<section class="mx-auto max-w-7xl px-4 py-14">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <p class="eyebrow">Hồ sơ gia đình PetCare</p>
      <h2 class="section-title mt-2">Các bé của <?= e($user['name']) ?></h2>
    </div>
    <p class="flex items-center gap-2 text-sm text-ink-500"><span class="text-forest-500"><?= $ic($I['book'], 'h-4 w-4') ?></span> Sổ sức khỏe được nhân viên cập nhật sau mỗi ca</p>
  </div>
  <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($pets as $p):
        $due = $p['next_vaccine'] ? (int)floor((strtotime($p['next_vaccine']['next_due_at']) - strtotime(date('Y-m-d'))) / 86400) : null; ?>
      <article class="flex flex-col rounded-3xl border border-ink-100 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-4">
          <span class="relative">
            <?php if ($p['photo']): ?>
              <img src="<?= e(upload_url($p['photo'])) ?>" alt="" class="h-[72px] w-[72px] rounded-2xl object-cover">
            <?php else: ?>
              <?= photo_img($p['species'] === 'cat' ? 'cat' : 'puppy', 'h-[72px] w-[72px] rounded-2xl object-cover', '', $p['species'], 200) ?>
            <?php endif; ?>
            <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 rounded-full bg-tag-500 px-2 py-0.5 text-[10px] font-bold text-white ring-2 ring-white"><?= e(species_label($p['species'])) ?></span>
          </span>
          <div class="min-w-0">
            <p class="font-display text-lg font-extrabold text-ink-900"><?= e($p['name']) ?></p>
            <p class="text-sm text-ink-500"><?= e($p['breed'] ?: species_label($p['species'])) ?> · <?= e(kg($p['weight'])) ?><?= $p['age'] !== null ? ' · ' . (int)$p['age'] . ' tuổi' : '' ?></p>
            <p class="mt-1 text-xs text-forest-600"><?= $p['last_care'] ? 'Chăm sóc gần nhất ' . e(vn_date($p['last_care'])) : 'Chưa có lần chăm sóc nào' ?></p>
          </div>
        </div>
        <div class="mt-4 flex items-center justify-between gap-3 rounded-2xl px-3.5 py-3 <?= $due !== null && $due <= 30 ? 'bg-tag-50' : 'bg-ink-50' ?>">
          <?php if ($p['upcoming']): ?>
            <span class="text-sm"><span class="block font-semibold text-ink-900">Lịch sắp tới</span><span class="text-xs text-ink-500"><?= e(day_label($p['upcoming']['booking_date'])) ?>, <?= e(hm($p['upcoming']['start_time'])) ?></span></span>
            <a href="<?= url('/bookings/' . $p['upcoming']['id']) ?>" class="btn btn-sm btn-outline">Xem lịch</a>
          <?php elseif ($p['next_vaccine']): ?>
            <span class="flex items-center gap-2 text-sm">
              <span class="text-tag-600"><?= $ic($I['syringe'], 'h-4 w-4') ?></span>
              <span><span class="block font-semibold text-ink-900">Tiêm nhắc <?= e($p['next_vaccine']['vaccine_name']) ?></span>
              <span class="text-xs <?= $due < 0 ? 'text-red-600' : 'text-ink-500' ?>"><?= $due < 0 ? 'Đã quá hạn ' . abs($due) . ' ngày' : 'Còn ' . $due . ' ngày' ?></span></span>
            </span>
            <a href="<?= url('/book', ['pet' => $p['id']]) ?>" class="btn btn-sm btn-accent">Đặt lịch</a>
          <?php else: ?>
            <span class="text-sm text-ink-600">Chưa có lịch hẹn nào sắp tới</span>
            <a href="<?= url('/book', ['pet' => $p['id']]) ?>" class="btn btn-sm btn-accent">Đặt lịch</a>
          <?php endif; ?>
        </div>
        <a href="<?= url('/pets/' . $p['id']) ?>" class="mt-3 flex items-center justify-between text-xs font-semibold text-ink-500 hover:text-tag-600">Mở sổ sức khỏe và lịch tiêm <?= $ic($I['arrow'], 'h-4 w-4') ?></a>
      </article>
    <?php endforeach; ?>
    <a href="<?= url('/pets/create') ?>" class="flex min-h-[220px] flex-col items-center justify-center rounded-3xl border-2 border-dashed border-ink-200 bg-white/60 p-6 text-center hover:border-tag-300 hover:bg-tag-50/50">
      <span class="grid h-12 w-12 place-items-center rounded-full bg-tag-100 text-tag-600"><?= $ic($I['plus'], 'h-6 w-6') ?></span>
      <span class="mt-3 font-bold text-ink-900">Thêm hồ sơ bé cưng mới</span>
      <span class="mt-1 max-w-[14rem] text-sm text-ink-500">Lưu giống, cân nặng và lịch tiêm để nhân viên chuẩn bị đúng dụng cụ</span>
    </a>
  </div>
</section>
<?php endif; ?>

<!-- ============ GÓI DỊCH VỤ ============ -->
<section class="border-y border-ink-100 bg-white" x-data="{tab: 'combo'}">
  <div class="mx-auto max-w-7xl px-4 py-16">
    <div class="flex flex-wrap items-end justify-between gap-5">
      <div>
        <p class="eyebrow">Được các gia đình chọn nhiều nhất</p>
        <h2 class="section-title mt-2">Gói dịch vụ &amp; combo tận gia</h2>
        <p class="mt-2 text-ink-500">Giá đã gồm công đến tận nhà trong nội thành. Combo rẻ hơn đặt lẻ từng dịch vụ.</p>
      </div>
      <div class="inline-flex rounded-2xl bg-ink-50 p-1 text-sm font-semibold" role="tablist">
        <button type="button" class="rounded-xl px-4 py-2" :class="tab === 'combo' ? 'bg-white text-tag-600 shadow-sm' : 'text-ink-500'" @click="tab = 'combo'" role="tab" :aria-selected="tab === 'combo'">Combo tiết kiệm</button>
        <button type="button" class="rounded-xl px-4 py-2" :class="tab === 'service' ? 'bg-white text-tag-600 shadow-sm' : 'text-ink-500'" @click="tab = 'service'" role="tab" :aria-selected="tab === 'service'">Dịch vụ lẻ</button>
      </div>
    </div>

    <div class="mt-8 grid gap-5 sm:grid-cols-2 <?= count($combos) % 3 === 0 ? 'lg:grid-cols-3' : 'lg:grid-cols-4' ?>" x-show="tab === 'combo'">
      <?php foreach ($combos as $c): $save = $c['retail_total'] - $c['price']; ?>
        <article class="group flex flex-col overflow-hidden rounded-3xl border border-ink-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-ink-900/5">
          <div class="relative aspect-[4/3] overflow-hidden bg-tag-100">
            <?= photo_img(service_photo_key($c['name']), 'h-full w-full object-cover transition duration-500 group-hover:scale-105', $c['name'], 'dog', 700) ?>
            <?php if ($save > 0): ?><span class="absolute left-3 top-3 rounded-full bg-tag-500 px-2.5 py-1 text-[11px] font-bold text-white shadow">Tiết kiệm <?= money($save) ?></span><?php endif; ?>
            <span class="absolute bottom-3 right-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-ink-800 backdrop-blur">~<?= (int)$c['duration'] ?> phút</span>
          </div>
          <div class="flex flex-1 flex-col p-5">
            <h3 class="font-display text-lg font-extrabold leading-snug text-ink-900"><?= e($c['name']) ?></h3>
            <p class="mt-1.5 line-clamp-2 text-sm text-ink-500"><?= e($c['description']) ?></p>
            <ul class="mt-3 flex-1 space-y-1.5 text-[13px] text-ink-700">
              <?php foreach ($c['services'] as $s): ?><li class="flex items-center gap-2"><span class="text-forest-500"><?= $ic($I['check'], 'h-3.5 w-3.5') ?></span><?= e($s['name']) ?></li><?php endforeach; ?>
            </ul>
            <div class="mt-5 flex items-end justify-between gap-2 border-t border-ink-100 pt-4">
              <div>
                <?php if ($save > 0): ?><p class="text-xs text-ink-400 line-through"><?= money($c['retail_total']) ?></p><?php endif; ?>
                <p class="font-display text-xl font-extrabold text-tag-600"><?= money($c['price']) ?></p>
              </div>
              <a href="<?= $isClient ? url('/book', ['combo' => $c['id']]) : $bookUrl ?>" class="btn btn-sm btn-accent">Chọn gói</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" x-cloak x-show="tab === 'service'">
      <?php foreach ($services as $s): ?>
        <article class="flex gap-4 rounded-3xl border border-ink-100 bg-white p-4 shadow-sm">
          <div class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl bg-tag-100"><?= photo_img(service_photo_key($s['name']), 'h-full w-full object-cover', $s['name'], 'dog', 300) ?></div>
          <div class="flex min-w-0 flex-1 flex-col">
            <h3 class="font-bold text-ink-900"><?= e($s['name']) ?></h3>
            <p class="mt-0.5 line-clamp-2 text-xs text-ink-500"><?= e($s['description']) ?></p>
            <div class="mt-auto flex items-center justify-between pt-2">
              <span class="text-sm"><strong class="text-tag-600"><?= money($s['price']) ?></strong> <span class="text-ink-400">· <?= (int)$s['duration_minutes'] ?> phút</span></span>
              <a href="<?= $isClient ? url('/book', ['service' => $s['id']]) : $bookUrl ?>" class="text-sm font-bold text-tag-600 hover:underline">Chọn</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ ẢNH TRỰC TIẾP ============ -->
<section id="camera" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-16">
  <div class="grid items-center gap-10 rounded-[2rem] border border-ink-100 bg-white p-5 shadow-sm sm:p-8 lg:grid-cols-[1.1fr_1fr] lg:p-10">
    <div class="relative">
      <div class="relative overflow-hidden rounded-3xl bg-ink-900">
        <?= photo_img('groom', 'aspect-[16/11] w-full object-cover', 'Bé cún sau khi được cắt tỉa tại nhà', 'dog', 1000) ?>
        <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-ink-900 shadow"><span class="rounded-full bg-tag-500 px-1.5 text-[10px] uppercase text-white">Mới</span> Sau khi cắt tỉa</span>
        <span class="absolute right-4 top-4 rounded-full bg-black/40 px-2.5 py-1 text-[11px] text-white/90 backdrop-blur">Hình minh họa</span>
        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 via-black/30 to-transparent px-5 pb-4 pt-16 text-white">
          <p class="text-xs text-white/80">10:42 · Nhân viên PetCare</p>
          <p class="mt-0.5 font-medium">Bé ngoan lắm, đã tỉa gọn chân và vùng mặt.</p>
        </div>
      </div>
      <!-- Dòng thời gian thu nhỏ: mô phỏng đúng trang khách sẽ thấy -->
      <ol class="mt-4 grid grid-cols-3 gap-3">
        <?php foreach ([['bath', '10:05', 'Đang tắm'], ['spa', '10:21', 'Sau khi sấy'], ['groom', '10:42', 'Sau khi cắt tỉa']] as [$k, $t, $l]): ?>
          <li>
            <div class="relative overflow-hidden rounded-2xl bg-ink-100">
              <?= photo_img($k, 'aspect-[4/3] w-full object-cover', $l, 'dog', 400) ?>
              <span class="absolute bottom-1.5 right-1.5 rounded-full bg-black/60 px-1.5 text-[10px] font-medium tabular-nums text-white"><?= $t ?></span>
            </div>
            <p class="mt-1.5 text-[12px] font-semibold text-ink-700"><?= $l ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
    <div>
      <p class="eyebrow">Chỉ có khi đặt qua PetCare</p>
      <h2 class="section-title mt-2">Đi làm vẫn thấy bé đang được chăm thế nào</h2>
      <p class="mt-3 leading-relaxed text-ink-600">Nhân viên chụp ảnh ở từng bước ngay trên điện thoại. Trang theo dõi của bạn tự hiện ảnh mới sau vài giây, không cần tải lại, không cần cài ứng dụng.</p>
      <ul class="mt-7 space-y-5">
        <li class="flex gap-4">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-tag-50 text-tag-600"><?= $ic($I['cam']) ?></span>
          <span><span class="block font-bold text-ink-900">Ảnh thật ở từng mốc</span><span class="text-sm text-ink-500">Lúc mới đến, đang tắm, sau khi sấy, sau khi tỉa, kèm một dòng ghi chú của nhân viên.</span></span>
        </li>
        <li class="flex gap-4">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-forest-50 text-forest-600"><?= $ic($I['shield']) ?></span>
          <span><span class="block font-bold text-ink-900">Chỉ bạn xem được</span><span class="text-sm text-ink-500">Ảnh không đăng công khai. Chỉ chủ lịch hẹn, nhân viên phụ trách và quản trị viên mở được.</span></span>
        </li>
        <li class="flex gap-4">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-ink-50 text-ink-700"><?= $ic($I['scale']) ?></span>
          <span><span class="block font-bold text-ink-900">Lưu lại trong sổ sức khỏe</span><span class="text-sm text-ink-500">Xong buổi, ảnh nằm trong album của bé cùng cân nặng và ghi chú, xem lại lúc nào cũng được.</span></span>
        </li>
      </ul>
      <a href="<?= $isClient ? url('/camera') : $bookUrl ?>" class="btn btn-outline mt-8"><?= $isClient ? 'Mở trang theo dõi hôm nay' : 'Đặt lịch để trải nghiệm' ?> <?= $ic($I['arrow'], 'h-4 w-4') ?></a>
    </div>
  </div>
</section>

<!-- ============ ĐÁNH GIÁ ============ -->
<?php if ($reviews): ?>
<section id="danh-gia" class="mx-auto max-w-7xl scroll-mt-24 px-4 pb-16">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <p class="eyebrow"><?= (int)$reviewStats['n'] ?> đánh giá · trung bình <?= $avg ?>/5</p>
      <h2 class="section-title mt-2">Ba mẹ thú cưng nói gì về PetCare?</h2>
      <p class="mt-2 text-ink-500">Chỉ khách đã hoàn thành dịch vụ mới viết được đánh giá.</p>
    </div>
  </div>
  <div class="mt-8 grid gap-5 md:grid-cols-3">
    <?php foreach ($reviews as $i => $r): ?>
      <figure class="flex flex-col rounded-3xl border border-ink-100 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between text-xs text-ink-400">
          <span class="text-base text-tag-500" aria-label="<?= (int)$r['rating'] ?> trên 5 sao"><?= $stars((int)$r['rating']) ?></span>
          <span><?= e(vn_date($r['created_at'])) ?></span>
        </div>
        <blockquote class="mt-3 flex-1 leading-relaxed text-ink-700">“<?= e($r['comment']) ?>”</blockquote>
        <div class="mt-4 overflow-hidden rounded-2xl bg-tag-50">
          <?= photo_img($r['pet_species'] === 'cat' ? 'cat2' : ($i % 2 ? 'puppy' : 'spa'), 'aspect-[16/9] w-full object-cover', 'Bé ' . $r['pet_name'], $r['pet_species'], 600) ?>
        </div>
        <figcaption class="mt-4 flex items-center gap-3">
          <span class="grid h-10 w-10 place-items-center rounded-full text-sm font-bold text-white <?= ['bg-tag-400', 'bg-forest-500', 'bg-ink-500'][$i % 3] ?>"><?= e($initials($r['client_name'])) ?></span>
          <span class="leading-tight"><span class="block font-bold text-ink-900"><?= e($r['client_name']) ?></span><span class="text-xs text-ink-500">Chủ của bé <?= e($r['pet_name']) ?> · <?= e($r['combo_name'] ?? 'Dịch vụ lẻ') ?></span></span>
        </figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ============ CÁC BƯỚC ============ -->
<section id="cach-dat-lich" class="mx-auto max-w-7xl scroll-mt-24 px-4 pb-16">
  <div class="rounded-[2rem] bg-forest-50 px-6 py-10 sm:px-10">
    <p class="eyebrow text-forest-700">Hướng dẫn đặt lịch</p>
    <h2 class="section-title mt-2">Bốn bước, khoảng một phút</h2>
    <ol class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <?php foreach ([
          ['Chọn bé', 'Tạo hồ sơ một lần: giống, cân nặng, lịch tiêm.'],
          ['Chọn gói', 'Một combo hoặc vài dịch vụ lẻ, thấy ngay tổng tiền.'],
          ['Chọn giờ', 'Chỉ hiện khung còn nhân viên rảnh, đặt trước ít nhất 2 giờ.'],
          ['Xác nhận', 'Cửa hàng duyệt đơn, nhân viên đến đúng giờ đã hẹn.'],
      ] as $i => [$h, $p]): ?>
        <li class="relative rounded-2xl bg-white p-5 shadow-sm">
          <span class="font-display text-4xl font-extrabold text-forest-200"><?= sprintf('%02d', $i + 1) ?></span>
          <h3 class="mt-1 font-bold text-ink-900"><?= e($h) ?></h3>
          <p class="mt-1 text-sm leading-relaxed text-ink-500"><?= e($p) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- ============ LỜI MỜI ============ -->
<section class="mx-auto max-w-7xl px-4">
  <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-ink-800 via-ink-700 to-tag-700 px-6 py-12 text-white sm:px-12">
    <div class="paw-bg pointer-events-none absolute inset-0"></div>
    <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-tag-400/30 blur-3xl"></div>
    <div class="relative grid items-center gap-8 lg:grid-cols-[1.3fr_1fr]">
      <div>
        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-tag-200 ring-1 ring-white/15">Dành cho khách hàng mới</span>
        <h2 class="mt-4 font-display text-3xl font-extrabold leading-tight sm:text-4xl">Đặt lịch đầu tiên cho bé<br class="hidden sm:block"> mà không cần ra khỏi nhà</h2>
        <p class="mt-3 max-w-lg text-white/75">Tạo tài khoản, thêm hồ sơ bé và chọn khung giờ còn trống. Thanh toán trực tiếp cho nhân viên sau khi làm xong.</p>
      </div>
      <div>
        <?php if (!$user): ?>
          <form method="get" action="<?= url('/register') ?>" class="flex flex-col gap-3 rounded-2xl bg-white p-2 sm:flex-row">
            <label class="sr-only" for="cta-email">Email của bạn</label>
            <input id="cta-email" name="email" type="email" required placeholder="Email của bạn" class="min-w-0 flex-1 rounded-xl border-0 px-4 py-3 text-ink-900 placeholder:text-ink-300 focus:ring-2 focus:ring-tag-300">
            <button class="btn btn-accent px-6 py-3">Tạo tài khoản</button>
          </form>
        <?php else: ?>
          <a href="<?= $bookUrl ?>" class="btn btn-accent w-full py-4 text-base sm:w-auto sm:px-8"><?= $isClient ? 'Đặt lịch ngay' : 'Vào trang làm việc' ?></a>
        <?php endif; ?>
        <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-[13px] text-white/80">
          <li class="flex items-center gap-1.5"><span class="text-tag-300"><?= $ic($I['check'], 'h-4 w-4') ?></span> Hủy miễn phí trước 2 giờ</li>
          <li class="flex items-center gap-1.5"><span class="text-tag-300"><?= $ic($I['check'], 'h-4 w-4') ?></span> Trả tiền sau khi làm xong</li>
          <li class="flex items-center gap-1.5"><span class="text-tag-300"><?= $ic($I['check'], 'h-4 w-4') ?></span> Ảnh từng bước khi đang làm</li>
        </ul>
      </div>
    </div>
  </div>
</section>
