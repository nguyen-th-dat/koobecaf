'use strict';

/**
 * Kiểm thử các tính năng nâng cấp của WebSocket server.
 *
 * Yêu cầu:
 *   - WebSocket server đang chạy (npm run ws)
 *   - PHP server / database của ứng dụng đã cấu hình trong .env
 *
 * Chạy:  node tests/ws_upgrade.js
 *
 * Kiểm tra:
 *   1. Token hết hạn → server báo lỗi KHÔNG đóng socket (client xin token mới).
 *   2. Ticket hết hạn → server báo lỗi KHÔNG đóng socket (client xin ticket mới).
 *   3. created_at của tin nhắn khớp với giờ DB (không lệch múi giờ).
 *   4. Presence: đóng socket → phía kia nhận presence online = false.
 *   5. Tin nhắn gửi qua HTTP fallback được đẩy realtime tới bạn bè.
 *   6. Chặn spam: gửi quá nhiều tin trong ngắn thời gian bị từ chối.
 *   7. Heartbeat đóng socket treo (không xác thực).
 */

const { execFileSync } = require('child_process');
const path = require('path');
const crypto = require('crypto');
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

/** Chạy một đoạn PHP trong thư mục gốc và trả về stdout. */
function php(script) {
    return execFileSync('php', ['-r', script], { cwd: ROOT_DIR, encoding: 'utf8' }).trim();
}

/** Cấp token đăng nhập (TTL tuỳ chọn) bằng đúng logic PHP. */
function issueToken(userId, ttl = null) {
    const ttlCode = ttl === null ? '' : `, ${ttl}`;
    return php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
echo \\App\\Core\\WebSocketToken::issue(${userId}${ttlCode});
`);
}

/** Cấp token đăng nhập đã hết hạn (ttl âm) để mô phỏng token cũ. */
function issueExpiredToken(userId) {
    return issueToken(userId, -10);
}

function issueTicket(userId, conversationId, ttl = null) {
    const ttlCode = ttl === null ? '' : `, ${ttl}`;
    return php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
$peerId = null;
foreach (\\App\\Models\\Conversation::memberIds(${conversationId}) as $id) {
    if ($id != ${userId}) { $peerId = $id; break; }
}
$peer = \\App\\Models\\User::find($peerId);
echo \\App\\Core\\WebSocketToken::issueTicket(${userId}, ${conversationId}, [
    'id' => (int) $peer['id'],
    'username' => $peer['username'],
    'display_name' => $peer['display_name'],
    'avatar_path' => $peer['avatar_path'],
]${ttlCode});
`);
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

