'use strict';

/**
 * Koobecaf — WebSocket server (chat + feed + thông báo realtime).
 *
 * Thay thế cơ chế AJAX polling 5 giây của trang /chat/{id} bằng kết nối
 * WebSocket giữ nguyên (push), đồng thời phát sự kiện feed/thông báo tới mọi
 * trang đã đăng nhập. Giao thức JSON nhỏ gọn, mọi frame có `type`.
 *
 * Khởi động:
 *   npm run ws
 *   node server/ws-server.js
 *
 * Môi trường (đọc từ .env của ứng dụng):
 *   WS_ENABLED, WS_HOST, WS_PORT, WS_PATH, WS_TOKEN_TTL, WS_HEARTBEAT_MS,
 *   WS_AUTH_TIMEOUT_MS, WS_RATE_LIMIT, APP_KEY, DB_*
 */

const http = require('http');
const path = require('path');
const crypto = require('crypto');
const { WebSocketServer } = require('ws');

const { loadEnv } = require('./lib/env');
const { verifyToken, verifyTicket } = require('./lib/token');
const { Database } = require('./lib/database');

const ROOT_DIR = path.resolve(__dirname, '..');
const fileEnv = loadEnv(ROOT_DIR);

function env(key, fallback) {
    const value = process.env[key] !== undefined && process.env[key] !== ''
        ? process.env[key]
        : fileEnv[key];

    return value === undefined || value === '' ? fallback : value;
}

function envBool(key, fallback) {
    const value = String(env(key, fallback)).toLowerCase();

    return ['1', 'true', 'yes', 'on'].includes(value);
}

const CONFIG = {
    enabled: envBool('WS_ENABLED', true),
    host: String(env('WS_HOST', '127.0.0.1')),
    port: Number(env('WS_PORT', 8080)),
    path: String(env('WS_PATH', '/ws')),
    appKey: String(env('APP_KEY', '')),
    heartbeatMs: Number(env('WS_HEARTBEAT_MS', 30000)),
    /** Thời gian chờ client xác thực sau khi mở kết nối. */
    authTimeoutMs: Number(env('WS_AUTH_TIMEOUT_MS', 10000)),
    /** Số tin nhắn tối đa gửi trong `rateWindowMs` (chống spam). */
    rateLimit: Number(env('WS_RATE_LIMIT', 20)),
    rateWindowMs: Number(env('WS_RATE_WINDOW_MS', 10000)),
    maxBodyLength: 16 * 1024,
    db: {
        host: String(env('DB_HOST', '127.0.0.1')),
        port: Number(env('DB_PORT', 3306)),
        user: String(env('DB_USERNAME', 'root')),
        password: String(env('DB_PASSWORD', '')),
        database: String(env('DB_DATABASE', 'koobecaf')),
        connectionLimit: Number(env('WS_DB_POOL', 10)),
    },
};

if (!CONFIG.appKey) {
    console.error('[ws] Thiếu APP_KEY trong .env — không thể xác thực token. Hãy thêm APP_KEY=<chuỗi ngẫu nhiên>.');
    process.exit(1);
}

if (!CONFIG.enabled) {
    console.log('[ws] WS_ENABLED=false — WebSocket server không khởi động.');
    process.exit(0);
}

/** @type {Database} */
let db;

/**
 * Socket đang kết nối, gom theo user.
 *
 * @type {Map<number, Set<import('ws').WebSocket>>}
 */
const clients = new Map();

let nextClientId = 1;

// -----------------------------------------------------------------
// Tiện ích
// -----------------------------------------------------------------

function log(...args) {
    console.log('[ws]', ...args);
}

function send(socket, payload) {
    if (socket.readyState !== socket.OPEN) {
        return;
    }
    try {
        socket.send(JSON.stringify(payload), (error) => {
            if (error) {
                // Khung không gửi được — kết nối chết giữa chừng, sẽ được
                // dọn bởi sự kiện 'close'.
            }
        });
    } catch (error) {
        // Kết nối chết giữa chừng — sẽ được dọn bởi sự kiện 'close'.
    }
}

/**
 * Lỗi khiến kết nối không thể tiếp tục (token sai, không xác thực…).
 *
 * Server gửi frame `error` rồi đóng socket.
 */
