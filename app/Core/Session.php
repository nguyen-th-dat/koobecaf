<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name((string) Env::get('SESSION_NAME', 'koobecaf_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => Env::bool('SESSION_SECURE', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Xoá toàn bộ dữ liệu phiên nhưng vẫn giữ phiên hoạt động.
     *
     * Dùng khi đăng xuất: cần xoá thông tin đăng nhập nhưng vẫn phải truyền
     * được thông báo "đăng xuất thành công" sang trang kế tiếp. Nếu hủy hẳn
     * session (Session::destroy()) thì cookie phiên bị xoá và thông báo flash
     * sẽ không tới được trình duyệt.
     */
    public static function clear(): void
    {
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
