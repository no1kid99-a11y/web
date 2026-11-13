<?php
/**
 * Form chụp ảnh gửi khách (nhân viên phụ trách hoặc Admin). Xử lý phía trình duyệt: public/assets/js/photos.js
 * @var string      $action     URL nhận ảnh
 * @var string      $listId     id của lưới ảnh để chèn ảnh vừa gửi lên đầu
 * @var string|null $counterId  id của số đếm ảnh
 * @var string|null $deleteBase nút gỡ trên ảnh vừa gửi
 * @var array       $photos     ảnh đã có (để gợi ý mốc tiếp theo)
 */
use App\Models\BookingPhoto;

$keys = array_keys(BookingPhoto::STAGES);
$next = 'arrive';
if (!empty($photos)) {
    $i = array_search($photos[0]['stage'], $keys, true);
    $next = $i === false || $i >= count($keys) - 2 ? $photos[0]['stage'] : $keys[$i + 1];
}
$uid = 'pu' . substr(md5($action), 0, 6);
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="space-y-4"
      data-photo-upload data-list="#<?= e($listId) ?>" <?= !empty($counterId) ? 'data-counter="#' . e($counterId) . '"' : '' ?>
      <?= !empty($deleteBase) ? 'data-delete-base="' . e(url($deleteBase)) . '"' : '' ?>>
  <?= csrf_field() ?>

  <fieldset>
    <legend class="label text-xs">Mốc đang chụp</legend>
    <div class="flex flex-wrap gap-1.5">
      <?php foreach (BookingPhoto::STAGES as $k => $label): ?>
        <label class="cursor-pointer">
          <input type="radio" name="stage" value="<?= e($k) ?>" class="peer sr-only" <?= $k === $next ? 'checked' : '' ?>>
          <span class="inline-block rounded-full border border-ink-200 bg-white px-3 py-1.5 text-xs font-medium text-ink-700 transition peer-checked:border-tag-500 peer-checked:bg-tag-500 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-tag-300"><?= e($label) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div>
    <label for="<?= $uid ?>-file" class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-tag-300 bg-tag-50/60 px-4 py-6 text-center transition hover:border-tag-500 hover:bg-tag-50">
      <span class="grid h-12 w-12 place-items-center rounded-full bg-tag-500 text-white shadow">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      </span>
      <span class="text-sm font-semibold text-ink-900">Chụp ảnh</span>
      <span class="text-xs text-ink-500">Điện thoại: mở camera sau · Máy tính: chọn ảnh có sẵn</span>
    </label>
    <input id="<?= $uid ?>-file" type="file" name="photo" accept="image/*" capture="environment" class="sr-only">
    <button type="button" data-webcam hidden class="mt-2 w-full rounded-xl border border-ink-200 bg-white px-3 py-2 text-xs font-semibold text-ink-700 hover:bg-ink-50">Hoặc chụp bằng webcam của máy này</button>
  </div>

  <div data-webcam-panel hidden class="overflow-hidden rounded-2xl bg-black">
    <video playsinline muted class="aspect-[4/3] w-full object-cover"></video>
    <div class="flex gap-2 p-2">
      <button type="button" data-snap class="btn btn-accent flex-1 py-2">Chụp</button>
      <button type="button" data-webcam-close class="rounded-xl bg-white/10 px-4 text-sm font-semibold text-white hover:bg-white/20">Đóng</button>
    </div>
  </div>

  <div data-preview hidden class="relative overflow-hidden rounded-2xl bg-ink-100">
    <img alt="Ảnh chuẩn bị gửi" class="max-h-72 w-full object-contain">
    <button type="button" data-clear class="absolute right-2 top-2 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-ink-800 shadow hover:bg-white">Chụp lại</button>
  </div>

  <div>
    <label class="label text-xs" for="<?= $uid ?>-caption">Một dòng cho chủ nuôi (không bắt buộc)</label>
    <input class="input" id="<?= $uid ?>-caption" name="caption" maxlength="255" placeholder="Bé hợp tác lắm, lông đã gỡ rối xong">
  </div>

  <p data-msg hidden role="status" aria-live="polite"></p>
  <button type="submit" data-submit data-keep class="btn btn-primary w-full py-2.5">Gửi ảnh cho khách</button>
</form>
