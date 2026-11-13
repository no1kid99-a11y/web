<?php
declare(strict_types=1);

namespace Core;

/** Ném ra để trả về trang lỗi 403/404/419. */
class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }
}