function fail(socket, message, code = 4000) {
    send(socket, { type: 'error', message, code, fatal: true });

    try {
        socket.close(code, message.slice(0, 100));
    } catch (error) {
        // ignore
    }
}

/**
 * Lỗi nghiệp vụ — kết nối vẫn dùng được, chỉ báo lỗi cho client.
 *
 * Client có thể tự xử lý (ví dụ: token hết hạn → xin token mới rồi xác thực
 * lại trên chính kết nối này) thay vì phải đóng rồi kết nối lại.
 */
function reject(socket, message, extra = {}) {
    send(socket, Object.assign({ type: 'error', message, fatal: false }, extra));
}

/** User đang online (có ít nhất một socket đã xác thực). */
function isOnline(userId) {
    return clients.has(userId);
}

/** Gửi tới mọi socket đang đăng nhập của một user. */
function sendToUser(userId, payload) {
    const sockets = clients.get(Number(userId));
    if (!sockets) {
        return;
    }
    for (const socket of sockets) {
        send(socket, payload);
    }
}

/** Gửi tới nhiều user cùng lúc (mỗi user nhận đúng một bản). */
function sendToUsers(userIds, payload) {
    const seen = new Set();
    for (const userId of userIds) {
        const key = String(userId);
        if (seen.has(key)) {
            continue;
        }
        seen.add(key);
        sendToUser(userId, payload);
    }
}

/**
 * Gửi tới các socket của các thành viên của cuộc trò chuyện.
 *
 * Không giới hạn theo phòng đã subscribe: tin nhắn tới một cuộc trò chuyện
 * là thông tin chung của cả hai thành viên, kể cả khi họ đang ở trang khác
 * (client tự quyết định có hiển thị hay không).
 *
 * @param {number[]} memberIds Danh sách user thuộc cuộc trò chuyện.
 */
function sendToConversationMembers(memberIds, payload, exceptSocket = null) {
    for (const memberId of memberIds) {
        const sockets = clients.get(Number(memberId));
        if (!sockets) {
            continue;
        }
        for (const socket of sockets) {
            if (socket === exceptSocket) {
                continue;
            }
            send(socket, payload);
        }
    }
}

/** Gửi tới các socket đã subscribe phòng chat (dùng cho typing / read). */
function sendToConversationRoom(conversationId, payload, exceptSocket = null) {
    for (const client of clients.values()) {
        for (const socket of client) {
            if (socket === exceptSocket || !socket._kbRooms.has(conversationId)) {
                continue;
            }
            send(socket, payload);
        }
    }
}

function parsePayload(raw) {
    if (typeof raw !== 'string' || raw.length === 0) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (error) {
        return null;
    }
}

// -----------------------------------------------------------------
// Presence — trạng thái online trong từng cuộc trò chuyện
// -----------------------------------------------------------------

/**
 * Báo trạng thái online/offline của một thành viên cho các socket đang mở
 * đúng cuộc trò chuyện đó.
 *
 * @param {number} userId       User vừa kết nối / ngắt kết nối.
 * @param {number[]} peerIds    Các thành viên cần thông báo.
 * @param {number} conversationId Cuộc trò chuyện chung.
 */
function broadcastPresenceTo(userId, peerIds, conversationId, online) {
    for (const peerId of peerIds) {
        if (peerId === userId) {
            continue;
        }

        const sockets = clients.get(peerId);
        if (!sockets) {
            continue;
        }

        for (const socket of sockets) {
            if (!socket._kbRooms.has(conversationId)) {
                continue;
            }
            send(socket, {
                type: 'presence',
                conversation_id: conversationId,
                user_id: userId,
                online,
            });
        }
    }
}

/**
 * Gom peer theo cuộc trò chuyện để phát trạng thái online/offline.
 *
 * @param {number} userId
 * @returns {Promise<Map<number, number[]>>}
 */
async function peersByConversation(userId) {
    const rows = await db.conversationPeers(userId);
    const grouped = new Map();

    for (const row of rows) {
        if (!grouped.has(row.conversation_id)) {
            grouped.set(row.conversation_id, []);
        }
        grouped.get(row.conversation_id).push(row.peer_id);
    }

    return grouped;
}

