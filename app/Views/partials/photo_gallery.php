<?php
/**
 * Lưới ảnh ca làm (bố cục trùng với hàm card() trong public/assets/js/photos.js).
 * @var array       $photos     ảnh đã qua BookingPhoto::present(), mới nhất trước
 * @var string|null $deleteBase ví dụ '/staff/photos'; có thì hiện nút gỡ ảnh
 * @var string      $id         id của khung lưới (để form chụp ảnh chèn ảnh mới vào)
 * @var string      $empty      câu hiển thị khi chưa có ảnh
 * @var string      $cols       lớp cột của lưới
 * @var bool        $showDate   ghi cả ngày chụp (album nhiều buổi)
 */
$deleteBase = $deleteBase ?? null;
$showDate = $showDate ?? false;
$cols = $cols ?? 'grid-cols-2 sm:grid-cols-3';
?>
<div id="<?= e($id ?? 'photo-list') ?>" class="grid gap-4 <?= e($cols) ?>" data-photo-list>
  <?php foreach ($photos as $p): ?>
    <figure class="photo-card relative" data-photo-id="<?= (int)$p['id'] ?>">
      <a href="<?= e($p['url']) ?>" data-lightbox data-label="<?= e($p['label']) ?>" data-caption="<?= e($p['caption']) ?>" data-time="<?= e($p['time'] . ' · ' . $p['date']) ?>" data-by="<?= e($p['by']) ?>"
         class="group relative block aspect-[4/3] overflow-hidden rounded-2xl bg-ink-100">
        <img src="<?= e($p['thumb']) ?>" alt="<?= e($p['label'] . ($p['caption'] ? ': ' . $p['caption'] : '')) ?>" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
        <span class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[11px] font-semibold text-ink-800 shadow-sm"><?= e($p['label']) ?></span>
        <span class="absolute bottom-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-[11px] font-medium tabular-nums text-white"><?= e($showDate ? $p['date'] : $p['time']) ?></span>
      </a>
      <?php if ($deleteBase): ?>
        <form method="post" action="<?= url($deleteBase . '/' . $p['id'] . '/delete') ?>" class="absolute right-2 top-2" data-confirm="Gỡ ảnh này? Khách sẽ không thấy nữa.">
          <?= csrf_field() ?>
          <button class="grid h-8 w-8 place-items-center rounded-full bg-white/90 text-red-600 shadow hover:bg-white" title="Gỡ ảnh" aria-label="Gỡ ảnh">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M10 11v6M14 11v6M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V4h6v3"/></svg>
          </button>
        </form>
      <?php endif; ?>
      <?php if ($p['caption'] !== ''): ?><figcaption class="mt-2 text-[13px] leading-snug text-ink-700"><?= e($p['caption']) ?></figcaption><?php endif; ?>
    </figure>
  <?php endforeach; ?>
  <?php if (!$photos): ?>
    <p class="col-span-full rounded-2xl border border-dashed border-ink-200 px-4 py-8 text-center text-sm text-ink-500" data-empty><?= e($empty ?? 'Chưa có ảnh nào.') ?></p>
  <?php endif; ?>
</div>
