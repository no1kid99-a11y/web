<?php
/**
 * Hàm tiện ích dùng trong controller và view.
 */
declare(strict_types=1);

use Core\Csrf;
use Core\Session;

/** Escape khi in ra HTML (chống XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Đường dẫn gốc của ứng dụng (hỗ trợ chạy trong thư mục con của XAMPP). */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($base === '.') {
            $base = '';
        }
    }
    return $base;
}

function url(string $path = '/', array $query = []): string
{
    $u = base_url() . '/' . ltrim($path, '/');
    if ($query) {
        $query = array_filter($query, fn($v) => $v !== null && $v !== '');
        if ($query) {
            $u .= '?' . http_build_query($query);
        }
    }
    return $u;
}

function asset(string $path): string
{
    return base_url() . '/assets/' . ltrim($path, '/');
}

function upload_url(?string $file): ?string
{
    return $file ? base_url() . '/uploads/pets/' . rawurlencode($file) : null;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::get('_old_input', []);
    return $old[$key] ?? $default;
}

function redirect(string $path, array $query = []): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path, $query)));
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && $host && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function flash(string $type, string $message): void
{
    $all = Session::get('_flash', []);
    $all[] = ['type' => $type, 'message' => $message];
    Session::set('_flash', $all);
}

function money(int|float|string|null $amount): string
{
    return number_format((float)$amount, 0, ',', '.') . "\u{00A0}đ";
}

function vn_date(?string $date): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : $date;
}

function vn_datetime(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $ts = strtotime($dt);
    return $ts ? date('d/m/Y H:i', $ts) : $dt;
}

function weekday_vn(string $date): string
{
    $names = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    return $names[(int)date('w', strtotime($date))];
}

function hm(?string $time): string
{
    return $time ? substr($time, 0, 5) : '';
}

function status_label(string $status): string
{
    return [
        'pending'     => 'Chờ duyệt',
        'confirmed'   => 'Đã xác nhận',
        'in_progress' => 'Đang tiến hành',
        'completed'   => 'Hoàn thành',
        'cancelled'   => 'Đã hủy',
    ][$status] ?? $status;
}

function status_badge(string $status): string
{
    [$cls, $dot] = [
        'pending'     => ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
        'confirmed'   => ['bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
        'in_progress' => ['bg-mint-50 text-mint-700 ring-mint-200', 'bg-mint-500'],
        'completed'   => ['bg-stone-100 text-stone-600 ring-stone-200', ''],
        'cancelled'   => ['bg-red-50 text-red-600 ring-red-100', 'bg-red-400'],
    ][$status] ?? ['bg-stone-100 text-stone-700 ring-stone-200', 'bg-stone-400'];
    $mark = $dot
        ? '<span class="h-1.5 w-1.5 rounded-full ' . $dot . '"></span>'
        : '<svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>';
    return '<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ' . $cls . '">' . $mark . e(status_label($status)) . '</span>';
}

/** Mã đơn hiển thị, ví dụ PC-007. Dùng thống nhất cho khách, nhân viên và Admin. */
function booking_code(int|string $id): string
{
    return 'PC-' . str_pad((string)(int)$id, 3, '0', STR_PAD_LEFT);
}

/** Ngày hẹn dạng ngắn: "Hôm nay", "Ngày mai" hoặc "T4 12/11". */
function day_label(string $date): string
{
    $today = date('Y-m-d');
    if ($date === $today) {
        return 'Hôm nay';
    }
    if ($date === date('Y-m-d', strtotime('+1 day'))) {
        return 'Ngày mai';
    }
    return weekday_vn($date) . ' ' . date('d/m', strtotime($date));
}

function role_label(string $role): string
{
    return ['client' => 'Khách hàng', 'staff' => 'Nhân viên', 'admin' => 'Quản trị viên'][$role] ?? $role;
}

function species_label(string $s): string
{
    return ['dog' => 'Chó', 'cat' => 'Mèo', 'other' => 'Khác'][$s] ?? $s;
}

function species_icon(string $s): string
{
    return ['dog' => '🐶', 'cat' => '🐱'][$s] ?? '🐾';
}

function action_label(string $action): string
{
    return [
        'booking.approve'     => 'Duyệt đơn',
        'booking.reject'      => 'Từ chối đơn',
        'booking.cancel'      => 'Hủy đơn',
        'booking.start'       => 'Bắt đầu dịch vụ',
        'booking.complete'    => 'Hoàn thành dịch vụ',
        'booking.reassign'    => 'Đổi nhân viên',
        'booking.reschedule'  => 'Dời lịch',
        'health.record'       => 'Ghi sổ sức khỏe',
        'photo.upload'        => 'Gửi ảnh cập nhật',
        'photo.delete'        => 'Gỡ ảnh cập nhật',
        'user.create'         => 'Tạo tài khoản',
        'user.update'         => 'Sửa tài khoản',
        'user.lock'           => 'Khóa tài khoản',
        'user.unlock'         => 'Mở khóa tài khoản',
        'user.role_change'    => 'Đổi vai trò',
        'user.reset_password' => 'Đặt lại mật khẩu',
        'service.save'        => 'Lưu dịch vụ',
        'combo.save'          => 'Lưu combo',
        'slot.save'           => 'Lưu khung giờ',
        'review.toggle'       => 'Ẩn/hiện đánh giá',
    ][$action] ?? $action;
}

function selected(mixed $a, mixed $b): string
{
    return (string)$a === (string)$b ? 'selected' : '';
}

function checked(bool $cond): string
{
    return $cond ? 'checked' : '';
}

/** Đường dẫn hiện tại (không gồm base và query) để đánh dấu menu. */
function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_url();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    if (str_starts_with($uri, '/index.php')) {
        $uri = substr($uri, 10);
    }
    return '/' . trim($uri, '/');
}

