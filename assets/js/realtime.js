/**
 * Koobecaf — client WebSocket cho chat realtime.
 *
 * Nối tới server/ws-server.js, xác thực bằng token do PHP cấp, subscribe vào
 * cuộc trò chuyện hiện tại và nhận tin nhắn theo thời gian thực.
 *
 * Nếu WebSocket không khả dụng, client âm thầm bỏ qua — app.js sẽ tiếp tục
 * dùng cơ chế polling dự phòng.
 *
 * Dữ liệu khởi tạo lấy từ <body data-ws-...> do view chat/show.php render.
 */

(function () {
    'use strict';

    const body = document.body;
    const enabled = body.dataset.wsEnabled === '1';
    const url = body.dataset.wsUrl || '';
    const token = body.dataset.wsToken || '';
    const ticket = body.dataset.wsTicket || '';
    const conversationId = Number(body.dataset.wsConversationId || 0);

    if (!enabled || !url || !token || !conversationId) {
        return;
    }

    const chat = window.KBChat;
    if (!chat) {
        return;
    }

    /** @type {WebSocket|null} */
    let socket = null;
    let reconnectTimer = null;
    let reconnectDelay = 1000;
    let typingTimer = null;
    let typingSent = false;
    let closedByUser = false;

    const MAX_RECONNECT_DELAY = 15000;

    // -----------------------------------------------------------------
    // Kết nối
    // -----------------------------------------------------------------

    function connect() {
        try {
            socket = new WebSocket(url);
        } catch (error) {
            scheduleReconnect();
            return;
        }

        socket.addEventListener('open', () => {
            reconnectDelay = 1000;
            sendFrame({ type: 'auth', token });
        });

        socket.addEventListener('message', (event) => {
            let payload;
            try {
                payload = JSON.parse(event.data);
            } catch (error) {
                return;
            }
            handleFrame(payload);
        });

        socket.addEventListener('close', () => {
            chat.state.realtime = false;
            if (!closedByUser) {
                scheduleReconnect();
            }
        });

        socket.addEventListener('error', () => {
            // Sự kiện 'close' sẽ xử lý việc kết nối lại.
        });
    }

    function scheduleReconnect() {
        if (reconnectTimer !== null) {
            return;
        }
        reconnectTimer = setTimeout(() => {
            reconnectTimer = null;
            connect();
        }, reconnectDelay);
        reconnectDelay = Math.min(reconnectDelay * 2, MAX_RECONNECT_DELAY);
    }

    function sendFrame(frame) {
        if (!socket || socket.readyState !== WebSocket.OPEN) {
            return false;
        }
        try {
            socket.send(JSON.stringify(frame));
            return true;
        } catch (error) {
            return false;
        }
    }

    // -----------------------------------------------------------------
    // Xử lý frame từ server
    // -----------------------------------------------------------------

    function handleFrame(payload) {
        switch (payload.type) {
            case 'auth:ok':
                chat.state.realtime = true;
                sendFrame({ type: 'subscribe', ticket });
                break;

            case 'subscribe:ok':
                // Đã đăng ký kênh — gửi trạng thái đã đọc cho các tin nhắn còn lại.
                sendFrame({ type: 'read', conversation_id: conversationId });
                break;

            case 'message:new': {
                const message = payload.message || {};
                const isMine = Number(message.sender_id) === window.KB_USER_ID;
                chat.appendMessage({
                    id: message.id,
                    sender_id: message.sender_id,
                    body: message.body,
                    created_at: message.created_at,
                    is_mine: isMine,
                });
                if (!isMine) {
                    sendFrame({ type: 'read', conversation_id: conversationId });
                }
                refreshBadges();
                break;
            }

            case 'message:read':
                if (Number(payload.reader_id) !== window.KB_USER_ID) {
                    chat.markMessagesRead(payload.ids || []);
                }
                break;

            case 'typing':
                showTyping(payload.typing);
                break;

            case 'notification:new':
                refreshBadges();
                break;

            case 'error':
                if (typeof window.showToast === 'function') {
                    window.showToast('error', payload.message || 'Lỗi realtime.');
                }
                break;

            default:
                break;
        }
    }

    // -----------------------------------------------------------------
    // Gửi tin nhắn
    // -----------------------------------------------------------------

    window.KBChatSend = function (body) {
        const sent = sendFrame({
            type: 'message',
            conversation_id: conversationId,
            body,
        });

        if (!sent) {
            // WebSocket chết giữa lúc gửi — fallback sang HTTP.
            return window.KBChat.sendViaHttp
                ? window.KBChat.sendViaHttp(body)
                : false;
        }

        return true;
    };

    // -----------------------------------------------------------------
    // Trạng thái đang gõ
    // -----------------------------------------------------------------

    const typingIndicator = document.getElementById('chatTyping');

    function showTyping(isTyping) {
        if (!typingIndicator) {
            return;
        }
        typingIndicator.classList.toggle('d-none', !isTyping);
    }

    function notifyTyping() {
        const input = document.querySelector('#chatForm input[name="body"]');
        if (!input) {
            return;
        }

        const hasText = input.value.trim().length > 0;
        if (hasText && !typingSent) {
            typingSent = true;
            sendFrame({ type: 'typing', conversation_id: conversationId, typing: true });
        } else if (!hasText && typingSent) {
            typingSent = false;
            sendFrame({ type: 'typing', conversation_id: conversationId, typing: false });
        }

        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            if (!typingSent) {
                return;
            }
            typingSent = false;
            sendFrame({ type: 'typing', conversation_id: conversationId, typing: false });
        }, 2500);
    }

    document.addEventListener('input', (event) => {
        if (event.target && event.target.matches('#chatForm input[name="body"]')) {
            notifyTyping();
        }
    });

    window.addEventListener('beforeunload', () => {
        closedByUser = true;
    });

    connect();
})();
