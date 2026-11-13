<?php
/**
 * Front controller: mọi request đi qua đây.
 * Chạy nhanh bằng PHP built-in server:  php -S localhost:8000 -t public public/index.php
 */
declare(strict_types=1);

// Built-in server: trả file tĩnh trực tiếp
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

require dirname(__DIR__) . '/core/bootstrap.php';

use Core\HttpException;
use Core\Router;
use Core\Session;
use Core\View;

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

Session::start();

$router = new Router();
require BASE_PATH . '/config/routes.php';

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', current_path());
} catch (HttpException $e) {
    http_response_code($e->status);
    if (str_starts_with(current_path(), '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo View::render('errors/error', ['status' => $e->status, 'message' => $e->getMessage(), 'title' => 'Lỗi ' . $e->status]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log((string)$e);
    $msg = config('app.debug') ? get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : 'Đã có lỗi xảy ra. Vui lòng thử lại sau.';
    try {
        echo View::render('errors/error', ['status' => 500, 'message' => $msg, 'title' => 'Lỗi hệ thống']);
    } catch (Throwable) {
        echo '<h1>500</h1><p>' . htmlspecialchars($msg) . '</p>';
    }
}
