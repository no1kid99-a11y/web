<?php
// Sao chép thành config.local.php và sửa cho đúng máy của bạn (file này không commit lên Git).
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'petcare',
        'username' => 'root',
        'password' => '',
    ],
    'app' => ['debug' => true],
];
