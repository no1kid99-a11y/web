<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="page-title">Dịch vụ</h1>
    <p class="mt-1 text-sm text-ink-600">Đổi giá chỉ áp dụng cho đơn mới; đơn đã đặt giữ giá tại thời điểm đặt.</p>
  </div>
  <a href="<?= url('/admin/services/create') ?>" class="btn btn-primary">Thêm dịch vụ</a>
</div>
<div class="panel mt-6 overflow-x-auto">
  <table class="tbl">
    <thead><tr><th>Dịch vụ</th><th class="text-right">Thời lượng</th><th class="text-right">Giá</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($services as $s): ?>
        <tr class="<?= (int)$s['is_active'] ? '' : 'text-ink-400' ?>">
          <td><p class="font-semibold"><?= e($s['name']) ?></p><p class="text-xs text-ink-500"><?= e($s['description']) ?></p></td>
          <td class="text-right tabular-nums"><?= (int)$s['duration_minutes'] ?> phút</td>
          <td class="text-right font-semibold tabular-nums"><?= money($s['price']) ?></td>
          <td><?= (int)$s['is_active'] ? '<span class="chip bg-emerald-100 text-emerald-800">Đang bán</span>' : '<span class="chip">Đã ẩn</span>' ?></td>
          <td class="whitespace-nowrap text-right">
            <a href="<?= url('/admin/services/' . $s['id'] . '/edit') ?>" class="btn btn-ghost btn-sm">Sửa</a>
            <form method="post" action="<?= url('/admin/services/' . $s['id'] . '/toggle') ?>" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= (int)$s['is_active'] ? 'Ẩn' : 'Hiện lại' ?></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
