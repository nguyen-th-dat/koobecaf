<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

final class FeedController extends Controller
{
    public function index(): void
    {
        $viewerId = auth_id();
        $page = max(1, $this->request->int('page', 1));
        $perPage = (int) config('app.per_page', 10);

        if ($viewerId === null) {
            $posts = Post::findPublic($perPage, ($page - 1) * $perPage);
            $total = Post::countPublic();
        } else {
            $posts = Post::feed($viewerId, $perPage, ($page - 1) * $perPage);
            $total = Post::countFeed($viewerId);
        }

        $this->view('feed/index', [
            'title'   => $viewerId === null ? 'Trang chủ' : 'Bảng tin',
            'posts'   => $posts,
            'page'    => $page,
            'perPage' => $perPage,
            'total'   => $total,
            'user'    => auth_user(),
        ]);
    }
}
