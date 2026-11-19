<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Message
{
    public static function create(int $conversationId, int $senderId, string $body): int
    {
        return Database::transaction(static function () use ($conversationId, $senderId, $body): int {
            // UPDATE trước rồi INSERT: ngược thứ tự sẽ giữ shared-lock khoá
            // ngoại rồi lại xin exclusive-lock trên cùng dòng conversations →
            // hai tin gửi song song dễ bị MySQL báo deadlock.
            Database::run('UPDATE conversations SET updated_at = NOW() WHERE id = ?', [$conversationId]);

            return Database::insert(
                'INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)',
                [$conversationId, $senderId, $body]
            );
        });
    }

    /** @return array<int,array> */
    public static function forConversation(int $conversationId, int $limit = 100): array
    {
        return Database::fetchAll(
            'SELECT m.*, u.username, u.display_name, u.avatar_path
             FROM messages m JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ?
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT ' . max(1, $limit),
            [$conversationId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM messages WHERE id = ? LIMIT 1', [$id]);
    }

    public static function markRead(int $conversationId, int $readerId): int
    {
        $stmt = Database::run(
            'UPDATE messages SET read_at = NOW()
             WHERE conversation_id = ? AND sender_id <> ? AND read_at IS NULL',
            [$conversationId, $readerId]
        );

        return $stmt->rowCount();
    }

    /** Số tin nhắn mới hơn tin nhắn đã biết (dùng cho polling). */
    public static function countAfter(int $conversationId, int $afterId): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM messages WHERE conversation_id = ? AND id > ?',
            [$conversationId, $afterId]
        );
    }
}
