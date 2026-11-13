<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Policies\BookingPolicy;
use Core\AppException;
use Core\Database;

/**
 * Ảnh cập nhật trực tiếp trong ca làm.
 * - Nhân viên chỉ gửi ảnh cho đơn được giao, khi đơn đang tiến hành.
 * - Admin gửi bổ sung được cả khi đơn đã hoàn thành (xử lý sự cố, khách nhờ gửi lại).
 * - Khách chỉ xem, không gửi hay xóa.
 */
class BookingPhotoService
{
    /** @return array bản ghi ảnh vừa lưu */
    public function add(array $actor, int $bookingId, ?array $file, string $stage, string $caption): array
    {
        $b = Booking::find($bookingId);
        if (!$b) {
            throw new AppException('Không tìm thấy đơn.');
        }
        if (!BookingPolicy::uploadPhoto($actor, $b)) {
            throw new AppException('Bạn không phụ trách đơn này.');
        }
        $allowed = $actor['role'] === 'admin' ? ['in_progress', 'completed'] : ['in_progress'];
        if (!in_array($b['status'], $allowed, true)) {
            throw new AppException('Chỉ gửi ảnh khi dịch vụ đang tiến hành.');
        }
        if (!isset(BookingPhoto::STAGES[$stage])) {
            $stage = 'other';
        }
        $limit = (int)config('booking_photo.per_booking', 40);
        if (BookingPhoto::countForBooking($bookingId) >= $limit) {
            throw new AppException("Mỗi lịch hẹn lưu tối đa $limit ảnh. Hãy xóa bớt ảnh trùng trước khi chụp tiếp.");
        }
        $caption = trim(preg_replace('/\s+/u', ' ', $caption) ?? '');

        // Lưu file trước; nếu ghi database lỗi thì xóa file để không sót rác.
        $saved = PhotoUploader::storeBookingPhoto($file);
        Database::begin();
        try {
            $id = Database::insert('booking_photos', [
                'booking_id'  => $bookingId,
                'uploaded_by' => (int)$actor['id'],
                'file'        => $saved['file'],
                'thumb'       => $saved['thumb'],
                'stage'       => $stage,
                'caption'     => $caption === '' ? null : mb_substr($caption, 0, 255),
                'created_at'  => Database::now(),
            ]);
            ActivityLogger::log($actor, 'photo.upload', 'booking', $bookingId, ['photo_id' => $id, 'stage' => $stage]);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            PhotoUploader::deleteBookingPhoto($saved['file']);
            PhotoUploader::deleteBookingPhoto($saved['thumb']);
            throw $e;
        }
        return BookingPhoto::forBooking($bookingId, $id - 1)[0];
    }

    /** Xóa ảnh chụp nhầm. Trả về id đơn chứa ảnh. */
    public function delete(array $actor, int $photoId): int
    {
        $p = BookingPhoto::find($photoId);
        $b = $p ? Booking::find((int)$p['booking_id']) : null;
        if (!$p || !$b || !BookingPolicy::uploadPhoto($actor, $b)) {
            throw new AppException('Không tìm thấy ảnh.');
        }
        if ($actor['role'] !== 'admin') {
            // Nhân viên chỉ xóa ảnh mình chụp, và chỉ khi ca còn đang làm.
            if ((int)$p['uploaded_by'] !== (int)$actor['id'] || $b['status'] !== 'in_progress') {
                throw new AppException('Ca đã kết thúc. Liên hệ quản trị viên nếu cần gỡ ảnh.');
            }
        }
        Database::begin();
        try {
            Database::query('DELETE FROM booking_photos WHERE id = ?', [$photoId]);
            ActivityLogger::log($actor, 'photo.delete', 'booking', (int)$b['id'], ['photo_id' => $photoId]);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
        PhotoUploader::deleteBookingPhoto($p['file']);
        PhotoUploader::deleteBookingPhoto($p['thumb']);
        return (int)$b['id'];
    }
}
