<h1 class="page-title">Khung giờ</h1>
<p class="mt-2 max-w-3xl text-ink-600">Mỗi đơn chiếm trọn một khung, và mỗi nhân viên chỉ nhận một đơn trong một khung. Tổng thời gian dịch vụ cộng <?= (int)$travel ?> phút di chuyển phải vừa độ dài khung.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
  <div class="panel overflow-x-auto">
    <table class="tbl">
      <thead><tr><th>Khung</th><th class="text-right">Độ dài</th><th class="text-right">Đơn sắp tới</th><th>Trạng thái</th><th>Sửa giờ</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($slots as $s): $locked = $s['future'] > 0; ?>
          <tr class="<?= (int)$s['is_active'] ? '' : 'text-ink-400' ?>">
            <td class="font-display text-lg font-semibold tabular-nums"><?= e(\App\Models\TimeSlot::label($s)) ?></td>
            <td class="text-right tabular-nums"><?= \App\Models\TimeSlot::minutes($s) ?> phút</td>
            <td class="text-right tabular-nums"><?= (int)$s['future'] ?></td>
            <td><?= (int)$s['is_active'] ? '<span class="chip bg-emerald-100 text-emerald-800">Nhận lịch</span>' : '<span class="chip">Đã ẩn</span>' ?></td>
            <td>
              <?php if ($locked): ?>
                <span class="text-xs text-ink-500">Khóa vì còn đơn</span>
              <?php else: ?>
                <form method="post" action="<?= url('/admin/slots/' . $s['id']) ?>" class="flex items-center gap-1">
                  <?= csrf_field() ?>
                  <label class="sr-only" for="s<?= $s['id'] ?>a">Bắt đầu</label>
                  <input class="input w-[6.5rem] px-2 py-1" id="s<?= $s['id'] ?>a" type="time" name="start_time" value="<?= e(hm($s['start_time'])) ?>">
                  <span>–</span>
                  <label class="sr-only" for="s<?= $s['id'] ?>b">Kết thúc</label>
                  <input class="input w-[6.5rem] px-2 py-1" id="s<?= $s['id'] ?>b" type="time" name="end_time" value="<?= e(hm($s['end_time'])) ?>">
                  <button class="btn btn-ghost btn-sm">Lưu</button>
                </form>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <form method="post" action="<?= url('/admin/slots/' . $s['id'] . '/toggle') ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" <?= (int)$s['is_active'] && $locked ? 'disabled title="Còn đơn giữ chỗ"' : '' ?>><?= (int)$s['is_active'] ? 'Ẩn' : 'Bật lại' ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <form method="post" action="<?= url('/admin/slots') ?>" class="panel h-fit space-y-4 p-5">
    <?= csrf_field() ?>
    <h2 class="panel-title">Thêm khung giờ</h2>
    <div class="grid grid-cols-2 gap-3">
      <div><label class="label" for="start_time">Bắt đầu</label><input class="input" id="start_time" name="start_time" type="time" required></div>
      <div><label class="label" for="end_time">Kết thúc</label><input class="input" id="end_time" name="end_time" type="time" required></div>
    </div>
    <p class="hint">Không được chồng lên khung đã có. Nên dài 120 phút như các khung hiện tại.</p>
    <button class="btn btn-primary w-full">Thêm khung</button>
  </form>
</div>
