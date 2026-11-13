<?php
$editing = $combo !== null;
$val = fn($k) => old($k, $combo[$k] ?? '');
$sel = array_map('intval', (array)old('service_ids', $selected));
$svcJson = array_map(fn($s) => ['id' => (int)$s['id'], 'price' => (int)$s['price'], 'duration' => (int)$s['duration_minutes']], $services);
?>
<div class="mx-auto max-w-2xl" x-data="{ sel: <?= e(json_encode($sel)) ?>, svc: <?= e(json_encode($svcJson)) ?>, price: '<?= e($val('price')) ?>',
  get retail() { return this.svc.filter(s => this.sel.includes(s.id)).reduce((a, s) => a + s.price, 0) },
  get duration() { return this.svc.filter(s => this.sel.includes(s.id)).reduce((a, s) => a + s.duration, 0) } }">
  <a href="<?= url('/admin/combos') ?>" class="text-sm text-ink-500 hover:text-ink-900">Combo</a>
  <h1 class="page-title mt-2"><?= $editing ? 'Sửa ' . e($combo['name']) : 'Thêm combo' ?></h1>
  <form method="post" action="<?= url($editing ? '/admin/combos/' . $combo['id'] : '/admin/combos') ?>" class="panel mt-6 space-y-5 p-6">
    <?= csrf_field() ?>
    <div><label class="label" for="name">Tên combo</label><input class="input" id="name" name="name" required maxlength="150" value="<?= e($val('name')) ?>"></div>
    <div><label class="label" for="description">Mô tả</label><textarea class="input" id="description" name="description" rows="2" maxlength="1000"><?= e($val('description')) ?></textarea></div>
    <fieldset>
      <legend class="label">Dịch vụ trong combo</legend>
      <div class="grid gap-2 sm:grid-cols-2">
        <?php foreach ($services as $s): ?>
          <label class="flex items-start gap-3 rounded-lg border border-ink-100 p-3 <?= (int)$s['is_active'] ? '' : 'opacity-50' ?>">
            <input type="checkbox" name="service_ids[]" value="<?= (int)$s['id'] ?>" class="mt-1 rounded" x-model.number="sel" <?= (int)$s['is_active'] ? '' : 'disabled' ?>>
            <span class="text-sm"><span class="block font-medium"><?= e($s['name']) ?></span><span class="text-xs text-ink-500"><?= (int)$s['duration_minutes'] ?> phút · <?= money($s['price']) ?><?= (int)$s['is_active'] ? '' : ' · đã ẩn' ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="mt-2 text-sm" :class="duration > <?= (int)$limit ?> ? 'text-red-700' : 'text-ink-600'">
        Tổng <span x-text="duration"></span> / <?= (int)$limit ?> phút · giá lẻ <span x-text="PetCare.money(retail)"></span>
      </p>
    </fieldset>
    <div>
      <label class="label" for="price">Giá combo (đồng)</label>
      <input class="input" id="price" name="price" inputmode="numeric" required x-model="price">
      <p class="hint" x-show="retail > 0 && +price > 0">Khách tiết kiệm <span x-text="PetCare.money(Math.max(0, retail - price))"></span> so với đặt lẻ.</p>
    </div>
    <div class="flex gap-3"><button class="btn btn-primary"><?= $editing ? 'Lưu combo' : 'Thêm combo' ?></button><a href="<?= url('/admin/combos') ?>" class="btn btn-ghost">Hủy</a></div>
  </form>
</div>
