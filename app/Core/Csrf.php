<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function check(?string $token): bool
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($stored) || $stored === '') {
            return false;
        }

        return is_string($token) && $token !== '' && hash_equals($stored, $token);
    }

    /** Lấy token từ form, query string hoặc header AJAX. */
    public static function extractToken(Request $request): ?string
    {
        $header = $request->header('X-CSRF-TOKEN');
        if (is_string($header) && $header !== '') {
            return $header;
        }

        $body = $request->string('_token');
        if ($body !== '') {
            return $body;
        }

        if ($request->isJson()) {
            $json = $request->json();
            $token = $json['_token'] ?? null;

            return is_string($token) ? $token : null;
        }

        return null;
    }

    public static function validate(Request $request): void
    {
        if (!$request->isPost()) {
            return;
        }

        if (self::check(self::extractToken($request))) {
            return;
        }

        if ($request->isJson()) {
            Response::error('Token CSRF không hợp lệ. Vui lòng tải lại trang.', 419);
        }

        http_response_code(419);
        echo 'Token CSRF không hợp lệ. Vui lòng quay lại và thử lại.';

        exit;
    }
}
