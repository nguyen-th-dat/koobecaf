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
 * Token và ticket đều có thời hạn ngắn. Khi server báo hết hạn, client tự xin
 * cấp lại và thử lại trên chính kết nối đang mở — không phải đóng rồi kết
 * nối lại (điều này trước đây khiến phiên chat kẹt vĩnh viễn sau 5 phút).
 *
 * Dữ liệu khởi tạo lấy từ <body data-ws-...> do layout main.php render.
 */

(function () {
    'use strict';

    const body = document.body;
    const enabled = body.dataset.wsEnabled === '1';
    let url = body.dataset.wsUrl || '';
    let token = body.dataset.wsToken || '';
    let ticket = body.dataset.wsTicket || '';
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
    let localIdCounter = 0;

    /** Tin nhắn đang chờ server xác nhận, theo local_id. */
    const pendingMessages = new Map();

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
            rejectAllPending(new Error('Mất kết nối.'));
            if (!closedByUser) {
                scheduleReconnect();
            }
        });

        socket.addEventListener('error', () => {
            // Sự kiện 'close' sẽ xử lý việc kết nối lại.
        });
    }

    function scheduleReconnect() {
        if (reconnectTimer !== null || closedByUser) {
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
    // Làm mới token / ticket
    // -----------------------------------------------------------------

    /**
     * Xin token đăng nhập mới.
     *
     * Token chỉ sống WS_TOKEN_TTL giây; một tab mở lâu sẽ hết hạn token.
     */
    async function refreshToken() {
        try {
            const response = await fetch(apiUrl('/api/ws/session'), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!payload.success || !payload.data.token) {
                return false;
            }

            token = payload.data.token;

            // Server có thể đã được cấu hình lại cổng/đường dẫn → cập nhật.
            if (payload.data.url) {
                url = payload.data.url;
            }

            return true;
        } catch (error) {
            return false;
        }
    }

    /** Xin ticket của cuộc trò chuyện hiện tại (dùng khi ticket cũ hết hạn). */
    async function refreshTicket() {
        if (conversationId <= 0) {
            return false;
        }

        try {
            const response = await fetch(apiUrl(`/api/ws/ticket/${conversationId}`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!payload.success || !payload.data.ticket) {
                return false;
            }

            ticket = payload.data.ticket;

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
                onAuthOk(payload);
                break;

            case 'subscribe:ok':
                onSubscribeOk(payload);
                break;

            case 'message:new':
                onMessageNew(payload);
                break;

            case 'message:read':
                if (chat && Number(payload.reader_id) !== window.KB_USER_ID) {
                    chat.markMessagesRead(payload.ids || []);
                }
                break;

            case 'typing':
                showTyping(payload.typing);
                break;

            case 'presence':
                onPresence(payload);
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

            case 'pong':
                break;

            case 'error':
                onError(payload);
                break;

            default:
                break;
        }
    }

    function onAuthOk(payload) {
        if (chat) {
            chat.state.realtime = true;

            if (ticket) {
                sendFrame({ type: 'subscribe', ticket });
            }
        }
    }

    function onSubscribeOk(payload) {
        if (!chat) {
            return;
        }

        chat.state.peer = (payload.conversation && payload.conversation.peer) || null;

        // Gửi trạng thái đã đọc cho các tin nhắn còn lại.
        sendFrame({ type: 'read', conversation_id: conversationId });

        if (payload.conversation && payload.conversation.peer_online) {
            markPeerOnline();
        }
    }

    function onMessageNew(payload) {
        const message = payload.message || {};
        const messageConversationId = Number(message.conversation_id) || 0;

        // Tin nhắn của chính mình vừa gửi qua WebSocket → server đã xác nhận.
        if (payload.local_id) {
            resolvePending(payload.local_id, message);
        }

        // Tin nhắn của cuộc trò chuyện khác → không thêm vào khung chat.
        // Thông báo (toast + badge) do sự kiện notification:new đảm nhiệm.
        if (messageConversationId !== conversationId) {
            refreshBadges();
            return;
        }

        if (!chat) {
            return;
        }

        const isMine = Number(message.sender_id) === window.KB_USER_ID;
        chat.appendMessage({
            id: message.id,
            sender_id: message.sender_id,
            body: message.body,
            created_at: message.created_at,
            is_mine: isMine,
            avatar_url: isMine ? '' : peerAvatarUrl(),
        });

        if (!isMine) {
            sendFrame({ type: 'read', conversation_id: conversationId });
            markPeerOnline();
        }

        refreshBadges();
    }

    /**
     * Xử lý lỗi từ server.
     *
     * Lỗi không chí mạng (fatal = false) giữ nguyên kết nối: client tự xin
     * token/ticket mới rồi thử lại.
     */
    function onError(payload) {
        if (payload.reason === 'token_expired') {
            refreshToken().then((ok) => {
                if (!ok) {
                    return;
                }
                if (!sendFrame({ type: 'auth', token })) {
                    // Socket đã động trong lúc chờ token mới → kết nối lại.
                    scheduleReconnect();
                }
            });

            return;
        }

        if (payload.reason === 'ticket_expired') {
            refreshTicket().then((ok) => {
                if (!ok) {
                    return;
                }
                if (!sendFrame({ type: 'subscribe', ticket })) {
                    scheduleReconnect();
                }
            });

            return;
        }

        if (payload.local_id) {
            rejectPending(payload.local_id, new Error(payload.message || 'Không gửi được tin nhắn.'));
        }

        if (typeof window.showToast === 'function') {
            window.showToast('error', payload.message || 'Lỗi realtime.');
        }
    }

    // -----------------------------------------------------------------
    // Tin nhắn đang chờ xác nhận
    // -----------------------------------------------------------------

    function resolvePending(localId, message) {
        const pending = pendingMessages.get(localId);
        if (!pending) {
            return;
        }
        pendingMessages.delete(localId);
        pending.resolve(message);
    }

    function rejectPending(localId, error) {
        const pending = pendingMessages.get(localId);
        if (!pending) {
            return;
        }
        pendingMessages.delete(localId);
        pending.reject(error);
    }

    function rejectAllPending(error) {
        for (const pending of pendingMessages.values()) {
            pending.reject(error);
        }
        pendingMessages.clear();
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
        const card = document.querySelector(`.post-card[data-post-id="${postId}"], .post-detail[data-post-id="${postId}"]`);
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
        if (!counter) {
            return;
        }

        // Thẻ bài viết có sẵn biểu tượng trong số liệu, trang chi tiết thì không.
        if (counter.querySelector('i')) {
            counter.innerHTML = `<i class="bi ${iconClass} me-1"></i>${value}`;
        } else {
            counter.textContent = value;
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
        const notification = payload.notification || null;

        // Thông báo từ chat (tin nhắn mới) không kèm payload đầy đủ — chỉ cần
        // làm mới badge, không được đặt badge về 0.
        if (!notification) {
            refreshBadges();

            return;
        }

        if (payload.unread_count !== undefined && payload.unread_count !== null) {
            updateNotificationBadge(Number(payload.unread_count) || 0);
        } else {
            refreshBadges();
        }

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
        // baseUrl có thể là chuỗi rỗng khi ứng dụng nằm ở thư mục gốc —
        // dùng ?? để không biến '' thành '/' (sẽ tạo URL //api/... sai).
        const base = document.body.dataset.baseUrl ?? '/';

        return base + path;
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
        /**
         * Gửi tin nhắn qua WebSocket.
         *
         * Trả về Promise resolve khi server xác nhận đã lưu tin nhắn, reject
         * khi server từ chối — app.js dùng promise này để fallback sang HTTP.
         *
         * @returns {Promise<object>}
         */
        window.KBChatSend = function (bodyText) {
            return new Promise((resolve, reject) => {
                const localId = `kb-${Date.now()}-${++localIdCounter}`;

                if (!sendFrame({
                    type: 'message',
                    conversation_id: conversationId,
                    body: bodyText,
                    local_id: localId,
                })) {
                    reject(new Error('WebSocket chưa sẵn sàng.'));

                    return;
                }

                pendingMessages.set(localId, { resolve, reject });

                // Chặn an toàn: server im lặng thì báo lỗi để app.js fallback.
                setTimeout(() => {
                    rejectPending(localId, new Error('Gửi tin nhắn quá thời gian chờ.'));
                }, 10000);
            });
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

    /**
     * Username của người đối diện.
     *
     * Lưu lại ngay khi script khởi động vì sau đó nhãn sẽ bị thay bằng
     * "Đang online" / "Ngoại tuyến".
     */
    const peerHandle = (() => {
        const text = document.querySelector('[data-chat-peer-status-text]');
        const handle = text ? text.textContent.trim() : '';
        if (text && handle) {
            text.dataset.kbHandle = handle;
        }

        return handle;
    })();

    /** Ảnh đại diện của người đối diện (dùng cho tin nhắn realtime). */
    function peerAvatarUrl() {
        const img = document.querySelector('.chat-header-peer img.avatar');
        return img ? img.getAttribute('src') || '' : '';
    }

    /** Đánh dấu người đối diện đang online khi có tin nhắn mới. */
    function markPeerOnline() {
        const dot = document.querySelector('[data-chat-peer-status]');
        if (dot) {
            dot.classList.add('online');
            dot.classList.remove('offline');
            dot.title = 'Đang online';
        }

        const text = document.querySelector('[data-chat-peer-status-text]');
        if (text) {
            text.textContent = 'Đang online';
            text.classList.add('is-online');
            text.title = peerHandle;
        }
    }

    /** Đánh dấu người đối diện đã offline. */
    function markPeerOffline() {
        const dot = document.querySelector('[data-chat-peer-status]');
        if (dot) {
            dot.classList.remove('online');
            dot.classList.add('offline');
            dot.title = 'Ngoại tuyến';
        }

        const text = document.querySelector('[data-chat-peer-status-text]');
        if (text) {
            text.textContent = peerHandle;
            text.classList.remove('is-online');
            text.title = peerHandle;
        }
    }

    /** Cập nhật trạng thái online/offline của người đối diện. */
    function onPresence(payload) {
        if (Number(payload.conversation_id) !== conversationId) {
            return;
        }
        if (Number(payload.user_id) === Number(window.KB_USER_ID)) {
            return;
        }

        if (payload.online) {
            markPeerOnline();
        } else {
            markPeerOffline();
        }
    }

    function notifyTyping() {
        const input = document.querySelector('#chatForm textarea[name="body"], #chatForm input[name="body"]');
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
        if (event.target && event.target.matches('#chatForm textarea[name="body"], #chatForm input[name="body"]')) {
            notifyTyping();
        }
    });

    window.addEventListener('beforeunload', () => {
        closedByUser = true;
    });

    connect();
})();
