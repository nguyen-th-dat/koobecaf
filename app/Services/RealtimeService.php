<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\WsPublisher;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/**
 * Phát sự kiện realtime (feed + thông báo) tới WebSocket server.
 *
 * Mọi hành động tương tác (đăng bài, thích, bình luận, chia sẻ, theo dõi,
 * kết bạn…) đều gọi qua đây để client nhận được cập nhật tức thì mà không
 * phải tải lại trang.
 *
 * Nếu WebSocket server không chạy, các lời gọi này bỏ qua âm thầm — ứng
 * dụng vẫn hoạt động bình thường với cơ chế polling dự phòng.
 */
final class RealtimeService
{
    /** Phát sự kiện "bài viết mới" tới feed của những người liên quan. */
    public static function postCreated(int $postId): void
    {
        $post = Post::find($postId);
        if ($post === null) {
            return;
        }

        WsPublisher::publish('post.created', [
            'audience' => Post::audience((int) $post['user_id'], (string) $post['privacy']),
            'post'     => self::postPayload($post),
        ]);
    }

    /** Phát sự kiện "bài viết bị xoá" tới những người đang xem feed. */
    public static function postDeleted(int $postId, int $authorId, string $privacy): void
    {
        WsPublisher::publish('post.deleted', [
            'audience' => Post::audience($authorId, $privacy),
            'post_id'  => $postId,
            'event'    => 'post.deleted',
        ]);
    }

    /** Phát sự kiện cập nhật nội dung bài viết. */
    public static function postUpdated(int $postId): void
    {
        $post = Post::find($postId);
        if ($post === null) {
            return;
        }

        WsPublisher::publish('post.updated', [
            'audience' => Post::audience((int) $post['user_id'], (string) $post['privacy']),
            'post_id'  => $postId,
            'event'    => 'post.updated',
            'stats'    => self::stats($postId),
        ]);
    }

    /** Phát sự kiện thích / bỏ thích. */
    public static function postLiked(int $postId, int $actorId, bool $liked): void
    {
        self::postEvent($postId, $actorId, $liked ? 'post.liked' : 'post.unliked');
    }

    /** Phát sự kiện bình luận mới. */
    public static function postCommented(int $postId, int $actorId, int $commentId): void
    {
        $comment = Comment::find($commentId);
        if ($comment === null) {
            return;
        }

        self::postEvent($postId, $actorId, 'post.commented', [
            'comment' => [
                'id'           => (int) $comment['id'],
                'content'      => (string) $comment['content'],
                'parent_id'    => $comment['parent_id'] !== null ? (int) $comment['parent_id'] : null,
                'created_at'   => (string) $comment['created_at'],
                'author'       => self::userPayload((int) $comment['user_id']),
            ],
        ]);
    }

    /** Phát sự kiện xoá bình luận. */
    public static function postUncommented(int $postId, int $actorId): void
    {
        self::postEvent($postId, $actorId, 'post.uncommented');
    }

    /** Phát sự kiện chia sẻ bài viết. */
    public static function postShared(int $postId, int $actorId): void
    {
        self::postEvent($postId, $actorId, 'post.shared');
    }

    /**
     * Phát sự kiện tương tác lên một bài viết tới những người được xem bài.
     *
     * @param array<string,mixed> $extra
     */
    private static function postEvent(int $postId, int $actorId, string $event, array $extra = []): void
    {
        $post = Post::find($postId);
        if ($post === null) {
            return;
        }

        WsPublisher::publish($event, array_merge([
            'audience' => Post::audience((int) $post['user_id'], (string) $post['privacy']),
            'post_id'  => $postId,
            'event'    => $event,
            'actor'    => self::userPayload($actorId),
            'stats'    => self::stats($postId),
        ], $extra));
    }

    /** Phát sự kiện theo dõi / kết bạn tới các bên liên quan. */
    public static function social(string $event, int $actorId, int $targetId): void
    {
        $audience = array_values(array_unique([$actorId, $targetId]));

        WsPublisher::publish('social', [
            'audience' => $audience,
            'event'    => $event,
            'actor'    => self::userPayload($actorId),
            'target'   => self::userPayload($targetId),
        ]);
    }

    /**
     * Phát một thông báo mới tới người nhận.
     *
     * @param array<string,mixed> $notification
     */
    public static function notification(int $userId, array $notification, int $unreadCount): void
    {
        WsPublisher::publish('notification.created', [
            'user_id'      => $userId,
            'notification' => $notification,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Payload bài viết gửi tới client.
     *
     * @param array<string,mixed> $post
     * @return array<string,mixed>
     */
    public static function postPayload(array $post): array
    {
        $postId = (int) $post['id'];

        $images = [];
        foreach (\App\Models\PostImage::all($postId) as $image) {
            $images[] = upload_url((string) $image['path']);
        }

        return [
            'id'           => $postId,
            'content'      => (string) $post['content'],
            'privacy'      => (string) $post['privacy'],
            'shared_post_id' => $post['shared_post_id'] !== null ? (int) $post['shared_post_id'] : null,
            'share_content' => (string) ($post['share_content'] ?? ''),
            'created_at'   => (string) $post['created_at'],
            // $post chứa cột `id` của bài viết nên không thể dùng làm hồ sơ
            // người dùng — luôn tải user theo user_id.
            'author'       => self::userPayload((int) $post['user_id']),
            'images'       => $images,
            'original'     => self::originalPayload($post),
            'url'          => url('/post/' . $postId),
            'permalink'    => url('/p/' . $postId),
            'stats'        => self::stats($postId),
        ];
    }

    /**
     * Payload bài viết gốc của một bài chia sẻ (null nếu không phải bài chia sẻ).
     *
     * @param array<string,mixed> $post
     * @return array<string,mixed>|null
     */
    private static function originalPayload(array $post): ?array
    {
        $originalId = $post['shared_post_id'] !== null ? (int) $post['shared_post_id'] : 0;
        if ($originalId <= 0) {
            return null;
        }

        $original = Post::find($originalId);
        if ($original === null) {
            return null;
        }

        $images = [];
        foreach (\App\Models\PostImage::all($originalId) as $image) {
            $images[] = upload_url((string) $image['path']);
        }

        return [
            'id'           => $originalId,
            'content'      => (string) $original['content'],
            'created_at'   => (string) $original['created_at'],
            'author'       => self::userPayload((int) $original['user_id']),
            'images'       => $images,
        ];
    }

    /**
     * Số liệu tương tác của bài viết.
     *
     * @return array<string,int>
     */
    public static function stats(int $postId): array
    {
        return [
            'like_count'    => Post::likeCount($postId),
            'comment_count' => Post::commentCount($postId),
            'share_count'   => Post::shareCount($postId),
        ];
    }

    /**
     * Payload người dùng gửi tới client.
     *
     * @param array<string,mixed>|null $user
     * @return array<string,mixed>|null
     */
    public static function userPayload(int $userId, ?array $user = null): ?array
    {
        if ($user === null || (int) ($user['id'] ?? 0) !== $userId) {
            // Bảo đảm id trả về luôn là id người dùng, không phải id của bản
            // ghi (post) khi truyền nhầm mảng bài viết.
            $user = User::find($userId);
        }
        if ($user === null) {
            return null;
        }

        return [
            'id'           => (int) $user['id'],
            'username'     => (string) $user['username'],
            'display_name' => (string) $user['display_name'],
            'avatar_url'   => avatar_url($user['avatar_path'] ?? null),
            'profile_url'  => url('/u/' . $user['username']),
        ];
    }
}
