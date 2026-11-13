<?php
$groups = ['booking' => 'Lịch hẹn', 'user' => 'Tài khoản', 'health' => 'Sổ sức khỏe', 'service' => 'Dịch vụ', 'combo' => 'Combo', 'slot' => 'Khung giờ', 'review' => 'Đánh giá'];
$targetUrl = fn($l) => match ($l['target_type']) {
    'booking' => url('/admin/bookings/' . $l['target_id']),
    'user' => url('/admin/users/' . $l['target_id']),
    'service' => url('/admin/services'),
    'combo' => url('/admin/combos'),
    'slot' => url('/admin/slots'),
    'review' => url('/admin/reviews'),
    default => null,
};
?>
<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="page-title">Nhật ký hoạt động</h1>
    <p class="mt-1 text-sm text-ink-600">Ghi trong cùng giao dịch với thao tác: thao tác thành công thì luôn có dòng nhật ký.</p>
  </div>
  <form method="get" action="<?= url('/admin/logs') ?>" class="flex flex-wrap gap-2">
    <label class="sr-only" for="action">Loại thao tác</label>
    <select class="input w-40" id="action" name="action">
      <option value="">Mọi thao tác</option>
      <?php foreach ($groups as $k => $l): ?><option value="<?= $k ?>." <?= selected($f['action'], "$k.") ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <label class="sr-only" for="user_id">Người thực hiện</label>
    <select class="input w-48" id="user_id" name="user_id">
      <option value="">Mọi người</option>
      <?php foreach ($actors as $a): ?><option value="<?= (int)$a['id'] ?>" <?= selected($f['user_id'], $a['id']) ?>><?= e($a['name']) ?> (<?= e(role_label($a['role'])) ?>)</option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline">Lọc</button>
  </form>
</div>

<div class="panel mt-6 overflow-x-auto">
  <?php if (!$logs): ?><p class="px-5 py-10 text-center text-sm text-ink-500">Chưa có dòng nhật ký nào.</p><?php else: ?>
    <table class="tbl">
      <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Thao tác</th><th>Đối tượng</th><th>Chi tiết</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): $d = $l['detail'] ? json_decode($l['detail'], true) : []; $link = $targetUrl($l); ?>
          <tr>
            <td class="whitespace-nowrap tabular-nums"><?= e(vn_datetime($l['created_at'])) ?></td>
            <td><?= e($l['user_name'] ?? 'Hệ thống') ?><p class="text-xs text-ink-500"><?= e(role_label($l['user_role'] ?? '')) ?></p></td>
            <td class="font-medium"><?= e(action_label($l['action'])) ?></td>
            <td class="whitespace-nowrap"><?= $link ? '<a class="link" href="' . e($link) . '">' : '' ?><?= e($groups[$l['target_type']] ?? $l['target_type']) ?> #<?= (int)$l['target_id'] ?><?= $link ? '</a>' : '' ?></td>
            <td class="text-ink-600"><?= ltrim(\Core\View::partial('partials/log_detail', ['d' => $d]), ' ·') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
  <nav class="mt-4 flex items-center justify-between text-sm" aria-label="Phân trang">
    <span class="text-ink-500">Trang <?= $page ?>/<?= $pages ?> · <?= (int)$total ?> dòng</span>
    <div class="flex gap-2">
      <?php if ($page > 1): ?><a class="btn btn-outline btn-sm" href="<?= url('/admin/logs', array_merge($f, ['page' => $page - 1])) ?>">Trang trước</a><?php endif; ?>
      <?php if ($page < $pages): ?><a class="btn btn-outline btn-sm" href="<?= url('/admin/logs', array_merge($f, ['page' => $page + 1])) ?>">Trang sau</a><?php endif; ?>
    </div>
  </nav>
<?php endif; ?>
