<div class="mx-auto max-w-2xl">
  <a href="<?= url('/admin/users') ?>" class="text-sm text-ink-500 hover:text-ink-900">Người dùng</a>
  <h1 class="page-title mt-2">Tạo tài khoản nội bộ</h1>
  <p class="mt-2 text-sm text-ink-600">Hệ thống tạo mật khẩu tạm và hiển thị một lần sau khi lưu. Người dùng phải đổi mật khẩu ở lần đăng nhập đầu tiên. Khách hàng tự đăng ký trên trang chủ.</p>
  <form method="post" action="<?= url('/admin/users') ?>" class="panel mt-6 space-y-5 p-6">
    <?= csrf_field() ?>
    <fieldset>
      <legend class="label">Vai trò</legend>
      <div class="grid gap-3 sm:grid-cols-2">
        <?php foreach (['staff' => ['Nhân viên', 'Đến nhà khách làm dịch vụ, chỉ thấy đơn được giao.'], 'admin' => ['Quản trị viên', 'Điều hành toàn hệ thống, không nhận lịch.']] as $r => [$l, $d]): ?>
          <label class="flex cursor-pointer gap-3 rounded-xl border border-ink-200 p-4 has-[:checked]:border-ink-900 has-[:checked]:ring-1 has-[:checked]:ring-ink-900">
            <input type="radio" name="role" value="<?= $r ?>" class="mt-1" <?= checked(old('role', 'staff') === $r) ?>>
            <span><span class="block font-semibold"><?= $l ?></span><span class="block text-xs text-ink-500"><?= $d ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <div>
      <label class="label" for="name">Họ tên</label>
      <input class="input" id="name" name="name" required value="<?= e(old('name')) ?>">
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
      <div>
        <label class="label" for="email">Email đăng nhập</label>
        <input class="input" id="email" name="email" type="email" required value="<?= e(old('email')) ?>" placeholder="ten@petcare.vn">
      </div>
      <div>
        <label class="label" for="phone">Số điện thoại</label>
        <input class="input" id="phone" name="phone" required value="<?= e(old('phone')) ?>">
      </div>
    </div>
    <div>
      <label class="label" for="address">Địa chỉ</label>
      <input class="input" id="address" name="address" value="<?= e(old('address')) ?>">
    </div>
    <div class="flex gap-3">
      <button class="btn btn-primary">Tạo tài khoản</button>
      <a href="<?= url('/admin/users') ?>" class="btn btn-ghost">Hủy</a>
    </div>
  </form>
</div>
