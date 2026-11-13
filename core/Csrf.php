<?php
declare(strict_types=1);

namespace Core;

/**
 * Token CSRF cho mọi form POST. Router tự kiểm tra trước khi gọi controller.
 */
class Csrf
{
    public static function token(): string
    {
        $t = Session::get('_csrf');
        if (!$t) {
            $t = bin2hex(random_bytes(32));
            Session::set('_csrf', $t);
        }
        return $t;
    }

    public static function verify(?string $token): bool
    {
        $t = Session::get('_csrf');
        return is_string($t) && is_string($token) && hash_equals($t, $token);
    }
}
