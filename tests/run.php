<?php
/**
 * Kiểm thử nghiệp vụ: chống trùng lịch, đổi nhân viên/dời lịch, phân quyền, quản lý người dùng, ảnh trực tiếp.
 * Chạy: php tests/run.php   (dùng SQLite tạm, không đụng tới database MySQL thật)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chạy bằng dòng lệnh: php tests/run.php');
}

putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . sys_get_temp_dir() . '/petcare_test_' . getmypid() . '.sqlite');
require __DIR__ . '/../core/bootstrap.php';
require __DIR__ . '/../database/seed.php';

use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Policies\BookingPolicy;
use App\Services\BookingPhotoService;
use App\Services\BookingService;
use App\Services\UserService;
use Core\AppException;
use Core\Auth;
use Core\Database;

// ---------------------------------------------------------------- harness
$results = ['pass' => 0, 'fail' => 0];

function fresh(): void
{
    Database::setPdo(null);
    @unlink(config('db.path'));
    $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents(BASE_PATH . '/database/schema.sqlite.sql'));
    foreach (array_filter(array_map('trim', preg_split('/;\s*(\r?\n|$)/', $sql))) as $st) {
        Database::pdo()->exec($st);
    }
    seed_database();
}

function test(string $name, callable $fn): void
{
    global $results;
    fresh();
    try {
        $fn();
        $results['pass']++;
        echo "  \033[32m✔\033[0m $name\n";
    } catch (Throwable $e) {
        $results['fail']++;
        echo "  \033[31m✘ $name\033[0m\n     " . get_class($e) . ': ' . $e->getMessage() . "\n     at " . $e->getFile() . ':' . $e->getLine() . "\n";
    }
}

function ok(bool $cond, string $msg = 'Điều kiện sai'): void
{
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

function fails(callable $fn, string $contains): AppException
{
    try {
        $fn();
    } catch (AppException $e) {
        ok(str_contains($e->getMessage(), $contains), "Thông báo lỗi không khớp. Mong đợi chứa \"$contains\", nhận: \"{$e->getMessage()}\"");
        return $e;
    }
    throw new RuntimeException("Mong đợi lỗi chứa \"$contains\" nhưng thao tác thành công");
}

function u(string $email): array
{
    return Database::fetch('SELECT * FROM users WHERE email = ?', [$email]);
}

function pet(string $name): array
{
    return Database::fetch('SELECT * FROM pets WHERE name = ?', [$name]);
}

function slot(string $start): int
{
    return (int)Database::value('SELECT id FROM time_slots WHERE start_time = ?', [$start]);
}

function day(int $offset): string
{
    return date('Y-m-d', strtotime("$offset days"));
}

/** Khóa tài khoản nhân viên trực tiếp trong database (chỉ để dựng tình huống kiểm thử). */
function lockStaff(array $staff): void
{
    Database::update('users', ['status' => 'locked'], 'id = :id', ['id' => $staff['id']]);
}

function book(array $client, string $petName, string $date, int $slotId, array $extra = []): int
{
    return (new BookingService())->create($client, array_merge([
        'pet_id' => pet($petName)['id'], 'booking_date' => $date, 'time_slot_id' => $slotId,
        'combo_id' => 1, 'service_ids' => [], 'address' => '25 Nguyễn Trãi, Hà Nội',
    ], $extra));
}

/** Mô phỏng tranh chấp: lần chọn đầu tiên trả về một nhân viên đã bận (dữ liệu cũ). */
class StaleChoiceService extends BookingService
{
    public int $calls = 0;
    public function __construct(private int $staleStaffId)
    {
        parent::__construct();
    }
    protected function pickStaff(string $date, int $slotId, ?int $excludeBookingId = null): ?int
    {
        return ++$this->calls === 1 ? $this->staleStaffId : parent::pickStaff($date, $slotId, $excludeBookingId);
    }
}

