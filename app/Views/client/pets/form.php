<?php
$editing = $pet !== null;
$val = fn($k) => old($k, $pet[$k] ?? '');
?>
<div class="mx-auto max-w-2xl">
  <a href="<?= url($editing ? '/pets/' . $pet['id'] : '/pets') ?>" class="text-sm text-ink-500 hover:text-ink-900">Quay lại</a>
  <h1 class="page-title mt-2"><?= $editing ? 'Sửa hồ sơ ' . e($pet['name']) : 'Thêm thú cưng' ?></h1>
  <form method="post" enctype="multipart/form-data" action="<?= url($editing ? '/pets/' . $pet['id'] : '/pets') ?>" class="panel mt-6 space-y-5 p-6" x-data="{preview: null}">
    <?= csrf_field() ?>
    <div class="flex items-center gap-5">
      <template x-if="preview"><img :src="preview" alt="" class="h-20 w-20 rounded-2xl object-cover"></template>
      <template x-if="!preview"><span><?= \Core\View::partial('partials/pet_avatar', ['photo' => $pet['photo'] ?? null, 'species' => $pet['species'] ?? 'other', 'size' => 'h-20 w-20 text-4xl']) ?></span></template>
      <div>
        <label class="label" for="photo">Ảnh đại diện</label>
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-ink-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-ink-200"
               @change="const f=$event.target.files[0]; preview = f ? URL.createObjectURL(f) : null">
        <p class="hint">JPG, PNG hoặc WEBP, tối đa 2 MB.</p>
        <?php if ($editing && $pet['photo']): ?>
          <label class="mt-2 flex items-center gap-2 text-sm text-ink-600"><input type="checkbox" name="remove_photo" value="1" class="rounded"> Xóa ảnh hiện tại</label>
        <?php endif; ?>
      </div>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
      <div>
        <label class="label" for="name">Tên</label>
        <input class="input" id="name" name="name" required maxlength="100" value="<?= e($val('name')) ?>">
      </div>
      <div>
        <label class="label" for="species">Loài</label>
        <select class="input" id="species" name="species" required>
          <?php foreach (['dog' => 'Chó', 'cat' => 'Mèo', 'other' => 'Khác'] as $k => $l): ?>
            <option value="<?= $k ?>" <?= selected($val('species'), $k) ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="grid gap-5 sm:grid-cols-3">
      <div>
        <label class="label" for="breed">Giống</label>
        <input class="input" id="breed" name="breed" maxlength="100" value="<?= e($val('breed')) ?>" placeholder="Poodle, Mèo Anh...">
      </div>
      <div>
        <label class="label" for="age">Tuổi</label>
        <input class="input" id="age" name="age" type="number" min="0" max="40" value="<?= e($val('age')) ?>">
      </div>
      <div>
        <label class="label" for="weight">Cân nặng (kg)</label>
        <input class="input" id="weight" name="weight" inputmode="decimal" value="<?= e(kg_input($val('weight'))) ?>" placeholder="5.2">
      </div>
    </div>
    <div class="flex gap-3">
      <button class="btn btn-primary"><?= $editing ? 'Lưu hồ sơ' : 'Thêm thú cưng' ?></button>
      <a href="<?= url($editing ? '/pets/' . $pet['id'] : '/pets') ?>" class="btn btn-ghost">Hủy</a>
    </div>
  </form>
</div>