/**
 * Báo trạng thái online/offline của một thành viên cho các socket đang mở
 * đúng cuộc trò chuyện đó.
 */
async function broadcastPresence(userId, conversationId, online) {
    const memberIds = await db.memberIds(conversationId);

    broadcastPresenceTo(userId, memberIds, conversationId, online);
}

/** Báo offline cho toàn bộ bạn bè của user vừa ngắt kết nối hoàn toàn. */
async function notifyOffline(userId) {
    const grouped = await peersByConversation(userId);

    for (const [conversationId, peerIds] of grouped) {
        broadcastPresenceTo(userId, peerIds, conversationId, false);
    }
}

// -----------------------------------------------------------------
// Xử lý sự kiện từ client
// -----------------------------------------------------------------

async function handleAuth(socket, payload) {
    const auth = verifyToken(String(payload.token || ''), CONFIG.appKey);
    if (!auth || auth.uid <= 0) {
        // Token hết hạn: giữ kết nối để client xin token mới rồi xác thực lại.
        // Token sai hoàn toàn thì đóng kết nối.
        const expired = isExpiredToken(payload.token);
        if (expired) {
            reject(socket, 'Token đã hết hạn.', { reason: 'token_expired' });

            return;
        }

        fail(socket, 'Token không hợp lệ.', 4001);

        return;
    }

    const user = await db.user(auth.uid);
    if (!user) {
        fail(socket, 'Người dùng không tồn tại.', 4001);

        return;
    }

    // Xác thực lại trên cùng socket (sau khi token hết hạn) — thay thế thông tin
    // cũ, không nhân đôi socket.
    if (socket._kbClientId) {
        socket._kbUserId = auth.uid;

        send(socket, {
            type: 'auth:ok',
            user: {
                id: Number(user.id),
                username: user.username,
                display_name: user.display_name,
                avatar_path: user.avatar_path || null,
            },
        });

        return;
    }

    socket._kbClientId = String(nextClientId++);
    socket._kbUserId = auth.uid;

    const clientId = socket._kbClientId;
    let sockets = clients.get(auth.uid);
    if (!sockets) {
        sockets = new Set();
        clients.set(auth.uid, sockets);
    }
    sockets.add(socket);

    if (socket._kbAuthTimer) {
        clearTimeout(socket._kbAuthTimer);
        socket._kbAuthTimer = null;
    }

    // Tự động vào phòng của mọi cuộc trò chuyện mà user là thành viên.
    // Nhờ vậy tin nhắn tới vẫn hiện realtime dù user đang ở trang khác
    // (trang danh sách hội thoại, feed…) thay vì chỉ trang /chat/{id}.
    const conversationIds = await db.conversationIds(auth.uid);
    for (const conversationId of conversationIds) {
        socket._kbRooms.set(conversationId, { peer: {} });
    }

    send(socket, {
        type: 'auth:ok',
        user: {
            id: Number(user.id),
            username: user.username,
            display_name: user.display_name,
            avatar_path: user.avatar_path || null,
        },
        conversations: conversationIds,
    });

    // Báo trạng thái online cho các thành viên khác trong những cuộc trò chuyện
    // đang mở cùng user.
    const grouped = await peersByConversation(auth.uid);
    for (const [conversationId, peerIds] of grouped) {
        broadcastPresenceTo(auth.uid, peerIds, conversationId, true);
    }

    log(`auth ok: user=${auth.uid} socket=${clientId} rooms=${conversationIds.length}`);
}

/** Kiểm tra token đã hết hạn (dùng để phân biệt lỗi "hết hạn" và "sai"). */
function isExpiredToken(token) {
    const raw = String(token || '');
    const index = raw.lastIndexOf('.');
    if (index <= 0) {
        return false;
    }

    try {
        const padded = raw.slice(0, index).replace(/-/g, '+').replace(/_/g, '/');
        const payload = JSON.parse(Buffer.from(padded, 'base64').toString('utf8'));

        return Number(payload.exp) > 0 && Number(payload.exp) * 1000 < Date.now();
    } catch (error) {
        return false;
    }
}

