<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RealtimeService;

final class ChatController extends Controller
{
    public function index(): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::redirect('/login');
        }

        $this->view('chat/index', [
            'title'         => 'Tin nhắn',
            'wide'          => true,
            'conversations' => Conversation::forUser($userId),
            'user'          => auth_user(),
        ]);
    }

    public function show(array $params): void
    {
        $userId = auth_id();
        $conversationId = (int) $params['id'];
        if ($userId === null) {
            Response::redirect('/login');
        }

        if (!Conversation::isMember($conversationId, $userId)) {
            http_response_code(403);
            $this->view('errors/403', ['title' => 'Bạn không có quyền truy cập cuộc trò chuyện này']);

            return;
        }

        Message::markRead($conversationId, $userId);

        $memberIds = Conversation::memberIds($conversationId);
        $otherId = null;
        foreach ($memberIds as $memberId) {
            if ($memberId !== $userId) {
                $otherId = $memberId;
            }
        }

        $messages = Message::forConversation($conversationId, 100);
        $messages = array_reverse($messages);

        // Dữ liệu khởi tạo cho client WebSocket.
        // Token đăng nhập do layout cấp cho mọi người dùng; ở đây chỉ thêm
        // ticket của cuộc trò chuyện để client subscribe vào phòng chat.
        $ws = [
            'enabled' => \App\Core\WebSocketToken::isEnabled(),
            'url'     => \App\Core\WebSocketToken::url(),
            'token'   => '',
            'ticket'  => '',
            'conversation_id' => $conversationId,
        ];

        if ($ws['enabled'] && $otherId !== null) {
            try {
                $peer = User::find($otherId);
                if ($peer !== null) {
                    $ws['ticket'] = \App\Core\WebSocketToken::issueTicket($userId, $conversationId, [
                        'id'           => (int) $peer['id'],
                        'username'     => (string) $peer['username'],
                        'display_name' => (string) $peer['display_name'],
                        'avatar_path'  => $peer['avatar_path'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                \App\Core\Logger::error('Không thể cấp ticket WebSocket: ' . $e->getMessage());
            }
        }

        $this->view('chat/show', [
            'title'         => 'Trò chuyện',
            'wide'          => true,
            'conversationId' => $conversationId,
            'other'         => $otherId !== null ? User::find($otherId) : null,
            'messages'      => $messages,
            'conversations' => Conversation::forUser($userId),
            'user'          => auth_user(),
            'ws'            => $ws,
        ]);
    }

    public function messages(array $params): void
    {
        $userId = auth_id();
        $conversationId = (int) $params['id'];
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }
        if (!Conversation::isMember($conversationId, $userId)) {
            Response::error('Bạn không có quyền truy cập cuộc trò chuyện này.', 403);
        }

        $afterId = $this->request->int('after_id', 0);
        $rows = Message::forConversation($conversationId, 100);
        $rows = array_reverse($rows);

        $items = [];
        foreach ($rows as $row) {
            if ((int) $row['id'] <= $afterId) {
                continue;
            }
            $items[] = [
                'id'         => (int) $row['id'],
                'sender_id'  => (int) $row['sender_id'],
                'body'       => $row['body'],
                'created_at' => $row['created_at'],
                'is_mine'    => (int) $row['sender_id'] === $userId,
            ];
        }

        $count = Message::markRead($conversationId, $userId);

        Response::success([
            'messages'    => $items,
            'new_count'   => $count,
            'total_unread' => Conversation::totalUnread($userId),
        ]);
    }

    public function send(array $params): void
    {
        $userId = auth_id();
        $conversationId = (int) $params['id'];
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }
        if (!Conversation::isMember($conversationId, $userId)) {
            Response::error('Bạn không có quyền truy cập cuộc trò chuyện này.', 403);
        }

        $body = trim((string) $this->request->input('body', ''));
        if ($body === '') {
            Response::error('Nội dung tin nhắn không được để trống.', 422);
        }
        if (mb_strlen($body) > 5000) {
            Response::error('Nội dung tin nhắn quá dài.', 422);
        }

        $messageId = Message::create($conversationId, $userId, $body);

        foreach (Conversation::memberIds($conversationId) as $memberId) {
            NotificationService::message($memberId, $userId, $conversationId);
        }

        // Đẩy tin nhắn realtime tới các thành viên: trường hợp này xảy ra khi
        // WebSocket của người gửi đang chết và client fallback sang HTTP.
        RealtimeService::chatMessage($conversationId, [
            'id'              => $messageId,
            'conversation_id' => $conversationId,
            'sender_id'       => $userId,
            'body'            => $body,
            'created_at'      => date('Y-m-d H:i:s'),
            'sender'          => RealtimeService::userPayload($userId),
        ]);

        Response::success([
            'id'         => $messageId,
            'body'       => $body,
            'created_at' => date('Y-m-d H:i:s'),
            'is_mine'    => true,
        ], 201);
    }

    public function start(array $params): void
    {
        $userId = auth_id();
        $otherId = (int) $params['id'];
        if ($userId === null) {
            Response::error('Cần đăng nhập.', 401);
        }
        if ($otherId === $userId) {
            Response::error('Bạn không thể nhắn tin cho chính mình.', 422);
        }
        if (User::find($otherId) === null) {
            Response::error('Người dùng không tồn tại.', 404);
        }

        $conversationId = Conversation::between($userId, $otherId);

        Response::redirect('/chat/' . $conversationId);
    }
}
