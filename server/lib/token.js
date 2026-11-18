'use strict';

/**
 * Xác thực token / ticket do PHP (App\Core\WebSocketToken) cấp.
 *
 * Cùng thuật toán với PHP:
 *   token  = base64url(payload) . '.' . base64url(HMAC-SHA256(PURPOSE . payload, APP_KEY))
 *
 * "purpose" tách riêng từng loại token để ticket không thể dùng để đăng nhập
 * và ngược lại.
 */

const crypto = require('crypto');

const PURPOSE_TOKEN = 'koobecaf:ws-token:';
const PURPOSE_TICKET = 'koobecaf:ws-ticket:';

function base64UrlEncode(buffer) {
    return Buffer.from(buffer).toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function base64UrlDecode(value) {
    let padded = value.replace(/-/g, '+').replace(/_/g, '/');
    const remainder = padded.length % 4;
    if (remainder !== 0) {
        padded += '='.repeat(4 - remainder);
    }
    return Buffer.from(padded, 'base64');
}

function sign(purpose, payload, appKey) {
    const encoded = base64UrlEncode(Buffer.from(JSON.stringify(payload), 'utf8'));
    const signature = base64UrlEncode(
        crypto.createHmac('sha256', appKey).update(purpose + encoded).digest()
    );

    return encoded + '.' + signature;
}

function verifySigned(purpose, token, appKey) {
    if (!appKey || typeof token !== 'string' || !token.includes('.')) {
        return null;
    }

    const index = token.lastIndexOf('.');
    const encoded = token.slice(0, index);
    const signature = token.slice(index + 1);

    const expected = base64UrlEncode(
        crypto.createHmac('sha256', appKey).update(purpose + encoded).digest()
    );

    const given = Buffer.from(signature, 'utf8');
    const expectedBuffer = Buffer.from(expected, 'utf8');
    if (given.length !== expectedBuffer.length || !crypto.timingSafeEqual(given, expectedBuffer)) {
        return null;
    }

    let payload;
    try {
        payload = JSON.parse(base64UrlDecode(encoded).toString('utf8'));
    } catch (error) {
        return null;
    }

    if (!payload || typeof payload !== 'object' || typeof payload.exp !== 'number') {
        return null;
    }
    if (payload.exp * 1000 < Date.now()) {
        return null;
    }

    return payload;
}

function verifyToken(token, appKey) {
    const payload = verifySigned(PURPOSE_TOKEN, token, appKey);
    if (!payload) {
        return null;
    }

    return {
        uid: Number(payload.uid) || 0,
        sid: String(payload.sid || ''),
        jti: String(payload.jti || ''),
        exp: Number(payload.exp) || 0,
    };
}

function verifyTicket(ticket, appKey) {
    const payload = verifySigned(PURPOSE_TICKET, ticket, appKey);
    if (!payload) {
        return null;
    }

    return {
        uid: Number(payload.uid) || 0,
        cid: Number(payload.cid) || 0,
        exp: Number(payload.exp) || 0,
        peer: payload.peer && typeof payload.peer === 'object' ? payload.peer : {},
    };
}

module.exports = {
    PURPOSE_TOKEN,
    PURPOSE_TICKET,
    base64UrlEncode,
    base64UrlDecode,
    sign,
    verifySigned,
    verifyToken,
    verifyTicket,
};
