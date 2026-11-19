<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\OldInput;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Follow;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;

final class ProfileController extends Controller
{
    public function show(array $params): void
    {
        $username = $params['username'] ?? '';
        $user = User::findByUsername($username);
        if ($user === null) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Không tìm thấy người dùng']);

            return;
        }

        $viewerId = auth_id();
        $userId = (int) $user['id'];
        $tab = $this->request->string('tab', 'posts');
        $page = max(1, $this->request->int('page', 1));
        $perPage = 10;

        $posts = [];
        $comments = [];
        $shared = [];
        $friends = [];
        $followers = [];
        $following = [];
        $photos = [];
        $total = 0;

        if ($tab === 'comments') {
            $comments = \App\Models\Comment::recentByUser($userId, 20);
        } elseif ($tab === 'shared') {
            $shared = \App\Models\Share::byUser($userId, 20);
        } elseif ($tab === 'friends') {
            $friends = \App\Models\Friendship::friends($userId);
        } elseif ($tab === 'followers') {
            $followers = \App\Models\Follow::followers($userId);
        } elseif ($tab === 'following') {
            $following = \App\Models\Follow::following($userId);
        } elseif ($tab === 'photos') {
            $photos = User::photos($userId, 30);
        } else {
            $total = Post::countByUser($userId);
            $posts = Post::byUser($userId, $viewerId, $perPage, ($page - 1) * $perPage);
        }

        $this->view('profile/show', [
            'title'       => $user['display_name'] . ' (' . $user['username'] . ')',
            'user'        => $user,
            'stats'       => User::stats($userId),
            'posts'       => $posts,
            'comments'    => $comments,
            'shared'      => $shared,
            'friends'     => $friends,
            'followers'   => $followers,
            'following'   => $following,
            'photos'      => $photos,
            'tab'         => $tab,
            'page'        => $page,
            'total'       => $total,
            'perPage'     => $perPage,
            'isFollowing' => $viewerId !== null && Follow::isFollowing($viewerId, $userId),
            'friendStatus' => $viewerId !== null ? Friendship::status($viewerId, $userId) : 'none',
            'isSelf'      => $viewerId === $userId,
            'extraCss'    => ['css/profile.css'],
        ]);
    }

    public function edit(): void
    {
        $user = auth_user();
        if ($user === null) {
            Response::redirect('/login');
        }

        $this->view('profile/edit', [
            'title' => 'Chỉnh sửa hồ sơ',
            'user'  => $user,
        ]);
    }

    public function update(): void
    {
        $user = auth_user();
        $userId = auth_id();
        if ($user === null || $userId === null) {
            Response::redirect('/login');
        }

        $displayName = $this->request->string('display_name');
        $bio = $this->request->string('bio');

        $validator = (new Validator($this->request->all()))
            ->required('display_name', 'Tên hiển thị')
            ->max('display_name', 100, 'Tên hiển thị')
            ->max('bio', 500, 'Tiểu sử');

        if ($validator->fails()) {
            $this->fail($validator->firstError(), '/settings/profile', ['display_name', 'bio']);
        }

        $data = [
            'display_name' => $displayName,
            'bio'          => $bio,
        ];

        $avatar = $this->request->file('avatar');
        if ($avatar !== null) {
            try {
                $path = \App\Services\UploadService::image($avatar, 'avatars');
                $data['avatar_path'] = $path;
                if (!empty($user['avatar_path'])) {
                    \App\Services\UploadService::delete((string) $user['avatar_path']);
                }
            } catch (\RuntimeException $e) {
                $this->fail($e->getMessage(), '/settings/profile', ['display_name', 'bio']);
            }
        }

        $cover = $this->request->file('cover');
        if ($cover !== null) {
            try {
                $path = \App\Services\UploadService::image($cover, 'covers');
                $data['cover_path'] = $path;
                if (!empty($user['cover_path'])) {
                    \App\Services\UploadService::delete((string) $user['cover_path']);
                }
            } catch (\RuntimeException $e) {
                $this->fail($e->getMessage(), '/settings/profile', ['display_name', 'bio']);
            }
        }

        User::updateProfile($userId, $data);
        setFlashMessage('success', 'Cập nhật hồ sơ thành công.');
        OldInput::clearDraft('profile');
        Response::redirect('/settings/profile');
    }

    public function updatePassword(): void
    {
        $userId = auth_id();
        $user = auth_user();
        if ($user === null || $userId === null) {
            Response::redirect('/login');
        }

        $current = (string) $this->request->input('current_password', '');
        $new = (string) $this->request->input('new_password', '');
        $confirm = (string) $this->request->input('new_password_confirmation', '');

        $validator = (new Validator($this->request->all()))
            ->required('current_password', 'Mật khẩu hiện tại')
            ->required('new_password', 'Mật khẩu mới')
            ->min('new_password', 8, 'Mật khẩu mới')
            ->matches('new_password_confirmation', 'new_password', 'Xác nhận mật khẩu');

        if ($validator->fails()) {
            $this->fail($validator->firstError(), '/settings/profile');
        }

        if (!password_verify($current, (string) $user['password_hash'])) {
            $this->fail('Mật khẩu hiện tại không đúng.', '/settings/profile');
        }

        User::updatePassword($userId, $new);
        setFlashMessage('success', 'Đổi mật khẩu thành công.');
        Response::redirect('/settings/profile');
    }

    public function updateTheme(): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::redirect('/login');
        }

        $theme = $this->request->string('theme', 'light');
        if (!in_array($theme, ['light', 'dark'], true)) {
            $theme = 'light';
        }

        User::updateTheme($userId, $theme);
        Session::set('theme', $theme);

        if ($this->request->isJson()) {
            Response::success(['theme' => $theme]);
        }

        Response::back('/');
    }
}
