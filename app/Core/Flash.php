<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Flash message — cơ chế truyền thông báo qua redirect.
 *
 * Thông báo được ghi vào session ở request xử lý POST, rồi được "nhặt" sang
 * request kế tiếp (sau redirect) và xoá khỏi session ngay khi hiển thị.
 * Nhờ vậy người dùng không mất thông báo khi trang được tải lại.
 *
 * Mỗi thông báo là một mảng: ['type' => 'success', 'message' => '...'].
 * Nhiều thông báo cùng loại được giữ lại để hiển thị dạng xếp chồng.
 *
 * Session chỉ bị xoá khi thông báo thực sự được hiển thị (pull()), nên một
 * request không render view — ví dụ request JSON hay request tĩnh — không
 * làm mất thông báo đang chờ.
 */
final class Flash
{
    /** Loại thông báo hợp lệ. */
    public const TYPES = ['success', 'error', 'warning', 'info'];

    /** Khóa lưu trong session. */
    private const SESSION_KEY = '_flash_messages';

    /** @var array<int,array{type:string,message:string}> */
    private static array $messages = [];

    private static bool $booted = false;

    /**
     * Nhặt thông báo từ session (gọi một lần mỗi request).
     *
     * Session vẫn giữ nguyên — thông báo chỉ bị xoá khi được hiển thị.
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        $stored = Session::get(self::SESSION_KEY, []);
        self::$messages = self::normalize(is_array($stored) ? $stored : []);
    }

    /**
     * Lưu một thông báo để hiển thị ở request kế tiếp.
     */
    public static function add(string $type, string $message): void
    {
        self::boot();

        $message = trim($message);
        if ($message === '') {
            return;
        }

        self::$messages[] = [
            'type'    => self::normalizeType($type),
            'message' => $message,
        ];

        self::$messages = array_slice(self::$messages, 0, 20);

        // Đồng bộ xuống session để thông báo sống sót qua redirect.
        Session::set(self::SESSION_KEY, self::$messages);
    }

    /**
     * Lấy toàn bộ thông báo để hiển thị và xoá khỏi session.
     *
     * Mỗi thông báo chỉ được trả về một lần duy nhất.
     *
     * @return array<int,array{type:string,message:string}>
     */
    public static function pull(): array
    {
        self::boot();

        $messages = self::$messages;
        self::$messages = [];
        Session::forget(self::SESSION_KEY);

        return $messages;
    }

    /**
     * Xem toàn bộ thông báo đang chờ mà không xoá.
     *
     * @return array<int,array{type:string,message:string}>
     */
    public static function all(): array
    {
        self::boot();

        return self::$messages;
    }

    /**
     * Chuẩn hoá danh sách thông báo: bỏ phần tử rác, ép kiểu và giới hạn độ dài.
     *
     * @param array<mixed> $raw
     *
     * @return array<int,array{type:string,message:string}>
     */
    private static function normalize(array $raw): array
    {
        $result = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $message = (string) ($item['message'] ?? '');
            if (trim($message) === '') {
                continue;
            }

            $result[] = [
                'type'    => self::normalizeType((string) ($item['type'] ?? 'info')),
                'message' => $message,
            ];
        }

        return array_slice($result, 0, 20);
    }

    private static function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        // `danger` là tên cũ của `error` trong hệ thống toast cũ.
        if ($type === 'danger') {
            return 'error';
        }

        return in_array($type, self::TYPES, true) ? $type : 'info';
    }
}
