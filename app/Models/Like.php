<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Like
{
    public static function add(int $postId, int $userId): bool
    {
        $exists = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?',
            [$postId, $userId]
        );
        if ($exists > 0) {
            return false;
        }

        Database::run('INSERT INTO likes (post_id, user_id) VALUES (?, ?)', [$postId, $userId]);

        return true;
    }

    public static function remove(int $postId, int $userId): bool
    {
        $stmt = Database::run('DELETE FROM likes WHERE post_id = ? AND user_id = ?', [$postId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public static function toggle(int $postId, int $userId): bool
    {
        if (self::remove($postId, $userId)) {
            return false;
        }
        self::add($postId, $userId);

        return true;
    }

    /** @return array<int,array> */
    public static function usersByPost(int $postId, int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.username, u.display_name, u.avatar_path
             FROM likes l JOIN users u ON u.id = l.user_id
             WHERE l.post_id = ?
             ORDER BY l.created_at DESC
             LIMIT ' . max(1, $limit),
            [$postId]
        );
    }
}
