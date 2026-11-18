'use strict';

/**
 * Kiểm thử end-to-end WebSocket: hai client thật kết nối tới server đang chạy.
 *
 * Yêu cầu:
 *   - WebSocket server đang chạy (npm run ws)
 *   - PHP server đang chạy (php -S localhost:8000 -t . router.php)
 *
 * Chạy:  node tests/ws_e2e.js
 *
 * Script dùng PHP CLI để cấp token/ticket (đúng thuật toán của ứng dụng),
 * sau đó kiểm tra: xác thực, subscribe, gửi tin nhắn, nhận tin nhắn,
 * đánh dấu đã đọc và trạng thái đang gõ.
 */

const { execFileSync } = require('child_process');
const path = require('path');
const WebSocket = require('ws');

const ROOT_DIR = path.resolve(__dirname, '..');
const WS_URL = process.env.KB_WS_URL || 'ws://127.0.0.1:8080/ws';

let passed = 0;
let failed = 0;

function check(name, condition, detail = '') {
    if (condition) {
        passed++;
        console.log(`PASS: ${name}`);
    } else {
        failed++;
        console.log(`FAIL: ${name}${detail ? ' — ' + detail : ''}`);
    }
}

/** Cấp token/ticket bằng đúng logic PHP của ứng dụng. */
function issueFromPhp(userId, conversationId) {
    const script = `
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
$peerId = (\\App\\Models\\Conversation::memberIds(${conversationId})[0] == ${userId})
    ? \\App\\Models\\Conversation::memberIds(${conversationId})[1]
    : \\App\\Models\\Conversation::memberIds(${conversationId})[0];
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
    const output = execFileSync('php', ['-r', script], { cwd: ROOT_DIR, encoding: 'utf8' });

    return JSON.parse(output);
}

/** Chờ frame có type khớp. */
function waitFor(socket, type, timeout = 8000) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => {
            socket.off('message', onMessage);
            reject(new Error(`timeout chờ frame "${type}"`));
        }, timeout);

        function onMessage(raw) {
            let payload;
            try {
                payload = JSON.parse(raw.toString());
            } catch (error) {
                return;
            }
            if (payload.type !== type) {
                return;
            }
            clearTimeout(timer);
            socket.off('message', onMessage);
            resolve(payload);
        }

        socket.on('message', onMessage);
    });
}

/**
 * Chờ frame có type khớp, đồng thời chấp nhận cả trường hợp socket bị đóng ngay
 * sau khi gửi frame (server gửi `error` rồi `close`).
 */
function waitForOrClose(socket, type, timeout = 8000) {
    return new Promise((resolve, reject) => {
        let settled = false;

        const timer = setTimeout(() => {
            if (settled) {
                return;
            }
            settled = true;
            socket.off('message', onMessage);
            socket.off('close', onClose);
            reject(new Error(`timeout chờ frame "${type}"`));
        }, timeout);

        function cleanup() {
            clearTimeout(timer);
            socket.off('message', onMessage);
            socket.off('close', onClose);
        }

        function onMessage(raw) {
            let payload;
            try {
                payload = JSON.parse(raw.toString());
            } catch (error) {
                return;
            }
            if (payload.type !== type) {
                return;
            }
            settled = true;
            cleanup();
            resolve(payload);
        }

        function onClose() {
            if (settled) {
                return;
            }
            settled = true;
            cleanup();
            reject(new Error(`socket đóng trước khi nhận frame "${type}"`));
        }

        socket.on('message', onMessage);
        socket.on('close', onClose);
    });
}

function connect() {
    return new Promise((resolve, reject) => {
        const socket = new WebSocket(WS_URL);
        const timer = setTimeout(() => reject(new Error('timeout kết nối WS')), 8000);
        socket.on('open', () => {
            clearTimeout(timer);
            resolve(socket);
        });
        socket.on('error', (error) => {
            clearTimeout(timer);
            reject(error);
        });
    });
}

async function main() {
    // Tìm một cuộc trò chuyện có 2 thành viên trong DB.
    const listScript = `
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
$row = \\App\\Core\\Database::fetchOne(
    'SELECT conversation_id FROM conversation_members GROUP BY conversation_id HAVING COUNT(*) = 2 LIMIT 1'
);
echo $row['conversation_id'] ?? '';
`;
    const conversationId = Number(execFileSync('php', ['-r', listScript], { cwd: ROOT_DIR, encoding: 'utf8' }).trim());
    if (!conversationId) {
        console.log('BỎ QUA: không tìm thấy cuộc trò chuyện nào có 2 thành viên trong database.');

        return;
    }

    const memberScript = `
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
echo implode(',', \\App\\Models\\Conversation::memberIds(${conversationId}));
`;
    const members = execFileSync('php', ['-r', memberScript], { cwd: ROOT_DIR, encoding: 'utf8' })
        .trim().split(',').map(Number);

    const [userA, userB] = members;
    console.log(`Cuộc trò chuyện #${conversationId} giữa user ${userA} và ${userB}\n`);

    const ticketA = issueFromPhp(userA, conversationId);
    const ticketB = issueFromPhp(userB, conversationId);

    // -------------------------------------------------------------
    // 1. Token sai bị từ chối
    // -------------------------------------------------------------
    const bad = await connect();
    const badResult = await new Promise((resolve) => {
        bad.on('message', (raw) => resolve(JSON.parse(raw.toString())));
        bad.send(JSON.stringify({ type: 'auth', token: 'khong-hop-le' }));
    });
    check('token không hợp lệ bị từ chối', badResult.type === 'error', `nhận "${badResult.type}"`);
    bad.close();

    // -------------------------------------------------------------
    // 2. Xác thực + subscribe thành công
    // -------------------------------------------------------------
    const socketA = await connect();
    const authA = waitFor(socketA, 'auth:ok');
    socketA.send(JSON.stringify({ type: 'auth', token: ticketA.token }));
    const authResult = await authA;
    check('xác thực bằng token hợp lệ', authResult.user && authResult.user.id === userA);

    const subA = waitFor(socketA, 'subscribe:ok');
    socketA.send(JSON.stringify({ type: 'subscribe', ticket: ticketA.ticket }));
    const subResult = await subA;
    check('subscribe vào cuộc trò chuyện thành công', subResult.conversation && subResult.conversation.id === conversationId);

    const socketB = await connect();
    const authB = waitFor(socketB, 'auth:ok');
    socketB.send(JSON.stringify({ type: 'auth', token: ticketB.token }));
    await authB;
    const subB = waitFor(socketB, 'subscribe:ok');
    socketB.send(JSON.stringify({ type: 'subscribe', ticket: ticketB.ticket }));
    await subB;
    check('client thứ hai subscribe thành công', true);

    // -------------------------------------------------------------
    // 3. Gửi tin nhắn — client kia nhận được
    // -------------------------------------------------------------
    const received = waitFor(socketB, 'message:new');
    socketA.send(JSON.stringify({
        type: 'message',
        conversation_id: conversationId,
        body: 'Tin nhắn kiểm thử WebSocket ' + Date.now(),
    }));
    const messageResult = await received;
    check('client nhận được tin nhắn realtime', Boolean(messageResult.message && messageResult.message.id));
    check('tin nhắn có đúng nội dung', String(messageResult.message.body).startsWith('Tin nhắn kiểm thử'));
    check('người nhận là thành viên hội thoại', messageResult.message.sender_id === userA);

    // -------------------------------------------------------------
    // 4. Trạng thái đang gõ
    // -------------------------------------------------------------
    const typing = waitFor(socketB, 'typing');
    socketA.send(JSON.stringify({ type: 'typing', conversation_id: conversationId, typing: true }));
    const typingResult = await typing;
    check('nhận trạng thái đang gõ', typingResult.typing === true && typingResult.user_id === userA);

    // -------------------------------------------------------------
    // 5. Đánh dấu đã đọc
    // -------------------------------------------------------------
    const readEvent = waitFor(socketA, 'message:read');
    socketB.send(JSON.stringify({ type: 'read', conversation_id: conversationId }));
    const readResult = await readEvent;
    check('nhận sự kiện đã đọc', Array.isArray(readResult.ids) && readResult.ids.length > 0);
    check('người đọc là client B', readResult.reader_id === userB);

    // -------------------------------------------------------------
    // 6. Client không thuộc hội thoại bị từ chối
    // -------------------------------------------------------------
    // Dùng một user KHÔNG phải thành viên của cuộc trò chuyện này.
    const outsiderScript = `
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
$members = \\App\\Models\\Conversation::memberIds(${conversationId});
$row = \\App\\Core\\Database::fetchOne('SELECT id FROM users WHERE id NOT IN (' . implode(',', $members) . ') LIMIT 1');
echo $row['id'] ?? '';
`;
    const outsiderId = Number(execFileSync('php', ['-r', outsiderScript], { cwd: ROOT_DIR, encoding: 'utf8' }).trim());
    if (!outsiderId) {
        console.log('BỎ QUA: không tìm thấy user ngoài cuộc trò chuyện để kiểm thử quyền.');
        socketA.close();
        socketB.close();

        return;
    }

    const outsiderTicket = issueFromPhp(outsiderId, conversationId);
    const outsider = await connect();
    const outsiderAuth = waitFor(outsider, 'auth:ok');
    outsider.send(JSON.stringify({ type: 'auth', token: outsiderTicket.token }));
    await outsiderAuth;
    const outsiderError = waitForOrClose(outsider, 'error');
    outsider.send(JSON.stringify({ type: 'message', conversation_id: conversationId, body: 'x' }));
    const outsiderResult = await outsiderError;
    check('client chưa subscribe bị từ chối gửi tin', outsiderResult.type === 'error');

    // -------------------------------------------------------------
    // 7. Client ngoài cuộc trò chuyện không subscribe được
    // -------------------------------------------------------------
    // Dùng socket mới vì socket trên đã bị server đóng sau lỗi ở bước 6.
    const outsider2 = await connect();
    const outsider2Auth = waitFor(outsider2, 'auth:ok');
    outsider2.send(JSON.stringify({ type: 'auth', token: outsiderTicket.token }));
    await outsider2Auth;
    const outsiderSub = waitForOrClose(outsider2, 'error');
    outsider2.send(JSON.stringify({ type: 'subscribe', ticket: outsiderTicket.ticket }));
    const outsiderSubResult = await outsiderSub;
    check('client không thuộc hội thoại bị từ chối subscribe', outsiderSubResult.type === 'error');

    outsider2.close();

    socketA.close();
    socketB.close();
    outsider.close();
}

main()
    .then(() => {
        console.log('');
        console.log(`PASS: ${passed} | FAIL: ${failed}`);
        process.exit(failed > 0 ? 1 : 0);
    })
    .catch((error) => {
        console.error('Lỗi:', error.message);
        console.log('');
        console.log(`PASS: ${passed} | FAIL: ${failed + 1}`);
        process.exit(1);
    });
