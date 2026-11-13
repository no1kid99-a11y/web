<?php
declare(strict_types=1);

namespace Core;

/**
 * Router tối giản: GET/POST, tham số {id}, middleware theo từng route.
 * Mọi request POST đều phải có token CSRF hợp lệ.
 */
class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, callable $define): void
    {
        $prevPrefix = $this->groupPrefix;
        $prevMw = $this->groupMiddleware;
        $this->groupPrefix .= $prefix;
        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);
        $define($this);
        $this->groupPrefix = $prevPrefix;
        $this->groupMiddleware = $prevMw;
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $full = rtrim($this->groupPrefix . $path, '/') ?: '/';
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[0-9]+)', $full) . '$#';
        $this->routes[] = [
            'method' => $method,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        $allowed = false;
        foreach ($this->routes as $r) {
            if (!preg_match($r['regex'], $path, $m)) {
                continue;
            }
            if ($r['method'] !== $method) {
                $allowed = true;
                continue;
            }
            $params = array_map('intval', array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));

            if ($method === 'POST' && !Csrf::verify($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
                throw new HttpException(419, 'Phiên làm việc đã hết hạn, vui lòng tải lại trang và thử lại.');
            }
            foreach ($r['middleware'] as $mw) {
                $this->runMiddleware($mw, $path);
            }
            [$class, $action] = $r['handler'];
            $controller = new $class();
            $controller->$action(...array_values($params));
            return;
        }
        throw new HttpException($allowed ? 405 : 404, $allowed ? 'Phương thức không được hỗ trợ.' : 'Không tìm thấy trang.');
    }

    private function runMiddleware(string $spec, string $path): void
    {
        [$name, $arg] = array_pad(explode(':', $spec, 2), 2, null);
        $map = [
            'auth'  => \App\Middleware\AuthMiddleware::class,
            'guest' => \App\Middleware\GuestMiddleware::class,
            'role'  => \App\Middleware\RoleMiddleware::class,
            'can'   => \App\Middleware\PermissionMiddleware::class,
        ];
        if (!isset($map[$name])) {
            throw new \RuntimeException("Middleware không tồn tại: $name");
        }
        (new $map[$name]())->handle($arg, $path);
    }
}
