<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

/**
 * Model cơ sở: CRUD đơn giản trên một bảng. Câu truy vấn riêng viết ở từng model con.
 */
abstract class Model
{
    protected static string $table;
    protected static bool $timestamps = true;

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . static::$table . ' WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        if (static::$timestamps && !isset($data['created_at'])) {
            $data['created_at'] = Database::now();
        }
        return Database::insert(static::$table, $data);
    }

    public static function updateById(int $id, array $data): int
    {
        if (static::$timestamps) {
            $data['updated_at'] = Database::now();
        }
        return Database::update(static::$table, $data, 'id = :id', ['id' => $id]);
    }
}
