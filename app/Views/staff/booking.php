<?php $hasRecord = (bool)$bookingRecords; ?>
<a href="<?= url('/staff') ?>" class="text-sm text-ink-500 hover:text-ink-900">Lịch làm việc</a>
<div class="mt-2 flex flex-wrap items-center justify-between gap-4">
  <h1 class="page-title">Đơn <?= booking_code($b['id']) ?> · <?= e($b['pet_name']) ?></h1>
  <?= status_badge($b['status']) ?>
</div>
<div class="mt-5"><?= \Core\View::partial('partials/status_tracker', ['b' => $b]) ?></div>

<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
  <div class="space-y-6">
    <section class="panel overflow-hidden">
      <div class="panel-head"><h2 class="panel-title">Thông tin đơn</h2></div>
      <?= \Core\View::partial('partials/booking_summary', ['b' => $b, 'showClient' => true]) ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h2 class="panel-title">Tiêm phòng của <?= e($b['pet_name']) ?></h2></div>
      <?= \Core\View::partial('partials/vaccination_table', ['vaccinations' => $vaccinations]) ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h2 class="panel-title">Lịch sử sức khỏe</h2></div>
      <?= \Core\View::partial('partials/health_timeline', ['records' => $petRecords]) ?>
    </section>
  </div>

  <div class="order-first space-y-6 lg:order-none">
    <section class="panel p-5">
      <h2 class="panel-title">Việc cần làm</h2>
      <?php if ($b['status'] === 'pending'): ?>
        <p class="mt-2 text-sm text-ink-600">Đơn đang chờ quản trị viên duyệt. Bạn sẽ thấy nút bắt đầu khi đơn được xác nhận.</p>
      <?php elseif ($b['status'] === 'confirmed'): ?>
        <?php if ($isToday): ?>
          <p class="mt-2 text-sm text-ink-600">Khi đến nơi, bấm bắt đầu. Trang theo dõi của khách tự chuyển sang trực tiếp và nhận ảnh bạn chụp.</p>
          <form method="post" action="<?= url('/staff/bookings/' . $b['id'] . '/start') ?>" class="mt-4">
            <?= csrf_field() ?><button class="btn btn-accent w-full py-2.5">Bắt đầu dịch vụ</button>
          </form>
        <?php else: ?>
          <p class="mt-2 text-sm text-ink-600">Nút bắt đầu chỉ mở vào ngày hẹn (<?= e(vn_date($b['booking_date'])) ?>).</p>
        <?php endif; ?>
        <p class="mt-4 text-xs text-ink-500">Khách vắng nhà hoặc thú cưng ốm? Gọi quản trị viên để hủy hoặc dời lịch. Nhân viên không tự hủy đơn.</p>
      <?php elseif ($b['status'] === 'in_progress'): ?>
        <ol class="mt-3 space-y-5 text-sm">
          <li class="flex gap-3">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold <?= $photos ? 'bg-emerald-600 text-white' : 'bg-tag-400 text-ink-900' ?>"><?= $photos ? '✓' : '1' ?></span>
            <div class="min-w-0 flex-1">
              <p class="font-semibold">Chụp ảnh gửi khách <span class="font-normal text-ink-500">(đã gửi <span id="photo-count"><?= count($photos) ?></span>)</span></p>
              <p class="mb-3 mt-0.5 text-xs text-ink-500">Chụp ở từng mốc: lúc đến, đang tắm, sau sấy, sau tỉa. Khách thấy ảnh sau vài giây.</p>
              <?= \Core\View::partial('partials/photo_uploader', [
                  'action' => url('/staff/bookings/' . $b['id'] . '/photos'),
                  'listId' => 'staff-photos', 'counterId' => 'photo-count', 'deleteBase' => '/staff/photos', 'photos' => $photos,
              ]) ?>
            </div>
          </li>
          <li class="flex gap-3">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold <?= $hasRecord ? 'bg-emerald-600 text-white' : 'bg-tag-400 text-ink-900' ?>"><?= $hasRecord ? '✓' : '2' ?></span>
            <div class="flex-1">
              <p class="font-semibold">Ghi sổ sức khỏe</p>
              <form method="post" action="<?= url('/staff/bookings/' . $b['id'] . '/health') ?>" class="mt-2 space-y-3">
                <?= csrf_field() ?>
                <div>
                  <label class="label text-xs" for="weight">Cân nặng hôm nay (kg)</label>
                  <input class="input" id="weight" name="weight" inputmode="decimal" placeholder="<?= e(kg_input($b['pet_weight'])) ?>" value="<?= e(old('weight')) ?>">
                </div>
                <div>
                  <label class="label text-xs" for="note">Tình trạng da lông, tai, móng, ghi chú cho chủ</label>
                  <textarea class="input" id="note" name="note" rows="4" required><?= e(old('note')) ?></textarea>
                </div>
                <button class="btn btn-outline w-full"><?= $hasRecord ? 'Thêm ghi chú' : 'Lưu sổ sức khỏe' ?></button>
              </form>
            </div>
          </li>
          <li class="flex gap-3">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-ink-100 text-xs font-bold">3</span>
            <div class="flex-1">
              <p class="font-semibold">Hoàn thành và nhận thanh toán <?= money($b['total_price']) ?></p>
              <form method="post" action="<?= url('/staff/bookings/' . $b['id'] . '/complete') ?>" class="mt-2" data-confirm="Xác nhận đã làm xong và nhận thanh toán?">
                <?= csrf_field() ?><button class="btn btn-primary w-full" <?= $hasRecord ? '' : 'disabled title="Hãy ghi sổ sức khỏe trước"' ?>>Hoàn thành đơn</button>
              </form>
            </div>
          </li>
        </ol>
      <?php elseif ($b['status'] === 'completed'): ?>
        <p class="mt-2 text-sm text-ink-600">Đơn đã hoàn thành<?= $b['review_rating'] ? ', khách chấm ' . (int)$b['review_rating'] . '/5 sao' : '' ?>.</p>
      <?php else: ?>
        <p class="mt-2 text-sm text-ink-600">Đơn đã hủy. Lý do: <?= e($b['cancel_reason']) ?></p>
      <?php endif; ?>
    </section>

    <?php if ($photos || $b['status'] === 'in_progress'): ?>
      <section class="panel" id="anh">
        <div class="panel-head"><h2 class="panel-title">Ảnh đã gửi khách</h2><span class="text-xs text-ink-500">Bấm ảnh để xem lớn</span></div>
        <div class="p-4">
          <?= \Core\View::partial('partials/photo_gallery', [
              'photos' => $photos, 'id' => 'staff-photos', 'cols' => 'grid-cols-2',
              'deleteBase' => $b['status'] === 'in_progress' ? '/staff/photos' : null,
              'empty' => 'Chưa gửi ảnh nào. Ảnh đầu tiên nên chụp lúc mới đến để khách yên tâm.',
          ]) ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($bookingRecords): ?>
      <section class="panel">
        <div class="panel-head"><h2 class="panel-title">Đã ghi cho đơn này</h2></div>
        <?= \Core\View::partial('partials/health_timeline', ['records' => $bookingRecords]) ?>
      </section>
    <?php endif; ?>
  </div>
</div>
