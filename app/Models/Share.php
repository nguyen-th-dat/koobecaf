<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Share
{
    public static function exists(int $postId, int $userId): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM shares WHERE post_id = ? AND user_id = ?',
            [$postId, $userId]
        ) > 0;
    }

    public static function create(int $postId, int $userId, string $content = ''): int
    {
        return Database::insert(
            'INSERT INTO shares (post_id, user_id, content) VALUES (?, ?, ?)',
            [$postId, $userId, $content]
        );
    }

    public static function delete(int $postId, int $userId): void
    {
        Database::run('DELETE FROM shares WHERE post_id = ? AND user_id = ?', [$postId, $userId]);
    }

    /** @return array<int,array> */
    public static function byUser(int $userId, int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT s.*, p.user_id AS post_author_id, p.content AS post_content, p.privacy AS post_privacy,
                    au.display_name AS author_display_name, au.username AS author_username,
                    au.avatar_path AS author_avatar_path
             FROM shares s
             JOIN posts p ON p.id = s.post_id
             JOIN users au ON au.id = p.user_id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC
             LIMIT ' . max(1, $limit),
            [$userId]
        );
    }
}