async function handleSubscribe(socket, payload) {
    if (!socket._kbUserId) {
        reject(socket, 'Chưa xác thực.', { reason: 'unauthenticated' });

        return;
    }

    const ticket = verifyTicket(String(payload.ticket || ''), CONFIG.appKey);
    if (!ticket) {
        // Ticket hết hạn → client xin ticket mới rồi subscribe lại, giữ kết nối.
        reject(socket, 'Ticket không hợp lệ hoặc đã hết hạn.', { reason: 'ticket_expired' });

        return;
    }
    if (ticket.uid !== socket._kbUserId) {
        fail(socket, 'Ticket không thuộc về bạn.', 4003);

        return;
    }

    const conversationId = ticket.cid;
    if (!(await db.isMember(conversationId, socket._kbUserId))) {
        fail(socket, 'Bạn không có quyền truy cập cuộc trò chuyện này.', 4003);

        return;
    }

    socket._kbRooms.set(conversationId, { peer: ticket.peer || {} });

    send(socket, {
        type: 'subscribe:ok',
        conversation: {
            id: conversationId,
            peer: ticket.peer || {},
            peer_online: isOnline(Number((ticket.peer || {}).id) || 0),
        },
    });

    // Báo cho phía kia biết mình đang online trong cuộc trò chuyện này.
    await broadcastPresence(socket._kbUserId, conversationId, true);
}

async function handleMessage(socket, payload) {
    if (!socket._kbUserId) {
        reject(socket, 'Chưa xác thực.', { reason: 'unauthenticated' });

        return;
    }

    const body = String(payload.body === undefined || payload.body === null ? '' : payload.body).trim();
    if (body === '') {
        reject(socket, 'Nội dung tin nhắn không được để trống.', { local_id: payload.local_id ?? null });

        return;
    }
    if (body.length > 5000) {
        reject(socket, 'Nội dung tin nhắn quá dài.', { local_id: payload.local_id ?? null });

        return;
    }

    // Chống spam: giới hạn số tin nhắn gửi trong một khoảng thời gian.
    if (!allowMessage(socket)) {
        reject(socket, 'Bạn gửi tin quá nhanh, hãy chậm lại.', { local_id: payload.local_id ?? null });

        return;
    }

    const conversationId = Number(payload.conversation_id) || 0;
    if (!(await db.isMember(conversationId, socket._kbUserId))) {
        reject(socket, 'Bạn không có quyền truy cập cuộc trò chuyện này.', {
            local_id: payload.local_id ?? null,
            reason: 'forbidden',
        });

        return;
    }

    const created = await db.createMessage(conversationId, socket._kbUserId, body);
    const sender = await db.user(socket._kbUserId);

    const message = {
        id: created.id,
        conversation_id: conversationId,
        sender_id: socket._kbUserId,
        body,
        created_at: created.created_at,
        sender: sender
            ? {
                id: Number(sender.id),
                username: sender.username,
                display_name: sender.display_name,
            }
            : null,
    };

    // Gửi cho tất cả thành viên đang online (bao gồm người gửi để đồng bộ client).
    const memberIds = await db.memberIds(conversationId);
    sendToConversationMembers(memberIds, {
        type: 'message:new',
        message,
        local_id: payload.local_id ?? null,
    });

    // Thông báo cho các thành viên khác + cập nhật badge unread.
    for (const memberId of memberIds) {
        if (memberId === socket._kbUserId) {
            continue;
        }

        const notificationId = await db.notify(memberId, socket._kbUserId, 'message', conversationId);
        sendToUser(memberId, {
            type: 'notification:new',
            notification: {
                id: notificationId,
                type: 'message',
                entity_id: conversationId,
                label: 'đã gửi cho bạn một tin nhắn',
                icon: 'bi-envelope-fill',
                actor: sender
                    ? {
                        id: Number(sender.id),
                        username: sender.username,
                        display_name: sender.display_name,
                        avatar_path: sender.avatar_path || null,
                    }
                    : null,
            },
            unread_count: await db.unreadNotificationCount(memberId),
        });
    }
}

