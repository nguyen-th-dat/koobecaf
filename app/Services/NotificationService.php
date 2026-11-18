<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

final class NotificationService
{
    public static function liked(int $postId, int $actorId, int $postOwnerId): void
    {
        if ($postOwnerId === $actorId) {
            return;
        }
        self::push($postOwnerId, $actorId, Notification::TYPE_LIKE, $postId);
    }

    public static function commented(int $postId, int $actorId, int $postOwnerId, ?int $parentAuthorId = null): void
    {
        if ($postOwnerId === $actorId) {
            return;
        }
        self::push($postOwnerId, $actorId, Notification::TYPE_COMMENT, $postId);
    }

    public static function replied(int $postId, int $actorId, int $parentAuthorId, ?int $parentId): void
    {
        if ($parentAuthorId === $actorId) {
            return;
        }
        // entity_id giữ id bình luận gốc để mỗi trả lời là một thông báo riêng,
        // post_id lưu vào `data` để điều hướng về đúng bài viết.
        self::push($parentAuthorId, $actorId, Notification::TYPE_REPLY, (int) ($parentId ?? 0), (string) $postId);
    }

    public static function shared(int $postId, int $actorId, int $postOwnerId): void
    {
        if ($postOwnerId === $actorId) {
            return;
        }
        self::push($postOwnerId, $actorId, Notification::TYPE_SHARE, $postId);
    }

    public static function friendRequested(int $targetId, int $actorId): void
    {
        if ($targetId === $actorId) {
            return;
        }
        self::push($targetId, $actorId, Notification::TYPE_FRIEND_REQUEST, 0);
    }

    public static function friendAccepted(int $targetId, int $actorId): void
    {
        if ($targetId === $actorId) {
            return;
        }
        self::push($targetId, $actorId, Notification::TYPE_FRIEND_ACCEPTED, 0);
    }

    public static function followed(int $targetId, int $actorId): void
    {
        if ($targetId === $actorId) {
            return;
        }
        self::push($targetId, $actorId, Notification::TYPE_FOLLOW, 0);
    }

    public static function message(int $targetId, int $actorId, int $conversationId): void
    {
        if ($targetId === $actorId) {
            return;
        }
        self::push($targetId, $actorId, Notification::TYPE_MESSAGE, $conversationId);
    }

    /**
     * Tạo thông báo và đẩy realtime tới người nhận.
     *
     * Nếu thông báo đã tồn tại (unique key) thì không tạo lại và không phát
     * sự kiện — tránh thông báo trùng.
     */
    private static function push(int $userId, int $actorId, string $type, int $entityId = 0, string $data = ''): void
    {
        if (Notification::exists($userId, $actorId, $type, $entityId)) {
            return;
        }

        $id = Notification::notify($userId, $actorId, $type, $entityId, $data);
        if ($id <= 0) {
            return;
        }

        $actor = User::find($actorId);

        RealtimeService::notification($userId, [
            'id'           => $id,
            'type'         => $type,
            'entity_id'    => $entityId,
            'label'        => notification_label($type),
            'icon'         => notification_icon($type),
            'created_at'   => date('Y-m-d H:i:s'),
            'actor'        => RealtimeService::userPayload($actorId, $actor),
            'url'          => \App\Controllers\NotificationController::targetUrl([
                'type'      => $type,
                'entity_id' => $entityId,
                'actor_id'  => $actorId,
                'data'      => $data,
            ]),
        ], Notification::unreadCount($userId));
    }
}
