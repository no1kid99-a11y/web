<?php
$id = (int)$u['id'];
$locked = $u['status'] === 'locked';
$internal = in_array($u['role'], ['staff', 'admin'], true);
?>
<a href="<?= url('/admin/users') ?>" class="text-sm text-ink-500 hover:text-ink-900">Người dùng</a>
<div class="mt-2 flex flex-wrap items-center gap-4">
  <span class="grid h-14 w-14 place-items-center rounded-2xl <?= $locked ? 'bg-ink-100 text-ink-400' : 'bg-tag-400 text-ink-900' ?> font-display text-2xl font-bold"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
  <div class="flex-1">
    <h1 class="page-title"><?= e($u['name']) ?><?= $isSelf ? ' <span class="text-base font-medium text-ink-500">(bạn)</span>' : '' ?></h1>
    <p class="text-sm text-ink-600">
      <?= e(role_label($u['role'])) ?> · tạo ngày <?= e(vn_date($u['created_at'])) ?><?= isset($creator) && $creator ? ' bởi ' . e($creator['name']) : '' ?>
      · <?= $u['last_login_at'] ? 'đăng nhập gần nhất ' . e(vn_datetime($u['last_login_at'])) : 'chưa đăng nhập' ?>
    </p>
  </div>
  <?php if ($locked): ?><span class="chip bg-red-100 text-red-800">Đã khóa</span><?php elseif ((int)$u['must_change_password']): ?><span class="chip bg-amber-100 text-amber-800">Chờ đổi mật khẩu tạm</span><?php else: ?><span class="chip bg-emerald-100 text-emerald-800">Hoạt động</span><?php endif; ?>
</div>

<?php if ($temp): ?>
  <div class="mt-6 rounded-xl border-2 border-tag-400 bg-tag-50 p-5" x-data="{copied:false}">
    <p class="font-semibold">Mật khẩu tạm (chỉ hiển thị một lần)</p>
    <div class="mt-2 flex flex-wrap items-center gap-3">
      <code class="rounded-lg bg-white px-4 py-2 font-mono text-lg tracking-wider ring-1 ring-tag-300"><?= e($temp) ?></code>
      <button type="button" class="btn btn-outline btn-sm" @click="navigator.clipboard.writeText('<?= e($temp) ?>'); copied=true" x-text="copied ? 'Đã sao chép' : 'Sao chép'"></button>
    </div>
    <p class="mt-2 text-sm text-ink-700">Gửi cho <?= e($u['name']) ?> qua kênh riêng. Hệ thống không lưu mật khẩu dạng đọc được nên sẽ không xem lại được.</p>
  </div>
<?php endif; ?>

