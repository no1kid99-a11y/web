<?php
/**
 * Cấu hình ứng dụng.
 * Có thể ghi đè bằng biến môi trường (DB_DRIVER, DB_HOST, ...) hoặc tạo file config/config.local.php
 * trả về mảng cùng cấu trúc (file này không commit lên Git).
 */
$config = [
    'app' => [
        'name'     => 'PetCare Home',
        'timezone' => 'Asia/Ho_Chi_Minh',
        'debug'    => true,
    ],
    'db' => [
        // 'mysql' cho XAMPP/Laragon. 'sqlite' chỉ dùng để chạy kiểm thử tự động.
        'driver'   => getenv('DB_DRIVER') ?: 'mysql',
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'petcare',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'path'     => getenv('DB_PATH') ?: __DIR__ . '/../storage/petcare.sqlite',
    ],
    'booking' => [
        'travel_minutes'  => 30,  // thời gian di chuyển cộng vào mỗi đơn
        'min_lead_hours'  => 2,   // phải đặt / hủy trước giờ hẹn ít nhất 2 giờ
        'max_days_ahead'  => 30,  // chỉ cho đặt trong vòng 30 ngày tới
        'retry_attempts'  => 3,   // 1 lần đầu + tối đa 2 lần thử lại
    ],
    'upload' => [
        'max_bytes' => 2 * 1024 * 1024,
        'allowed'   => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
    ],
    // Ảnh nhân viên chụp trong ca làm (lưu ở storage/booking_photos, ngoài thư mục public)
    'booking_photo' => [
        'max_bytes'   => 12 * 1024 * 1024, // ảnh gốc từ điện thoại; trình duyệt thường đã thu nhỏ trước khi gửi
        'max_side'    => 1600,             // cạnh dài tối đa sau khi server thu nhỏ (cần extension gd)
        'thumb_side'  => 480,
        'per_booking' => 40,
        'poll_seconds'=> 5,                // trang khách tự lấy ảnh mới sau mỗi 5 giây
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}
return $config;