/** Mô phỏng hai giao dịch cùng vượt qua bước kiểm tra ở server: chỉ mục duy nhất phải chặn. */
class SkipRecheckService extends StaleChoiceService
{
    public int $checks = 0;
    public function isStaffAvailable(int $staffId, string $date, int $slotId, ?int $excludeBookingId = null): bool
    {
        return ++$this->checks === 1 ? true : parent::isStaffAvailable($staffId, $date, $slotId, $excludeBookingId);
    }
}

$ha = fn() => u('ha.do@gmail.com');
$huy = fn() => u('huy.vu@gmail.com');
$lan = fn() => u('lananh.bui@gmail.com');
$admin = fn() => u('admin@petcare.vn');
$an = fn() => u('an.le@petcare.vn');
$binh = fn() => u('binh.pham@petcare.vn');
$chau = fn() => u('chau.hoang@petcare.vn');

// ================================================================ Kịch bản trùng lịch (đề cương)
echo "\nKịch bản kiểm thử trùng lịch\n";

test('1. Hai khách cùng đặt khi chỉ còn một nhân viên rảnh: chỉ một đơn thành công', function () use ($ha, $huy, $an, $binh) {
    $D = day(8);
    lockStaff($an());
    lockStaff($binh());
    $id = book($ha(), 'Lu', $D, slot('08:00:00'));
    ok((int)Booking::find($id)['staff_id'] === (int)u('chau.hoang@petcare.vn')['id'], 'Phải gán cho Châu');
    fails(fn() => book($huy(), 'Bơ', $D, slot('08:00:00')), 'kín lịch');
});

test('1b. Tranh chấp: lựa chọn nhân viên đã cũ thì khóa dòng + kiểm tra lại, tự chọn người khác', function () use ($ha, $huy, $an) {
    $D = day(8);
    $first = book($ha(), 'Lu', $D, slot('08:00:00'));
    $taken = (int)Booking::find($first)['staff_id'];
    $svc = new StaleChoiceService($taken);
    $id = $svc->create($huy(), ['pet_id' => pet('Bơ')['id'], 'booking_date' => $D, 'time_slot_id' => slot('08:00:00'), 'combo_id' => 1, 'address' => '88 Cầu Giấy, Hà Nội']);
    ok((int)Booking::find($id)['staff_id'] !== $taken, 'Không được gán trùng nhân viên');
    ok($svc->calls === 2, 'Phải thử lại đúng một lần');
});

test('1c. Tranh chấp lọt qua server: chỉ mục uq_staff_slot chặn, hệ thống thử lại với nhân viên khác', function () use ($ha, $huy) {
    $D = day(8);
    $first = book($ha(), 'Lu', $D, slot('08:00:00'));
    $taken = (int)Booking::find($first)['staff_id'];
    $svc = new SkipRecheckService($taken);
    $id = $svc->create($huy(), ['pet_id' => pet('Bơ')['id'], 'booking_date' => $D, 'time_slot_id' => slot('08:00:00'), 'combo_id' => 1, 'address' => '88 Cầu Giấy, Hà Nội']);
    ok((int)Booking::find($id)['staff_id'] !== $taken, 'Không được gán trùng nhân viên');
    $dupes = Database::value('SELECT COUNT(*) FROM bookings WHERE staff_id = ? AND booking_date = ? AND time_slot_id = ? AND active_flag = 1', [$taken, $D, slot('08:00:00')]);
    ok((int)$dupes === 1, 'Nhân viên chỉ có một lịch trong khung');
});

test('1d. Chỉ mục duy nhất ở database tự chặn hai đơn giữ chỗ cùng nhân viên, cùng khung', function () use ($ha) {
    $D = day(8);
    $id = book($ha(), 'Lu', $D, slot('08:00:00'));
    $b = Booking::find($id);
    $row = $b;
    unset($row['id'], $row['active_flag']);
    $row['pet_id'] = pet('Mun')['id'];
    try {
        Database::insert('bookings', $row);
        throw new RuntimeException('Database không chặn');
    } catch (PDOException $e) {
        ok(Database::uniqueViolation($e) === 'uq_staff_slot', 'Phải nhận diện đúng uq_staff_slot');
    }
    // Đơn đã hủy không chặn ai
    Database::update('bookings', ['status' => 'cancelled'], 'id = :id', ['id' => $id]);
    Database::insert('bookings', $row);
});

