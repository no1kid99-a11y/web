<?php
$tab = fn($r) => url('/admin/users', array_filter(['role' => $r, 'status' => $status, 'q' => $q]));
$total = array_sum($counts);
?>
<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="page-title">Người dùng</h1>
    <p class="mt-1 text-sm text-ink-600">Tài khoản không bị xóa, chỉ khóa, để giữ nguyên lịch sử đơn và sổ sức khỏe.</p>
  </div>
  <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary">Tạo tài khoản nhân viên</a>
</div>

<div class="mt-5 flex flex-wrap items-center justify-between gap-3">
  <nav class="flex flex-wrap gap-2" aria-label="Lọc theo vai trò">
    <a href="<?= $tab(null) ?>" class="rounded-full px-3.5 py-1.5 text-sm font-medium <?= !$role ? 'bg-ink-900 text-white' : 'bg-white text-ink-700 ring-1 ring-ink-200 hover:bg-ink-50' ?>">Tất cả <span class="opacity-60"><?= (int)$total ?></span></a>
    <?php foreach (['admin', 'staff', 'client'] as $r): ?>
      <a href="<?= $tab($r) ?>" class="rounded-full px-3.5 py-1.5 text-sm font-medium <?= $role === $r ? 'bg-ink-900 text-white' : 'bg-white text-ink-700 ring-1 ring-ink-200 hover:bg-ink-50' ?>"><?= e(role_label($r)) ?> <span class="opacity-60"><?= (int)($counts[$r] ?? 0) ?></span></a>
    <?php endforeach; ?>
  </nav>
  <form method="get" action="<?= url('/admin/users') ?>" class="flex gap-2">
    <?php if ($role): ?><input type="hidden" name="role" value="<?= e($role) ?>"><?php endif; ?>
    <label class="sr-only" for="ustatus">Trạng thái</label>
    <select class="input w-36" id="ustatus" name="status" onchange="this.form.submit()">
      <option value="">Mọi trạng thái</option>
      <option value="active" <?= selected($status, 'active') ?>>Đang hoạt động</option>
      <option value="locked" <?= selected($status, 'locked') ?>>Đã khóa</option>
    </select>
    <label class="sr-only" for="uq">Tìm</label>
    <input class="input w-56" id="uq" name="q" value="<?= e($q) ?>" placeholder="Tên, email, SĐT">
    <button class="btn btn-outline">Tìm</button>
  </form>
</div>
<p class="mt-2 text-xs text-ink-500">Số đếm trên thẻ chỉ tính tài khoản đang hoạt động.</p>

<div class="panel mt-4 overflow-x-auto">
  <?php if (!$users): ?>
    <p class="px-5 py-10 text-center text-sm text-ink-500">Không tìm thấy tài khoản nào.</p>
  <?php else: ?>
    <table class="tbl">
      <thead><tr><th>Người dùng</th><th>Vai trò</th><th>Số điện thoại</th><th class="text-right">Thú cưng</th><th class="text-right">Đơn</th><th>Đăng nhập gần nhất</th><th>Trạng thái</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr class="hover:bg-ink-50/50 <?= $u['status'] === 'locked' ? 'text-ink-400' : '' ?>">
            <td>
              <a class="font-semibold hover:underline" href="<?= url('/admin/users/' . $u['id']) ?>"><?= e($u['name']) ?></a>
              <p class="text-xs text-ink-500"><?= e($u['email']) ?></p>
            </td>
            <td><span class="chip <?= ['admin' => 'bg-ink-900 text-white', 'staff' => 'bg-tag-100 text-ink-900', 'client' => ''][$u['role']] ?>"><?= e(role_label($u['role'])) ?></span></td>
            <td class="tabular-nums"><?= e($u['phone']) ?></td>
            <td class="text-right tabular-nums"><?= $u['role'] === 'client' ? (int)$u['pet_count'] : '—' ?></td>
            <td class="text-right tabular-nums"><?= $u['role'] === 'admin' ? '—' : (int)$u['booking_count'] ?></td>
            <td class="text-xs"><?= $u['last_login_at'] ? e(vn_datetime($u['last_login_at'])) : 'Chưa đăng nhập' ?></td>
            <td>
              <?php if ($u['status'] === 'locked'): ?><span class="chip bg-red-100 text-red-800">Đã khóa</span>
              <?php elseif ((int)$u['must_change_password']): ?><span class="chip bg-amber-100 text-amber-800">Chờ đổi mật khẩu</span>
              <?php else: ?><span class="chip bg-emerald-100 text-emerald-800">Hoạt động</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
