<div class="flex flex-wrap items-end justify-between gap-4">
  <h1 class="page-title">Đánh giá của khách</h1>
  <?php if ($avg !== null): ?>
    <p class="text-sm text-ink-600"><span class="font-display text-3xl font-bold text-ink-900"><?= number_format($avg, 1, ',', '') ?></span>/5 từ <?= count($reviews) ?> lượt</p>
  <?php endif; ?>
</div>
<?php if (!$reviews): ?>
  <p class="mt-6 rounded-xl border border-dashed border-ink-200 bg-white px-5 py-8 text-center text-sm text-ink-500">Chưa có đánh giá nào cho các đơn bạn phụ trách.</p>
<?php else: ?>
  <div class="mt-6 grid gap-4 md:grid-cols-2">
    <?php foreach ($reviews as $r): ?>
      <figure class="panel p-5">
        <div class="flex items-center justify-between">
          <span class="text-lg text-tag-500"><?= str_repeat('★', (int)$r['rating']) ?><span class="text-ink-200"><?= str_repeat('★', 5 - (int)$r['rating']) ?></span></span>
          <span class="text-xs text-ink-500"><?= e(vn_date($r['booking_date'])) ?></span>
        </div>
        <blockquote class="mt-2 text-sm text-ink-800"><?= $r['comment'] ? nl2br(e($r['comment'])) : '<span class="text-ink-400">Không có nhận xét</span>' ?></blockquote>
        <figcaption class="mt-3 text-xs text-ink-500"><?= e($r['client_name']) ?> · <?= e($r['pet_name']) ?> · <?= e($r['combo_name'] ?? 'Dịch vụ lẻ') ?></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
