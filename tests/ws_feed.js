'use strict';

/**
 * Kiểm thử end-to-end WebSocket cho feed và thông báo.
 *
 * Yêu cầu:
 *   - WebSocket server đang chạy (npm run ws)
 *   - PHP server / database của ứng dụng đã cấu hình trong .env
 *
 * Chạy:  node tests/ws_feed.js
 *
 * Test này kiểm tra chuỗi đầy đủ:
 *   1. Client xác thực bằng token đăng nhập (cấp bởi PHP).
 *   2. PHP phát sự kiện `post.created` → client nhận `feed:post`.
 *   3. PHP phát sự kiện `post.liked` → client nhận `feed:event` kèm số liệu.
 *   4. PHP phát sự kiện `notification.created` → client nhận `notification:new`.
 *   5. PHP phát sự kiện `social` → client nhận `feed:social`.
 *   6. Người không nằm trong audience không nhận được sự kiện.
 */

const { execFileSync } = require('child_process');
const path = require('path');
const WebSocket = require('ws');

const ROOT_DIR = path.resolve(__dirname, '..');
const WS_URL = process.env.KB_WS_URL || 'ws://127.0.0.1:8080/ws';
const PUBLISH_URL = process.env.KB_WS_PUBLISH_URL || 'http://127.0.0.1:8080/internal/publish';

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

/** Cấp token đăng nhập bằng đúng logic PHP của ứng dụng. */
function issueToken(userId) {
    const output = php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
echo \\App\\Core\\WebSocketToken::issue(${userId});
`);

    return output;
}

/** Phát sự kiện nội bộ tới WebSocket server (dùng chính WsPublisher của PHP). */
function publish(event, payload) {
    const json = JSON.stringify(payload).replace(/'/g, "\\'");
    const output = php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
$ok = \\App\\Core\\WsPublisher::publish('${event}', json_decode('${json}', true));
echo $ok ? 'ok' : 'fail';
`);

    return output === 'ok';
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

/** Tìm hai user bất kỳ trong database. */
function pickUsers() {
    const output = php(`
require 'app/bootstrap.php';
\\App\\Core\\Env::load('.env');
$rows = \\App\\Core\\Database::fetchAll('SELECT id FROM users ORDER BY id ASC LIMIT 2');
echo implode(',', array_map(fn($r) => (int) $r['id'], $rows));
`);

    return output.split(',').filter(Boolean).map(Number);
}

async function main() {
    const users = pickUsers();
    if (users.length < 2) {
        console.log('BỎ QUA: cần ít nhất 2 user trong database để kiểm thử.');

        return;
    }

    const [userA, userB] = users;
    console.log(`Kiểm thử feed/thông báo với user ${userA} và ${userB}\n`);

    const socketA = await connect();
    const authA = waitFor(socketA, 'auth:ok');
    socketA.send(JSON.stringify({ type: 'auth', token: issueToken(userA) }));
    const authResult = await authA;
    check('xác thực bằng token đăng nhập (không cần ticket chat)', authResult.user && authResult.user.id === userA);

    // -------------------------------------------------------------
    // 1. Sự kiện bài viết mới
    // -------------------------------------------------------------
    const postFrame = waitFor(socketA, 'feed:post');
    publish('post.created', {
        audience: [userA],
        post: { id: 999001, content: 'Bài kiểm thử realtime', author: { id: userB, display_name: 'B' } },
    });
    const postResult = await postFrame;
    check('nhận được feed:post', Boolean(postResult.post && postResult.post.id === 999001));
    check('feed:post chứa payload bài viết', Boolean(postResult.post && postResult.post.content === 'Bài kiểm thử realtime'));

    // -------------------------------------------------------------
    // 2. Sự kiện tương tác (like / comment / share)
    // -------------------------------------------------------------
    const eventFrame = waitFor(socketA, 'feed:event');
    publish('post.liked', {
        audience: [userA],
        post_id: 12345,
        actor: { id: userB, display_name: 'B' },
        stats: { like_count: 7, comment_count: 2, share_count: 1 },
    });
    const eventResult = await eventFrame;
    check('nhận được feed:event', eventResult.event === 'post.liked' && eventResult.post_id === 12345);
    check('feed:event kèm số liệu cập nhật', Boolean(eventResult.stats && eventResult.stats.like_count === 7));

    // -------------------------------------------------------------
    // 3. Thông báo mới
    // -------------------------------------------------------------
    const notifFrame = waitFor(socketA, 'notification:new');
    publish('notification.created', {
        user_id: userA,
        unread_count: 4,
        notification: { id: 555001, type: 'like', label: 'đã thích bài viết của bạn' },
    });
    const notifResult = await notifFrame;
    check('nhận được notification:new', Boolean(notifResult.notification && notifResult.notification.id === 555001));
    check('notification:new kèm số chưa đọc', notifResult.unread_count === 4);

    // -------------------------------------------------------------
    // 4. Sự kiện mạng xã hội
    // -------------------------------------------------------------
    const socialFrame = waitFor(socketA, 'feed:social');
    publish('social', {
        audience: [userA, userB],
        event: 'follow',
        actor: { id: userB, display_name: 'B' },
        target: { id: userA, display_name: 'A' },
    });
    const socialResult = await socialFrame;
    check('nhận được feed:social', socialResult.event === 'follow');

    // -------------------------------------------------------------
    // 5. Người ngoài audience không nhận được sự kiện
    // -------------------------------------------------------------
    const socketB = await connect();
    const authB = waitFor(socketB, 'auth:ok');
    socketB.send(JSON.stringify({ type: 'auth', token: issueToken(userB) }));
    await authB;

    const collector = collect(socketB, 1500);
    publish('post.created', {
        audience: [userA],
        post: { id: 999002, content: 'Không ai khác nhận được' },
    });
    const framesB = await collector;
    const leaked = framesB.some((frame) => frame.type === 'feed:post');
    check('người ngoài audience không nhận feed:post', !leaked);

    // -------------------------------------------------------------
    // 6. Chữ ký sai bị từ chối
    // -------------------------------------------------------------
    const response = await fetch(PUBLISH_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-KB-Signature': 'sai-chu-ky' },
        body: JSON.stringify({ event: 'post.created', payload: { audience: [userA], post: { id: 1 } } }),
    });
    check('endpoint nội bộ từ chối chữ ký sai', response.status === 401, `status ${response.status}`);

    socketA.close();
    socketB.close();
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
