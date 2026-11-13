<div class="mx-auto grid max-w-5xl overflow-hidden rounded-[2rem] border border-ink-100 bg-white shadow-xl shadow-ink-900/5 md:grid-cols-[1fr_1.15fr]">
<div class="relative hidden md:block">
  <?= photo_img('puppy', 'absolute inset-0 h-full w-full object-cover', 'Chú chó con', 'dog', 800) ?>
  <div class="absolute inset-0 bg-gradient-to-t from-ink-900/80 via-transparent to-transparent"></div>
  <ul class="absolute inset-x-6 bottom-6 space-y-2 text-sm text-white">
    <li class="rounded-xl bg-black/25 px-3 py-2 backdrop-blur">Chọn khung giờ còn trống, không lo trùng lịch</li>
    <li class="rounded-xl bg-black/25 px-3 py-2 backdrop-blur">Nhận ảnh của bé trong lúc nhân viên làm</li>
    <li class="rounded-xl bg-black/25 px-3 py-2 backdrop-blur">Trả tiền sau khi làm xong</li>
  </ul>
</div>
<div class="p-8 sm:p-10">
  <p class="eyebrow">Dành cho chủ thú cưng</p>
  <h1 class="page-title mt-2">Tạo tài khoản</h1>
  <p class="mt-2 text-sm text-ink-600">Dành cho chủ thú cưng. Tài khoản nhân viên do cửa hàng cấp.</p>
  <form method="post" action="<?= url('/register') ?>" class="mt-8 space-y-5">
    <?= csrf_field() ?>
    <div>
      <label class="label" for="name">Họ tên</label>
      <input class="input" id="name" name="name" required maxlength="100" value="<?= e(old('name')) ?>" autocomplete="name">
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
      <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" name="email" type="email" required value="<?= e(old('email', $_GET['email'] ?? '')) ?>" autocomplete="email">
      </div>
      <div>
        <label class="label" for="phone">Số điện thoại</label>
        <input class="input" id="phone" name="phone" required value="<?= e(old('phone')) ?>" autocomplete="tel" placeholder="09xx xxx xxx">
      </div>
    </div>
    <div>
      <label class="label" for="address">Địa chỉ nhận dịch vụ</label>
      <input class="input" id="address" name="address" maxlength="255" value="<?= e(old('address')) ?>" autocomplete="street-address" placeholder="Số nhà, đường, quận">
      <p class="hint">Dùng làm địa chỉ mặc định khi đặt lịch, có thể đổi ở từng đơn.</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
      <div>
        <label class="label" for="password">Mật khẩu</label>
        <input class="input" id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
        <p class="hint">Ít nhất 8 ký tự.</p>
      </div>
      <div>
        <label class="label" for="password_confirmation">Nhập lại mật khẩu</label>
        <input class="input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
      </div>
    </div>
    <button class="btn btn-accent w-full py-3 text-base">Tạo tài khoản</button>
  </form>
  <p class="mt-6 text-sm text-ink-600">Đã có tài khoản? <a class="link" href="<?= url('/login') ?>">Đăng nhập</a></p>
</div>
</div>
