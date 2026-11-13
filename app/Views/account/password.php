<div class="mx-auto max-w-lg">
  <h1 class="page-title">Đổi mật khẩu</h1>
  <?php if ($forced): ?>
    <p class="mt-3 rounded-lg border border-tag-300 bg-tag-50 px-4 py-3 text-sm">Bạn đang dùng mật khẩu tạm do quản trị viên cấp. Hãy đặt mật khẩu riêng để tiếp tục sử dụng hệ thống.</p>
  <?php endif; ?>
  <form method="post" action="<?= url('/password') ?>" class="panel mt-6 space-y-5 p-6">
    <?= csrf_field() ?>
    <div>
      <label class="label" for="current_password"><?= $forced ? 'Mật khẩu tạm' : 'Mật khẩu hiện tại' ?></label>
      <input class="input" id="current_password" name="current_password" type="password" required autocomplete="current-password">
    </div>
    <div>
      <label class="label" for="password">Mật khẩu mới</label>
      <input class="input" id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
      <p class="hint">Ít nhất 8 ký tự, khác mật khẩu hiện tại.</p>
    </div>
    <div>
      <label class="label" for="password_confirmation">Nhập lại mật khẩu mới</label>
      <input class="input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
    </div>
    <button class="btn btn-primary">Lưu mật khẩu mới</button>
  </form>
</div>
