<a href="<?= url('/bookings') ?>" class="text-sm text-ink-500 hover:text-ink-900">Lịch hẹn của tôi</a>
<div class="mt-2 flex flex-wrap items-center justify-between gap-4">
  <h1 class="page-title">Lịch hẹn <?= booking_code($b['id']) ?></h1>
  <?php if ($b['status'] === 'in_progress'): ?>
    <a href="<?= url('/bookings/' . $b['id'] . '/camera') ?>" class="btn btn-accent">
      <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-600"></span></span>
      Theo dõi trực tiếp
    </a>
  <?php elseif ($b['status'] === 'confirmed'): ?>
    <a href="<?= url('/bookings/' . $b['id'] . '/camera') ?>" class="btn btn-outline">Mở trang theo dõi</a>
  <?php endif; ?>
</div>

<div class="mt-6"><?= \Core\View::partial('partials/status_tracker', ['b' => $b]) ?></div>

<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
  <div class="space-y-6">
    <section class="panel overflow-hidden">
      <div class="panel-head"><h2 class="panel-title">Chi tiết</h2><span class="text-xs text-ink-500">Đặt lúc <?= e(vn_datetime($b['created_at'])) ?></span></div>
      <?= \Core\View::partial('partials/booking_summary', ['b' => $b]) ?>
    </section>

    <?php if ($photos): ?>
      <section class="panel" id="anh">
        <div class="panel-head">
          <h2 class="panel-title">Ảnh trong buổi chăm sóc</h2>
          <span class="text-xs text-ink-500"><?= count($photos) ?> ảnh do <?= e($b['staff_name']) ?> chụp</span>
        </div>
        <div class="p-4 sm:p-5"><?= \Core\View::partial('partials/photo_gallery', ['photos' => $photos, 'id' => 'booking-photos']) ?></div>
      </section>
    <?php elseif ($b['status'] === 'in_progress'): ?>
      <section class="panel p-5 text-sm text-ink-600">
        Nhân viên đang chăm sóc bé. Mở <a class="link" href="<?= url('/bookings/' . $b['id'] . '/camera') ?>">Theo dõi trực tiếp</a> để thấy ảnh ngay khi được chụp.
      </section>
    <?php endif; ?>

    <?php if ($records): ?>
      <section class="panel">
        <div class="panel-head"><h2 class="panel-title">Ghi chú sức khỏe lần này</h2></div>
        <?= \Core\View::partial('partials/health_timeline', ['records' => $records]) ?>
      </section>
    <?php endif; ?>
  </div>

  <div class="space-y-6">
    <?php if ($b['status'] === 'completed'): ?>
      <section class="panel p-5">
        <h2 class="panel-title">Đánh giá dịch vụ</h2>
        <?php if ($review): ?>
          <div class="mt-3 text-2xl text-tag-500" aria-label="<?= (int)$review['rating'] ?> trên 5 sao"><?= str_repeat('★', (int)$review['rating']) ?><span class="text-ink-200"><?= str_repeat('★', 5 - (int)$review['rating']) ?></span></div>
          <?php if ($review['comment']): ?><p class="mt-2 text-sm text-ink-700"><?= nl2br(e($review['comment'])) ?></p><?php endif; ?>
          <p class="mt-3 text-xs text-ink-500">Gửi lúc <?= e(vn_datetime($review['created_at'])) ?></p>
        <?php else: ?>
          <form method="post" action="<?= url('/bookings/' . $b['id'] . '/review') ?>" class="mt-3 space-y-4" x-data="{r: 5, hover: 0}">
            <?= csrf_field() ?>
            <fieldset>
              <legend class="label">Bạn chấm mấy sao?</legend>
              <div class="flex gap-1 text-3xl" @mouseleave="hover = 0">
                <template x-for="i in 5" :key="i">
                  <label class="cursor-pointer" :class="(hover || r) >= i ? 'text-tag-500' : 'text-ink-200'" @mouseenter="hover = i">
                    <input type="radio" name="rating" class="sr-only" :value="i" x-model.number="r"><span x-text="'★'"></span><span class="sr-only" x-text="i + ' sao'"></span>
                  </label>
                </template>
              </div>
            </fieldset>
            <div>
              <label class="label" for="comment">Nhận xét</label>
              <textarea class="input" id="comment" name="comment" rows="3" maxlength="1000" placeholder="Nhân viên có đúng giờ, bé có thoải mái không?"></textarea>
            </div>
            <button class="btn btn-primary w-full">Gửi đánh giá</button>
          </form>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if (in_array($b['status'], ['pending', 'confirmed'], true)): ?>
      <section class="panel p-5">
        <h2 class="panel-title">Hủy lịch hẹn</h2>
        <?php if ($canCancel): ?>
          <form method="post" action="<?= url('/bookings/' . $b['id'] . '/cancel') ?>" class="mt-3 space-y-3" data-confirm="Hủy lịch hẹn <?= booking_code($b['id']) ?>?">
            <?= csrf_field() ?>
            <label class="label" for="reason">Lý do (không bắt buộc)</label>
            <input class="input" id="reason" name="reason" maxlength="255" placeholder="Bận đột xuất...">
            <button class="btn btn-danger-outline w-full">Hủy lịch hẹn</button>
          </form>
        <?php else: ?>
          <p class="mt-2 text-sm text-ink-600">Đã quá thời hạn hủy online (trước giờ hẹn 2 giờ). Vui lòng gọi hotline 1900 6868 nếu cần thay đổi.</p>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="rounded-xl bg-ink-100 p-5 text-sm text-ink-700">
      <p class="font-semibold text-ink-900">Cần hỗ trợ?</p>
      <p class="mt-1">Gọi 1900 6868 để đổi giờ hoặc báo thông tin đặc biệt về bé. Việc dời lịch do cửa hàng thực hiện để đảm bảo không trùng lịch nhân viên.</p>
    </section>
  </div>
</div>
