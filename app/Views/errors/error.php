<?php
$titles = [403 => 'Không có quyền truy cập', 404 => 'Không tìm thấy trang', 405 => 'Thao tác không hợp lệ', 419 => 'Phiên làm việc hết hạn', 500 => 'Lỗi hệ thống'];
?>
<div class="mx-auto max-w-lg py-16 text-center">
  <p class="font-display text-7xl font-bold text-tag-400"><?= (int)$status ?></p>
  <h1 class="mt-4 font-display text-3xl font-bold"><?= e($titles[$status] ?? 'Có lỗi xảy ra') ?></h1>
  <p class="mt-3 text-ink-600"><?= e($message) ?></p>
  <div class="mt-8 flex justify-center gap-3">
    <a href="<?= url(\Core\Auth::home()) ?>" class="btn btn-primary">Về trang chính</a>
    <a href="javascript:history.back()" class="btn btn-outline">Quay lại</a>
  </div>
</div>
