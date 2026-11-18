<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Post
{
    public const PRIVACY_PUBLIC = 'public';
    public const PRIVACY_FOLLOWERS = 'followers';
    public const PRIVACY_PRIVATE = 'private';

    /**
     * Điều kiện SQL giới hạn bài viết mà người xem được phép thấy.
     *
     * @return array{0:string,1:array}
     */
    public static function visibilitySql(?int $viewerId, string $alias = 'p'): array
    {
        if ($viewerId === null) {
            return ["{$alias}.privacy = 'public'", []];
        }

        $sql = "(
            {$alias}.user_id = ?
            OR {$alias}.privacy = 'public'
            OR ({$alias}.privacy = 'followers' AND (
                EXISTS (SELECT 1 FROM follows f WHERE f.follower_id = ? AND f.following_id = {$alias}.user_id)
                OR EXISTS (SELECT 1 FROM friendships fr WHERE fr.status = 'accepted' AND ((fr.user_id = ? AND fr.friend_id = {$alias}.user_id) OR (fr.friend_id = ? AND fr.user_id = {$alias}.user_id)))
            ))
        )";

        return [$sql, [$viewerId, $viewerId, $viewerId, $viewerId]];
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT p.*, u.username, u.display_name, u.avatar_path, u.id AS author_id
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function canView(array $post, ?int $viewerId): bool
    {
        $authorId = (int) $post['user_id'];
        if ($viewerId !== null && $viewerId === $authorId) {
            return true;
        }

        $privacy = (string) $post['privacy'];
        if ($privacy === self::PRIVACY_PUBLIC) {
            return true;
        }
        if ($viewerId === null) {
            return false;
        }
        if ($privacy === self::PRIVACY_PRIVATE) {
            return false;
        }

        $isFollower = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?',
            [$viewerId, $authorId]
        ) > 0;
        if ($isFollower) {
            return true;
        }

        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM friendships
             WHERE status = 'accepted' AND ((user_id = ? AND friend_id = ?) OR (friend_id = ? AND user_id = ?))",
            [$viewerId, $authorId, $authorId, $viewerId]
        ) > 0;
    }

    public static function canManage(array $post, ?int $viewerId): bool
    {
        return $viewerId !== null && (int) $post['user_id'] === $viewerId;
    }

    /** Bài viết của riêng người dùng (dùng cho trang cá nhân). Không gồm bài chia sẻ. */
    public static function byUser(int $userId, ?int $viewerId, int $limit = 20, int $offset = 0): array
    {
        [$vis, $params] = self::visibilitySql($viewerId);

        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name, u.avatar_path
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.user_id = ? AND p.shared_post_id IS NULL AND {$vis}
             ORDER BY p.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            array_merge([$userId], $params)
        );
    }

    /** Tổng số bài viết gốc của người dùng (không tính bài chia sẻ). */
    public static function countByUser(int $userId): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM posts WHERE user_id = ? AND shared_post_id IS NULL',
            [$userId]
        );
    }

    /** News feed: bài của chính mình + người được theo dõi + bạn bè. */
    public static function feed(int $viewerId, int $limit = 10, int $offset = 0): array
    {
        [$vis, $visParams] = self::visibilitySql($viewerId);

        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name, u.avatar_path
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE (p.user_id = ?
                    OR p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?)
                    OR p.user_id IN (SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'))
               AND {$vis}
             ORDER BY p.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            array_merge([$viewerId, $viewerId, $viewerId], $visParams)
        );
    }

    public static function countFeed(int $viewerId): int
    {
        [$vis, $visParams] = self::visibilitySql($viewerId);

        return (int) Database::fetchValue(
            "SELECT COUNT(*)
             FROM posts p
             WHERE (p.user_id = ?
                    OR p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?)
                    OR p.user_id IN (SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'))
               AND {$vis}",
            array_merge([$viewerId, $viewerId, $viewerId], $visParams)
        );
    }

    /**
     * Danh sách user id sẽ nhận bài viết realtime (tác giả + người theo dõi + bạn bè).
     *
     * Phạm vi này khớp với truy vấn feed (`Post::feed`) nên bài mới hiện đúng
     * ở feed của những người thực sự nhìn thấy nó.
     *
     * @return array<int,int>
     */
    public static function audience(int $authorId, string $privacy): array
    {
        if ($privacy === self::PRIVACY_PRIVATE) {
            return [$authorId];
        }

        $rows = Database::fetchAll(
            "SELECT follower_id AS user_id FROM follows WHERE following_id = ?
             UNION
             SELECT friend_id AS user_id FROM friendships WHERE user_id = ? AND status = 'accepted'
             UNION
             SELECT user_id AS user_id FROM friendships WHERE friend_id = ? AND status = 'accepted'
             UNION
             SELECT ? AS user_id",
            [$authorId, $authorId, $authorId, $authorId]
        );

        return array_map(static fn (array $row): int => (int) $row['user_id'], $rows);
    }

    /** Bài viết công khai (dùng cho khách chưa đăng nhập). */
    public static function findPublic(int $limit = 10, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name, u.avatar_path
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.privacy = 'public'
             ORDER BY p.created_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
    }

    public static function countPublic(): int
    {
        return (int) Database::fetchValue("SELECT COUNT(*) FROM posts WHERE privacy = 'public'");
    }

    public static function create(int $userId, string $content, string $privacy): int
    {
        return Database::insert(
            'INSERT INTO posts (user_id, content, privacy, shared_url) VALUES (?, ?, ?, ?)',
            [$userId, $content, $privacy, self::extractUrl($content)]
        );
    }

    /** Lấy URL đầu tiên xuất hiện trong nội dung bài viết. */
    public static function extractUrl(string $content): string
    {
        if (preg_match('#https?://[^\s<>"\'\)]+#i', $content, $m) === 1) {
            return rtrim($m[0], '.,;:');
        }

        return '';
    }

    public static function createShare(int $userId, int $originalPostId, string $content, string $privacy): int
    {
        return Database::insert(
            'INSERT INTO posts (user_id, content, privacy, shared_post_id, share_content) VALUES (?, ?, ?, ?, ?)',
            [$userId, '', $privacy, $originalPostId, $content]
        );
    }

    public static function update(int $id, string $content, string $privacy): void
    {
        Database::run(
            'UPDATE posts SET content = ?, privacy = ?, shared_url = ? WHERE id = ?',
            [$content, $privacy, self::extractUrl($content), $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM posts WHERE id = ?', [$id]);
    }

    /** @return array<int,array> */
    public static function images(int $postId): array
    {
        return Database::fetchAll(
            'SELECT id, path, position FROM post_images WHERE post_id = ? ORDER BY position ASC, id ASC',
            [$postId]
        );
    }

    /** @param array<int,string> $paths */
    public static function attachImages(int $postId, array $paths): void
    {
        $position = 0;
        foreach ($paths as $path) {
            Database::run(
                'INSERT INTO post_images (post_id, path, position) VALUES (?, ?, ?)',
                [$postId, $path, $position]
            );
            $position++;
        }
    }

    public static function deleteImages(int $postId): void
    {
        Database::run('DELETE FROM post_images WHERE post_id = ?', [$postId]);
    }

    public static function countImages(int $postId): int
    {
        return (int) Database::fetchValue('SELECT COUNT(*) FROM post_images WHERE post_id = ?', [$postId]);
    }

    public static function likeCount(int $postId): int
    {
        return (int) Database::fetchValue('SELECT COUNT(*) FROM likes WHERE post_id = ?', [$postId]);
    }

    public static function commentCount(int $postId): int
    {
        return (int) Database::fetchValue('SELECT COUNT(*) FROM comments WHERE post_id = ?', [$postId]);
    }

    public static function shareCount(int $postId): int
    {
        return (int) Database::fetchValue('SELECT COUNT(*) FROM shares WHERE post_id = ?', [$postId]);
    }

    public static function isLiked(int $postId, int $userId): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?',
            [$postId, $userId]
        ) > 0;
    }

    public static function likedUserIds(int $postId): array
    {
        $rows = Database::fetchAll('SELECT user_id FROM likes WHERE post_id = ?', [$postId]);

        return array_map(static fn (array $row): int => (int) $row['user_id'], $rows);
    }

    /** Tìm kiếm bài viết công khai theo nội dung. */
    public static function search(string $keyword, int $limit = 20, int $offset = 0): array
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';

        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name, u.avatar_path
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.privacy = 'public' AND p.content LIKE ?
             ORDER BY p.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            [$like]
        );
    }

    public static function countSearch(string $keyword): int
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';

        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM posts WHERE privacy = 'public' AND content LIKE ?",
            [$like]
        );
    }
}
