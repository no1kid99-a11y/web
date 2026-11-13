<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Kết nối PDO dùng chung (prepared statements, chống SQL Injection).
 * Driver chính là MySQL/MariaDB; SQLite chỉ dùng cho kiểm thử tự động.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect(config('db'));
        }
        return self::$pdo;
    }

    public static function connect(array $c): PDO
    {
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        if ($c['driver'] === 'sqlite') {
            $pdo = new PDO('sqlite:' . $c['path'], null, null, $opts);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            return $pdo;
        }
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['database']);
        $pdo = new PDO($dsn, $c['username'], $c['password'], $opts);
        // Cùng múi giờ với PHP để quy tắc "trước giờ hẹn 2 giờ" không lệch.
        $pdo->exec("SET time_zone = '+07:00'");
        return $pdo;
    }

    /** Dùng trong kiểm thử để gắn kết nối riêng. */
    public static function setPdo(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function driver(): string
    {
        return self::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $cols),
            implode(', ', array_map(fn($c) => ':' . $c, $cols))
        );
        self::query($sql, $data);
        return (int)self::pdo()->lastInsertId();
    }

    /** UPDATE theo điều kiện; trả về số dòng bị ảnh hưởng. */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        $params = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = :set_$col";
            $params["set_$col"] = $val;
        }
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
        return self::query($sql, array_merge($params, $whereParams))->rowCount();
    }

    public static function begin(): void
    {
        self::pdo()->beginTransaction();
    }

    public static function commit(): void
    {
        self::pdo()->commit();
    }

    /** Chỉ rollback khi giao dịch còn mở (InnoDB tự rollback khi deadlock). */
    public static function rollBack(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }

    public static function inTransaction(): bool
    {
        return self::pdo()->inTransaction();
    }

    /**
     * Khóa đúng một dòng đã tồn tại (không sinh gap lock).
     * SQLite khóa cả file khi ghi nên không cần FOR UPDATE.
     */
    public static function lockRow(string $table, int $id): ?array
    {
        $sql = "SELECT * FROM $table WHERE id = ?" . (self::driver() === 'mysql' ? ' FOR UPDATE' : '');
        return self::fetch($sql, [$id]);
    }

    /**
     * Nhận diện lỗi trùng khóa duy nhất và trả về tên chỉ mục (uq_staff_slot, uq_pet_slot, ...).
     * MySQL: mã 1062, thông báo chứa tên chỉ mục. SQLite: mã 19, thông báo chứa danh sách cột.
     * Không dựa vào SQLSTATE 23000 vì mã này gồm cả lỗi khóa ngoại.
     */
    public static function uniqueViolation(PDOException $e): ?string
    {
        $code = (int)($e->errorInfo[1] ?? 0);
        $msg = $e->getMessage();
        if ($code === 1062) {
            if (preg_match("/for key '([^']+)'/", $msg, $m)) {
                $key = $m[1];
                return str_contains($key, '.') ? substr($key, strrpos($key, '.') + 1) : $key;
            }
            return 'unknown';
        }
        if ($code === 19 && str_contains($msg, 'UNIQUE constraint failed')) {
            if (str_contains($msg, 'bookings.staff_id')) {
                return 'uq_staff_slot';
            }
            if (str_contains($msg, 'bookings.pet_id')) {
                return 'uq_pet_slot';
            }
            if (str_contains($msg, 'users.email')) {
                return 'uq_users_email';
            }
            if (str_contains($msg, 'reviews.booking_id')) {
                return 'uq_reviews_booking';
            }
            return 'unknown';
        }
        return null;
    }

    public static function isDeadlock(PDOException $e): bool
    {
        $code = (int)($e->errorInfo[1] ?? 0);
        return $code === 1213 || $code === 1205 || $code === 5 /* SQLITE_BUSY */;
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public static function today(): string
    {
        return date('Y-m-d');
    }
}
