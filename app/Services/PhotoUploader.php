<?php
declare(strict_types=1);

namespace App\Services;

use Core\AppException;

/**
 * Upload ảnh an toàn: kiểm tra MIME thật bằng finfo, giới hạn dung lượng, đổi tên ngẫu nhiên.
 *
 * - Ảnh thú cưng: public/uploads/pets (thư mục có .htaccess chặn thực thi PHP).
 * - Ảnh nhân viên chụp trong ca làm: storage/booking_photos, NGOÀI thư mục public.
 *   Trình duyệt không mở thẳng được, phải đi qua PhotoController (có kiểm tra quyền).
 */
class PhotoUploader
{
    private const NAME_RE = '/^[a-f0-9]{32}(_t)?\.(jpg|png|webp)$/';

    public static function dir(): string
    {
        return BASE_PATH . '/public/uploads/pets';
    }

    public static function bookingDir(): string
    {
        return BASE_PATH . '/storage/booking_photos';
    }

    /** Ảnh hồ sơ thú cưng. Trả về tên file đã lưu, hoặc null nếu không chọn file. */
    public static function store(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $ext = self::check($file, (int)config('upload.max_bytes'));
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        self::moveTo($file['tmp_name'], self::dir(), $name);
        return $name;
    }

    /**
     * Ảnh cập nhật trong ca làm.
     * Nếu PHP có extension gd: xoay đúng chiều theo EXIF, thu nhỏ cạnh dài về 1600px, tạo thêm ảnh nhỏ 480px.
     * Không có gd thì giữ nguyên file gốc (trình duyệt đã thu nhỏ sẵn trước khi gửi).
     *
     * @return array{file: string, thumb: ?string}
     */
    public static function storeBookingPhoto(?array $file): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new AppException('Vui lòng chụp hoặc chọn một ảnh.');
        }
        $max = (int)config('booking_photo.max_bytes', 12 * 1024 * 1024);
        $ext = self::check($file, $max);
        $base = bin2hex(random_bytes(16));
        $dir = self::bookingDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $img = self::canResize() ? self::load($file['tmp_name'], $ext) : null;
        if (!$img) {
            self::moveTo($file['tmp_name'], $dir, "$base.$ext");
            return ['file' => "$base.$ext", 'thumb' => null];
        }

        $img = self::fixOrientation($img, $file['tmp_name'], $ext);
        $full = self::fit($img, (int)config('booking_photo.max_side', 1600));
        $thumb = self::fit($img, (int)config('booking_photo.thumb_side', 480));
        $ok = imagejpeg($full, "$dir/$base.jpg", 85) && imagejpeg($thumb, "$dir/{$base}_t.jpg", 78);
        if (!$ok) {
            throw new AppException('Không lưu được ảnh.');
        }
        return ['file' => "$base.jpg", 'thumb' => "{$base}_t.jpg"];
    }

    public static function delete(?string $name): void
    {
        if ($name && preg_match(self::NAME_RE, $name)) {
            @unlink(self::dir() . '/' . $name);
        }
    }

    public static function deleteBookingPhoto(?string $name): void
    {
        if ($name && preg_match(self::NAME_RE, $name)) {
            @unlink(self::bookingDir() . '/' . $name);
        }
    }

    /** Đường dẫn tuyệt đối của ảnh ca làm, null nếu tên file không hợp lệ hoặc file đã mất. */
    public static function bookingPath(?string $name): ?string
    {
        if (!$name || !preg_match(self::NAME_RE, $name)) {
            return null;
        }
        $path = self::bookingDir() . '/' . $name;
        return is_file($path) ? $path : null;
    }

    // ------------------------------------------------------------------

    /** Kiểm tra file tải lên, trả về đuôi file chuẩn (jpg/png/webp). */
    private static function check(array $file, int $maxBytes): string
    {
        $mb = rtrim(rtrim(number_format($maxBytes / 1048576, 1, '.', ''), '0'), '.');
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new AppException($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
                ? "Ảnh quá lớn (tối đa $mb MB)."
                : 'Tải ảnh lên không thành công, vui lòng thử lại.');
        }
        if ($file['size'] > $maxBytes) {
            throw new AppException("Ảnh quá lớn (tối đa $mb MB).");
        }
        if (!is_uploaded_file($file['tmp_name']) && PHP_SAPI !== 'cli') {
            throw new AppException('File tải lên không hợp lệ.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = config('upload.allowed');
        if (!isset($allowed[$mime])) {
            throw new AppException('Chỉ nhận ảnh JPG, PNG hoặc WEBP.');
        }
        if (@getimagesize($file['tmp_name']) === false) {
            throw new AppException('File không phải là ảnh hợp lệ.');
        }
        return $allowed[$mime];
    }

    private static function moveTo(string $tmp, string $dir, string $name): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $dest = $dir . '/' . $name;
        $ok = PHP_SAPI === 'cli' ? copy($tmp, $dest) : move_uploaded_file($tmp, $dest);
        if (!$ok) {
            throw new AppException('Không lưu được ảnh.');
        }
    }

    private static function canResize(): bool
    {
        return function_exists('imagecreatefromjpeg') && function_exists('imagejpeg');
    }

    private static function load(string $path, string $ext): ?\GdImage
    {
        $img = match ($ext) {
            'jpg'  => @imagecreatefromjpeg($path),
            'png'  => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
        return $img ?: null;
    }

    /** Ảnh chụp dọc trên điện thoại thường lưu nằm ngang kèm cờ EXIF; xoay lại cho đúng. */
    private static function fixOrientation(\GdImage $img, string $path, string $ext): \GdImage
    {
        if ($ext !== 'jpg' || !function_exists('exif_read_data')) {
            return $img;
        }
        $o = (int)(@exif_read_data($path)['Orientation'] ?? 1);
        $rotated = match ($o) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
        return $rotated ?: $img;
    }

    /** Thu nhỏ để cạnh dài không quá $side, nền trắng cho ảnh PNG trong suốt. */
    private static function fit(\GdImage $src, int $side): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $side / max($w, $h));
        $nw = max(1, (int)round($w * $scale));
        $nh = max(1, (int)round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $dst;
    }
}
