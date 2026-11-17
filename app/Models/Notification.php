<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Notification
{
    public const TYPE_LIKE = 'like';
    public const TYPE_COMMENT = 'comment';
    public const TYPE_REPLY = 'reply';
    public const TYPE_SHARE = 'share';
    public const TYPE_FRIEND_REQUEST = 'friend_request';
    public const TYPE_FRIEND_ACCEPTED = 'friend_accepted';
    public const TYPE_MESSAGE = 'message';
    public const TYPE_FOLLOW = 'follow';

    /**
     * Tạo thông báo nếu chưa tồn tại (dựa trên unique key).
     */
    public static function notify(int $userId, int $actorId, string $type, int $entityId = 0, string $data = ''): int
    {
        $existing = Database::fetchOne(
            'SELECT id FROM notifications WHERE user_id = ? AND actor_id = ? AND type = ? AND entity_id = ?',
            [$userId, $actorId, $type, $entityId]
        );
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return Database::insert(
            'INSERT INTO notifications (user_id, actor_id, type, entity_id, data) VALUES (?, ?, ?, ?, ?)',
            [$userId, $actorId, $type, $entityId, $data]
        );
    }

    /** @return array<int,array> */
    public static function forUser(int $userId, int $limit = 30, int $offset = 0): array
    {
        return Database::fetchAll(
            'SELECT n.*, u.username AS actor_username, u.display_name AS actor_display_name, u.avatar_path AS actor_avatar_path
             FROM notifications n JOIN users u ON u.id = n.actor_id
             WHERE n.user_id = ?
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            [$userId]
        );
    }

    public static function countForUser(int $userId): int
    {
        return (int) Database::fetchValue('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [$userId]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM notifications WHERE id = ? LIMIT 1', [$id]);
    }

    public static function markRead(int $id, int $userId): bool
    {
        $stmt = Database::run('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);

        return $stmt->rowCount() > 0;
    }

    public static function markAllRead(int $userId): int
    {
        $stmt = Database::run('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', [$userId]);

        return $stmt->rowCount();
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
    }
}
