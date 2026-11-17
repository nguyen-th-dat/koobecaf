<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Notification;

final class NotificationController extends Controller
{
    public function index(): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::redirect('/login');
        }

        $page = max(1, $this->request->int('page', 1));
        $perPage = 20;

        $this->view('notifications/index', [
            'title'         => 'Thông báo',
            'notifications' => Notification::forUser($userId, $perPage, ($page - 1) * $perPage),
            'unread'        => Notification::unreadCount($userId),
            'user'          => auth_user(),
            'page'          => $page,
            'totalPages'    => max(1, (int) ceil(Notification::countForUser($userId) / $perPage)),
        ]);
    }

    public function markRead(array $params): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }

        $notification = Notification::find((int) $params['id']);
        if ($notification === null || (int) $notification['user_id'] !== $userId) {
            Response::error('Thông báo không tồn tại.', 404);
        }

        Notification::markRead((int) $notification['id'], $userId);

        Response::success([
            'unread' => Notification::unreadCount($userId),
            'url'    => $this->targetUrl($notification),
        ]);
    }

    public function markAllRead(): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }

        $count = Notification::markAllRead($userId);

        Response::success(['updated' => $count, 'unread' => 0]);
    }

    /** Đường dẫn điều hướng khi nhấn vào thông báo. */
    public static function targetUrl(array $notification): string
    {
        $entityId = (int) $notification['entity_id'];

        if ($notification['type'] === Notification::TYPE_REPLY) {
            // `data` chứa post_id của bài viết có bình luận được trả lời.
            $postId = (int) ($notification['data'] ?? 0);

            return $postId > 0 ? '/post/' . $postId : '/notifications';
        }

        return match ($notification['type']) {
            Notification::TYPE_LIKE, Notification::TYPE_COMMENT, Notification::TYPE_SHARE => '/post/' . $entityId,
            Notification::TYPE_FRIEND_REQUEST   => '/friends/requests',
            Notification::TYPE_FRIEND_ACCEPTED  => '/friends',
            Notification::TYPE_MESSAGE          => '/chat/' . $entityId,
            Notification::TYPE_FOLLOW           => '/u/' . (\App\Models\User::find((int) $notification['actor_id'])['username'] ?? ''),
            default                            => '/notifications',
        };
    }
}
