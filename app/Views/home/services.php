<div class="max-w-3xl">
  <h1 class="page-title">Dịch vụ và bảng giá</h1>
  <p class="mt-3 text-ink-600">Mỗi lần đặt, bạn chọn một combo hoặc vài dịch vụ lẻ. Tổng thời gian làm cộng <?= (int)$travel ?> phút di chuyển phải nằm gọn trong một khung giờ 2 tiếng.</p>
</div>

<h2 class="mt-10 font-display text-2xl font-semibold">Combo</h2>
<div class="mt-4 panel overflow-x-auto">
  <table class="tbl">
    <thead><tr><th>Combo</th><th>Gồm</th><th class="text-right">Thời gian</th><th class="text-right">Giá lẻ</th><th class="text-right">Giá combo</th></tr></thead>
    <tbody>
      <?php foreach ($combos as $c): ?>
        <tr>
          <td><p class="font-semibold"><?= e($c['name']) ?></p><p class="text-xs text-ink-500"><?= e($c['description']) ?></p></td>
          <td><?= e(implode(', ', array_column($c['services'], 'name'))) ?></td>
          <td class="text-right tabular-nums"><?= (int)$c['duration'] ?> phút</td>
          <td class="text-right tabular-nums text-ink-400 line-through"><?= money($c['retail_total']) ?></td>
          <td class="text-right font-semibold tabular-nums"><?= money($c['price']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<h2 class="mt-10 font-display text-2xl font-semibold">Dịch vụ lẻ</h2>
<div class="mt-4 panel overflow-x-auto">
  <table class="tbl">
    <thead><tr><th>Dịch vụ</th><th>Mô tả</th><th class="text-right">Thời gian</th><th class="text-right">Giá</th></tr></thead>
    <tbody>
      <?php foreach ($services as $s): ?>
        <tr>
          <td class="font-semibold"><?= e($s['name']) ?></td>
          <td class="text-ink-600"><?= e($s['description']) ?></td>
          <td class="text-right tabular-nums"><?= (int)$s['duration_minutes'] ?> phút</td>
          <td class="text-right font-semibold tabular-nums"><?= money($s['price']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="mt-8">
  <a href="<?= url(\Core\Auth::check() ? '/book' : '/register') ?>" class="btn btn-accent">Đặt lịch chăm sóc</a>
</div>
