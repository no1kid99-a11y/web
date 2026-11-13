<a href="<?= url('/admin/users/' . $owner['id']) ?>" class="text-sm text-ink-500 hover:text-ink-900">Khách hàng <?= e($owner['name']) ?></a>
<div class="mt-3 flex flex-wrap items-center gap-5">
  <?= \Core\View::partial('partials/pet_avatar', ['photo' => $pet['photo'], 'species' => $pet['species'], 'size' => 'h-20 w-20 text-4xl']) ?>
  <div>
    <h1 class="page-title"><?= e($pet['name']) ?><?= $pet['is_active'] ? '' : ' <span class="text-base text-ink-500">(đã xóa)</span>' ?></h1>
    <p class="text-ink-600"><?= e(species_label($pet['species'])) ?><?= $pet['breed'] ? ' · ' . e($pet['breed']) : '' ?><?= $pet['age'] !== null ? ' · ' . (int)$pet['age'] . ' tuổi' : '' ?> · <?= e(kg($pet['weight'])) ?></p>
  </div>
</div>
<p class="mt-2 text-xs text-ink-500">Hồ sơ thú cưng do khách hàng quản lý; quản trị viên chỉ xem.</p>
<div class="mt-6 grid gap-6 lg:grid-cols-2">
  <section class="panel"><div class="panel-head"><h2 class="panel-title">Tiêm phòng</h2></div><?= \Core\View::partial('partials/vaccination_table', ['vaccinations' => $vaccinations]) ?></section>
  <section class="panel"><div class="panel-head"><h2 class="panel-title">Sổ sức khỏe</h2></div><?= \Core\View::partial('partials/health_timeline', ['records' => $records]) ?></section>
</div>
<section class="panel mt-6 overflow-x-auto">
  <div class="panel-head"><h2 class="panel-title">Lịch hẹn</h2></div>
  <?php if (!$bookings): ?><p class="px-5 py-6 text-sm text-ink-500">Chưa có lịch hẹn.</p><?php else: ?>
    <table class="tbl">
      <thead><tr><th>Mã</th><th>Ngày</th><th>Nhân viên</th><th>Trạng thái</th><th class="text-right">Tổng</th></tr></thead>
      <tbody><?php foreach ($bookings as $b): ?>
        <tr><td><a class="link" href="<?= url('/admin/bookings/' . $b['id']) ?>"><?= booking_code($b['id']) ?></a></td><td><?= e(vn_date($b['booking_date'])) ?> <?= e(hm($b['start_time'])) ?></td><td><?= e($b['staff_name']) ?></td><td><?= status_badge($b['status']) ?></td><td class="text-right tabular-nums"><?= money($b['total_price']) ?></td></tr>
      <?php endforeach; ?></tbody>
    </table>
  <?php endif; ?>
</section>
