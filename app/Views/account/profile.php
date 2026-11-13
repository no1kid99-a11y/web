<?php $isClient = $u['role'] === 'client'; ?>
<div class="mx-auto max-w-2xl">
  <h1 class="page-title">Thông tin cá nhân</h1>
  <p class="mt-2 text-sm text-ink-600">
    <?= e(role_label($u['role'])) ?> · tham gia từ <?= e(vn_date($u['created_at'])) ?>
    <?php if ($u['last_login_at']): ?> · đăng nhập gần nhất <?= e(vn_datetime($u['last_login_at'])) ?><?php endif; ?>
  </p>
  <form method="post" action="<?= url('/profile') ?>" class="panel mt-6 space-y-5 p-6">
    <?= csrf_field() ?>
    <div>
      <label class="label" for="name">Họ tên</label>
      <input class="input disabled:bg-ink-50 disabled:text-ink-500" id="name" name="name" required value="<?= e(old('name', $u['name'])) ?>" <?= $isClient ? '' : 'readonly' ?>>
      <?php if (!$isClient): ?><p class="hint">Họ tên và email của tài khoản nội bộ do quản trị viên cập nhật.</p><?php endif; ?>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
      <div>
        <label class="label" for="email">Email</label>
        <?php if ($isClient): ?>
          <input class="input" id="email" name="email" type="email" required value="<?= e(old('email', $u['email'])) ?>">
        <?php else: ?>
          <input class="input bg-ink-50 text-ink-500" id="email" value="<?= e($u['email']) ?>" readonly>
        <?php endif; ?>
      </div>
      <div>
        <label class="label" for="phone">Số điện thoại</label>
        <input class="input" id="phone" name="phone" required value="<?= e(old('phone', $u['phone'])) ?>">
      </div>
    </div>
    <div>
      <label class="label" for="address">Địa chỉ</label>
      <input class="input" id="address" name="address" value="<?= e(old('address', $u['address'])) ?>">
    </div>
    <button class="btn btn-primary">Lưu thay đổi</button>
  </form>
</div>
