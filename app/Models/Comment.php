<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Comment
{
    public static function find(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT c.*, u.username, u.display_name, u.avatar_path
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.id = ? LIMIT 1',
            [$id]
        );
    }

    /** @return array<int,array> Bình luận gốc kèm các trả lời (lồng nhau). */
    public static function forPost(int $postId): array
    {
        $rows = Database::fetchAll(
            'SELECT c.*, u.username, u.display_name, u.avatar_path
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.post_id = ?
             ORDER BY c.created_at ASC',
            [$postId]
        );

        $ids = [];
        $children = [];
        foreach ($rows as $row) {
            $ids[(int) $row['id']] = true;
        }
        foreach ($rows as $row) {
            $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : 0;
            if ($parentId !== 0 && !isset($ids[$parentId])) {
                $parentId = 0;
            }
            $children[$parentId][] = $row;
        }

        $build = static function (int $parentId) use (&$build, $children): array {
            $result = [];
            foreach ($children[$parentId] ?? [] as $row) {
                $row['replies'] = $build((int) $row['id']);
                $result[] = $row;
            }

            return $result;
        };

        return $build(0);
    }

    public static function countForPost(int $postId): int
    {
        return (int) Database::fetchValue('SELECT COUNT(*) FROM comments WHERE post_id = ?', [$postId]);
    }

    public static function create(int $postId, int $userId, string $content, ?int $parentId = null): int
    {
        return Database::insert(
            'INSERT INTO comments (post_id, user_id, parent_id, content) VALUES (?, ?, ?, ?)',
            [$postId, $userId, $parentId, $content]
        );
    }

    public static function update(int $id, string $content): void
    {
        Database::run('UPDATE comments SET content = ? WHERE id = ?', [$content, $id]);
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM comments WHERE id = ?', [$id]);
    }

    /** Bình luận mới nhất của người dùng (dùng cho trang cá nhân). */
    public static function recentByUser(int $userId, int $limit = 10): array
    {
        return Database::fetchAll(
            'SELECT c.*, p.user_id AS post_author_id,
                    u.display_name, u.username, u.avatar_path
             FROM comments c
             JOIN posts p ON p.id = c.post_id
             JOIN users u ON u.id = c.user_id
             WHERE c.user_id = ?
             ORDER BY c.created_at DESC
             LIMIT ' . max(1, $limit),
            [$userId]
        );
    }
}
