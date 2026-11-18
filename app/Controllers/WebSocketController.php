<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\WebSocketToken;
use App\Models\Conversation;
use App\Models\User;

/**
 * Cấp token/ticket cho client WebSocket.
 *
 * - GET /api/ws/session            → token đăng nhập (dùng để bắt tay)
 * - GET /api/ws/ticket/{cid}       → ticket theo cuộc trò chuyện (dùng để subscribe)
 */
final class WebSocketController extends Controller
{
    public function session(): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }

        if (!WebSocketToken::isEnabled()) {
            Response::error('Tính năng WebSocket đang tắt.', 503);
        }

        Response::success([
            'token'    => WebSocketToken::issue($userId),
            'url'      => WebSocketToken::url(),
            'expires_in' => WebSocketToken::ttl(),
        ]);
    }

    public function ticket(array $params): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }

        if (!WebSocketToken::isEnabled()) {
            Response::error('Tính năng WebSocket đang tắt.', 503);
        }

        $conversationId = (int) $params['id'];
        if (!Conversation::isMember($conversationId, $userId)) {
            Response::error('Bạn không có quyền truy cập cuộc trò chuyện này.', 403);
        }

        $peer = $this->peerOf($conversationId, $userId);
        if ($peer === null) {
            Response::error('Không tìm thấy người trò chuyện.', 404);
        }

        Response::success([
            'ticket'      => WebSocketToken::issueTicket($userId, $conversationId, $peer),
            'conversation_id' => $conversationId,
            'peer'        => [
                'id'           => (int) $peer['id'],
                'username'     => $peer['username'],
                'display_name' => $peer['display_name'],
                'avatar_url'   => avatar_url($peer['avatar_path'] ?? null),
            ],
        ]);
    }

    /**
     * Thông tin người còn lại trong cuộc trò chuyện (cuộc trò chuyện luôn 2 người).
     *
     * @return array{id:int,username:string,display_name:string,avatar_path:?string}|null
     */
    private function peerOf(int $conversationId, int $userId): ?array
    {
        foreach (Conversation::memberIds($conversationId) as $memberId) {
            if ($memberId === $userId) {
                continue;
            }

            $user = User::find($memberId);
            if ($user === null) {
                continue;
            }

            return [
                'id'           => (int) $user['id'],
                'username'     => (string) $user['username'],
                'display_name' => (string) $user['display_name'],
                'avatar_path'  => $user['avatar_path'] ?? null,
            ];
        }

        return null;
    }
}
