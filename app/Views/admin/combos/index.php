<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="page-title">Combo</h1>
    <p class="mt-1 text-sm text-ink-600">Mỗi combo gồm ít nhất 2 dịch vụ, tổng thời lượng không quá <?= (int)$limit ?> phút.</p>
  </div>
  <a href="<?= url('/admin/combos/create') ?>" class="btn btn-primary">Thêm combo</a>
</div>
<div class="panel mt-6 overflow-x-auto">
  <table class="tbl">
    <thead><tr><th>Combo</th><th>Gồm dịch vụ</th><th class="text-right">Thời lượng</th><th class="text-right">Giá lẻ</th><th class="text-right">Giá combo</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($combos as $c): ?>
        <tr class="<?= (int)$c['is_active'] ? '' : 'text-ink-400' ?>">
          <td><p class="font-semibold"><?= e($c['name']) ?></p><p class="text-xs text-ink-500"><?= e($c['description']) ?></p></td>
          <td><?= e(implode(', ', array_column($c['services'], 'name'))) ?></td>
          <td class="text-right tabular-nums"><?= (int)$c['duration'] ?> phút</td>
          <td class="text-right tabular-nums text-ink-500"><?= money($c['retail_total']) ?></td>
          <td class="text-right font-semibold tabular-nums"><?= money($c['price']) ?><?php if ($c['retail_total'] > $c['price']): ?><p class="text-xs font-normal text-emerald-700">giảm <?= money($c['retail_total'] - $c['price']) ?></p><?php endif; ?></td>
          <td><?= (int)$c['is_active'] ? '<span class="chip bg-emerald-100 text-emerald-800">Đang bán</span>' : '<span class="chip">Đã ẩn</span>' ?></td>
          <td class="whitespace-nowrap text-right">
            <a href="<?= url('/admin/combos/' . $c['id'] . '/edit') ?>" class="btn btn-ghost btn-sm">Sửa</a>
            <form method="post" action="<?= url('/admin/combos/' . $c['id'] . '/toggle') ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= (int)$c['is_active'] ? 'Ẩn' : 'Hiện lại' ?></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
