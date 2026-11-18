<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne('SELECT * FROM users WHERE email = ? LIMIT 1', [strtolower($email)]);
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::fetchOne('SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = ?';
        $params = [strtolower($email)];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        return (int) Database::fetchValue($sql, $params) > 0;
    }

    public static function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE username = ?';
        $params = [$username];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        return (int) Database::fetchValue($sql, $params) > 0;
    }

    public static function create(array $data): int
    {
        return Database::insert(
            'INSERT INTO users (username, email, password_hash, display_name, bio) VALUES (?, ?, ?, ?, ?)',
            [
                $data['username'],
                strtolower($data['email']),
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['display_name'],
                $data['bio'] ?? '',
            ]
        );
    }

    public static function updateProfile(int $id, array $data): void
    {
        $fields = [];
        $params = [];
        foreach (['display_name', 'bio', 'avatar_path', 'cover_path'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $params[] = $data[$field];
            }
        }
        if ($fields === []) {
            return;
        }
        $params[] = $id;
        Database::run('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
    }

    public static function updatePassword(int $id, string $plainPassword): void
    {
        Database::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($plainPassword, PASSWORD_DEFAULT), $id]);
    }

    public static function updateTheme(int $id, string $theme): void
    {
        Database::run('UPDATE users SET theme = ? WHERE id = ?', [$theme, $id]);
    }

    /** @return array<int,array> */
    public static function search(string $keyword, int $limit = 20, ?int $exceptId = null): array
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';
        $sql = 'SELECT id, username, display_name, avatar_path, bio FROM users
                WHERE (username LIKE ? OR display_name LIKE ? OR email LIKE ?)';
        $params = [$like, $like, $like];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' ORDER BY display_name ASC LIMIT ' . max(1, $limit);

        return Database::fetchAll($sql, $params);
    }

    public static function suggestions(int $userId, int $limit = 5): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.username, u.display_name, u.avatar_path
             FROM users u
             WHERE u.id <> ?
               AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
             ORDER BY RAND()
             LIMIT ' . max(1, $limit),
            [$userId, $userId]
        );
    }

    public static function followingSuggestions(int $userId, int $limit = 5): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.username, u.display_name, u.avatar_path
             FROM users u
             JOIN follows f ON f.following_id = u.id AND f.follower_id = ?
             WHERE u.id <> ?
             LIMIT ' . max(1, $limit),
            [$userId, $userId]
        );
    }

    /** Số liệu tổng hợp cho trang cá nhân. */
    public static function stats(int $id): array
    {
        return [
            'posts'     => \App\Models\Post::countByUser($id),
            'friends'   => (int) Database::fetchValue("SELECT COUNT(*) FROM friendships WHERE status = 'accepted' AND (user_id = ? OR friend_id = ?)", [$id, $id]),
            'followers' => (int) Database::fetchValue('SELECT COUNT(*) FROM follows WHERE following_id = ?', [$id]),
            'following' => (int) Database::fetchValue('SELECT COUNT(*) FROM follows WHERE follower_id = ?', [$id]),
            'comments'  => (int) Database::fetchValue('SELECT COUNT(*) FROM comments WHERE user_id = ?', [$id]),
            'shares'    => (int) Database::fetchValue('SELECT COUNT(*) FROM shares WHERE user_id = ?', [$id]),
            'likes'     => (int) Database::fetchValue('SELECT COUNT(*) FROM likes WHERE user_id = ?', [$id]),
            'photos'    => (int) Database::fetchValue(
                'SELECT COUNT(*) FROM post_images pi
                 JOIN posts p ON p.id = pi.post_id
                 WHERE p.user_id = ? AND p.shared_post_id IS NULL',
                [$id]
            ),
        ];
    }

    /**
     * Danh sách ảnh người dùng đã đăng kèm bài viết gốc.
     *
     * @return array<int,array>
     */
    public static function photos(int $id, int $limit = 24): array
    {
        return Database::fetchAll(
            'SELECT pi.id, pi.path, pi.post_id, p.content, p.created_at
             FROM post_images pi
             JOIN posts p ON p.id = pi.post_id
             WHERE p.user_id = ? AND p.shared_post_id IS NULL
             ORDER BY p.created_at DESC, pi.position ASC
             LIMIT ' . max(1, $limit),
            [$id]
        );
    }
}
