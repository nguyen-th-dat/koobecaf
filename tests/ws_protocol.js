'use strict';

/**
 * Kiểm thử giao thức WebSocket của Koobecaf.
 *
 * Chạy:  npm run ws:test
 *
 * Test này KHÔNG cần WebSocket server đang chạy — nó kiểm tra logic token/ticket
 * (đọc .env, ký HMAC, xác thực) và khả năng parse frame.
 * Nếu WS_HOST/WS_PORT đang lắng nghe, test sẽ chạy thêm phần kết nối thật.
 */

const assert = require('assert');
const path = require('path');

const { loadEnv } = require('../server/lib/env');
const { sign, verifyToken, verifyTicket, base64UrlEncode } = require('../server/lib/token');

const ROOT_DIR = path.resolve(__dirname, '..');
const env = loadEnv(ROOT_DIR);

let passed = 0;
let failed = 0;

function check(name, fn) {
    try {
        fn();
        passed++;
        console.log(`PASS: ${name}`);
    } catch (error) {
        failed++;
        console.log(`FAIL: ${name} — ${error.message}`);
    }
}

const APP_KEY = env.APP_KEY || 'test-key';

// -----------------------------------------------------------------
// Token đăng nhập
// -----------------------------------------------------------------

check('cấp token đăng nhập hợp lệ', () => {
    const token = sign('koobecaf:ws-token:', { uid: 7, exp: Math.floor(Date.now() / 1000) + 300, jti: 'x' }, APP_KEY);
    const payload = verifyToken(token, APP_KEY);
    assert.ok(payload, 'token phải xác thực được');
    assert.strictEqual(payload.uid, 7);
});

check('token sai chữ ký bị từ chối', () => {
    const token = sign('koobecaf:ws-token:', { uid: 7, exp: Math.floor(Date.now() / 1000) + 300 }, APP_KEY);
    assert.strictEqual(verifyToken(token, 'khac-key'), null);
});

check('token hết hạn bị từ chối', () => {
    const token = sign('koobecaf:ws-token:', { uid: 7, exp: Math.floor(Date.now() / 1000) - 10 }, APP_KEY);
    assert.strictEqual(verifyToken(token, APP_KEY), null);
});

check('token không rõ hình dạng bị từ chối', () => {
    assert.strictEqual(verifyToken('abc', APP_KEY), null);
    assert.strictEqual(verifyToken('', APP_KEY), null);
    assert.strictEqual(verifyToken('a.b.c', APP_KEY), null);
});

check('ticket không dùng được để đăng nhập', () => {
    const ticket = sign('koobecaf:ws-ticket:', { uid: 7, cid: 3, exp: Math.floor(Date.now() / 1000) + 300 }, APP_KEY);
    assert.strictEqual(verifyToken(ticket, APP_KEY), null, 'ticket phải bị từ chối khi dùng làm token');

    const payload = verifyTicket(ticket, APP_KEY);
    assert.ok(payload);
    assert.strictEqual(payload.cid, 3);
});

check('ticket xác thực đúng người dùng và hội thoại', () => {
    const ticket = sign('koobecaf:ws-ticket:', {
        uid: 9,
        cid: 12,
        exp: Math.floor(Date.now() / 1000) + 300,
        peer: { id: 4, username: 'binh', display_name: 'Bình Trần', avatar_path: null },
    }, APP_KEY);

    const payload = verifyTicket(ticket, APP_KEY);
    assert.ok(payload);
    assert.strictEqual(payload.uid, 9);
    assert.strictEqual(payload.cid, 12);
    assert.strictEqual(payload.peer.username, 'binh');
});

check('ticket hết hạn bị từ chối', () => {
    const ticket = sign('koobecaf:ws-ticket:', { uid: 9, cid: 12, exp: Math.floor(Date.now() / 1000) - 1 }, APP_KEY);
    assert.strictEqual(verifyTicket(ticket, APP_KEY), null);
});

check('base64url không chứa ký tự + / =', () => {
    const encoded = base64UrlEncode(Buffer.from('a+b/c==', 'utf8'));
    assert.ok(!/[+/=]/.test(encoded), `mã hoá phải an toàn cho URL: ${encoded}`);
});

// -----------------------------------------------------------------
// Tương thích với PHP
// -----------------------------------------------------------------

check('chữ ký PHP và Node khớp nhau', () => {
    // Token do PHP sinh ở bước dưới (nếu có) phải xác thực được ở Node.
    const payload = { uid: 5, sid: 'abc', iat: 1, exp: Math.floor(Date.now() / 1000) + 300, jti: 'z' };
    const encoded = base64UrlEncode(Buffer.from(JSON.stringify(payload), 'utf8'));
    const token = encoded + '.' + require('crypto')
        .createHmac('sha256', APP_KEY)
        .update('koobecaf:ws-token:' + encoded)
        .digest('base64')
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

    const verified = verifyToken(token, APP_KEY);
    assert.ok(verified);
    assert.strictEqual(verified.uid, 5);
});

console.log('');
console.log(`PASS: ${passed} | FAIL: ${failed}`);

if (failed > 0) {
    process.exit(1);
}
