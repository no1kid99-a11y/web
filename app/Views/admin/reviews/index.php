<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="page-title">Đánh giá</h1>
    <p class="mt-1 text-sm text-ink-600">Đánh giá bị ẩn không hiện trên trang chủ và không tính vào điểm trung bình.</p>
  </div>
  <?php if ($stats['avg']): ?><p class="text-sm text-ink-600"><span class="font-display text-3xl font-bold text-ink-900"><?= number_format((float)$stats['avg'], 1, ',', '') ?></span>/5 · <?= (int)$stats['n'] ?> đánh giá hiển thị</p><?php endif; ?>
</div>
<div class="panel mt-6 overflow-x-auto">
  <?php if (!$reviews): ?><p class="px-5 py-10 text-center text-sm text-ink-500">Chưa có đánh giá nào.</p><?php else: ?>
    <table class="tbl">
      <thead><tr><th>Điểm</th><th>Nhận xét</th><th>Khách · Thú cưng</th><th>Nhân viên</th><th>Ngày</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($reviews as $r): ?>
          <tr class="<?= (int)$r['is_hidden'] ? 'bg-ink-50/70 text-ink-400' : '' ?>">
            <td class="whitespace-nowrap text-tag-500"><?= str_repeat('★', (int)$r['rating']) ?><span class="text-ink-200"><?= str_repeat('★', 5 - (int)$r['rating']) ?></span></td>
            <td class="max-w-md"><?= $r['comment'] ? nl2br(e($r['comment'])) : '<span class="text-ink-400">Không có nhận xét</span>' ?><?php if ((int)$r['is_hidden']): ?> <span class="chip">Đã ẩn</span><?php endif; ?></td>
            <td><?= e($r['client_name']) ?><p class="text-xs text-ink-500"><?= e($r['pet_name']) ?> · <a class="hover:underline" href="<?= url('/admin/bookings/' . $r['booking_id']) ?>">đơn <?= booking_code($r['booking_id']) ?></a></p></td>
            <td><?= e($r['staff_name']) ?></td>
            <td class="whitespace-nowrap tabular-nums"><?= e(vn_date($r['created_at'])) ?></td>
            <td class="text-right"><form method="post" action="<?= url('/admin/reviews/' . $r['id'] . '/toggle') ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= (int)$r['is_hidden'] ? 'Hiện lại' : 'Ẩn' ?></button></form></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
