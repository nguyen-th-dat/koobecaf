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
 * Việc gửi không được làm hỏng luồng chính: timeout ngắn, lỗi bị bỏ qua âm
 * thầm (client sẽ tự đồng bộ lại khi tải trang hoặc qua API polling dự phòng).
 */
final class WsPublisher
{
    /** Timeout (giây) cho request nội bộ tới WebSocket server. */
    private const TIMEOUT = 2;

    /**
     * Phát một sự kiện.
     *
     * @param array<string,mixed> $payload
     */
    public static function publish(string $event, array $payload = []): bool
    {
        if (!WebSocketToken::isEnabled() || $event === '') {
            return false;
        }

        $url = self::url();
        if ($url === '') {
            return false;
        }

        $body = json_encode(
            ['event' => $event, 'payload' => $payload],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (!is_string($body)) {
            return false;
        }

        $signature = hash_hmac('sha256', $body, self::key());

        $error = null;
        $response = self::post($url, $body, $signature, $error);
        if ($response === false) {
            Logger::info(
                'Không gửi được sự kiện "' . $event . '" tới WebSocket server'
                . ($error !== null ? ': ' . $error : '.') . ' ('
                . $url . ')'
            );

            return false;
        }

        return true;
    }

    /**
     * Gửi POST và trả về nội dung phản hồi, hoặc false khi thất bại.
     *
     * @param string|null $error Nhận mô tả nguyên nhân thất bại (nếu có).
     */
    private static function post(string $url, string $body, string $signature, ?string &$error = null): string|false
    {
        $headers = [
            'Content-Type: application/json',
            'X-KB-Signature: ' . $signature,
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                $error = 'không khởi tạo được cURL';

                return false;
            }

            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            ]);

            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                $error = $curlError !== '' ? $curlError : 'không kết nối được tới ' . $url;

                return false;
            }

            if ($status < 200 || $status >= 300) {
                $error = 'HTTP ' . $status . ' — ' . substr(trim((string) $response), 0, 200);

                return false;
            }

            return (string) $response;
        }

        // Dự phòng khi không có extension cURL.
        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $body,
                'timeout'       => self::TIMEOUT,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = 'không kết nối được tới ' . $url;

            return false;
        }

        return $response;
    }

    /** URL endpoint nội bộ của WebSocket server. */
    public static function url(): string
    {
        $explicit = (string) Env::get('WS_INTERNAL_URL', '');
        if ($explicit !== '') {
            return $explicit;
        }

        $port = (int) Env::get('WS_PORT', 8080);

        // WebSocket server thường lắng nghe trên 127.0.0.1 cùng máy với ứng
        // dụng PHP, nên endpoint nội bộ cũng gọi theo địa chỉ loopback.
        return 'http://127.0.0.1:' . $port . '/internal/publish';
    }

    private static function key(): string
    {
        return (string) Env::get('APP_KEY', '');
    }
}