test('2. Ba nhân viên, một người bị khóa: khung kín sau 2 đơn', function () use ($ha, $huy, $lan, $chau) {
    $D = day(9);
    lockStaff($chau());
    book($ha(), 'Lu', $D, slot('10:00:00'));
    book($huy(), 'Bơ', $D, slot('10:00:00'));
    $slots = (new BookingService())->availableSlots($D, (int)pet('Miu')['id']);
    $s10 = array_values(array_filter($slots, fn($s) => $s['id'] === slot('10:00:00')))[0];
    ok($s10['available'] === false && $s10['reason'] === 'Đã kín lịch', 'Khung 10:00 phải kín');
    fails(fn() => book($lan(), 'Miu', $D, slot('10:00:00')), 'kín lịch');
});

test('2b. Chỉ nhân viên đang hoạt động được tính; Admin không bao giờ được tính', function () use ($an) {
    $D = day(9);
    $svc = new BookingService();
    $before = array_column($svc->availableStaff($D, slot('08:00:00')), 'id');
    ok(count($before) === 3 && !in_array(u('admin@petcare.vn')['id'], $before), 'Chỉ 3 nhân viên, không có Admin');
    lockStaff($an());
    $after = array_column($svc->availableStaff($D, slot('08:00:00')), 'id');
    ok(count($after) === 2 && !in_array($an()['id'], $after), 'Nhân viên bị khóa không được tính');
});

test('2c. Tự gán người ít đơn nhất trong ngày', function () use ($ha, $huy) {
    $D = day(9);
    $a = Booking::find(book($ha(), 'Lu', $D, slot('08:00:00')));
    $b = Booking::find(book($huy(), 'Bơ', $D, slot('10:00:00')));
    ok($a['staff_id'] !== $b['staff_id'], 'Đơn thứ hai phải dồn cho người chưa có lịch');
});

test('3. Admin đổi nhân viên sang người đã có lịch ở khung đó: bị từ chối', function () use ($ha, $huy, $admin) {
    $D = day(10);
    $a = Booking::find(book($ha(), 'Lu', $D, slot('08:00:00')));
    $b = Booking::find(book($huy(), 'Bơ', $D, slot('08:00:00')));
    fails(fn() => (new BookingService())->reassign($admin(), (int)$a['id'], (int)$b['staff_id']), 'không rảnh');
    // Sang người rảnh thì được và có nhật ký
    $free = (new BookingService())->availableStaff($D, slot('08:00:00'))[0]['id'];
    (new BookingService())->reassign($admin(), (int)$a['id'], (int)$free);
    ok((int)Booking::find((int)$a['id'])['staff_id'] === (int)$free);
    ok((int)Database::value("SELECT COUNT(*) FROM activity_logs WHERE action = 'booking.reassign'") === 1, 'Phải ghi nhật ký');
});

test('3b. Không đổi nhân viên sang người đã bị khóa', function () use ($ha, $admin, $chau) {
    $D = day(10);
    $a = book($ha(), 'Lu', $D, slot('08:00:00'));
    if ((int)Booking::find($a)['staff_id'] === (int)$chau()['id']) {
        Database::update('bookings', ['staff_id' => u('an.le@petcare.vn')['id']], 'id = :id', ['id' => $a]);
    }
    lockStaff($chau());
    fails(fn() => (new BookingService())->reassign($admin(), $a, (int)$chau()['id']), 'đã bị khóa');
});

