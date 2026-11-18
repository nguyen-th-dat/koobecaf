'use strict';

/**
 * Koobecaf — WebSocket server (chat realtime).
 *
 * Thay thế cơ chế AJAX polling 5 giây của trang /chat/{id} bằng kết nối
 * WebSocket giữ nguyên (push). Giao thức JSON nhỏ gọn, mọi frame có `type`.
 *
 * Khởi động:
 *   npm run ws
 *   node server/ws-server.js
 *
 * Môi trường (đọc từ .env của ứng dụng):
 *   WS_ENABLED, WS_HOST, WS_PORT, WS_PATH, WS_TOKEN_TTL, APP_KEY, DB_*
 */

const http = require('http');
const path = require('path');
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

/** @type {Database} */
let db;

/** @type {Map<string, {userId:number, sockets:Set<import('ws').WebSocket>, rooms:Map<number,object>}>} */
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

function fail(socket, message, code = 4000) {
    send(socket, { type: 'error', message });

    // Chờ khung 'error' được gửi đi rồi mới đóng socket, tránh client không
    // kịp nhận thông báo lỗi.
    try {
        socket.close(code, message.slice(0, 100));
    } catch (error) {
        // ignore
    }
}

/** Gửi tới mọi socket đang đăng nhập của một user. */
function sendToUser(userId, payload) {
    const client = clients.get(String(userId));
    if (!client) {
        return;
    }
    for (const socket of client.sockets) {
        send(socket, payload);
    }
}

/** Gửi tới mọi thành viên của một cuộc trò chuyện (trừ socket đã loại trừ nếu muốn). */
function sendToConversation(conversationId, payload, exceptSocket = null) {
    for (const client of clients.values()) {
        if (!client.rooms.has(conversationId)) {
            continue;
        }
        for (const socket of client.sockets) {
            if (socket === exceptSocket) {
                continue;
            }
            send(socket, payload);
        }
    }
}

