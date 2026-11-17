<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;

final class ApiController extends Controller
{
    public function unreadNotifications(): void
    {
        $userId = auth_id();
        Response::success(['unread' => $userId !== null ? Notification::unreadCount($userId) : 0]);
    }

    public function unreadMessages(): void
    {
        $userId = auth_id();
        Response::success(['unread' => $userId !== null ? Conversation::totalUnread($userId) : 0]);
    }

    public function feed(): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }

        $page = max(1, $this->request->int('page', 1));
        $perPage = (int) config('app.per_page', 10);

        $posts = Post::feed($userId, $perPage, ($page - 1) * $perPage);

        $items = [];
        foreach ($posts as $post) {
            $items[] = $this->postPayload($post, $userId);
        }

        Response::success([
            'posts' => $items,
            'has_more' => count($posts) === $perPage,
        ]);
    }

    public function searchUsers(): void
    {
        $keyword = trim((string) $this->request->input('q', ''));
        if ($keyword === '') {
            Response::success(['users' => []]);
        }

        $users = User::search($keyword, 10);
        $items = [];
        foreach ($users as $user) {
            $items[] = [
                'id'           => (int) $user['id'],
                'username'     => $user['username'],
                'display_name' => $user['display_name'],
                'avatar_url'   => upload_url($user['avatar_path'] ?? null),
                'profile_url'  => url('/u/' . $user['username']),
                'chat_url'     => url('/chat/start/' . (int) $user['id']),
            ];
        }

        Response::success(['users' => $items]);
    }

    public function searchPosts(): void
    {
        $keyword = trim((string) $this->request->input('q', ''));
        $page = max(1, $this->request->int('page', 1));
        $perPage = 10;

        if ($keyword === '') {
            Response::success(['posts' => [], 'has_more' => false]);
        }

        $posts = Post::search($keyword, $perPage, ($page - 1) * $perPage);
        $items = [];
        foreach ($posts as $post) {
            $items[] = $this->postPayload($post, auth_id());
        }

        Response::success([
            'posts'    => $items,
            'has_more' => count($posts) === $perPage,
        ]);
    }

    public function comments(array $params): void
    {
        $postId = (int) $params['id'];
        $post = Post::find($postId);
        if ($post === null || !Post::canView($post, auth_id())) {
            Response::error('Bài viết không khả dụng.', 404);
        }

        $items = [];
        foreach (Comment::forPost($postId) as $comment) {
            $items[] = $this->commentPayload($comment);
        }

        Response::success(['comments' => $items]);
    }

    private function postPayload(array $post, ?int $viewerId): array
    {
        $postId = (int) $post['id'];
        $images = [];
        foreach (\App\Models\PostImage::all($postId) as $image) {
            $images[] = upload_url((string) $image['path']);
        }

        return [
            'id'           => $postId,
            'content'      => $post['content'],
            'privacy'      => $post['privacy'],
            'created_at'   => $post['created_at'],
            'author'       => [
                'id'           => (int) $post['user_id'],
                'username'     => $post['username'],
                'display_name' => $post['display_name'],
                'avatar_url'   => upload_url($post['avatar_path'] ?? null),
                'profile_url'  => url('/u/' . $post['username']),
            ],
            'images'       => $images,
            'like_count'   => Post::likeCount($postId),
            'comment_count' => Post::commentCount($postId),
            'share_count'  => Post::shareCount($postId),
            'liked'        => $viewerId !== null && Post::isLiked($postId, $viewerId),
            'url'          => url('/post/' . $postId),
        ];
    }

    private function commentPayload(array $comment): array
    {
        $replies = [];
        foreach ($comment['replies'] as $reply) {
            $replies[] = $this->commentPayload($reply);
        }

        return [
            'id'           => (int) $comment['id'],
            'content'      => $comment['content'],
            'parent_id'    => $comment['parent_id'] !== null ? (int) $comment['parent_id'] : null,
            'created_at'   => $comment['created_at'],
            'author'       => [
                'id'           => (int) $comment['user_id'],
                'username'     => $comment['username'],
                'display_name' => $comment['display_name'],
                'avatar_url'   => upload_url($comment['avatar_path'] ?? null),
                'profile_url'  => url('/u/' . $comment['username']),
            ],
            'replies'      => $replies,
        ];
    }
}