/** Gom mọi frame nhận được trong khoảng thời gian cho trước. */
function collect(socket, duration = 1200) {
    const frames = [];
    const handler = (raw) => {
        try {
            frames.push(JSON.parse(raw.toString()));
        } catch (error) {
            // ignore
        }
    };

    socket.on('message', handler);

    return new Promise((resolve) => {
        setTimeout(() => {
            socket.off('message', handler);
            resolve(frames);
        }, duration);
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

/** Tìm một cuộc trò chuyện có 2 thành viên trong DB. */
function pickConversation() {
    const output = php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
$row = \\App\\Core\\Database::fetchOne(
    'SELECT conversation_id FROM conversation_members GROUP BY conversation_id HAVING COUNT(*) = 2 LIMIT 1'
);
echo $row['conversation_id'] ?? '';
`);

    return Number(output);
}

function memberIds(conversationId) {
    const output = php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
echo implode(',', \\App\\Models\\Conversation::memberIds(${conversationId}));
`);

    return output.split(',').filter(Boolean).map(Number);
}

/**
 * Tạo 2 user + 1 cuộc trò chuyện tạm để test presence.
 *
 * Dùng user tạm tránh ảnh hưởng của các phiên đang mở sẵn (ví dụ trình
 * duyệt đang đăng nhập) — nếu user thật còn socket khác thì họ vẫn "online"
 * và cảm ứng offline sẽ không được phát.
 */
function createTempPair() {
    const output = php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
$tag = 'ws_tmp_' . time();
$ids = [];
foreach (['a', 'b'] as $part) {
    $id = \\App\\Core\\Database::insert(
        'INSERT INTO users (username, email, password_hash, display_name) VALUES (?, ?, ?, ?)',
        [
            $tag . '_' . $part,
            $tag . '_' . $part . '@example.test',
            password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            'WS Test ' . strtoupper($part),
        ]
    );
    $ids[] = $id;
}
$conversationId = \\App\\Core\\Database::insert('INSERT INTO conversations () VALUES ()');
foreach ($ids as $id) {
    \\App\\Core\\Database::insert(
        'INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?)',
        [$conversationId, $id]
    );
}
echo $conversationId . ',' . implode(',', $ids);
`);

    const [conversationId, ...ids] = output.split(',').map(Number);

    return { conversationId, userA: ids[0], userB: ids[1] };
}

/** Xoá dữ liệu tạm sau khi test. */
function cleanupTempPair(conversationId, userIds) {
    php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
\\App\\Core\\Database::run('DELETE FROM conversation_members WHERE conversation_id = ?', [${conversationId}]);
\\App\\Core\\Database::run('DELETE FROM conversations WHERE id = ?', [${conversationId}]);
\\App\\Core\\Database::run('DELETE FROM users WHERE id IN (${userIds.join(',')})');
`);
}

async function main() {
    const conversationId = pickConversation();
    if (!conversationId) {
        console.log('BỎ QUA: không tìm thấy cuộc trò chuyện nào có 2 thành viên trong database.');

        return;
    }

    const [userA, userB] = memberIds(conversationId);
    console.log(`Cuộc trò chuyện #${conversationId} giữa user ${userA} và ${userB}\n`);

    // -------------------------------------------------------------
    // 1. Token hết hạn → lỗi nhưng KHÔNG đóng socket
    // -------------------------------------------------------------
    const expiredSocket = await connect();
    const expiredError = waitFor(expiredSocket, 'error');
    expiredSocket.send(JSON.stringify({ type: 'auth', token: issueExpiredToken(userA) }));
    const expiredResult = await expiredError;

    check('token hết hạn báo lỗi', expiredResult.reason === 'token_expired', `reason "${expiredResult.reason}"`);
    check('token hết hạn không đóng socket', expiredResult.fatal === false);

    // Socket vẫn dùng được → xác thực lại bằng token hợp lệ.
    const reauth = waitFor(expiredSocket, 'auth:ok');
    expiredSocket.send(JSON.stringify({ type: 'auth', token: issueToken(userA) }));
    const reauthResult = await reauth;
    check('xác thực lại trên cùng socket sau khi hết hạn', reauthResult.user && reauthResult.user.id === userA);
    expiredSocket.close();

    // -------------------------------------------------------------
    // 2. Ticket hết hạn → lỗi nhưng KHÔNG đóng socket
    // -------------------------------------------------------------
    const ticketSocket = await connect();
    const authFrame = waitFor(ticketSocket, 'auth:ok');
    ticketSocket.send(JSON.stringify({ type: 'auth', token: issueToken(userA) }));
    await authFrame;

    const ticketError = waitFor(ticketSocket, 'error');
    ticketSocket.send(JSON.stringify({ type: 'subscribe', ticket: issueTicket(userA, conversationId, -10) }));
    const ticketResult = await ticketError;

    check('ticket hết hạn báo lỗi', ticketResult.reason === 'ticket_expired', `reason "${ticketResult.reason}"`);
    check('ticket hết hạn không đóng socket', ticketResult.fatal === false);

    // Subscribe lại bằng ticket hợp lệ trên chính kết nối đó.
    const subFrame = waitFor(ticketSocket, 'subscribe:ok');
    ticketSocket.send(JSON.stringify({ type: 'subscribe', ticket: issueTicket(userA, conversationId) }));
    const subResult = await subFrame;
    check('subscribe lại được sau khi ticket hết hạn', subResult.conversation && subResult.conversation.id === conversationId);

    // -------------------------------------------------------------
    // 3. created_at khớp giờ DB
    // -------------------------------------------------------------
    const messageFrame = waitFor(ticketSocket, 'message:new');
    ticketSocket.send(JSON.stringify({
        type: 'message',
        conversation_id: conversationId,
        body: 'Kiểm tra múi giờ ' + Date.now(),
    }));
    const messageResult = await messageFrame;

    const dbCreatedAt = php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
date_default_timezone_set('Asia/Ho_Chi_Minh');
$row = \\App\\Core\\Database::fetchOne('SELECT created_at FROM messages WHERE id = ?', [${messageResult.message.id}]);
echo $row['created_at'] ?? '';
`);

    check(
        'created_at của tin nhắn khớp giờ DB',
        String(messageResult.message.created_at) === String(dbCreatedAt),
        `server "${messageResult.message.created_at}" vs DB "${dbCreatedAt}"`
    );

    // -------------------------------------------------------------
    // 4. Presence offline khi socket đóng
    // -------------------------------------------------------------
    // Dùng user tạm: nếu user thật còn phiên khác đang mở (trình duyệt đang
    // đăng nhập) thì họ vẫn online và sự kiện offline sẽ không được phát.
    const temp = createTempPair();
    const presenceUserA = temp.userA;
    const presenceUserB = temp.userB;

    const presencePeer = await connect();
    const presencePeerAuth = waitFor(presencePeer, 'auth:ok');
    presencePeer.send(JSON.stringify({ type: 'auth', token: issueToken(presenceUserB) }));
    await presencePeerAuth;
    const presencePeerSub = waitFor(presencePeer, 'subscribe:ok');
    presencePeer.send(JSON.stringify({
        type: 'subscribe',
        ticket: issueTicket(presenceUserB, temp.conversationId),
    }));
    await presencePeerSub;

    const presenceTarget = await connect();
    const presenceTargetAuth = waitFor(presenceTarget, 'auth:ok');
    presenceTarget.send(JSON.stringify({ type: 'auth', token: issueToken(presenceUserA) }));
    await presenceTargetAuth;

    const presenceFrames = collect(presencePeer, 3000);
    presenceTarget.close();

    const allPresence = (await presenceFrames).filter((frame) => frame.type === 'presence');
    const presence = allPresence.find((frame) => frame.online === false);
    check('nhận sự kiện presence khi bạn bè đóng kết nối', allPresence.length > 0, 'không nhận được frame presence');
    check('presence báo offline', presence && presence.online === false);
    check(
        'presence đúng cuộc trò chuyện',
        presence && Number(presence.conversation_id) === temp.conversationId
    );
    presencePeer.close();
    cleanupTempPair(temp.conversationId, [presenceUserA, presenceUserB]);

    // -------------------------------------------------------------
    // 5. Tin nhắn gửi qua HTTP fallback được đẩy realtime
    // -------------------------------------------------------------
    const httpSocket = await connect();
    const httpAuth = waitFor(httpSocket, 'auth:ok');
    httpSocket.send(JSON.stringify({ type: 'auth', token: issueToken(userB) }));
    await httpAuth;

    const httpReceiver = waitFor(httpSocket, 'message:new');
    php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
\\App\\Services\\RealtimeService::chatMessage(${conversationId}, [
    'id' => 999777,
    'conversation_id' => ${conversationId},
    'sender_id' => ${userA},
    'body' => 'Tin nhắn qua HTTP fallback',
    'created_at' => date('Y-m-d H:i:s'),
]);
`);
    const httpResult = await httpReceiver;
    check('tin nhắn gửi qua HTTP được đẩy realtime', httpResult.message && httpResult.message.body === 'Tin nhắn qua HTTP fallback');
    httpSocket.close();

    // -------------------------------------------------------------
    // 6. Chặn spam
    // -------------------------------------------------------------
    const spamSocket = await connect();
    const spamAuth = waitFor(spamSocket, 'auth:ok');
    spamSocket.send(JSON.stringify({ type: 'auth', token: issueToken(userA) }));
    await spamAuth;
    const spamSub = waitFor(spamSocket, 'subscribe:ok');
    spamSocket.send(JSON.stringify({ type: 'subscribe', ticket: issueTicket(userA, conversationId) }));
    await spamSub;

    const spamFrames = collect(spamSocket, 4000);
    for (let i = 0; i < 30; i++) {
        spamSocket.send(JSON.stringify({
            type: 'message',
            conversation_id: conversationId,
            body: `spam ${i}`,
        }));
    }

    const spamErrors = (await spamFrames).filter((frame) => frame.type === 'error');
    check('gửi quá nhiều tin bị chặn', spamErrors.length > 0, 'không có frame lỗi nào');
    spamSocket.close();

    // -------------------------------------------------------------
    // 7. Socket treo không xác thực bị đóng
    // -------------------------------------------------------------
    const idleSocket = await connect();
    const closed = await new Promise((resolve) => {
        const timer = setTimeout(() => resolve(false), 15000);
        idleSocket.on('close', () => {
            clearTimeout(timer);
            resolve(true);
        });
    });
    check('socket không xác thực bị đóng sau thời gian chờ', closed);
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
