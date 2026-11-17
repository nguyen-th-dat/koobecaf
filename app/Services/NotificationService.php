<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;

final class NotificationService
{
    public static function liked(int $postId, int $actorId, int $postOwnerId): void
    {
        if ($postOwnerId === $actorId) {
            return;
        }
        Notification::notify($postOwnerId, $actorId, Notification::TYPE_LIKE, $postId);
    }

    public static function commented(int $postId, int $actorId, int $postOwnerId, ?int $parentAuthorId = null): void
    {
        if ($postOwnerId === $actorId) {
            return;
        }
        Notification::notify($postOwnerId, $actorId, Notification::TYPE_COMMENT, $postId);
    }

    public static function replied(int $postId, int $actorId, int $parentAuthorId, ?int $parentId): void
    {
        if ($parentAuthorId === $actorId) {
            return;
        }
        // entity_id giữ id bình luận gốc để mỗi trả lời là một thông báo riêng,
        // post_id lưu vào `data` để điều hướng về đúng bài viết.
        Notification::notify($parentAuthorId, $actorId, Notification::TYPE_REPLY, (int) ($parentId ?? 0), (string) $postId);
    }

    public static function shared(int $postId, int $actorId, int $postOwnerId): void
    {
        if ($postOwnerId === $actorId) {
            return;
        }
        Notification::notify($postOwnerId, $actorId, Notification::TYPE_SHARE, $postId);
    }

    public static function friendRequested(int $targetId, int $actorId): void
    {
        if ($targetId === $actorId) {
            return;
        }
        Notification::notify($targetId, $actorId, Notification::TYPE_FRIEND_REQUEST, 0);
    }

    public static function friendAccepted(int $targetId, int $actorId): void
    {
        if ($targetId === $actorId) {
            return;
        }
        Notification::notify($targetId, $actorId, Notification::TYPE_FRIEND_ACCEPTED, 0);
    }

    public static function followed(int $targetId, int $actorId): void
    {
        if ($targetId === $actorId) {
            return;
        }
        Notification::notify($targetId, $actorId, Notification::TYPE_FOLLOW, 0);
    }

    public static function message(int $targetId, int $actorId, int $conversationId): void
    {
        if ($targetId === $actorId) {
            return;
        }
        Notification::notify($targetId, $actorId, Notification::TYPE_MESSAGE, $conversationId);
    }
}
