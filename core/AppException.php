<?php
declare(strict_types=1);

namespace Core;

/**
 * Lỗi nghiệp vụ có thông báo hiển thị cho người dùng.
 * $details mang dữ liệu kèm theo, ví dụ danh sách đơn còn giữ chỗ khi khóa nhân viên.
 */
class AppException extends \RuntimeException
{
    public function __construct(string $message, public readonly array $details = [], public readonly string $kind = 'error')
    {
        parent::__construct($message);
    }
}
