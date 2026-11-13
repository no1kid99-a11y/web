<?php
/**
 * Khởi động ứng dụng: autoload PSR-4 đơn giản, cấu hình, múi giờ, session, hàm tiện ích.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $map = ['Core\\' => BASE_PATH . '/core/', 'App\\' => BASE_PATH . '/app/'];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

$GLOBALS['__config'] = require BASE_PATH . '/config/config.php';
date_default_timezone_set(config('app.timezone'));

require BASE_PATH . '/core/helpers.php';

if (config('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['__config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}
