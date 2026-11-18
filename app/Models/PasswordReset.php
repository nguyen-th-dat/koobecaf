<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PasswordReset
{
    /** Token dùng để đặt lại mật khẩu có hiệu lực trong số phút. */
    public const EXPIRE_MINUTES = 60;

    /**
     * Tạo token đặt lại mật khẩu cho user.
     *
     * Mọi token cũ của user bị vô hiệu hóa trước khi tạo token mới.
     *
     * @return string token thô (chỉ gửi qua email, database lưu bản hash)
     */
    public static function create(int $userId): string
    {
        self::invalidateAll($userId);

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::EXPIRE_MINUTES * 60);

        Database::insert(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
            [$userId, hash('sha256', $token), $expiresAt]
        );

        return $token;
    }

    /**
     * Tìm bản ghi reset hợp lệ theo token thô.
     *
     * @return array{user_id:int,expires_at:string}|null
     */
    public static function findValid(string $token): ?array
    {
        $row = Database::fetchOne(
            'SELECT user_id, expires_at FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1',
            [hash('sha256', $token)]
        );

        if ($row === null) {
            return null;
        }

        return [
            'user_id'    => (int) $row['user_id'],
            'expires_at' => (string) $row['expires_at'],
        ];
    }

    /** Đánh dấu token đã dùng để không thể dùng lại. */
    public static function markUsed(string $token): void
    {
        Database::run(
            'UPDATE password_resets SET used_at = NOW() WHERE token_hash = ?',
            [hash('sha256', $token)]
        );
    }

    /** Vô hiệu hóa toàn bộ token đang chờ của user. */
    public static function invalidateAll(int $userId): void
    {
        Database::run(
            'UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL',
            [$userId]
        );
    }

    /** Dọn các token đã hết hạn. */
    public static function purgeExpired(): void
    {
        Database::run('DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL');
    }
}
