<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gửi sự kiện từ ứng dụng PHP sang WebSocket server để phát tới client.
 *
 * Ứng dụng PHP và WebSocket server là hai tiến trình riêng biệt nên cần cầu
 * nối để thông báo cho client. Cầu nối đi qua HTTP POST tới endpoint nội bộ
 * `/internal/publish` của WebSocket server, kèm chữ ký HMAC-SHA256 của body
 * (dùng chung `APP_KEY`) — server chỉ chấp nhận event có chữ ký hợp lệ.
 *
 * Việc gửi không chặn luồng chính: timeout ngắn, lỗi bị bỏ qua âm thầm
 * (client sẽ tự đồng bộ lại qua khi load trang hoặc qua API).
 */
final class WsPublisher
{
    /** Timeout (giây) cho request nội bộ tới WebSocket server. */
    private const TIMEOUT = 2;

    /**
     * Phát một sự kiện.
     *
     * @param array<string,mixed> $event
     */
    public static function publish(array $event): bool
    {
        if (!WebSocketToken::isEnabled()) {
            return false;
        }

        $url = self::url();
        if ($url === '') {
            return false;
        }

        $body = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) {
            return false;
        }

        $signature = hash_hmac('sha256', $body, self::key());

        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-KB-Signature: ' . $signature,
            ],
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($response === false) {
            Logger::info('Không gửi được sự kiện tới WebSocket server: ' . $event['event'] ?? '');

            return false;
        }

        return $status >= 200 && $status < 300;
    }

    /** URL endpoint nội bộ của WebSocket server. */
    public static function url(): string
    {
        $explicit = (string) Env::get('WS_INTERNAL_URL', '');
        if ($explicit !== '') {
            return $explicit;
        }

        $host = (string) Env::get('WS_HOST', '127.0.0.1');
        $port = (int) Env::get('WS_PORT', 8080);

        // Host/port của WebSocket server là nơi lắng nghe, nên gọi nội bộ
        // qua chính địa chỉ đó (thường là 127.0.0.1 trên cùng máy).
        return 'http://' . $host . ':' . $port . '/internal/publish';
    }

    private static function key(): string
    {
        return (string) Env::get('APP_KEY', '');
    }
}
