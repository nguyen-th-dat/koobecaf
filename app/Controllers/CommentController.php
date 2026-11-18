<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Comment;
use App\Models\Post;
use App\Services\NotificationService;
use App\Services\RealtimeService;

final class CommentController extends Controller
{
    public function store(array $params): void
    {
        $post = Post::find((int) $params['id']);
        $userId = auth_id();

        if ($post === null || !Post::canView($post, $userId)) {
            Response::error('Bài viết không khả dụng.', 404);
        }

        $content = trim((string) $this->request->input('content', ''));
        $parentId = $this->request->int('parent_id', 0) ?: null;

        $validator = (new Validator($this->request->all()))
            ->required('content', 'Nội dung bình luận')
            ->max('content', 2000, 'Nội dung bình luận');

        if ($validator->fails()) {
            Response::error($validator->firstError() ?? 'Nội dung không hợp lệ.', 422);
        }

        $parent = null;
        if ($parentId !== null) {
            $parent = Comment::find($parentId);
            if ($parent === null || (int) $parent['post_id'] !== (int) $post['id']) {
                $parentId = null;
            }
        }

        $commentId = Comment::create((int) $post['id'], $userId, $content, $parentId);

        NotificationService::commented((int) $post['id'], $userId, (int) $post['user_id']);
        if ($parent !== null) {
            NotificationService::replied((int) $post['id'], $userId, (int) $parent['user_id'], $parentId);
        }

        $comment = Comment::find($commentId);

        RealtimeService::postCommented((int) $post['id'], (int) $userId, (int) $commentId);

        Response::success([
            'comment'       => $comment,
            'comment_count' => Post::commentCount((int) $post['id']),
        ], 201);
    }

    public function update(array $params): void
    {
        $userId = auth_id();
        $comment = Comment::find((int) $params['id']);
        if ($comment === null || $userId === null || (int) $comment['user_id'] !== $userId) {
            Response::error('Bạn không có quyền sửa bình luận này.', 403);
        }

        $content = trim((string) $this->request->input('content', ''));
        if ($content === '') {
            Response::error('Nội dung bình luận không được để trống.', 422);
        }
        if (mb_strlen($content) > 2000) {
            Response::error('Nội dung bình luận không được vượt quá 2000 ký tự.', 422);
        }

        Comment::update((int) $comment['id'], $content);

        Response::success(['comment' => Comment::find((int) $comment['id'])]);
    }

    public function destroy(array $params): void
    {
        $userId = auth_id();
        $comment = Comment::find((int) $params['id']);
        if ($comment === null || $userId === null || (int) $comment['user_id'] !== $userId) {
            Response::error('Bạn không có quyền xóa bình luận này.', 403);
        }

        Comment::delete((int) $comment['id']);

        RealtimeService::postUncommented((int) $comment['post_id'], $userId);

        Response::success(['comment_count' => Post::commentCount((int) $comment['post_id'])]);
    }
}