/** Cho phép tối đa `CONFIG.rateLimit` tin nhắn mỗi `rateWindowMs`. */
function allowMessage(socket) {
    const now = Date.now();
    if (!socket._kbRate || now - socket._kbRate.startedAt > CONFIG.rateWindowMs) {
        socket._kbRate = { startedAt: now, count: 0 };
    }

    socket._kbRate.count += 1;

    return socket._kbRate.count <= CONFIG.rateLimit;
}

async function handleTyping(socket, payload) {
    if (!socket._kbUserId) {
        return;
    }

    const conversationId = Number(payload.conversation_id) || 0;
    if (!socket._kbRooms.has(conversationId)) {
        return;
    }

    sendToConversationRoom(conversationId, {
        type: 'typing',
        conversation_id: conversationId,
        user_id: socket._kbUserId,
        typing: Boolean(payload.typing),
    }, socket);
}

async function handleRead(socket, payload) {
    if (!socket._kbUserId) {
        return;
    }

    const conversationId = Number(payload.conversation_id) || 0;
    if (!socket._kbRooms.has(conversationId)) {
        return;
    }

    const ids = await db.markRead(conversationId, socket._kbUserId);
    if (ids.length === 0) {
        return;
    }

    sendToConversationRoom(conversationId, {
        type: 'message:read',
        conversation_id: conversationId,
        reader_id: socket._kbUserId,
        ids,
    });
}

async function handleFrame(socket, raw) {
    const payload = parsePayload(raw);
    if (!payload) {
        reject(socket, 'Frame không hợp lệ.');

        return;
    }

    const type = String(payload.type || '');

    switch (type) {
        case 'auth':
            await handleAuth(socket, payload);
            return;
        case 'subscribe':
            await handleSubscribe(socket, payload);
            return;
        case 'message':
            await handleMessage(socket, payload);
            return;
        case 'typing':
            await handleTyping(socket, payload);
            return;
        case 'read':
            await handleRead(socket, payload);
            return;
        case 'ping':
            send(socket, { type: 'pong', time: Date.now() });
            return;
        default:
            reject(socket, `Unknown type: ${type}`);
    }
}

// -----------------------------------------------------------------
// Sự kiện feed & thông báo (đẩy từ ứng dụng PHP)
// -----------------------------------------------------------------

/**
 * Đẩy một bài viết mới tới những người được phép xem.
 *
 * `audience` do PHP tính sẵn (tác giả + người theo dõi + bạn bè tương ứng với
 * mức quyền riêng tư) nên server không cần tự suy luận lại quyền xem.
 */
function broadcastPost(payload) {
    const audience = Array.isArray(payload.audience) ? payload.audience : [];
    if (audience.length === 0) {
        return;
    }

    sendToUsers(audience, {
        type: 'feed:post',
        post: payload.post || null,
    });
}

/**
 * Đẩy sự kiện tương tác (like/comment/share) tới người xem bài viết.
 */
function broadcastPostEvent(payload) {
    const audience = Array.isArray(payload.audience) ? payload.audience : [];
    if (audience.length === 0) {
        return;
    }

    sendToUsers(audience, {
        type: 'feed:event',
        event: payload.event || 'post:update',
        post_id: Number(payload.post_id) || 0,
        actor: payload.actor || null,
        stats: payload.stats || null,
        comment: payload.comment || null,
    });
}

/**
 * Đẩy thông báo mới tới đúng người nhận.
 */
function broadcastNotification(payload) {
    const userId = Number(payload.user_id) || 0;
    if (userId <= 0) {
        return;
    }

    sendToUser(userId, {
        type: 'notification:new',
        notification: payload.notification || null,
        unread_count: Number(payload.unread_count) || 0,
    });
}

/** Đẩy sự kiện trạng thái mạng xã hội (follow / kết bạn) tới người liên quan. */
function broadcastSocial(payload) {
    const audience = Array.isArray(payload.audience) ? payload.audience : [];
    if (audience.length === 0) {
        return;
    }

    sendToUsers(audience, {
        type: 'feed:social',
        event: payload.event || 'follow',
        actor: payload.actor || null,
        target: payload.target || null,
    });
}

    /**
     * Đẩy tin nhắn chat vừa được gửi qua HTTP (fallback) tới các thành viên.
     *
     * Khi WebSocket của người gửi đang chết, client gửi tin qua HTTP; nếu không
     * có bước này thì phía bạn bè sẽ không thấy tin nhắn cho tới lần polling kế
     * tiếp.
     */
    function broadcastChatMessage(payload) {
        const conversationId = Number(payload.conversation_id) || 0;
        if (conversationId <= 0) {
            return;
        }

        const message = payload.message || null;
        if (!message) {
            return;
        }

        const recipients = Array.isArray(payload.audience) && payload.audience.length > 0
            ? payload.audience.map(Number)
            : [];

        for (const userId of recipients) {
            sendToUser(userId, {
                type: 'message:new',
                message,
                local_id: null,
            });
        }
    }
