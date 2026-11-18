'use strict';

/**
 * Công cụ trợ giúp: gửi trạng thái "đang gõ" qua WebSocket.
 *
 * Dùng để kiểm thử chỉ báo đang gõ trên trình duyệt:
 *   node tests/ws_typing.js <userId> <conversationId> <true|false>
 */

const { execFileSync } = require('child_process');
const path = require('path');
const WebSocket = require('ws');

const ROOT_DIR = path.resolve(__dirname, '..');
const WS_URL = process.env.KB_WS_URL || 'ws://127.0.0.1:8080/ws';

const userId = Number(process.argv[2]);
const conversationId = Number(process.argv[3]);
const typing = (process.argv[4] ?? 'true') === 'true';

function issueFromPhp() {
    const script = `
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
$peerId = null;
foreach (\\App\\Models\\Conversation::memberIds(${conversationId}) as $id) {
    if ($id != ${userId}) { $peerId = $id; break; }
}
$peer = \\App\\Models\\User::find($peerId);
echo json_encode([
    'token'  => \\App\\Core\\WebSocketToken::issue(${userId}),
    'ticket' => \\App\\Core\\WebSocketToken::issueTicket(${userId}, ${conversationId}, [
        'id' => (int) $peer['id'],
        'username' => $peer['username'],
        'display_name' => $peer['display_name'],
        'avatar_path' => $peer['avatar_path'],
    ]),
]);
`;
    return JSON.parse(execFileSync('php', ['-r', script], { cwd: ROOT_DIR, encoding: 'utf8' }));
}

const { token, ticket } = issueFromPhp();
const socket = new WebSocket(WS_URL);

socket.on('open', () => socket.send(JSON.stringify({ type: 'auth', token })));

socket.on('message', (raw) => {
    const payload = JSON.parse(raw.toString());
    if (payload.type === 'auth:ok') {
        socket.send(JSON.stringify({ type: 'subscribe', ticket }));
        return;
    }
    if (payload.type === 'subscribe:ok') {
        socket.send(JSON.stringify({ type: 'typing', conversation_id: conversationId, typing }));
        console.log('Đã gửi trạng thái đang gõ:', typing);
        setTimeout(() => {
            socket.close();
            process.exit(0);
        }, 300);
    }
});

socket.on('error', (error) => {
    console.error('Lỗi:', error.message);
    process.exit(1);
});