test('4. Admin dời lịch sang khung mà thú cưng đã có đơn khác: bị từ chối', function () use ($ha, $admin) {
    $D = day(11);
    $a = book($ha(), 'Lu', $D, slot('08:00:00'));
    book($ha(), 'Lu', $D, slot('13:00:00'));
    fails(fn() => (new BookingService())->reschedule($admin(), $a, $D, slot('13:00:00')), 'Thú cưng đã có lịch');
    // Dời sang khung trống thì được, giữ nguyên nhân viên nếu vẫn rảnh
    $before = Booking::find($a);
    (new BookingService())->reschedule($admin(), $a, $D, slot('15:00:00'));
    $after = Booking::find($a);
    ok($after['time_slot_id'] == slot('15:00:00') && $after['staff_id'] == $before['staff_id']);
});

test('4b. Dời lịch sang khung mà nhân viên hiện tại bận: tự chọn nhân viên khác', function () use ($ha, $huy, $admin) {
    $D = day(11);
    $a = Booking::find(book($ha(), 'Lu', $D, slot('08:00:00')));
    // Huy đặt khung 10:00 và cố tình gán cho đúng nhân viên của đơn a
    $b = book($huy(), 'Bơ', $D, slot('10:00:00'));
    Database::update('bookings', ['staff_id' => $a['staff_id']], 'id = :id', ['id' => $b]);
    (new BookingService())->reschedule($admin(), (int)$a['id'], $D, slot('10:00:00'));
    ok(Booking::find((int)$a['id'])['staff_id'] != $a['staff_id'], 'Phải đổi sang nhân viên rảnh');
});

test('5. Admin khóa nhân viên còn đơn trong tương lai: bị chặn kèm danh sách', function () use ($admin, $an) {
    $e = fails(fn() => (new UserService())->lock($admin(), (int)$an()['id']), 'chuyển các đơn');
    ok(count($e->details['bookings']) >= 2);
    ok($an()['status'] === 'active');
});

test('5b. Chuyển hết đơn rồi khóa nhân viên: từ đó không được gán lịch mới', function () use ($admin, $an, $ha) {
    $svc = new BookingService();
    foreach (Booking::futureHoldingForStaff((int)$an()['id']) as $b) {
        if ($b['status'] === 'in_progress') {
            Database::update('bookings', ['status' => 'completed'], 'id = :id', ['id' => $b['id']]);
            continue;
        }
        $svc->transition($admin(), (int)$b['id'], 'cancelled', 'Nhân viên nghỉ việc');
    }
    (new UserService())->lock($admin(), (int)$an()['id']);
    ok($an()['status'] === 'locked');
    $D = day(8);
    foreach (['08:00:00', '10:00:00', '13:00:00'] as $t) {
        ok(!in_array($an()['id'], array_column($svc->availableStaff($D, slot($t)), 'id')), 'An không còn trong danh sách rảnh');
    }
    $id = book($ha(), 'Lu', $D, slot('08:00:00'));
    ok((int)Booking::find($id)['staff_id'] !== (int)$an()['id'], 'Đơn mới không gán cho nhân viên đã khóa');
});

test('6. Admin và nhân viên cùng đổi trạng thái một đơn: chỉ một thao tác được ghi', function () use ($admin, $an) {
    // Đơn hôm nay 15:00 của Châu đang "Đã xác nhận"; dùng đơn in_progress của An không phù hợp, nên tạo tình huống:
    $bid = (int)Database::value("SELECT id FROM bookings WHERE status = 'confirmed' AND booking_date = ?", [day(0)]);
    $b = Booking::find($bid);
    $staff = u('chau.hoang@petcare.vn');
    $svc = new BookingService();
    $planStaff = $svc->prepareTransition($staff, $bid, 'in_progress');           // nhân viên mở trang, đọc "Đã xác nhận"
    $svc->transition($admin(), $bid, 'cancelled', 'Khách báo vắng nhà');        // Admin hủy trước
    fails(fn() => $svc->applyTransition($staff, $planStaff), 'tải lại trang');  // nhân viên bấm sau
    ok(Booking::find($bid)['status'] === 'cancelled', 'Trạng thái giữ theo thao tác đầu tiên');
});

