<a href="<?= url('/pets') ?>" class="text-sm text-ink-500 hover:text-ink-900">Thú cưng của tôi</a>
<div class="mt-3 flex flex-wrap items-center gap-5">
  <?= \Core\View::partial('partials/pet_avatar', ['photo' => $pet['photo'], 'species' => $pet['species'], 'size' => 'h-24 w-24 text-5xl']) ?>
  <div class="flex-1">
    <h1 class="page-title"><?= e($pet['name']) ?></h1>
    <p class="mt-1 text-ink-600">
      <?= e(species_label($pet['species'])) ?><?= $pet['breed'] ? ' · ' . e($pet['breed']) : '' ?>
      <?= $pet['age'] !== null ? ' · ' . (int)$pet['age'] . ' tuổi' : '' ?> · <?= e(kg($pet['weight'])) ?>
    </p>
  </div>
  <div class="flex flex-wrap gap-2">
    <a href="<?= url('/book', ['pet' => $pet['id']]) ?>" class="btn btn-accent">Đặt lịch cho <?= e($pet['name']) ?></a>
    <a href="<?= url('/pets/' . $pet['id'] . '/edit') ?>" class="btn btn-outline">Sửa hồ sơ</a>
    <form method="post" action="<?= url('/pets/' . $pet['id'] . '/delete') ?>" data-confirm="Xóa hồ sơ <?= e($pet['name']) ?>? Lịch sử đơn cũ vẫn được giữ.">
      <?= csrf_field() ?><button class="btn btn-danger-outline">Xóa</button>
    </form>
  </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-[1fr_1fr]">
  <section class="panel" id="vaccinations">
    <div class="panel-head"><h2 class="panel-title">Lịch sử tiêm phòng</h2></div>
    <?= \Core\View::partial('partials/vaccination_table', ['vaccinations' => $vaccinations, 'deletablePetId' => (int)$pet['id']]) ?>
    <form method="post" action="<?= url('/pets/' . $pet['id'] . '/vaccinations') ?>" class="grid gap-3 border-t border-ink-100 bg-ink-50/50 p-5 sm:grid-cols-[1.4fr_1fr_1fr_auto] sm:items-end">
      <?= csrf_field() ?>
      <div>
        <label class="label text-xs" for="vaccine_name">Vắc-xin</label>
        <input class="input" id="vaccine_name" name="vaccine_name" required placeholder="Dại, 7 bệnh...">
      </div>
      <div>
        <label class="label text-xs" for="vaccinated_at">Ngày tiêm</label>
        <input class="input" id="vaccinated_at" name="vaccinated_at" type="date" required max="<?= date('Y-m-d') ?>">
      </div>
      <div>
        <label class="label text-xs" for="next_due_at">Nhắc lại</label>
        <input class="input" id="next_due_at" name="next_due_at" type="date">
      </div>
      <button class="btn btn-primary">Thêm</button>
    </form>
  </section>

  <section class="panel">
    <div class="panel-head"><h2 class="panel-title">Sổ sức khỏe</h2><span class="text-xs text-ink-500">Do nhân viên cập nhật</span></div>
    <?= \Core\View::partial('partials/health_timeline', ['records' => $records]) ?>
  </section>
</div>

<section class="panel mt-6" id="album">
  <div class="panel-head">
    <h2 class="panel-title">Album chăm sóc</h2>
    <span class="text-xs text-ink-500">Ảnh nhân viên chụp trong các buổi làm<?= $album ? ' · ' . count($album) . ' ảnh' : '' ?></span>
  </div>
  <div class="p-4 sm:p-5">
    <?= \Core\View::partial('partials/photo_gallery', [
        'photos' => $album, 'id' => 'pet-album', 'cols' => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4', 'showDate' => true,
        'empty' => 'Sau mỗi buổi chăm sóc, ảnh nhân viên chụp sẽ được lưu ở đây.',
    ]) ?>
  </div>
</section>

<section class="panel mt-6">
  <div class="panel-head"><h2 class="panel-title">Các lần chăm sóc</h2></div>
  <?php if (!$bookings): ?>
    <p class="px-5 py-6 text-sm text-ink-500"><?= e($pet['name']) ?> chưa có lịch hẹn nào.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead><tr><th>Mã</th><th>Ngày</th><th>Dịch vụ</th><th>Nhân viên</th><th>Trạng thái</th><th class="text-right">Tổng</th></tr></thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><a class="link" href="<?= url('/bookings/' . $b['id']) ?>"><?= booking_code($b['id']) ?></a></td>
              <td class="tabular-nums"><?= e(vn_date($b['booking_date'])) ?>, <?= e(hm($b['start_time'])) ?></td>
              <td><?= e($b['combo_name'] ?? 'Dịch vụ lẻ') ?></td>
              <td><?= e($b['staff_name']) ?></td>
              <td><?= status_badge($b['status']) ?></td>
              <td class="text-right tabular-nums"><?= money($b['total_price']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
