<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PostImage
{
    /** @return array<int,array> */
    public static function all(int $postId): array
    {
        return Database::fetchAll(
            'SELECT id, path, position FROM post_images WHERE post_id = ? ORDER BY position ASC, id ASC',
            [$postId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM post_images WHERE id = ? LIMIT 1', [$id]);
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM post_images WHERE id = ?', [$id]);
    }
}
