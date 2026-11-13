<?php /** @var array $records */ ?>
<?php if (!$records): ?>
  <p class="px-5 py-6 text-sm text-ink-500">Chưa có ghi chép nào. Nhân viên sẽ ghi sổ sau mỗi lần chăm sóc.</p>
<?php else: ?>
  <ol class="relative space-y-5 px-5 py-5">
    <?php foreach ($records as $i => $h): ?>
      <li class="relative pl-7">
        <span class="absolute left-0 top-1.5 h-3 w-3 rounded-full <?= $i === 0 ? 'bg-tag-400 ring-4 ring-tag-100' : 'bg-ink-200' ?>"></span>
        <?php if ($i < count($records) - 1): ?><span class="absolute left-[5px] top-5 h-[calc(100%+0.5rem)] w-px bg-ink-100"></span><?php endif; ?>
        <div class="flex flex-wrap items-baseline justify-between gap-2">
          <p class="text-sm font-semibold text-ink-900"><?= e(vn_datetime($h['recorded_at'])) ?></p>
          <?php if ($h['weight'] !== null): ?><span class="chip"><?= e(kg($h['weight'])) ?></span><?php endif; ?>
        </div>
        <p class="mt-1 text-sm leading-relaxed text-ink-700"><?= nl2br(e($h['note'])) ?></p>
        <p class="mt-1 text-xs text-ink-500">Ghi bởi <?= e($h['recorder_name']) ?></p>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>
