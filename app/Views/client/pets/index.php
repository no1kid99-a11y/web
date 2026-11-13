<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="page-title">Thú cưng của tôi</h1>
    <p class="mt-2 text-ink-600">Hồ sơ giúp nhân viên chuẩn bị đúng dụng cụ và ghi sổ sức khỏe sau mỗi lần chăm sóc.</p>
  </div>
  <a href="<?= url('/pets/create') ?>" class="btn btn-primary">Thêm thú cưng</a>
</div>

<?php if (!$pets): ?>
  <div class="mt-10 rounded-2xl border-2 border-dashed border-ink-200 p-12 text-center">
    <p class="text-4xl">🐾</p>
    <p class="mt-3 font-display text-xl font-semibold">Chưa có hồ sơ thú cưng</p>
    <p class="mt-1 text-sm text-ink-600">Thêm bé đầu tiên để bắt đầu đặt lịch.</p>
    <a href="<?= url('/pets/create') ?>" class="btn btn-accent mt-5">Thêm thú cưng</a>
  </div>
<?php else: ?>
  <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($pets as $p): ?>
      <a href="<?= url('/pets/' . $p['id']) ?>" class="group rounded-2xl border border-ink-100 bg-white p-5 hover:border-ink-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-tag-400">
        <div class="flex items-center gap-4">
          <?= \Core\View::partial('partials/pet_avatar', ['photo' => $p['photo'], 'species' => $p['species'], 'size' => 'h-16 w-16 text-3xl']) ?>
          <div class="min-w-0">
            <p class="font-display text-xl font-semibold group-hover:underline"><?= e($p['name']) ?></p>
            <p class="text-sm text-ink-600"><?= e(species_label($p['species'])) ?><?= $p['breed'] ? ' · ' . e($p['breed']) : '' ?></p>
          </div>
        </div>
        <dl class="mt-5 grid grid-cols-3 gap-2 border-t border-ink-100 pt-4 text-sm">
          <div><dt class="text-xs text-ink-500">Tuổi</dt><dd class="font-semibold"><?= $p['age'] !== null ? (int)$p['age'] . ' tuổi' : '—' ?></dd></div>
          <div><dt class="text-xs text-ink-500">Cân nặng</dt><dd class="font-semibold"><?= e(kg($p['weight'])) ?></dd></div>
          <div><dt class="text-xs text-ink-500">Lần gần nhất</dt><dd class="font-semibold"><?= $p['last_care'] ? e(vn_date($p['last_care'])) : '—' ?></dd></div>
        </dl>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
