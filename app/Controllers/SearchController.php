<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\User;

final class SearchController extends Controller
{
    public function index(): void
    {
        $keyword = trim((string) $this->request->input('q', ''));
        $page = max(1, $this->request->int('page', 1));
        $perPage = 10;

        $users = [];
        $posts = [];
        $totalPosts = 0;

        if ($keyword !== '') {
            $users = User::search($keyword, 10);
            $posts = Post::search($keyword, $perPage, ($page - 1) * $perPage);
            $totalPosts = Post::countSearch($keyword);
        }

        $this->view('search/results', [
            'title'      => $keyword === '' ? 'Tìm kiếm' : 'Kết quả cho "' . $keyword . '"',
            'keyword'    => $keyword,
            'users'      => $users,
            'posts'      => $posts,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPosts' => $totalPosts,
        ]);
    }
}
