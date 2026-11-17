<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(array $data = [], int $status = 200): never
    {
        self::json(['success' => true, 'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400, array $extra = []): never
    {
        self::json(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    public static function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . self::withBasePath($url), true, $status);
        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        self::redirect($referer !== '' ? $referer : $fallback);
    }

    /**
     * Thêm base path vào URL nội bộ.
     *
     * URL tuyệt đối (http://…) và URL đã có base path đều được giữ nguyên.
     */
    private static function withBasePath(string $url): string
    {
        if (preg_match('#^(https?:)?//#i', $url) === 1) {
            return $url;
        }

        $base = rtrim((string) Env::get('APP_BASE_PATH', ''), '/');
        if ($base === '' || str_starts_with($url, $base . '/') || $url === $base) {
            return $url;
        }

        return $base . '/' . ltrim($url, '/');
    }
}