/**
 * Xử lý body của endpoint nội bộ `/internal/publish`.
 */
async function handleInternalPublish(body) {
    let event = '';
    let payload = {};

    if (Array.isArray(body)) {
        // Batch: mảng các sự kiện.
        for (const item of body) {
            await dispatchInternalEvent(item);
        }

        return;
    }

    if (body && typeof body === 'object') {
        event = String(body.event || '');
        payload = body.payload && typeof body.payload === 'object' ? body.payload : {};
    }

    await dispatchInternalEvent({ event, payload });
}

async function dispatchInternalEvent(item) {
    if (!item || typeof item !== 'object') {
        return;
    }

    const event = String(item.event || '');
    const payload = item.payload && typeof item.payload === 'object' ? item.payload : {};

    switch (event) {
        case 'post.created':
            broadcastPost(payload);
            return;
        case 'post.updated':
        case 'post.deleted':
        case 'post.liked':
        case 'post.unliked':
        case 'post.commented':
        case 'post.uncommented':
        case 'post.shared':
            // Tên sự kiện nằm ở cấp ngoài payload → gắn vào để client biết
            // loại tương tác (like / comment / share / deleted…). Payload do
            // PHP gửi có thể ghi đè lại (ví dụ 'post.liked').
            broadcastPostEvent(Object.assign({}, payload, { event: payload.event || event }));
            return;
        case 'notification.created':
            broadcastNotification(payload);
            return;
        case 'social':
            broadcastSocial(payload);
            return;
        case 'chat.message':
            broadcastChatMessage(payload);
            return;
        default:
            log(`bỏ qua sự kiện không hợp lệ: ${event}`);
    }
}

// -----------------------------------------------------------------
// Khởi động server
// -----------------------------------------------------------------

/**
 * Kiểm tra chữ ký HMAC của body nội bộ.
 *
 * Ứng dụng PHP ký body bằng APP_KEY (`X-KB-Signature` header). Endpoint này
 * chỉ nên được gọi từ máy chủ ứng dụng, không để lộ ra ngoài.
 */
function verifySignature(rawBody, signature) {
    if (!CONFIG.appKey || typeof signature !== 'string' || signature === '') {
        return false;
    }

    const expected = crypto.createHmac('sha256', CONFIG.appKey).update(rawBody).digest('hex');
    const given = Buffer.from(signature, 'utf8');
    const expectedBuffer = Buffer.from(expected, 'utf8');

    if (given.length !== expectedBuffer.length) {
        return false;
    }

    return crypto.timingSafeEqual(given, expectedBuffer);
}

function readRequestBody(req, limit = 512 * 1024) {
    return new Promise((resolve, reject) => {
        const chunks = [];
        let size = 0;

        req.on('data', (chunk) => {
            size += chunk.length;
            if (size > limit) {
                reject(new Error('Body quá lớn'));
                req.destroy();

                return;
            }
            chunks.push(chunk);
        });
        req.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
        req.on('error', reject);
    });
}