test('7. Đơn Chờ duyệt quá giờ hẹn tự hủy "Hết hạn" và trả khung', function () use ($ha) {
    $id = Database::insert('bookings', [
        'user_id' => $ha()['id'], 'pet_id' => pet('Mun')['id'], 'time_slot_id' => slot('08:00:00'),
        'staff_id' => u('an.le@petcare.vn')['id'], 'booking_date' => day(-1), 'address' => 'x', 'total_price' => 1,
        'discount_amount' => 0, 'status' => 'pending', 'created_at' => Database::now(),
    ]);
    (new BookingService())->expirePending();
    $b = Booking::find($id);
    ok($b['status'] === 'cancelled' && $b['cancel_reason'] === 'Hết hạn' && $b['active_flag'] === null);
});

// ================================================================ Quy tắc đặt lịch
echo "\nQuy tắc đặt lịch\n";

test('Không trộn combo với dịch vụ lẻ', function () use ($ha) {
    fails(fn() => book($ha(), 'Lu', day(8), slot('08:00:00'), ['combo_id' => 1, 'service_ids' => [2]]), 'không trộn');
});

test('Tổng thời lượng + di chuyển vượt khung: bị chặn', function () use ($ha) {
    // Tắm 45 + Tỉa 60 + di chuyển 30 = 135 > 120
    fails(fn() => book($ha(), 'Lu', day(8), slot('08:00:00'), ['combo_id' => null, 'service_ids' => [1, 2]]), 'vượt độ dài khung');
});

test('Combo: giá chốt, giảm giá và sao chép giá lẻ', function () use ($ha) {
    $id = book($ha(), 'Lu', day(8), slot('08:00:00'), ['combo_id' => 2]);
    $b = Booking::find($id);
    ok((int)$b['total_price'] === 280000 && (int)$b['discount_amount'] === 40000, 'Combo Toàn diện 280k, giảm 40k');
    ok(count(Booking::services($id)) === 3);
});

test('Không đặt ngày đã qua, quá sát giờ, quá 30 ngày; không đặt cho thú cưng của người khác', function () use ($ha) {
    fails(fn() => book($ha(), 'Lu', day(-1), slot('08:00:00')), 'đã qua');
    fails(fn() => book($ha(), 'Lu', day(40), slot('08:00:00')), '30 ngày');
    fails(fn() => book($ha(), 'Bơ', day(8), slot('08:00:00')), 'Thú cưng không hợp lệ');
});

test('Một thú cưng không có hai lịch cùng khung', function () use ($ha) {
    book($ha(), 'Lu', day(8), slot('08:00:00'));
    fails(fn() => book($ha(), 'Lu', day(8), slot('08:00:00')), 'đã có lịch');
});

test('Khung giờ đã ẩn không nhận đơn mới', function () use ($ha) {
    Database::update('time_slots', ['is_active' => 0], 'id = :id', ['id' => slot('15:00:00')]);
    fails(fn() => book($ha(), 'Lu', day(8), slot('15:00:00')), 'ngừng nhận');
});

// ================================================================ Phân quyền
echo "\nPhân quyền\n";

test('Nhân viên không duyệt được đơn; Client không hủy được đơn người khác', function () use ($an, $huy) {
    $pending = (int)Database::value("SELECT id FROM bookings WHERE status = 'pending' AND user_id = ?", [u('ha.do@gmail.com')['id']]);
    fails(fn() => (new BookingService())->transition($an(), $pending, 'confirmed'), 'quản trị viên');
    fails(fn() => (new BookingService())->transition($huy(), $pending, 'cancelled'), 'không có quyền');
});

