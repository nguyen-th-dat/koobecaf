<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Models\Friendship;
use App\Models\User;
use App\Services\NotificationService;

final class FriendController extends Controller
{
    public function index(): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::redirect('/login');
        }

        $this->view('friends/index', [
            'title'   => 'Bạn bè',
            'friends' => Friendship::friends($userId),
            'user'    => auth_user(),
        ]);
    }

    public function requests(): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::redirect('/login');
        }

        $this->view('friends/requests', [
            'title'    => 'Lời mời kết bạn',
            'requests' => Friendship::requests($userId),
            'user'     => auth_user(),
        ]);
    }

    public function request(array $params): void
    {
        $targetId = (int) $params['id'];
        $userId = auth_id();
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }
        if ($targetId === $userId) {
            Response::error('Bạn không thể tự kết bạn với chính mình.', 422);
        }
        if (User::find($targetId) === null) {
            Response::error('Người dùng không tồn tại.', 404);
        }

        $status = Friendship::status($userId, $targetId);
        if ($status === 'accepted') {
            Response::error('Hai người đã là bạn bè.', 409);
        }
        if ($status === 'pending_outgoing') {
            Response::error('Bạn đã gửi lời mời này.', 409);
        }

        $accepted = $status === 'pending_incoming';
        Friendship::request($userId, $targetId);

        if ($accepted) {
            NotificationService::friendAccepted($targetId, $userId);
            Session::flash('success', 'Đã chấp nhận lời mời kết bạn.');
        } else {
            NotificationService::friendRequested($targetId, $userId);
            Session::flash('success', 'Đã gửi lời mời kết bạn.');
        }

        if ($this->request->isJson()) {
            Response::success(['status' => Friendship::status($userId, $targetId)]);
        }

        Response::back('/friends');
    }

    public function accept(array $params): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }

        $friendshipId = (int) $params['id'];
        $request = \App\Core\Database::fetchOne(
            "SELECT * FROM friendships WHERE id = ? AND friend_id = ?",
            [$friendshipId, $userId]
        );
        if ($request === null) {
            Response::error('Lời mời không tồn tại.', 404);
        }

        Friendship::accept($userId, $friendshipId);
        NotificationService::friendAccepted((int) $request['user_id'], $userId);
        Session::flash('success', 'Đã chấp nhận lời mời kết bạn.');

        Response::back('/friends/requests');
    }

    public function decline(array $params): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }

        Friendship::decline($userId, (int) $params['id']);
        Session::flash('success', 'Đã từ chối lời mời.');

        Response::back('/friends/requests');
    }

    public function cancel(array $params): void
    {
        $userId = auth_id();
        $targetId = (int) $params['id'];
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }

        Friendship::cancel($userId, $targetId);
        Session::flash('success', 'Đã hủy lời mời.');

        Response::back('/friends');
    }

    public function remove(array $params): void
    {
        $userId = auth_id();
        $targetId = (int) $params['id'];
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }

        Friendship::unfriend($userId, $targetId);
        Session::flash('success', 'Đã hủy kết bạn.');

        Response::back('/friends');
    }
}
