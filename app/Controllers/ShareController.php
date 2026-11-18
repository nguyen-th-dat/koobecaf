<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Models\Post;
use App\Models\Share;
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
        RealtimeService::postShared((int) $post['id'], (int) $userId);
        RealtimeService::postCreated($sharePostId);

        Response::success([
            'share_count' => Post::shareCount((int) $post['id']),
            'shared'      => true,
        ], 201);
    }
}
