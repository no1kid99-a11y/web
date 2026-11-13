<?php
$size = $size ?? 'h-14 w-14 text-2xl';
$photo = $photo ?? null;
$species = $species ?? 'other';
?>
<?php if ($photo): ?>
  <img src="<?= e(upload_url($photo)) ?>" alt="" class="<?= $size ?> shrink-0 rounded-2xl object-cover">
<?php else: ?>
  <span class="<?= $size ?> grid shrink-0 place-items-center rounded-2xl <?= $species === 'cat' ? 'bg-ink-100' : 'bg-tag-100' ?>" aria-hidden="true"><?= species_icon($species) ?></span>
<?php endif; ?>
