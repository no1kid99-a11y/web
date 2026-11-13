<div class="mx-auto grid max-w-5xl overflow-hidden rounded-[2rem] border border-ink-100 bg-white shadow-xl shadow-ink-900/5 md:grid-cols-2">
  <div class="p-8 sm:p-12">
    <p class="eyebrow">Chào mừng trở lại</p>
    <h1 class="page-title mt-2">Đăng nhập</h1>
    <p class="mt-2 text-sm text-ink-500">Khách hàng, nhân viên và quản trị viên dùng chung trang này.</p>
    <form method="post" action="<?= url('/login') ?>" class="mt-8 space-y-5">
      <?= csrf_field() ?>
      <div>
        <label class="label" for="email">Email</label>
        <input class="input py-3" id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>" autofocus>
      </div>
      <div>
        <label class="label" for="password">Mật khẩu</label>
        <input class="input py-3" id="password" name="password" type="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-accent w-full py-3 text-base">Đăng nhập</button>
    </form>
    <p class="mt-6 text-sm text-ink-600">Chưa có tài khoản? <a class="link" href="<?= url('/register') ?>">Tạo tài khoản khách hàng</a></p>
  </div>
  <div class="relative hidden min-h-[560px] md:block">
    <?= photo_img('login', 'absolute inset-0 h-full w-full object-cover', 'Chó và mèo được chăm sóc tại nhà', 'dog', 900) ?>
    <div class="absolute inset-0 bg-gradient-to-t from-ink-900/85 via-ink-900/25 to-transparent"></div>
    <div class="absolute inset-x-6 bottom-6 rounded-2xl bg-white/12 p-5 text-white ring-1 ring-white/20 backdrop-blur-md">
      <p class="font-bold">Tài khoản dùng thử</p>
      <p class="text-xs text-white/70">Có sẵn sau khi chạy lệnh cài dữ liệu mẫu.</p>
      <dl class="mt-3 grid gap-2 text-[13px]">
        <?php foreach ([['Quản trị viên', 'admin@petcare.vn', 'Admin@123'], ['Nhân viên', 'an.le@petcare.vn', 'Staff@123'], ['Khách hàng', 'ha.do@gmail.com', 'Khach@123']] as [$r, $em, $pw]): ?>
          <div class="flex flex-wrap items-baseline justify-between gap-x-3 rounded-xl bg-black/20 px-3 py-2">
            <dt class="text-white/70"><?= $r ?></dt>
            <dd><span class="font-semibold"><?= $em ?></span> · <span class="text-tag-200"><?= $pw ?></span></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </div>
  </div>
</div>
