<?php
/** @var array $vaccinations @var ?int $deletablePetId */
$deletablePetId = $deletablePetId ?? null;
$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+30 days'));
?>
<?php if (!$vaccinations): ?>
  <p class="px-5 py-6 text-sm text-ink-500">Chưa có mũi tiêm nào được ghi lại.</p>
<?php else: ?>
  <div class="overflow-x-auto">
    <table class="tbl">
      <thead><tr><th>Vắc-xin</th><th>Ngày tiêm</th><th>Nhắc lại</th><?php if ($deletablePetId): ?><th></th><?php endif; ?></tr></thead>
      <tbody>
        <?php foreach ($vaccinations as $v): ?>
          <tr>
            <td class="font-medium"><?= e($v['vaccine_name']) ?></td>
            <td class="tabular-nums"><?= e(vn_date($v['vaccinated_at'])) ?></td>
            <td class="tabular-nums">
              <?php if ($v['next_due_at']): ?>
                <?= e(vn_date($v['next_due_at'])) ?>
                <?php if ($v['next_due_at'] < $today): ?><span class="ml-1 chip bg-red-100 text-red-800">Quá hạn</span>
                <?php elseif ($v['next_due_at'] <= $soon): ?><span class="ml-1 chip bg-tag-100 text-ink-900">Sắp đến</span><?php endif; ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <?php if ($deletablePetId): ?>
              <td class="text-right">
                <form method="post" action="<?= url("/pets/$deletablePetId/vaccinations/{$v['id']}/delete") ?>" data-confirm="Xóa mũi tiêm này?">
                  <?= csrf_field() ?><button class="text-xs font-medium text-red-700 hover:underline">Xóa</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
