<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Follow
{
    public static function isFollowing(int $followerId, int $followingId): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?',
            [$followerId, $followingId]
        ) > 0;
    }

    public static function add(int $followerId, int $followingId): bool
    {
        if ($followerId === $followingId || self::isFollowing($followerId, $followingId)) {
            return false;
        }

        Database::run('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)', [$followerId, $followingId]);

        return true;
    }

    public static function remove(int $followerId, int $followingId): bool
    {
        $stmt = Database::run('DELETE FROM follows WHERE follower_id = ? AND following_id = ?', [$followerId, $followingId]);

        return $stmt->rowCount() > 0;
    }

    /** @return array<int,array> */
    public static function followers(int $userId): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.username, u.display_name, u.avatar_path, u.bio
             FROM follows f JOIN users u ON u.id = f.follower_id
             WHERE f.following_id = ?
             ORDER BY f.created_at DESC',
            [$userId]
        );
    }

    /** @return array<int,array> */
    public static function following(int $userId): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.username, u.display_name, u.avatar_path, u.bio
             FROM follows f JOIN users u ON u.id = f.following_id
             WHERE f.follower_id = ?
             ORDER BY f.created_at DESC',
            [$userId]
        );
    }
}
