<div class="flex flex-wrap items-end justify-between gap-4">
  <h1 class="page-title">Lịch hẹn của tôi</h1>
  <a href="<?= url('/book') ?>" class="btn btn-accent">Đặt lịch mới</a>
</div>

<nav class="mt-6 flex flex-wrap gap-2" aria-label="Lọc theo trạng thái">
  <a href="<?= url('/bookings') ?>" class="rounded-full px-3.5 py-1.5 text-sm font-medium <?= !$status ? 'bg-ink-900 text-white' : 'bg-white text-ink-700 ring-1 ring-ink-200 hover:bg-ink-50' ?>">Tất cả <span class="opacity-60"><?= (int)$total ?></span></a>
  <?php foreach (\App\Models\Booking::STATUSES as $s): if (empty($counts[$s])) continue; ?>
    <a href="<?= url('/bookings', ['status' => $s]) ?>" class="rounded-full px-3.5 py-1.5 text-sm font-medium <?= $status === $s ? 'bg-ink-900 text-white' : 'bg-white text-ink-700 ring-1 ring-ink-200 hover:bg-ink-50' ?>"><?= e(status_label($s)) ?> <span class="opacity-60"><?= (int)$counts[$s] ?></span></a>
  <?php endforeach; ?>
</nav>

<?php if (!$upcoming && !$past): ?>
  <div class="mt-10 rounded-2xl border-2 border-dashed border-ink-200 p-12 text-center">
    <p class="font-display text-xl font-semibold"><?= $status ? 'Không có lịch hẹn ở trạng thái này' : 'Bạn chưa có lịch hẹn nào' ?></p>
    <p class="mt-1 text-sm text-ink-600">Chọn dịch vụ và khung giờ còn trống, chỉ mất khoảng một phút.</p>
    <a href="<?= url('/book') ?>" class="btn btn-accent mt-5">Đặt lịch chăm sóc</a>
  </div>
<?php endif; ?>

<?php if ($upcoming): ?>
  <section class="mt-8">
    <h2 class="font-display text-xl font-semibold">Sắp tới</h2>
    <div class="panel mt-3 divide-y divide-ink-100 overflow-hidden">
      <?php foreach ($upcoming as $b): ?><?= \Core\View::partial('partials/booking_row_client', ['b' => $b]) ?><?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($past): ?>
  <section class="mt-8">
    <h2 class="font-display text-xl font-semibold">Đã qua</h2>
    <div class="panel mt-3 divide-y divide-ink-100 overflow-hidden">
      <?php foreach ($past as $b): ?><?= \Core\View::partial('partials/booking_row_client', ['b' => $b]) ?><?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
