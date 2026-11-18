<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Follow;
use App\Models\User;
use App\Services\RealtimeService;

final class FollowController extends Controller
{
    public function toggle(array $params): void
    {
        $targetId = (int) $params['id'];
        $userId = auth_id();

        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }
        if ($targetId === $userId) {
            Response::error('Bạn không thể theo dõi chính mình.', 422);
        }
        if (User::find($targetId) === null) {
            Response::error('Người dùng không tồn tại.', 404);
        }

        $following = Follow::isFollowing($userId, $targetId);
        if ($following) {
            Follow::remove($userId, $targetId);
        } else {
            Follow::add($userId, $targetId);
            \App\Services\NotificationService::followed($targetId, $userId);
        }

        RealtimeService::social($following ? 'unfollow' : 'follow', $userId, $targetId);

        Response::success([
            'following' => !$following,
            'followers_count' => (int) \App\Core\Database::fetchValue(
                'SELECT COUNT(*) FROM follows WHERE following_id = ?',
                [$targetId]
            ),
        ]);
    }
}