async function main() {
    db = new Database(CONFIG.db);

    const server = http.createServer(async (req, res) => {
        const requestUrl = (req.url || '').split('?')[0];

        if (requestUrl === '/health' || requestUrl === '/healthz') {
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({
                status: 'ok',
                clients: clients.size,
                sockets: Array.from(clients.values()).reduce((sum, set) => sum + set.size, 0),
                uptime: Math.round(process.uptime()),
            }));

            return;
        }

        // Endpoint nội bộ: ứng dụng PHP đẩy sự kiện feed/thông báo vào đây.
        if (requestUrl === '/internal/publish' && req.method === 'POST') {
            let raw = '';
            try {
                raw = await readRequestBody(req);
            } catch (error) {
                res.writeHead(413, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: false, message: 'Body quá lớn.' }));

                return;
            }

            const signature = req.headers['x-kb-signature'];
            if (!verifySignature(raw, Array.isArray(signature) ? signature[0] : signature)) {
                res.writeHead(401, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: false, message: 'Chữ ký không hợp lệ.' }));

                return;
            }

            let body = null;
            try {
                body = raw === '' ? {} : JSON.parse(raw);
            } catch (error) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: false, message: 'JSON không hợp lệ.' }));

                return;
            }

            await handleInternalPublish(body);

            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ ok: true }));

            return;
        }

        res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end('Not found');
    });

    const wss = new WebSocketServer({
        server,
        path: '/' + CONFIG.path.replace(/^\/+/, ''),
        maxPayload: CONFIG.maxBodyLength,
    });

    wss.on('connection', (socket, request) => {
        socket.isAlive = true;
        socket._kbClientId = null;
        socket._kbUserId = 0;
        socket._kbRooms = new Map();
        socket._kbRate = null;

        // Socket mở mà không xác thực sẽ bị đóng sau một khoảng thời gian —
        // tránh giữ kết nối rác.
        socket._kbAuthTimer = setTimeout(() => {
            if (!socket._kbClientId) {
                try {
                    socket.close(4001, 'auth timeout');
                } catch (error) {
                    // ignore
                }
            }
        }, CONFIG.authTimeoutMs);

        socket.on('pong', () => {
            socket.isAlive = true;
        });

        socket.on('message', (raw) => {
            handleFrame(socket, raw.toString()).catch((error) => {
                console.error('[ws] Lỗi xử lý frame:', error);
                reject(socket, 'Đã xảy ra lỗi khi xử lý yêu cầu.');
            });
        });

        socket.on('close', () => {
            if (socket._kbAuthTimer) {
                clearTimeout(socket._kbAuthTimer);
                socket._kbAuthTimer = null;
            }

            const userId = socket._kbUserId;
            const clientId = socket._kbClientId;
            if (!clientId) {
                return;
            }

            const sockets = clients.get(userId);
            if (sockets) {
                sockets.delete(socket);
                if (sockets.size === 0) {
                    clients.delete(userId);

                    // Người dùng rời đi hoàn toàn → báo offline cho các phòng
                    // chat mà họ đang ở.
                    notifyOffline(userId).catch((error) => {
                        console.error('[ws] Lỗi phát trạng thái offline:', error);
                    });
                }
            }

            log(`closed: socket=${clientId}`);
        });

        socket.on('error', (error) => {
            console.error('[ws] Lỗi socket:', error.message);
        });

        log(`connection từ ${request.socket.remoteAddress}`);
    });

    // Heartbeat cho MỌI socket (kể cả chưa xác thực) — dọn kết nối treo.
    const heartbeat = setInterval(() => {
        for (const socket of wss.clients) {
            if (socket.isAlive === false) {
                socket.terminate();
                continue;
            }
            socket.isAlive = false;
            try {
                socket.ping();
            } catch (error) {
                socket.terminate();
            }
        }
    }, CONFIG.heartbeatMs);

    wss.on('close', () => clearInterval(heartbeat));

    server.listen(CONFIG.port, CONFIG.host, () => {
        log(`đang lắng nghe tại ws://${CONFIG.host}:${CONFIG.port}/${CONFIG.path.replace(/^\/+/, '')}`);
        log(`database: ${CONFIG.db.user}@${CONFIG.db.host}:${CONFIG.db.port}/${CONFIG.db.database}`);
    });

    const shutdown = async () => {
        log('đang tắt...');
        clearInterval(heartbeat);
        for (const sockets of clients.values()) {
            for (const socket of sockets) {
                socket.close(1001, 'server shutdown');
            }
        }
        wss.close();
        await db.close();
        process.exit(0);
    };

    process.on('SIGINT', shutdown);
    process.on('SIGTERM', shutdown);
}

main().catch((error) => {
    console.error('[ws] Không thể khởi động:', error);
    process.exit(1);
});