test('Nhân viên chỉ thao tác đơn được giao cho mình, và chỉ trong ngày hẹn', function () use ($binh, $an) {
    $todayChau = (int)Database::value("SELECT id FROM bookings WHERE status = 'confirmed' AND booking_date = ?", [day(0)]);
    fails(fn() => (new BookingService())->transition($binh(), $todayChau, 'in_progress'), 'không phụ trách');
    $tomorrowAn = (int)Database::value("SELECT id FROM bookings WHERE status = 'confirmed' AND booking_date = ? AND staff_id = ?", [day(1), $an()['id']]);
    fails(fn() => (new BookingService())->transition($an(), $tomorrowAn, 'in_progress'), 'trong ngày hẹn');
});

test('Nhân viên phải ghi sổ sức khỏe trước khi hoàn thành; cân nặng cập nhật vào hồ sơ', function () use ($an) {
    $bid = (int)Database::value("SELECT id FROM bookings WHERE status = 'in_progress'");
    $svc = new BookingService();
    fails(fn() => $svc->transition($an(), $bid, 'completed'), 'ghi sổ sức khỏe');
    $svc->addHealthRecord($an(), $bid, 5.6, 'Khỏe mạnh');
    $svc->transition($an(), $bid, 'completed');
    ok(Booking::find($bid)['status'] === 'completed');
    ok((float)pet('Lu')['weight'] === 5.6, 'pets.weight phải được cập nhật');
});

test('Client chỉ hủy được trước giờ hẹn 2 giờ; Admin hủy phải có lý do', function () use ($huy, $admin) {
    $today = (int)Database::value("SELECT id FROM bookings WHERE status = 'confirmed' AND booking_date = ?", [day(0)]);
    // Đặt giờ khung về sát hiện tại để giả lập còn dưới 2 giờ
    Database::update('time_slots', ['start_time' => date('H:i:s', time() + 1800), 'end_time' => '23:59:00'], 'id = :id', ['id' => slot('15:00:00')]);
    fails(fn() => (new BookingService())->transition($huy(), $today, 'cancelled'), 'ít nhất 2 giờ');
    fails(fn() => (new BookingService())->transition($admin(), $today, 'cancelled', ''), 'lý do');
    (new BookingService())->transition($admin(), $today, 'cancelled', 'Khách báo bận');
});

test('Bảng quyền RBAC: đúng vai trò cho từng quyền', function () use ($ha, $an, $admin) {
    ok(Auth::can('booking.create', $ha()) && !Auth::can('booking.create', $an()) && !Auth::can('booking.create', $admin()));
    ok(Auth::can('health.write', $an()) && Auth::can('health.write', $admin()) && !Auth::can('health.write', $ha()));
    ok(Auth::can('user.manage', $admin()) && !Auth::can('user.manage', $an()) && !Auth::can('user.manage', $ha()));
});

// ================================================================ Quản lý người dùng
echo "\nQuản lý người dùng\n";

test('Tạo nhân viên: mật khẩu tạm, bắt đổi mật khẩu, có nhật ký; không tạo được Client', function () use ($admin) {
    [$id, $temp] = (new UserService())->createInternal($admin(), ['name' => 'Ngô Đức', 'email' => 'duc@petcare.vn', 'phone' => '0912000009', 'role' => 'staff']);
    $u = u('duc@petcare.vn');
    ok(password_verify($temp, $u['password']) && (int)$u['must_change_password'] === 1 && $u['role'] === 'staff');
    ok((int)Database::value("SELECT COUNT(*) FROM activity_logs WHERE action = 'user.create'") === 1);
    fails(fn() => (new UserService())->createInternal($admin(), ['name' => 'X', 'email' => 'x@x.vn', 'phone' => '0912000010', 'role' => 'client']), 'Khách hàng tự đăng ký');
});

test('Khóa khách hàng: tự hủy đơn Chờ duyệt / Đã xác nhận với lý do "Tài khoản bị khóa"', function () use ($admin, $ha) {
    $n = (new UserService())->lock($admin(), (int)$ha()['id']);
    ok($n === 2, "Phải hủy 2 đơn, thực tế $n");
    ok((int)Database::value("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND cancel_reason = 'Tài khoản bị khóa'", [$ha()['id']]) === 2);
    ok((int)Database::value("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'in_progress'", [$ha()['id']]) === 1, 'Đơn đang làm giữ nguyên');
});

