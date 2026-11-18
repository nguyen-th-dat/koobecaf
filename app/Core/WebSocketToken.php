<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cấp token đăng nhập cho kết nối WebSocket.
 *
 * Token là chuỗi `payload.signature`:
 *   - payload : base64url(JSON) chứa uid / sid / iat / exp / jti
 *   - signature: base64url(HMAC-SHA256(purpose . payload, APP_KEY))
 *
 * Node server (server/ws-server.js) xác thực lại chữ ký bằng cùng APP_KEY nên
 * không cần chia sẻ session PHP với tiến trình Node.
 *
 * Ngoài token đăng nhập, hệ thống còn dùng "ticket" cho từng cuộc trò chuyện:
 * ticket chứa uid + conversation_id + thông tin người kia, nhờ vậy Node không
 * cần gọi lại PHP chỉ để biết client có quyền subscribe vào hội thoại nào.
 */
final class WebSocketToken
{
    /** Token đăng nhập chỉ dùng trong thời gian ngắn, hết hạn thì client xin cấp lại. */
    public const DEFAULT_TTL = 300;

    private const PURPOSE_TOKEN = 'koobecaf:ws-token:';
    private const PURPOSE_TICKET = 'koobecaf:ws-ticket:';
    // -----------------------------------------------------------------
    // Token đăng nhập
    // -----------------------------------------------------------------

    public static function issue(int $userId, ?int $ttl = null): string
    {
        $now = time();
        $payload = [
            'uid' => $userId,
            'sid' => substr(hash('sha256', (string) session_id()), 0, 32),
            'iat' => $now,
            'exp' => $now + ($ttl ?? self::DEFAULT_TTL),
            'jti' => bin2hex(random_bytes(8)),
        ];

        return self::sign(self::PURPOSE_TOKEN, $payload);
    }

    /**
     * @return array{uid:int,iat:int,exp:int,jti:string,sid:string}|null
     */
    public static function verify(string $token): ?array
    {
        $payload = self::verifySigned(self::PURPOSE_TOKEN, $token);
        if ($payload === null) {
            return null;
        }

        return [
            'uid' => (int) $payload['uid'],
            'iat' => (int) ($payload['iat'] ?? 0),
            'exp' => (int) ($payload['exp'] ?? 0),
            'jti' => (string) ($payload['jti'] ?? ''),
            'sid' => (string) ($payload['sid'] ?? ''),
        ];
    }

    // -----------------------------------------------------------------
    // Ticket theo cuộc trò chuyện
    // -----------------------------------------------------------------

    /**
     * @param array{id:int,username:string,display_name:string,avatar_path:?string} $peer
     */
    public static function issueTicket(int $userId, int $conversationId, array $peer, ?int $ttl = null): string
    {
        $now = time();
        $payload = [
            'uid'  => $userId,
            'cid'  => $conversationId,
            'exp'  => $now + ($ttl ?? self::DEFAULT_TTL),
            'peer' => [
                'id'           => (int) $peer['id'],
                'username'     => (string) $peer['username'],
                'display_name' => (string) $peer['display_name'],
                'avatar_path'  => $peer['avatar_path'] ?? null,
            ],
        ];

        return self::sign(self::PURPOSE_TICKET, $payload);
    }

    /**
     * @return array{uid:int,cid:int,exp:int,peer:array}|null
     */
    public static function verifyTicket(string $ticket): ?array
    {
        $payload = self::verifySigned(self::PURPOSE_TICKET, $ticket);
        if ($payload === null) {
            return null;
        }

        return [
            'uid'  => (int) ($payload['uid'] ?? 0),
            'cid'  => (int) ($payload['cid'] ?? 0),
            'exp'  => (int) ($payload['exp'] ?? 0),
            'peer' => is_array($payload['peer'] ?? null) ? $payload['peer'] : [],
        ];
    }

    // -----------------------------------------------------------------
    // Kết nối
    // -----------------------------------------------------------------

    /** URL đầy đủ của WebSocket server để client kết nối. */
    public static function url(): string
    {
        $host = (string) Env::get('WS_HOST', '127.0.0.1');
        $port = (int) Env::get('WS_PORT', 8080);
        $path = (string) Env::get('WS_PATH', '/ws');
        $secure = Env::bool('SESSION_SECURE', false);

        $scheme = $secure ? 'wss' : 'ws';

        // Client thường truy cập qua tên miền khác (vd. koobecaf.local) nên
        // dùng đúng host mà trình duyệt đang dùng thay vì 127.0.0.1.
        $requestHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($requestHost !== '') {
            $name = strtolower(explode(':', $requestHost)[0]);
            if (!in_array($name, ['127.0.0.1', 'localhost', '::1'], true)) {
                $host = $name;
            }
        }

        return $scheme . '://' . $host . ':' . $port . '/' . ltrim($path, '/');
    }

    public static function isEnabled(): bool
    {
        return Env::bool('WS_ENABLED', true);
    }

    public static function ttl(): int
    {
        return max(30, (int) Env::get('WS_TOKEN_TTL', self::DEFAULT_TTL));
    }

    // -----------------------------------------------------------------
    // Nội bộ
    // -----------------------------------------------------------------

    /**
     * @param array<string,mixed> $payload
     */
    private static function sign(string $purpose, array $payload): string
    {
        $key = self::key();
        if ($key === '') {
            throw new \RuntimeException('APP_KEY chưa được cấu hình, không thể cấp token WebSocket.');
        }

        $encoded = self::base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $purpose . $encoded, $key, true));

        return $encoded . '.' . $signature;
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function verifySigned(string $purpose, string $token): ?array
    {
        $key = self::key();
        if ($key === '' || !str_contains($token, '.')) {
            return null;
        }

        [$encoded, $signature] = explode('.', $token, 2);
        $expected = self::base64UrlEncode(hash_hmac('sha256', $purpose . $encoded, $key, true));
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = self::base64UrlDecode($encoded);
        if ($decoded === null) {
            return null;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload) || !isset($payload['exp']) || (int) $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private static function key(): string
    {
        return (string) Env::get('APP_KEY', '');
    }

    private static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $encoded): ?string
    {
        $padded = strtr($encoded, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }
}
