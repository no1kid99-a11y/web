<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Policies\BookingPolicy;
use App\Services\PhotoUploader;
use Core\Controller;

/**
 * Trả file ảnh ca làm. Ảnh nằm ngoài thư mục public nên không ai mở thẳng được bằng đường dẫn;
 * mọi lượt xem đều đi qua đây và phải qua BookingPolicy::view:
 * chủ đơn, nhân viên được giao đơn, hoặc Admin. Người khác nhận 404 (không lộ là ảnh có tồn tại).
 */
class PhotoController extends Controller
{
    public function show(int $id): void
    {
        $photo = BookingPhoto::find($id);
        $booking = $photo ? Booking::find((int)$photo['booking_id']) : null;
        $this->notFoundUnless($booking && BookingPolicy::view($this->user(), $booking), 'Không tìm thấy ảnh.');

        $name = $this->query('size') === 'thumb' && $photo['thumb'] ? $photo['thumb'] : $photo['file'];
        $path = PhotoUploader::bookingPath($name);
        $this->notFoundUnless($path, 'Ảnh không còn trên máy chủ.');

        $etag = '"' . md5($name . filemtime($path)) . '"';
        header_remove('Pragma');
        header_remove('Expires');
        header('Cache-Control: private, max-age=604800');
        header('ETag: ' . $etag);
        if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            return;
        }
        header('Content-Type: ' . match (pathinfo($path, PATHINFO_EXTENSION)) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        });
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }
}
