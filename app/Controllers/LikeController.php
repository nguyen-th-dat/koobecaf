<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Like;
use App\Models\Post;
use App\Services\NotificationService;

final class LikeController extends Controller
{
    public function toggle(array $params): void
    {
        $post = Post::find((int) $params['id']);
        $userId = auth_id();

        if ($post === null || !Post::canView($post, $userId)) {
            Response::error('Bài viết không khả dụng.', 404);
        }

        $liked = Like::toggle((int) $post['id'], $userId);

        if ($liked) {
            NotificationService::liked((int) $post['id'], $userId, (int) $post['user_id']);
        }

        Response::success([
            'liked'      => $liked,
            'like_count' => Post::likeCount((int) $post['id']),
        ]);
    }

    public function users(array $params): void
    {
        $post = Post::find((int) $params['id']);
        if ($post === null || !Post::canView($post, auth_id())) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Không tìm thấy bài viết']);

            return;
        }

        $this->view('posts/likes', [
            'title' => 'Người đã thích',
            'post'  => $post,
            'users' => Like::usersByPost((int) $post['id'], 100),
        ]);
    }
}
