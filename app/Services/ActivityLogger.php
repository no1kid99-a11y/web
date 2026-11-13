<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;

/**
 * Ghi nhật ký thao tác. Luôn gọi BÊN TRONG giao dịch của thao tác chính:
 * thao tác thất bại thì không có log, thao tác thành công thì luôn có log.
 */
class ActivityLogger
{
    public static function log(?array $actor, string $action, string $targetType, ?int $targetId, array $detail = []): void
    {
        Database::insert('activity_logs', [
            'user_id'     => $actor['id'] ?? null,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'detail'      => $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at'  => Database::now(),
        ]);
    }
}
