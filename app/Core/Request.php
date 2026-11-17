<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $query;
    private array $body;
    private array $files;
    private array $server;
    private ?array $jsonCache = null;

    public function __construct()
    {
        $this->query = $_GET;
        $this->body = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;

        if ($this->isJson()) {
            $this->body = array_merge($this->body, $this->json());
        }
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST' && isset($this->server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = strtoupper($this->server['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }

        return $method;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function path(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        $base = rtrim((string) Env::get('APP_BASE_PATH', ''), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function boolean(string $key): bool
    {
        $value = $this->input($key);
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /** @return array<string,mixed>|null */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (is_array($file['name'])) {
            return null;
        }

        return $file;
    }

    /**
     * Nhiều file cùng lúc (input name="images[]").
     *
     * @return array<int,array<string,mixed>>
     */
    public function files(string $key): array
    {
        $entry = $this->files[$key] ?? null;
        if (!is_array($entry) || !is_array($entry['name'] ?? null)) {
            return [];
        }

        $files = [];
        foreach (array_keys($entry['name']) as $index) {
            if ((int) ($entry['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = [
                'name'     => $entry['name'][$index],
                'type'     => $entry['type'][$index],
                'tmp_name' => $entry['tmp_name'][$index],
                'error'    => $entry['error'][$index],
                'size'     => $entry['size'][$index],
            ];
        }

        return $files;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function isJson(): bool
    {
        $contentType = (string) ($this->server['CONTENT_TYPE'] ?? '');

        return str_contains($contentType, 'application/json');
    }

    public function json(): array
    {
        if ($this->jsonCache !== null) {
            return $this->jsonCache;
        }

        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return $this->jsonCache = [];
        }
        $decoded = json_decode($raw, true);

        return $this->jsonCache = is_array($decoded) ? $decoded : [];
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
