<?php
/**
 * Thẻ tiêu đề trang khu vực quản trị.
 * @var string $title  @var ?string $subtitle  @var ?string $badge  @var string $icon (path SVG)  @var string $actions (HTML)
 */
$subtitle = $subtitle ?? null;
$badge = $badge ?? null;
$actions = $actions ?? '';
$icon = $icon ?? 'M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 9l2 2 4-4';
?>
<section class="flex flex-col gap-5 rounded-2xl border border-tag-100 bg-gradient-to-r from-tag-50 via-[#FFF8EE] to-mint-50 p-5 sm:flex-row sm:items-center sm:p-6">
  <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-tag-400 text-ink-900 shadow-sm shadow-tag-400/40">
    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="<?= $icon ?>"/></svg>
  </span>
  <div class="min-w-0 flex-1">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
      <h1 class="text-xl font-semibold tracking-tight text-ink-900 sm:text-2xl"><?= e($title) ?></h1>
      <?php if ($badge): ?>
        <span class="rounded-full bg-mint-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-mint-700"><?= e($badge) ?></span>
      <?php endif; ?>
    </div>
    <?php if ($subtitle): ?><p class="mt-1 max-w-2xl text-sm text-ink-500"><?= e($subtitle) ?></p><?php endif; ?>
  </div>
  <?php if ($actions): ?><div class="flex shrink-0 flex-wrap gap-3"><?= $actions ?></div><?php endif; ?>
</section>