test('Không tự khóa / tự hạ quyền; không khóa hay hạ quyền Admin cuối cùng', function () use ($admin) {
    fails(fn() => (new UserService())->lock($admin(), (int)$admin()['id']), 'tự khóa');
    fails(fn() => (new UserService())->changeRole($admin(), (int)$admin()['id'], 'staff'), 'tự đổi');
    $trang = u('trang.tran@petcare.vn');
    (new UserService())->lock($admin(), (int)$trang['id']);
    // Giờ chỉ còn một Admin hoạt động; tạo Admin thứ hai bằng cách mở khóa rồi thử hạ quyền người còn lại
    (new UserService())->unlock($admin(), (int)$trang['id']);
    (new UserService())->changeRole($trang, (int)$admin()['id'], 'staff'); // còn Trang nên được
    fails(fn() => (new UserService())->changeRole($admin(), (int)$trang['id'], 'staff'), 'quản trị viên');
});

test('Đổi vai trò: không nâng Client; nhân viên còn đơn không thành Admin', function () use ($admin, $ha, $an, $chau) {
    fails(fn() => (new UserService())->changeRole($admin(), (int)$ha()['id'], 'staff'), 'khách hàng');
    fails(fn() => (new UserService())->changeRole($admin(), (int)$an()['id'], 'admin'), 'còn đơn');
});

test('Đặt lại mật khẩu: mật khẩu tạm mới + bắt đổi', function () use ($admin, $an) {
    $temp = (new UserService())->resetPassword($admin(), (int)$an()['id']);
    ok(password_verify($temp, $an()['password']) && (int)$an()['must_change_password'] === 1);
});

echo "\nẢnh cập nhật trực tiếp\n";

/** Ảnh giả lập như $_FILES['photo']: PNG 40x30 thật (hoặc file văn bản đội lốt ảnh). */
function fakeUpload(bool $real = true): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'pcimg');
    file_put_contents($tmp, $real
        ? base64_decode('iVBORw0KGgoAAAANSUhEUgAAACgAAAAeCAIAAADRv8uKAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAALklEQVRIie3NMQEAMAgAoLlcBjOisazg5wMFiK58F/7JKhaLxWKxWCwWi8XilQHCuAG41RD4JAAAAABJRU5ErkJggg==')
        : "<?php echo 'không phải ảnh';");
    return ['name' => 'a.png', 'type' => 'image/png', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];
}

/** Dọn file ảnh do kịch bản tạo ra trong storage/booking_photos. */
function cleanPhotos(): void
{
    foreach (Database::fetchAll('SELECT file, thumb FROM booking_photos') as $p) {
        \App\Services\PhotoUploader::deleteBookingPhoto($p['file']);
        \App\Services\PhotoUploader::deleteBookingPhoto($p['thumb']);
    }
}

$running = fn() => Database::fetch("SELECT * FROM bookings WHERE status = 'in_progress' LIMIT 1");

test('Nhân viên phụ trách chụp ảnh khi đang làm, khách chủ đơn xem được qua feed', function () use ($an, $ha, $running) {
    $b = $running();
    ok((int)$b['staff_id'] === (int)$an()['id'] && (int)$b['user_id'] === (int)$ha()['id'], 'Dữ liệu mẫu: đơn đang làm của An/Hà');
    $p = (new BookingPhotoService())->add($an(), (int)$b['id'], fakeUpload(), 'bath', '  Bé đang tắm   rất ngoan ');
    ok($p['caption'] === 'Bé đang tắm rất ngoan', 'Ghi chú được làm gọn khoảng trắng');
    ok(\App\Services\PhotoUploader::bookingPath($p['file']) !== null, 'File ảnh phải nằm trong storage/booking_photos');
    ok(BookingPolicy::view($ha(), $b) && BookingPolicy::camera($ha(), $b), 'Khách chủ đơn mở được trang theo dõi');
    $feed = BookingPhoto::forBooking((int)$b['id'], 0);
    ok(count($feed) === 1 && BookingPhoto::present($feed[0])['label'] === 'Đang tắm');
    ok(BookingPhoto::forBooking((int)$b['id'], (int)$p['id']) === [], 'Hỏi "ảnh sau id cuối" thì không trả lại ảnh cũ');
    ok(count(BookingPhoto::forPet((int)$b['pet_id'])) === 1, 'Ảnh có trong album của thú cưng');
    ok((int)Database::value("SELECT COUNT(*) FROM activity_logs WHERE action = 'photo.upload'") === 1, 'Có nhật ký');
    cleanPhotos();
});

