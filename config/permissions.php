<?php
/**
 * Bảng quyền tập trung (RBAC): quyền => các vai trò được phép.
 * Code gọi Auth::can('booking.approve') thay vì so sánh role rải rác.
 * Quyền theo chủ sở hữu bản ghi (đơn của mình, đơn được giao) do BookingPolicy kiểm tra thêm.
 */
return [
    // Khách hàng
    'pet.manage'          => ['client'],
    'booking.create'      => ['client'],
    'booking.cancel_own'  => ['client'],
    'review.write'        => ['client'],

    // Nhân viên (và Admin xử lý sự cố)
    'booking.view_assigned' => ['staff'],
    'booking.progress'      => ['staff', 'admin'],
    'health.write'          => ['staff', 'admin'],
    'photo.upload'          => ['staff', 'admin'], // ảnh cập nhật trong ca làm

    // Chỉ Admin
    'booking.view_all'   => ['admin'],
    'booking.approve'    => ['admin'],
    'booking.cancel_any' => ['admin'],
    'booking.reassign'   => ['admin'],
    'booking.reschedule' => ['admin'],
    'user.manage'        => ['admin'],
    'catalog.manage'     => ['admin'],
    'slot.manage'        => ['admin'],
    'review.moderate'    => ['admin'],
    'report.view'        => ['admin'],
    'log.view'           => ['admin'],
];
