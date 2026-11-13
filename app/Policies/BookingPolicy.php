<?php
declare(strict_types=1);

namespace App\Policies;

use Core\Auth;

/**
 * Quyền theo bản ghi: có quyền chức năng chưa đủ, còn phải đúng dữ liệu.
 * - Client chỉ thao tác đơn có user_id là mình.
 * - Nhân viên chỉ thao tác đơn có staff_id là mình.
 * - Admin xem và xử lý mọi đơn.
 */
class BookingPolicy
{
    public static function view(array $user, array $booking): bool
    {
        return match ($user['role']) {
            'admin'  => true,
            'staff'  => (int)$booking['staff_id'] === (int)$user['id'],
            'client' => (int)$booking['user_id'] === (int)$user['id'],
            default  => false,
        };
    }

    public static function cancel(array $user, array $booking): bool
    {
        if ($user['role'] === 'admin') {
            return Auth::can('booking.cancel_any', $user);
        }
        return $user['role'] === 'client'
            && Auth::can('booking.cancel_own', $user)
            && (int)$booking['user_id'] === (int)$user['id'];
    }

    public static function progress(array $user, array $booking): bool
    {
        if (!Auth::can('booking.progress', $user)) {
            return false;
        }
        return $user['role'] === 'admin' || (int)$booking['staff_id'] === (int)$user['id'];
    }

    public static function writeHealth(array $user, array $booking): bool
    {
        if (!Auth::can('health.write', $user)) {
            return false;
        }
        return $user['role'] === 'admin' || (int)$booking['staff_id'] === (int)$user['id'];
    }

    public static function review(array $user, array $booking): bool
    {
        return Auth::can('review.write', $user) && (int)$booking['user_id'] === (int)$user['id'];
    }

    /** Gửi hoặc xóa ảnh cập nhật: nhân viên phụ trách đơn, hoặc Admin. */
    public static function uploadPhoto(array $user, array $booking): bool
    {
        if (!Auth::can('photo.upload', $user)) {
            return false;
        }
        return $user['role'] === 'admin' || (int)$booking['staff_id'] === (int)$user['id'];
    }

    /**
     * Trang "Theo dõi trực tiếp" của khách: đúng chủ đơn, đơn đã duyệt trở đi.
     * Mở sẵn từ lúc Đã xác nhận để trang tự chuyển sang trực tiếp khi nhân viên bấm bắt đầu;
     * sau khi hoàn thành vẫn mở để xem lại ảnh.
     */
    public static function camera(array $user, array $booking): bool
    {
        return $user['role'] === 'client'
            && (int)$booking['user_id'] === (int)$user['id']
            && in_array($booking['status'], ['confirmed', 'in_progress', 'completed'], true);
    }
}
