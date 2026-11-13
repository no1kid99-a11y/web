<?php
/** @var array $b */
$steps = ['pending' => 'Chờ duyệt', 'confirmed' => 'Đã xác nhận', 'in_progress' => 'Đang tiến hành', 'completed' => 'Hoàn thành'];
$order = array_keys($steps);
$cur = array_search($b['status'], $order, true);
?>
<?php if ($b['status'] === 'cancelled'): ?>
  <div class="rounded-xl border border-ink-200 bg-ink-50 px-5 py-4">
    <p class="font-semibold text-ink-900">Lịch hẹn đã hủy</p>
    <?php if ($b['cancel_reason']): ?><p class="mt-1 text-sm text-ink-600">Lý do: <?= e($b['cancel_reason']) ?></p><?php endif; ?>
  </div>
<?php else: ?>
  <ol class="grid grid-cols-4 gap-2" aria-label="Tiến trình đơn">
    <?php foreach ($order as $i => $key): $done = $i <= $cur; ?>
      <li class="border-t-4 pt-2 <?= $i < $cur ? 'border-ink-900' : ($i === $cur ? 'border-tag-400' : 'border-ink-100') ?>" <?= $i === $cur ? 'aria-current="step"' : '' ?>>
        <p class="text-xs font-medium sm:text-sm <?= $done ? 'text-ink-900' : 'text-ink-400' ?>"><?= e($steps[$key]) ?></p>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>
