<?php
declare(strict_types=1);

namespace App\Services;

/** Báo cho vòng lặp giao dịch biết cần chọn lại nhân viên và thử lại. */
class RetryException extends \RuntimeException
{
}
