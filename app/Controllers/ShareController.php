<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Post;
use App\Models\Share;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RealtimeService;

final class ShareController extends Controller
{
    public function store(array $params): void
    {
        $post = Post::find((int) $params['id']);
        $userId = auth_id();

        if ($post === null || !Post::canView($post, $userId)) {
            Response::error('Bài viết không khả dụng.', 404);
        }

        if (Share::exists((int) $post['id'], $userId)) {
            Response::error('Bạn đã chia sẻ bài viết này.', 409);
        }

        $content = trim((string) $this->request->input('content', ''));
        $privacy = $this->request->string('privacy', Post::PRIVACY_PUBLIC);
        if (!in_array($privacy, [Post::PRIVACY_PUBLIC, Post::PRIVACY_FOLLOWERS, Post::PRIVACY_PRIVATE], true)) {
            $privacy = Post::PRIVACY_PUBLIC;
        }

        Share::create((int) $post['id'], $userId, $content);
        $sharePostId = Post::createShare($userId, (int) $post['id'], $content, $privacy);

        NotificationService::shared((int) $post['id'], $userId, (int) $post['user_id']);
        RealtimeService::postShared((int) $post['id'], $userId);
        RealtimeService::postCreated($sharePostId);

        Response::success([
            'share_count' => Post::shareCount((int) $post['id']),
            'shared'      => true,
        ], 201);
    }

    /**
     * Dữ liệu cho hộp thoại chia sẻ: thông tin bài viết và danh sách người nhận.
     */
    public function targets(array $params): void
    {
        $userId = auth_id();
        $post = Post::find((int) $params['id']);

        if ($post === null || !Post::canView($post, $userId)) {
            Response::error('Bài viết không khả dụng.', 404);
        }

        $keyword = trim((string) $this->request->input('q', ''));

        $people = [];
        foreach (User::shareTargets($userId, $keyword) as $person) {
            $people[] = [
                'id'            => (int) $person['id'],
                'username'      => (string) $person['username'],
                'display_name'  => (string) $person['display_name'],
                'avatar_url'    => avatar_url($person['avatar_path'] ?? null),
                'profile_url'   => url('/u/' . $person['username']),
                'conversation_id' => $person['conversation_id'] !== null ? (int) $person['conversation_id'] : null,
            ];
        }

        Response::success([
            'post' => [
                'id'         => (int) $post['id'],
                'permalink'  => absolute_url('/p/' . (int) $post['id']),
                'content'    => (string) $post['content'],
                'author'     => [
                    'display_name' => (string) $post['display_name'],
                    'username'     => (string) $post['username'],
                    'avatar_url'   => avatar_url($post['avatar_path'] ?? null),
                ],
            ],
            'people' => $people,
        ]);
    }

    /**
     * Gửi bài viết cho một người qua khung chat.
     */
    public function sendMessage(array $params): void
    {
        $userId = auth_id();
        $post = Post::find((int) $params['id']);

        if ($post === null || !Post::canView($post, $userId)) {
            Response::error('Bài viết không khả dụng.', 404);
        }

        $targetId = (int) $this->request->input('user_id', 0);
        if ($targetId <= 0 || $targetId === $userId) {
            Response::error('Người nhận không hợp lệ.', 422);
        }

        if (User::find($targetId) === null) {
            Response::error('Người dùng không tồn tại.', 404);
        }

        $conversationId = Conversation::between($userId, $targetId);
        $body = trim((string) $this->request->input('body', ''));
        // Gửi URL tuyệt đối để người nhận bấm được trong khung chat.
        $link = absolute_url('/p/' . (int) $post['id']);
        $text = $body !== '' ? $body . ' ' . $link : $link;

        $messageId = Message::create($conversationId, $userId, $text);

        foreach (Conversation::memberIds($conversationId) as $memberId) {
            NotificationService::message($memberId, $userId, $conversationId);
        }

        Response::success([
            'message_id'     => $messageId,
            'conversation_id' => $conversationId,
        ], 201);
    }
}
