<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

/**
 * Ảnh nhân viên chụp trong ca làm, gắn với một lịch hẹn.
 * Ảnh thuộc lịch hẹn nên cũng nằm trong "album chăm sóc" của thú cưng.
 */
class BookingPhoto extends Model
{
    protected static string $table = 'booking_photos';
    protected static bool $timestamps = false;

    /** Các mốc chụp gợi ý cho nhân viên (khóa => nhãn hiển thị). */
    public const STAGES = [
        'arrive' => 'Lúc mới đến',
        'bath'   => 'Đang tắm',
        'dry'    => 'Sau khi sấy',
        'groom'  => 'Sau khi cắt tỉa',
        'nail'   => 'Vệ sinh tai, móng',
        'done'   => 'Hoàn thành',
        'other'  => 'Khoảnh khắc khác',
    ];

    private const SELECT = 'SELECT p.*, u.name AS uploader_name FROM booking_photos p JOIN users u ON u.id = p.uploaded_by';

    /** Ảnh của một đơn, mới nhất trước. $afterId > 0: chỉ lấy ảnh mới hơn (dùng cho trang tự cập nhật). */
    public static function forBooking(int $bookingId, int $afterId = 0): array
    {
        return Database::fetchAll(
            self::SELECT . ' WHERE p.booking_id = ? AND p.id > ? ORDER BY p.id DESC',
            [$bookingId, $afterId]
        );
    }

    /** Album chăm sóc của thú cưng: ảnh từ mọi lịch hẹn, mới nhất trước. */
    public static function forPet(int $petId, int $limit = 60): array
    {
        return Database::fetchAll(
            self::SELECT . ' JOIN bookings b ON b.id = p.booking_id
             WHERE b.pet_id = ? ORDER BY p.id DESC LIMIT ' . max(1, $limit),
            [$petId]
        );
    }

    /** Toàn bộ id ảnh hiện còn của đơn (để trang khách biết ảnh nào vừa bị xóa). */
    public static function idsForBooking(int $bookingId): array
    {
        return array_map('intval', array_column(
            Database::fetchAll('SELECT id FROM booking_photos WHERE booking_id = ?', [$bookingId]),
            'id'
        ));
    }

    public static function countForBooking(int $bookingId): int
    {
        return (int)Database::value('SELECT COUNT(*) FROM booking_photos WHERE booking_id = ?', [$bookingId]);
    }

    /** Dữ liệu gọn gửi cho trình duyệt (JSON và view dùng chung). */
    public static function present(array $p): array
    {
        return [
            'id'      => (int)$p['id'],
            'url'     => url('/photos/' . $p['id']),
            'thumb'   => url('/photos/' . $p['id'] . ($p['thumb'] ? '?size=thumb' : '')),
            'stage'   => $p['stage'],
            'label'   => self::STAGES[$p['stage']] ?? self::STAGES['other'],
            'caption' => (string)($p['caption'] ?? ''),
            'time'    => date('H:i', strtotime((string)$p['created_at'])),
            'date'    => vn_date((string)$p['created_at']),
            'by'      => (string)($p['uploader_name'] ?? ''),
            'at'      => (string)$p['created_at'],
        ];
    }
}
