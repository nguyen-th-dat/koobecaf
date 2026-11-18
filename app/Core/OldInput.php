<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Giá trị form cũ sau khi validate thất bại.
 *
 * Giá trị được ghi vào session ở request xử lý POST, rồi được "nhặt" sang bộ nhớ
 * tạm ở request kế tiếp (sau redirect) và xoá khỏi session để không dùng lại
 * lâu dài. Nhờ vậy người dùng không mất dữ liệu đã nhập khi bị báo lỗi.
 */
final class OldInput
{
    /** @var array<string,mixed> */
    private static array $values = [];

    /** @var array<int,string> */
    private static array $keys = [];

    /** @var array<int,string> */
    private static array $clearedDrafts = [];

    /** Nhặt giá trị cũ từ session (gọi một lần mỗi request). */
    public static function boot(): void
    {
        $stored = Session::get('_old', []);
        Session::forget('_old');

        self::$values = is_array($stored) ? $stored : [];
        self::$keys = array_values(array_map('strval', array_keys(self::$values)));

        $cleared = Session::get('_draft_cleared', []);
        Session::forget('_draft_cleared');

        self::$clearedDrafts = is_array($cleared)
            ? array_values(array_filter($cleared, 'is_string'))
            : [];
    }

    /** Lưu giá trị cũ để dùng ở request kế tiếp. */
    public static function flash(array $values): void
    {
        Session::set('_old', $values);
    }

    public static function get(string $key, mixed $default = ''): mixed
    {
        if (!array_key_exists($key, self::$values)) {
            return $default;
        }

        $value = self::$values[$key];

        return $value === null ? $default : $value;
    }

    /** @return array<int,string> Danh sách field có giá trị cũ được render từ server. */
    public static function keys(): array
    {
        return self::$keys;
    }

    /**
     * Báo cho trình duyệt xoá nháp đã lưu sau khi form được xử lý thành công.
     */
    public static function clearDraft(string ...$keys): void
    {
        $current = Session::get('_draft_cleared', []);
        $merged = array_values(array_unique(array_merge(is_array($current) ? $current : [], $keys)));

        Session::set('_draft_cleared', $merged);
        self::$clearedDrafts = $merged;
    }

    /** @return array<int,string> */
    public static function clearedDrafts(): array
    {
        return self::$clearedDrafts;
    }
}
