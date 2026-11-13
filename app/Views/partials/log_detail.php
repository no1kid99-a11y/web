<?php
/** Hiển thị chi tiết JSON của nhật ký thành câu ngắn. @var array $d */
$d = $d ?? [];
$parts = [];
if (isset($d['from'], $d['to'])) {
    $parts[] = e($d['from']) . ' → ' . e($d['to']);
}
if (isset($d['from_staff'], $d['to_staff'])) {
    $parts[] = 'NV ' . e($d['from_staff']) . ' → ' . e($d['to_staff']);
}
if (isset($d['stage'])) {
    $parts[] = 'Mốc: ' . e(\App\Models\BookingPhoto::STAGES[$d['stage']] ?? $d['stage']);
}
if (isset($d['photo_id'])) {
    $parts[] = 'Ảnh #' . (int)$d['photo_id'];
}
foreach (['reason' => 'Lý do', 'staff' => 'Nhân viên', 'date' => 'Ngày', 'email' => 'Email', 'role' => 'Vai trò', 'weight' => 'Cân nặng (kg)', 'cancelled_bookings' => 'Số đơn bị hủy', 'name' => 'Tên', 'price' => 'Giá'] as $k => $label) {
    if (isset($d[$k]) && !is_array($d[$k])) {
        $parts[] = $label . ': ' . e((string)$d[$k]);
    }
}
foreach ($d as $k => $v) {
    if (is_array($v) && array_key_exists('old', $v)) {
        $parts[] = e($k) . ': ' . e((string)$v['old']) . ' → ' . e((string)$v['new']);
    }
}
if ($parts) {
    echo ' · ' . implode(' · ', $parts);
}