<div class="mt-6 grid gap-6 xl:grid-cols-[1fr_380px]">
  <div class="space-y-6">
    <form method="post" action="<?= url("/admin/users/$id") ?>" class="panel">
      <?= csrf_field() ?>
      <div class="panel-head"><h2 class="panel-title">Thông tin tài khoản</h2></div>
      <div class="grid gap-5 p-5 sm:grid-cols-2">
        <div><label class="label" for="name">Họ tên</label><input class="input" id="name" name="name" required value="<?= e(old('name', $u['name'])) ?>"></div>
        <div><label class="label" for="email">Email</label><input class="input" id="email" name="email" type="email" required value="<?= e(old('email', $u['email'])) ?>"></div>
        <div><label class="label" for="phone">Số điện thoại</label><input class="input" id="phone" name="phone" required value="<?= e(old('phone', $u['phone'])) ?>"></div>
        <div><label class="label" for="address">Địa chỉ</label><input class="input" id="address" name="address" value="<?= e(old('address', $u['address'])) ?>"></div>
      </div>
      <div class="border-t border-ink-100 px-5 py-3"><button class="btn btn-primary">Lưu thay đổi</button></div>
    </form>

    <?php if ($u['role'] === 'client'): ?>
      <section class="panel overflow-hidden">
        <div class="panel-head"><h2 class="panel-title">Thú cưng</h2></div>
        <?php if (!$pets): ?><p class="px-5 py-6 text-sm text-ink-500">Chưa có thú cưng.</p><?php else: ?>
          <ul class="grid gap-3 p-5 sm:grid-cols-2">
            <?php foreach ($pets as $p): ?>
              <li><a href="<?= url('/admin/pets/' . $p['id']) ?>" class="flex items-center gap-3 rounded-xl border border-ink-100 p-3 hover:border-ink-300 <?= $p['is_active'] ? '' : 'opacity-50' ?>">
                <?= \Core\View::partial('partials/pet_avatar', ['photo' => $p['photo'], 'species' => $p['species'], 'size' => 'h-10 w-10 text-xl']) ?>
                <span><span class="block font-semibold"><?= e($p['name']) ?><?= $p['is_active'] ? '' : ' (đã xóa)' ?></span><span class="text-xs text-ink-500"><?= e(species_label($p['species'])) ?> · <?= e(kg($p['weight'])) ?></span></span>
              </a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if (isset($bookings)): ?>
      <section class="panel overflow-hidden">
        <div class="panel-head"><h2 class="panel-title"><?= $u['role'] === 'staff' ? 'Đơn đang giữ lịch' : 'Lịch sử đơn' ?></h2><span class="text-xs text-ink-500"><?= count($bookings) ?> đơn</span></div>
        <?php if (!$bookings): ?><p class="px-5 py-6 text-sm text-ink-500">Không có đơn nào.</p><?php else: ?>
          <div class="overflow-x-auto">
            <table class="tbl">
              <thead><tr><th>Mã</th><th>Ngày giờ</th><th><?= $u['role'] === 'staff' ? 'Khách' : 'Nhân viên' ?></th><th>Thú cưng</th><th>Trạng thái</th><th class="text-right">Tổng</th></tr></thead>
              <tbody>
                <?php foreach ($bookings as $b): ?>
                  <tr>
                    <td><a class="link" href="<?= url('/admin/bookings/' . $b['id']) ?>"><?= booking_code($b['id']) ?></a></td>
                    <td class="tabular-nums"><?= e(vn_date($b['booking_date'])) ?> <?= e(hm($b['start_time'])) ?></td>
                    <td><?= e($u['role'] === 'staff' ? $b['client_name'] : $b['staff_name']) ?></td>
                    <td><?= e($b['pet_name']) ?></td>
                    <td><?= status_badge($b['status']) ?></td>
                    <td class="text-right tabular-nums"><?= money($b['total_price']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

  </div>

  <div class="space-y-6">
    <section class="panel p-5">
      <h2 class="panel-title">Thao tác tài khoản</h2>
      <?php if ($isSelf): ?>
        <p class="mt-2 text-sm text-ink-600">Bạn không thể tự khóa hoặc tự đổi vai trò của mình. Đổi mật khẩu tại <a class="link" href="<?= url('/password') ?>">trang đổi mật khẩu</a>.</p>
      <?php else: ?>
        <div class="mt-4 space-y-5">
          <div>
            <?php if ($locked): ?>
              <form method="post" action="<?= url("/admin/users/$id/unlock") ?>"><?= csrf_field() ?><button class="btn btn-outline w-full">Mở khóa tài khoản</button></form>
            <?php else: ?>
              <form method="post" action="<?= url("/admin/users/$id/lock") ?>" data-confirm="Khóa tài khoản <?= e($u['name']) ?>?<?= $u['role'] === 'client' ? ' Các đơn Chờ duyệt và Đã xác nhận của khách sẽ tự hủy.' : '' ?>">
                <?= csrf_field() ?><button class="btn btn-danger-outline w-full">Khóa tài khoản</button>
              </form>
              <p class="hint">
                <?= ['client' => 'Khách bị đăng xuất ngay; các đơn chưa thực hiện tự hủy với lý do "Tài khoản bị khóa".',
                     'staff' => 'Bị chặn nếu nhân viên còn đơn giữ lịch. Hãy chuyển các đơn đó cho người khác trước.',
                     'admin' => 'Không khóa được quản trị viên cuối cùng còn hoạt động.'][$u['role']] ?>
              </p>
            <?php endif; ?>
          </div>

          <div class="border-t border-ink-100 pt-5">
            <form method="post" action="<?= url("/admin/users/$id/reset-password") ?>" data-confirm="Tạo mật khẩu tạm mới cho <?= e($u['name']) ?>? Mật khẩu hiện tại sẽ không dùng được nữa.">
              <?= csrf_field() ?><button class="btn btn-outline w-full">Đặt lại mật khẩu</button>
            </form>
            <p class="hint">Tạo mật khẩu tạm và bắt người dùng đổi ở lần đăng nhập tới.</p>
          </div>

          <?php if ($internal): ?>
            <form method="post" action="<?= url("/admin/users/$id/role") ?>" class="border-t border-ink-100 pt-5" data-confirm="Đổi vai trò của <?= e($u['name']) ?>?">
              <?= csrf_field() ?>
              <label class="label" for="role">Vai trò</label>
              <div class="flex gap-2">
                <select class="input" id="role" name="role">
                  <option value="staff" <?= selected($u['role'], 'staff') ?>>Nhân viên</option>
                  <option value="admin" <?= selected($u['role'], 'admin') ?>>Quản trị viên</option>
                </select>
                <button class="btn btn-outline">Đổi</button>
              </div>
              <p class="hint">Chỉ đổi qua lại giữa Nhân viên và Quản trị viên. Quản trị viên không nhận lịch nên nhân viên còn đơn không đổi được.</p>
            </form>
          <?php else: ?>
            <p class="border-t border-ink-100 pt-5 text-xs text-ink-500">Tài khoản khách hàng không nâng được thành tài khoản nội bộ. Hãy tạo tài khoản nhân viên riêng nếu cần.</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="panel">
      <div class="panel-head"><h2 class="panel-title">Nhật ký tài khoản</h2></div>
      <?php if (!$logs): ?><p class="px-5 py-5 text-sm text-ink-500">Chưa có thay đổi nào.</p><?php else: ?>
        <ul class="divide-y divide-ink-100 text-sm">
          <?php foreach ($logs as $l): $d = $l['detail'] ? json_decode($l['detail'], true) : []; ?>
            <li class="px-5 py-3">
              <p class="font-semibold"><?= e(action_label($l['action'])) ?></p>
              <p class="text-xs text-ink-500"><?= e(vn_datetime($l['created_at'])) ?> · <?= e($l['user_name'] ?? 'Hệ thống') ?><?= \Core\View::partial('partials/log_detail', ['d' => $d]) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>
