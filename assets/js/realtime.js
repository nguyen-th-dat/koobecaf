/**
 * Koobecaf — client WebSocket (chat + feed + thông báo realtime).
 *
 * Nối tới server/ws-server.js, xác thực bằng token do PHP cấp rồi nhận:
 *
 *   - Tin nhắn trong cuộc trò chuyện hiện tại (trang /chat/{id}).
 *   - Bài viết mới trong feed (trang bảng tin).
 *   - Cập nhật số liệu like/bình luận/chia sẻ của bài viết đang xem.
 *   - Thông báo mới: badge chưa đọc + danh sách thông báo + toast.
 *
 * Nếu WebSocket không khả dụng, client âm thầm bỏ qua — trang chat sẽ tiếp
 * tục dùng cơ chế polling dự phòng của app.js.
 *
 * Dữ liệu khởi tạo lấy từ <body data-ws-...> do layout main.php render.
 */

(function () {
    'use strict';

    const body = document.body;
    const enabled = body.dataset.wsEnabled === '1';
    const url = body.dataset.wsUrl || '';
    const token = body.dataset.wsToken || '';
    const ticket = body.dataset.wsTicket || '';
    const conversationId = Number(body.dataset.wsConversationId || 0);

    if (!enabled || !url || !token) {
        return;
    }

    /** Trang chat có hợp đồng KBChat; các trang khác chỉ cần feed + thông báo. */
    const chat = conversationId > 0 ? window.KBChat : null;

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
            if (chat) {
                chat.state.realtime = false;
            }
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
        window.__kbFrames = window.__kbFrames || [];
        window.__kbFrames.push(payload.type);
        try {
            dispatchFrame(payload);
        } catch (error) {
            window.__kbErr = String(error && error.stack ? error.stack : error);
        }
    }

    function dispatchFrame(payload) {
        switch (payload.type) {
            case 'auth:ok':
                if (chat) {
                    chat.state.realtime = true;
                    sendFrame({ type: 'subscribe', ticket });
                }
                break;

            case 'subscribe:ok':
                // Đã đăng ký kênh — gửi trạng thái đã đọc cho các tin nhắn còn lại.
                sendFrame({ type: 'read', conversation_id: conversationId });
                break;

            case 'message:new': {
                if (!chat) {
                    break;
                }
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
                if (chat && Number(payload.reader_id) !== window.KB_USER_ID) {
                    chat.markMessagesRead(payload.ids || []);
                }
                break;

            case 'typing':
                showTyping(payload.typing);
                break;

            case 'notification:new':
                handleNotification(payload);
                break;

            case 'feed:post':
                handleNewPost(payload.post);
                break;

            case 'feed:event':
                handlePostEvent(payload);
                break;

            case 'feed:social':
                handleSocialEvent(payload);
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
    // Feed — bài viết mới
    // -----------------------------------------------------------------

    /**
     * Chèn bài viết mới lên đầu feed.
     *
     * Thẻ bài viết được render sẵn từ server (API /api/posts/{id}/card) nên
     * giao diện luôn giống hệt bài viết render bình thường.
     */
    async function handleNewPost(post) {
        window.__kbNew = window.__kbNew || [];
        window.__kbNew.push('enter ' + JSON.stringify(post && post.id) + ' author=' + JSON.stringify(post && post.author));
        if (!post || !post.id) {
            return;
        }

        const feedList = document.querySelector('[data-feed-list]');
        if (!feedList) {
            // Không ở trang feed — chỉ báo nhẹ bằng toast.
            notifyNewPost(post);
            return;
        }

        // Bài của chính mình vừa đăng sẽ xuất hiện sau khi tải lại trang.
        if (Number(post.author && post.author.id) === Number(window.KB_USER_ID)) {
            return;
        }

        if (feedList.querySelector(`[data-post-id="${post.id}"]`)) {
            return;
        }

        const payload = await fetchCard(post.id);
        if (!payload) {
            return;
        }

        const holder = document.createElement('div');
        holder.innerHTML = payload.html.trim();

        const card = holder.firstElementChild;
        if (!card) {
            return;
        }

        card.classList.add('is-realtime-new');
        feedList.insertBefore(card, feedList.firstChild);

        // Feed rỗng (chỉ hiện empty state) → bỏ empty state đi.
        const empty = document.querySelector('[data-feed-empty]');
        if (empty) {
            empty.remove();
        }

        notifyNewPost(post);
    }

    /** Gọi API lấy HTML thẻ bài viết (render sẵn từ server). */
    async function fetchCard(postId) {
        try {
            const response = await fetch(apiUrl(`/api/posts/${postId}/card`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!payload.success) {
                return null;
            }

            return payload.data;
        } catch (error) {
            return null;
        }
    }

    function notifyNewPost(post) {
        const author = post.author || {};
        const name = author.display_name || 'Có người';
        const text = author.username ? `@${author.username} ` : '';

        showToast('info', `${name} ${text}vừa đăng bài viết mới.`);
    }

    // -----------------------------------------------------------------
    // Feed — cập nhật số liệu tương tác
    // -----------------------------------------------------------------

    function handlePostEvent(payload) {
        const postId = Number(payload.post_id) || 0;
        if (!postId) {
            return;
        }

        const stats = payload.stats || {};
        const card = document.querySelector(`.post-card[data-post-id="${postId}"]`);
        if (!card) {
            return;
        }

        updateCounter(card, '[data-like-count]', stats.like_count, 'bi-hand-thumbs-up');
        updateCounter(card, '[data-comment-count]', stats.comment_count, 'bi-chat');
        updateCounter(card, '[data-share-count]', stats.share_count, 'bi-share');

        if (payload.event === 'post.liked' || payload.event === 'post.unliked') {
            const actor = payload.actor || {};
            const mine = Number(actor.id) === Number(window.KB_USER_ID);
            const liked = payload.event === 'post.liked';

            const button = card.querySelector('[data-action="like"]');
            if (button && !mine) {
                button.classList.toggle('active', liked);
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = liked ? 'bi bi-hand-thumbs-up-fill' : 'bi bi-hand-thumbs-up';
                }
            }
        }

        if (payload.event === 'post.deleted') {
            card.remove();
            return;
        }

        if (payload.event === 'post.commented' && payload.comment) {
            appendRealtimeComment(card, payload.comment);
        }
    }

    function updateCounter(card, selector, value, iconClass) {
        if (value === undefined || value === null) {
            return;
        }

        const counter = card.querySelector(selector);
        if (counter) {
            counter.innerHTML = `<i class="bi ${iconClass} me-1"></i>${value}`;
        }
    }

    /** Thêm bình luận mới vào khung bình luận đang mở. */
    function appendRealtimeComment(card, comment) {
        const container = card.querySelector('.post-comments');
        if (!container || container.classList.contains('d-none')) {
            return;
        }

        const list = container.querySelector('[data-comment-list]');
        if (!list || list.querySelector(`[data-comment-id="${comment.id}"]`)) {
            return;
        }

        const placeholder = list.querySelector('p');
        if (placeholder) {
            placeholder.remove();
        }

        if (typeof window.KBBuildCommentNode === 'function') {
            list.appendChild(window.KBBuildCommentNode(comment));
        }
    }

    // -----------------------------------------------------------------
    // Sự kiện mạng xã hội (theo dõi / kết bạn)
    // -----------------------------------------------------------------

    function handleSocialEvent(payload) {
        const actor = payload.actor || {};

        // Không báo cho chính người vừa thao tác.
        if (Number(actor.id) === Number(window.KB_USER_ID)) {
            return;
        }

        const name = actor.display_name || 'Ai đó';

        const messages = {
            follow: `${name} đã bắt đầu theo dõi bạn.`,
            unfollow: `${name} đã ngừng theo dõi bạn.`,
            'friend.requested': `${name} đã gửi lời mời kết bạn.`,
            'friend.accepted': `${name} đã chấp nhận lời mời kết bạn.`,
        };

        const message = messages[payload.event];
        if (message) {
            showToast('info', message);
        }
    }

    // -----------------------------------------------------------------
    // Thông báo
    // -----------------------------------------------------------------

    async function handleNotification(payload) {
        // Sự kiện từ chat (tin nhắn mới) không có payload thông báo — chỉ cần
        // làm mới badge, không được đặt badge về 0.
        if (payload.type_name) {
            refreshBadges();

            return;
        }

        const notification = payload.notification || {};
        const unread = Number(payload.unread_count) || 0;

        updateNotificationBadge(unread);
        await appendNotificationItem(notification);

        const actor = notification.actor || {};
        const name = actor.display_name || 'Có người';
        const label = notification.label || 'đã tương tác với bạn';

        showToast('info', `${name} ${label}.`);
    }

    function updateNotificationBadge(unread) {
        document.querySelectorAll('[data-badge="notifications"]').forEach((el) => {
            el.textContent = unread > 99 ? '99+' : unread;
            el.classList.toggle('d-none', unread === 0);
        });
    }

    /** Chèn dòng thông báo mới lên đầu danh sách (nếu đang ở trang thông báo). */
    async function appendNotificationItem(notification) {
        const list = document.querySelector('[data-notification-list]');
        if (!list || !notification.id) {
            return;
        }

        if (list.querySelector(`[data-notification-id="${notification.id}"]`)) {
            return;
        }

        try {
            const response = await fetch(apiUrl(`/api/notifications/${notification.id}/item`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!payload.success) {
                return;
            }

            const holder = document.createElement('div');
            holder.innerHTML = payload.html.trim();
            const item = holder.firstElementChild;
            if (!item) {
                return;
            }

            item.classList.add('is-realtime-new');
            list.insertBefore(item, list.firstChild);

            const empty = document.querySelector('[data-notification-empty]');
            if (empty) {
                empty.remove();
            }
        } catch (error) {
            // Bỏ qua — badge vẫn đã được cập nhật.
        }
    }

    // -----------------------------------------------------------------
    // Tiện ích
    // -----------------------------------------------------------------

    function apiUrl(path) {
        return (body.dataset.baseUrl || '/') + path;
    }

    function showToast(type, message) {
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
        }
    }

    function refreshBadges() {
        if (typeof window.KBRefreshBadges === 'function') {
            window.KBRefreshBadges();
        }
    }

    // -----------------------------------------------------------------
    // Gửi tin nhắn (chỉ có ở trang chat)
    // -----------------------------------------------------------------

    if (chat) {
        window.KBChatSend = function (bodyText) {
            const sent = sendFrame({
                type: 'message',
                conversation_id: conversationId,
                body: bodyText,
            });

            if (!sent) {
                // WebSocket chết giữa lúc gửi — fallback sang HTTP.
                return window.KBChat.sendViaHttp
                    ? window.KBChat.sendViaHttp(bodyText)
                    : false;
            }

            return true;
        };
    }

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