test('Chặn gửi ảnh: nhân viên khác, khách hàng, đơn chưa bắt đầu, file giả ảnh', function () use ($an, $binh, $ha, $running) {
    $b = $running();
    fails(fn() => (new BookingPhotoService())->add($binh(), (int)$b['id'], fakeUpload(), 'bath', ''), 'không phụ trách');
    fails(fn() => (new BookingPhotoService())->add($ha(), (int)$b['id'], fakeUpload(), 'bath', ''), 'không phụ trách');
    $confirmed = (int)Database::value("SELECT id FROM bookings WHERE staff_id = ? AND status = 'confirmed' LIMIT 1", [$an()['id']]);
    ok($confirmed > 0, 'Dữ liệu mẫu: An có đơn đã xác nhận');
    fails(fn() => (new BookingPhotoService())->add($an(), $confirmed, fakeUpload(), 'bath', ''), 'đang tiến hành');
    fails(fn() => (new BookingPhotoService())->add($an(), (int)$b['id'], fakeUpload(false), 'bath', ''), 'JPG, PNG');
    fails(fn() => (new BookingPhotoService())->add($an(), (int)$b['id'], null, 'bath', ''), 'chụp hoặc chọn');
    ok((int)Database::value('SELECT COUNT(*) FROM booking_photos') === 0, 'Không lưu ảnh nào');
});

test('Khách khác không xem được ảnh; nhân viên chỉ gỡ ảnh khi ca còn đang làm', function () use ($an, $ha, $huy, $admin, $running) {
    $b = $running();
    $p = (new BookingPhotoService())->add($an(), (int)$b['id'], fakeUpload(), 'dry', '');
    ok(!BookingPolicy::view($huy(), $b) && !BookingPolicy::camera($huy(), $b), 'Khách khác bị chặn (route trả 404)');
    (new BookingService())->addHealthRecord($an(), (int)$b['id'], null, 'Da lông tốt');
    (new BookingService())->transition($an(), (int)$b['id'], 'completed');
    $done = Database::fetch('SELECT * FROM bookings WHERE id = ?', [$b['id']]);
    ok(BookingPolicy::camera($ha(), $done), 'Hoàn thành rồi khách vẫn xem lại được ảnh');
    fails(fn() => (new BookingPhotoService())->add($an(), (int)$b['id'], fakeUpload(), 'done', ''), 'đang tiến hành');
    fails(fn() => (new BookingPhotoService())->delete($an(), (int)$p['id']), 'Ca đã kết thúc');
    $q = (new BookingPhotoService())->add($admin(), (int)$b['id'], fakeUpload(), 'done', 'Admin gửi bù');
    ok((new BookingPhotoService())->delete($admin(), (int)$p['id']) === (int)$b['id'], 'Admin gỡ được ảnh');
    ok(BookingPhoto::idsForBooking((int)$b['id']) === [(int)$q['id']], 'Feed báo ảnh đã gỡ để trang khách xóa theo');
    cleanPhotos();
});

// ---------------------------------------------------------------- tổng kết
@unlink(config('db.path'));
echo "\n" . ($results['fail'] ? "\033[31m" : "\033[32m") . "{$results['pass']} đạt, {$results['fail']} lỗi\033[0m\n";
exit($results['fail'] ? 1 : 0);
