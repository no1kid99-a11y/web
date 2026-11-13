<?php
/**
 * Tạo bảng và nạp dữ liệu mẫu.
 *   php database/install.php            -> tạo lại toàn bộ bảng + dữ liệu mẫu
 *   php database/install.php --no-seed  -> chỉ tạo bảng
 * Với MySQL, script tự tạo database (mặc định "petcare") nếu chưa có. Có thể tạo tay bằng:
 *   CREATE DATABASE petcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chạy script này bằng dòng lệnh: php database/install.php');
}

require __DIR__ . '/../core/bootstrap.php';

use Core\Database;

$driver = config('db.driver');
echo "Driver: $driver\n";

$file = $driver === 'sqlite' ? __DIR__ . '/schema.sqlite.sql' : __DIR__ . '/schema.sql';
$sql = file_get_contents($file);
// Bỏ dòng chú thích rồi tách theo dấu chấm phẩy cuối câu.
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
$statements = array_filter(array_map('trim', preg_split('/;\s*(\r?\n|$)/', $sql)));

// MySQL: tự tạo database nếu chưa có (không cần vào phpMyAdmin).
if ($driver === 'mysql') {
    $c = config('db');
    if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$c['database'])) {
        fwrite(STDERR, "Tên database không hợp lệ: {$c['database']} (chỉ dùng chữ, số, dấu gạch dưới).\n");
        exit(1);
    }
    try {
        $server = new PDO(
            "mysql:host={$c['host']};port={$c['port']};charset=utf8mb4",
            $c['username'],
            $c['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$c['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database '{$c['database']}' đã sẵn sàng.\n";
        $server = null;
    } catch (PDOException $e) {
        $code = (int)($e->errorInfo[1] ?? 0);
        fwrite(STDERR, "Không kết nối được MySQL: {$e->getMessage()}\n");
        fwrite(STDERR, match ($code) {
            1045 => "Sai tài khoản hoặc mật khẩu MySQL. Tạo config/config.local.php theo config.local.example.php và điền đúng.\n",
            2002 => "MySQL chưa chạy. Mở XAMPP Control Panel và bấm Start ở dòng MySQL.\n",
            default => "Kiểm tra MySQL đã bật và thông tin trong config/config.php.\n",
        });
        exit(1);
    }
}

try {
    $pdo = Database::pdo();
} catch (PDOException $e) {
    fwrite(STDERR, "Không kết nối được database: {$e->getMessage()}\n");
    fwrite(STDERR, "Kiểm tra config/config.php và chắc chắn đã tạo database '" . config('db.database') . "'.\n");
    exit(1);
}

foreach ($statements as $st) {
    $pdo->exec($st);
}
echo "Đã tạo " . count($statements) . " câu lệnh schema.\n";

// Database vừa làm mới nên ảnh ca làm cũ không còn đơn nào trỏ tới: dọn luôn cho khỏi rác.
foreach (glob(BASE_PATH . '/storage/booking_photos/*') ?: [] as $old) {
    if (preg_match('/\.(jpg|png|webp)$/', $old)) {
        @unlink($old);
    }
}

if (!in_array('--no-seed', $argv, true)) {
    require __DIR__ . '/seed.php';
    seed_database();
    echo "Đã nạp dữ liệu mẫu.\n\n";
    echo "Tài khoản demo:\n";
    echo "  Admin     : admin@petcare.vn  / Admin@123\n";
    echo "  Nhân viên : an.le@petcare.vn  / Staff@123  (binh.pham@, chau.hoang@)\n";
    echo "  Khách hàng: ha.do@gmail.com   / Khach@123  (huy.vu@, lananh.bui@)\n";
}
