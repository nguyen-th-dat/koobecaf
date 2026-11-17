<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Friendship
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const DECLINED = 'declined';

    /**
     * Trạng thái quan hệ giữa hai user từ góc nhìn của $userId.
     *
     * @return string|null 'none'|'pending_outgoing'|'pending_incoming'|'accepted'|'declined'
     */
    public static function status(int $userId, int $otherId): ?string
    {
        $row = Database::fetchOne(
            'SELECT user_id, friend_id, status FROM friendships
             WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
             LIMIT 1',
            [$userId, $otherId, $otherId, $userId]
        );
        if ($row === null) {
            return 'none';
        }
        if ($row['status'] === self::ACCEPTED) {
            return self::ACCEPTED;
        }
        if ($row['status'] === self::DECLINED) {
            return 'none';
        }

        return (int) $row['user_id'] === $userId ? 'pending_outgoing' : 'pending_incoming';
    }

    public static function request(int $userId, int $friendId): bool
    {
        if ($userId === $friendId) {
            return false;
        }
        $status = self::status($userId, $friendId);
        if ($status === 'none') {
            $existing = Database::fetchOne(
                'SELECT id FROM friendships
                 WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
                 LIMIT 1',
                [$userId, $friendId, $friendId, $userId]
            );
            if ($existing !== null) {
                Database::run(
                    'UPDATE friendships SET user_id = ?, friend_id = ?, status = ?, updated_at = NOW() WHERE id = ?',
                    [$userId, $friendId, self::PENDING, $existing['id']]
                );
            } else {
                Database::run(
                    'INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, ?)',
                    [$userId, $friendId, self::PENDING]
                );
            }

            return true;
        }

        if ($status === 'pending_incoming') {
            Database::run(
                'UPDATE friendships SET status = ?, updated_at = NOW() WHERE user_id = ? AND friend_id = ?',
                [self::ACCEPTED, $friendId, $userId]
            );

            return true;
        }

        return false;
    }

    public static function accept(int $userId, int $friendshipId): bool
    {
        $row = Database::fetchOne(
            "SELECT * FROM friendships WHERE id = ? AND friend_id = ? AND status = 'pending'",
            [$friendshipId, $userId]
        );
        if ($row === null) {
            return false;
        }
        Database::run('UPDATE friendships SET status = ?, updated_at = NOW() WHERE id = ?', [self::ACCEPTED, $friendshipId]);

        return true;
    }

    public static function decline(int $userId, int $friendshipId): bool
    {
        $stmt = Database::run(
            "UPDATE friendships SET status = 'declined', updated_at = NOW() WHERE id = ? AND friend_id = ? AND status = 'pending'",
            [$friendshipId, $userId]
        );

        return $stmt->rowCount() > 0;
    }

    public static function cancel(int $userId, int $friendId): bool
    {
        $stmt = Database::run(
            "DELETE FROM friendships WHERE user_id = ? AND friend_id = ? AND status = 'pending'",
            [$userId, $friendId]
        );

        return $stmt->rowCount() > 0;
    }

    public static function unfriend(int $userId, int $friendId): bool
    {
        $stmt = Database::run(
            "DELETE FROM friendships WHERE status = 'accepted' AND ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))",
            [$userId, $friendId, $friendId, $userId]
        );

        return $stmt->rowCount() > 0;
    }

    public static function areFriends(int $userId, int $otherId): bool
    {
        return self::status($userId, $otherId) === self::ACCEPTED;
    }

    /** @return array<int,array> */
    public static function friends(int $userId): array
    {
        return Database::fetchAll(
            "SELECT u.id, u.username, u.display_name, u.avatar_path, u.bio
             FROM friendships fr
             JOIN users u ON u.id = IF(fr.user_id = ?, fr.friend_id, fr.user_id)
             WHERE fr.status = 'accepted' AND (fr.user_id = ? OR fr.friend_id = ?)
             ORDER BY u.display_name ASC",
            [$userId, $userId, $userId]
        );
    }

    /** @return array<int,array> */
    public static function requests(int $userId, string $status = self::PENDING): array
    {
        return Database::fetchAll(
            "SELECT fr.id, fr.user_id, fr.friend_id, fr.status, fr.created_at,
                    u.username, u.display_name, u.avatar_path, u.bio
             FROM friendships fr
             JOIN users u ON u.id = fr.user_id
             WHERE fr.friend_id = ? AND fr.status = ?
             ORDER BY fr.created_at DESC",
            [$userId, $status]
        );
    }

    public static function countPending(int $userId): int
    {
        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM friendships WHERE friend_id = ? AND status = 'pending'",
            [$userId]
        );
    }
}
