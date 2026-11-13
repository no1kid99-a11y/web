<?php $editing = $service !== null; $val = fn($k) => old($k, $service[$k] ?? ''); ?>
<div class="mx-auto max-w-2xl">
  <a href="<?= url('/admin/services') ?>" class="text-sm text-ink-500 hover:text-ink-900">Dịch vụ</a>
  <h1 class="page-title mt-2"><?= $editing ? 'Sửa ' . e($service['name']) : 'Thêm dịch vụ' ?></h1>
  <form method="post" action="<?= url($editing ? '/admin/services/' . $service['id'] : '/admin/services') ?>" class="panel mt-6 space-y-5 p-6">
    <?= csrf_field() ?>
    <div><label class="label" for="name">Tên dịch vụ</label><input class="input" id="name" name="name" required maxlength="150" value="<?= e($val('name')) ?>"></div>
    <div><label class="label" for="description">Mô tả</label><textarea class="input" id="description" name="description" rows="3" maxlength="1000"><?= e($val('description')) ?></textarea></div>
    <div class="grid gap-5 sm:grid-cols-2">
      <div><label class="label" for="price">Giá (đồng)</label><input class="input" id="price" name="price" inputmode="numeric" required value="<?= e($val('price')) ?>"></div>
      <div>
        <label class="label" for="duration_minutes">Thời lượng (phút)</label>
        <input class="input" id="duration_minutes" name="duration_minutes" type="number" min="5" max="<?= (int)$limit ?>" required value="<?= e($val('duration_minutes')) ?>">
        <p class="hint">Tối đa <?= (int)$limit ?> phút để vừa một khung giờ sau khi trừ thời gian di chuyển.</p>
      </div>
    </div>
    <div class="flex gap-3"><button class="btn btn-primary"><?= $editing ? 'Lưu dịch vụ' : 'Thêm dịch vụ' ?></button><a href="<?= url('/admin/services') ?>" class="btn btn-ghost">Hủy</a></div>
  </form>
</div>