function conversationOf(socket, conversationId) {
    const client = clients.get(String(socket._kbUserId));
    if (!client) {
        return null;
    }

    return client.rooms.get(conversationId) || null;
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
// Xử lý sự kiện từ client
// -----------------------------------------------------------------

async function handleAuth(socket, payload) {
    if (socket._kbClientId) {
        fail(socket, 'Phiên đã được xác thực.', 4002);
        return;
    }

    const auth = verifyToken(String(payload.token || ''), CONFIG.appKey);
    if (!auth || auth.uid <= 0) {
        fail(socket, 'Token không hợp lệ hoặc đã hết hạn.', 4001);
        return;
    }

    const user = await db.user(auth.uid);
    if (!user) {
        fail(socket, 'Người dùng không tồn tại.', 4001);
        return;
    }

    socket._kbClientId = String(nextClientId++);
    socket._kbUserId = auth.uid;
    socket._kbRooms = new Map();

    const clientId = socket._kbClientId;
    let client = clients.get(String(auth.uid));
    if (!client) {
        client = { userId: auth.uid, sockets: new Set(), rooms: new Map() };
        clients.set(String(auth.uid), client);
    }
    client.sockets.add(socket);

    send(socket, {
        type: 'auth:ok',
        user: {
            id: Number(user.id),
            username: user.username,
            display_name: user.display_name,
            avatar_path: user.avatar_path || null,
        },
    });

    log(`auth ok: user=${auth.uid} socket=${clientId}`);
}

async function handleSubscribe(socket, payload) {
    if (!socket._kbUserId) {
        fail(socket, 'Chưa xác thực.', 4001);
        return;
    }

    const ticket = verifyTicket(String(payload.ticket || ''), CONFIG.appKey);
    if (!ticket) {
        fail(socket, 'Ticket không hợp lệ hoặc đã hết hạn.', 4001);
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

    const client = clients.get(String(socket._kbUserId));
    client.rooms.set(conversationId, { peer: ticket.peer || {} });

    send(socket, {
        type: 'subscribe:ok',
        conversation: {
            id: conversationId,
            peer: ticket.peer || {},
        },
    });
}

async function handleMessage(socket, payload) {
    if (!socket._kbUserId) {
        fail(socket, 'Chưa xác thực.', 4001);
        return;
    }

    const body = String(payload.body === undefined || payload.body === null ? '' : payload.body).trim();
    if (body === '') {
        fail(socket, 'Nội dung tin nhắn không được để trống.');
        return;
    }
    if (body.length > 5000) {
        fail(socket, 'Nội dung tin nhắn quá dài.');
        return;
    }

    const conversationId = Number(payload.conversation_id) || 0;
    const room = conversationOf(socket, conversationId);
    if (!room) {
        fail(socket, 'Bạn chưa đăng ký theo dõi cuộc trò chuyện này.', 4003);
        return;
    }
    if (!(await db.isMember(conversationId, socket._kbUserId))) {
        fail(socket, 'Bạn không có quyền truy cập cuộc trò chuyện này.', 4003);
        return;
    }

    const messageId = await db.createMessage(conversationId, socket._kbUserId, body);
    const createdAt = new Date().toISOString().slice(0, 19).replace('T', ' ');

    const message = {
        id: messageId,
        conversation_id: conversationId,
        sender_id: socket._kbUserId,
        body,
        created_at: createdAt,
    };

    // Gửi cho tất cả thành viên đang online (bao gồm người gửi để đồng bộ client).
    sendToConversation(conversationId, {
        type: 'message:new',
        message,
    });

    // Thông báo cho các thành viên khác + cập nhật badge unread.
    const memberIds = await db.memberIds(conversationId);
    for (const memberId of memberIds) {
        if (memberId === socket._kbUserId) {
            continue;
        }
        await db.notify(memberId, socket._kbUserId, 'message', conversationId);
        sendToUser(memberId, {
            type: 'notification:new',
            type_name: 'message',
            conversation_id: conversationId,
        });
    }
}

async function handleTyping(socket, payload) {
    if (!socket._kbUserId) {
        return;
    }

    const conversationId = Number(payload.conversation_id) || 0;
    if (!conversationOf(socket, conversationId)) {
        return;
    }

    sendToConversation(conversationId, {
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
    if (!conversationOf(socket, conversationId)) {
        return;
    }

    const ids = await db.markRead(conversationId, socket._kbUserId);
    if (ids.length === 0) {
        return;
    }

    sendToConversation(conversationId, {
        type: 'message:read',
        conversation_id: conversationId,
        reader_id: socket._kbUserId,
        ids,
    });
}

async function handleFrame(socket, raw) {
    const payload = parsePayload(raw);
    if (!payload) {
        send(socket, { type: 'error', message: 'Frame không hợp lệ.' });
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
            send(socket, { type: 'error', message: `Unknown type: ${type}` });
    }
}

// -----------------------------------------------------------------
// Khởi động server
// -----------------------------------------------------------------

async function main() {
    db = new Database(CONFIG.db);

    const server = http.createServer((req, res) => {
        if (req.url === '/health' || req.url === '/healthz') {
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({
                status: 'ok',
                clients: clients.size,
                uptime: Math.round(process.uptime()),
            }));

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

        socket.on('pong', () => {
            socket.isAlive = true;
        });

        socket.on('message', (raw) => {
            handleFrame(socket, raw.toString()).catch((error) => {
                console.error('[ws] Lỗi xử lý frame:', error);
                send(socket, { type: 'error', message: 'Đã xảy ra lỗi khi xử lý yêu cầu.' });
            });
        });

        socket.on('close', () => {
            const clientId = socket._kbClientId;
            if (!clientId) {
                return;
            }

            const client = clients.get(String(socket._kbUserId));
            if (client) {
                client.sockets.delete(socket);
                if (client.sockets.size === 0) {
                    clients.delete(String(socket._kbUserId));
                }
            }
            log(`closed: socket=${clientId}`);
        });

        socket.on('error', (error) => {
            console.error('[ws] Lỗi socket:', error.message);
        });

        log(`connection từ ${request.socket.remoteAddress}`);
    });

    const heartbeat = setInterval(() => {
        for (const client of clients.values()) {
            for (const socket of client.sockets) {
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
        for (const client of clients.values()) {
            for (const socket of client.sockets) {
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
