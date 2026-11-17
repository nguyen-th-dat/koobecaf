<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Conversation
{
    /** Tìm cuộc trò chuyện giữa hai user, tạo mới nếu chưa có. */
    public static function between(int $userA, int $userB): int
    {
        $row = Database::fetchOne(
            'SELECT c.id
             FROM conversations c
             JOIN conversation_members m1 ON m1.conversation_id = c.id AND m1.user_id = ?
             JOIN conversation_members m2 ON m2.conversation_id = c.id AND m2.user_id = ?
             WHERE (SELECT COUNT(*) FROM conversation_members cm WHERE cm.conversation_id = c.id) = 2
             LIMIT 1',
            [$userA, $userB]
        );
        if ($row !== null) {
            return (int) $row['id'];
        }

        return Database::transaction(static function () use ($userA, $userB): int {
            $conversationId = Database::insert('INSERT INTO conversations (created_at, updated_at) VALUES (NOW(), NOW())');
            Database::run('INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?)', [$conversationId, $userA]);
            Database::run('INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?)', [$conversationId, $userB]);

            return $conversationId;
        });
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM conversations WHERE id = ? LIMIT 1', [$id]);
    }

    public static function isMember(int $conversationId, int $userId): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM conversation_members WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $userId]
        ) > 0;
    }

    /** @return array<int,array> Danh sách cuộc trò chuyện của user kèm thông tin người kia. */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT c.id,
                    c.updated_at,
                    u.id AS other_id,
                    u.username AS other_username,
                    u.display_name AS other_display_name,
                    u.avatar_path AS other_avatar_path,
                    (SELECT COUNT(*) FROM messages m
                      WHERE m.conversation_id = c.id
                        AND m.sender_id <> ?
                        AND m.read_at IS NULL) AS unread_count,
                    (SELECT m.body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_message,
                    (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_message_at
             FROM conversations c
             JOIN conversation_members cm ON cm.conversation_id = c.id AND cm.user_id = ?
             JOIN conversation_members cm2 ON cm2.conversation_id = c.id AND cm2.user_id <> ?
             JOIN users u ON u.id = cm2.user_id
             ORDER BY COALESCE(last_message_at, c.created_at) DESC',
            [$userId, $userId, $userId]
        );
    }

    /** @return array<int,int> Danh sách user trong cuộc trò chuyện. */
    public static function memberIds(int $conversationId): array
    {
        $rows = Database::fetchAll('SELECT user_id FROM conversation_members WHERE conversation_id = ?', [$conversationId]);

        return array_map(static fn (array $row): int => (int) $row['user_id'], $rows);
    }

    public static function totalUnread(int $userId): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*)
             FROM messages m
             JOIN conversation_members cm ON cm.conversation_id = m.conversation_id AND cm.user_id = ?
             WHERE m.sender_id <> ? AND m.read_at IS NULL',
            [$userId, $userId]
        );
    }
}