function nav_active(string $prefix, bool $exact = false): bool
{
    $p = current_path();
    return $exact ? $p === $prefix : ($p === $prefix || str_starts_with($p, rtrim($prefix, '/') . '/'));
}

/** Cân nặng dạng nhập liệu: 5.2 (dấu chấm). */
function kg_input(mixed $w): string
{
    if ($w === null || $w === '') {
        return '';
    }
    return rtrim(rtrim(number_format((float)$w, 2, '.', ''), '0'), '.');
}

/** Cân nặng để hiển thị: 5,2 kg. */
function kg(mixed $w): string
{
    return ($w === null || $w === '') ? '—' : str_replace('.', ',', kg_input($w)) . ' kg';
}

/*
 * ---------------------------------------------------------------------------
 * Ảnh minh họa giao diện khách hàng.
 * Thứ tự ưu tiên: ảnh nhóm tự đặt trong public/assets/img/photos/{key}.jpg|png|webp
 * -> ảnh Unsplash (miễn phí bản quyền, tải trên trình duyệt người xem)
 * -> hình vẽ dự phòng (khi không có mạng hoặc ảnh lỗi).
 * ---------------------------------------------------------------------------
 */
const PHOTO_SOURCES = [
    'hero'    => 'photo-1587300003388-59208cc962cb', // chó vàng nhìn thẳng
    'bath'    => 'photo-1516734212186-a967f81ad0d7', // tắm cho chó
    'groom'   => 'photo-1583337130417-3346a1be7dee', // chó lông xù sau khi tỉa
    'walk'    => 'photo-1548199973-03cce0bbc87b',    // chó chạy ngoài trời
    'spa'     => 'photo-1543466835-00a7907e9de1',    // chó nằm thư giãn
    'care'    => 'photo-1517849845537-4d257902454a', // chó nhỏ quấn chăn
    'cat'     => 'photo-1514888286974-6c03e2ca1dba', // mèo
    'cat2'    => 'photo-1573865526739-10659fec78a5', // mèo
    'puppy'   => 'photo-1537151608828-ea2b11777ee8', // chó con
    'camera'  => 'photo-1516734212186-a967f81ad0d7', // khối camera: cảnh tắm tại nhà
    'login'   => 'photo-1450778869180-41d0601e046e', // chó và mèo
];

function photo(string $key, int $width = 900): string
{
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        if (is_file(BASE_PATH . "/public/assets/img/photos/$key.$ext")) {
            return asset("img/photos/$key.$ext");
        }
    }
    $id = PHOTO_SOURCES[$key] ?? PHOTO_SOURCES['hero'];
    return "https://images.unsplash.com/$id?auto=format&fit=crop&w=$width&q=75";
}

/** Thẻ <img> có hình vẽ dự phòng theo loài khi ảnh không tải được. */
function photo_img(string $key, string $class = '', string $alt = '', string $species = 'dog', int $width = 900): string
{
    $fallback = asset('img/fallback-' . ($species === 'cat' ? 'cat' : 'dog') . '.svg');
    return '<img src="' . e(photo($key, $width)) . '" alt="' . e($alt) . '" class="' . e($class) . '" loading="lazy" decoding="async"'
        . ' onerror="this.onerror=null;this.src=\'' . e($fallback) . '\'">';
}

/** Chọn ảnh phù hợp cho một dịch vụ/combo dựa vào tên. */
function service_photo_key(string $name): string
{
    $n = mb_strtolower($name);
    return match (true) {
        str_contains($n, 'dạo') => 'walk',
        str_contains($n, 'tỉa') || str_contains($n, 'toàn diện') => 'groom',
        str_contains($n, 'massage') || str_contains($n, 'spa') => 'spa',
        str_contains($n, 'răng') || str_contains($n, 'móng') => 'care',
        str_contains($n, 'mèo') => 'cat',
        default => 'bath',
    };
}
