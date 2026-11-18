/**
 * Koobecaf - JavaScript giao diện và tương tác AJAX.
 * Không phụ thuộc framework: menu, drawer, toast và alert được viết tay.
 */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const baseUrl = document.body.dataset.baseUrl ?? '/';
window.KB_USER_ID = Number(document.body.dataset.userId || 0);

function apiUrl(path) {
    return baseUrl + path;
}

async function request(url, options = {}) {
    const config = Object.assign({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
    }, options);

    if (config.body && !(config.body instanceof FormData)) {
        config.headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, config);
    let payload;
    try {
        payload = await response.json();
    } catch (e) {
        payload = { success: false, message: 'Phản hồi không hợp lệ.' };
    }
    return payload;
}

/* =============================================================
   Thông báo nhanh (toast)

   Hệ thống toast nằm trong assets/js/toast.js — hàm showToast(type, message),
   được nạp trước file này nên luôn sẵn sàng dùng.

   Toàn bộ thông báo từ PHP (flash message) cũng được đẩy vào cùng hàng đợi
   nên giao diện luôn đồng nhất.
   ============================================================= */

/* =============================================================
   Menu thả xuống
   ============================================================= */
const openMenus = [];

function positionMenu(menu, toggle, placement) {
    const rect = toggle.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    const gap = 6;
    const margin = 12;

    let top = rect.bottom + gap;
    let left = placement === 'bottom-end' ? rect.right - menuRect.width : rect.left;

    // Giữ menu nằm trong khung nhìn
    if (top + menuRect.height > window.innerHeight - margin) {
        const above = rect.top - gap - menuRect.height;
        top = above >= margin ? above : Math.max(margin, window.innerHeight - menuRect.height - margin);
    }
    left = Math.min(Math.max(margin, left), window.innerWidth - menuRect.width - margin);

    menu.style.top = `${Math.round(top)}px`;
    menu.style.left = `${Math.round(left)}px`;
}

function openMenu(menu, toggle, placement) {
    closeMenus();
    menu.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    // Ẩn tạm thời để tránh nháy menu ở góc trước khi định vị
    menu.style.visibility = 'hidden';
    positionMenu(menu, toggle, placement);
    menu.style.visibility = '';
    openMenus.push({ menu, toggle, placement });
}

function closeMenus() {
    openMenus.splice(0).forEach(({ menu, toggle }) => {
        menu.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    });
}

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-menu-toggle]');
    if (toggle) {
        const target = toggle.dataset.menuTarget;
        const menu = target
            ? document.querySelector(target)
            : toggle.parentElement.querySelector('.menu');
        if (!menu) return;
        event.preventDefault();
        event.stopPropagation();
        if (menu.classList.contains('is-open')) {
            closeMenus();
        } else {
            openMenu(menu, toggle, toggle.dataset.menuPlacement || 'bottom-start');
        }
        return;
    }

    if (!event.target.closest('.menu')) {
        closeMenus();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeMenus();
    }
});

window.addEventListener('resize', () => {
    openMenus.forEach(({ menu, toggle, placement }) => positionMenu(menu, toggle, placement));
});

/* =============================================================
   Ngăn kéo (drawer)
   ============================================================= */
const drawerBackdrop = (() => {
    const el = document.createElement('div');
    el.className = 'drawer-backdrop';
    document.body.appendChild(el);
    return el;
})();

let openDrawer = null;

function showDrawer(drawer) {
    if (!drawer) return;
    if (openDrawer && openDrawer !== drawer) {
        hideDrawer();
    }
    drawer.classList.add('is-open');
    drawerBackdrop.classList.add('is-open');
    document.body.classList.add('has-drawer');
    openDrawer = drawer;
}

function hideDrawer() {
    if (!openDrawer) return;
    openDrawer.classList.remove('is-open');
    drawerBackdrop.classList.remove('is-open');
    document.body.classList.remove('has-drawer');
    openDrawer = null;
}

drawerBackdrop.addEventListener('click', hideDrawer);

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-drawer-toggle]');
    if (toggle) {
        event.preventDefault();
        showDrawer(document.querySelector(toggle.dataset.drawerToggle));
        return;
    }

    const dismiss = event.target.closest('[data-drawer-dismiss]');
    if (dismiss) {
        event.preventDefault();
        hideDrawer();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        hideDrawer();
    }
});

/* =============================================================
   Kẹp nội dung bài viết dài kèm nút "Xem thêm"
   ============================================================= */
const CLAMP_LINES = 5;

function isOverflowing(el) {
    return el.scrollHeight > el.clientHeight + 1;
}

/**
 * Kẹp nội dung dài ở CLAMP_LINES dòng và chèn nút "Xem thêm" nếu bị cắt.
 *
 * Phải đo trong lúc đang áp dụng clamp: khi bỏ clamp, chiều cao phần tử bằng
 * chiều cao nội dung nên scrollHeight luôn bằng clientHeight.
 */
function clampPostText(el) {
    el.classList.add('is-clamped');

    if (!isOverflowing(el)) {
        el.classList.remove('is-clamped');
        return;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'post-expand';
    button.textContent = 'Xem thêm';

    button.addEventListener('click', () => {
        const collapsed = el.classList.contains('is-clamped');
        el.classList.toggle('is-clamped', !collapsed);
        button.textContent = collapsed ? 'Xem thêm' : 'Thu gọn';
    });

    el.insertAdjacentElement('afterend', button);
}

document.querySelectorAll('[data-clampable]').forEach(el => {
    el.dataset.clampReady = '1';
    clampPostText(el);
});

// Áp lại sau khi phông chữ tải xong (chiều cao dòng có thể đổi)
window.addEventListener('load', () => {
    document.querySelectorAll('[data-clampable]').forEach(el => {
        if (el.dataset.clampReady === '1') return;
        el.dataset.clampReady = '1';
        clampPostText(el);
    });
});

/* =============================================================
   Thanh trên cùng: đổ bóng khi cuộn trang
   ============================================================= */
const topbar = document.querySelector('.topbar');
if (topbar) {
    const updateTopbar = () => topbar.classList.toggle('is-scrolled', window.scrollY > 4);
    updateTopbar();
    window.addEventListener('scroll', updateTopbar, { passive: true });
}

/* =============================================================
   Xác nhận trước khi xóa
   ============================================================= */
document.addEventListener('submit', (event) => {
    const form = event.target;
    const message = form.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

/* =============================================================
   Hiện/ẩn mật khẩu
   ============================================================= */
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-toggle-password]');
    if (!button) return;
    const input = document.querySelector(button.dataset.togglePassword);
    if (!input) return;
    const type = input.type === 'password' ? 'text' : 'password';
    input.type = type;
    button.innerHTML = type === 'password'
        ? '<i class="bi bi-eye"></i>'
        : '<i class="bi bi-eye-slash"></i>';
});

/* =============================================================
   Chọn giao diện
   ============================================================= */
document.addEventListener('click', (event) => {
    const button = event.target.closest('.theme-btn');
    if (!button) return;
    const theme = button.dataset.theme;
    document.documentElement.setAttribute('data-theme', theme);
    const input = document.getElementById('themeInput');
    if (input) input.value = theme;
    document.querySelectorAll('.theme-btn').forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');
    fetch(apiUrl('/settings/theme'), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
        body: JSON.stringify({ theme: theme }),
        credentials: 'same-origin',
    }).then((response) => {
        if (!response.ok) {
            showToast('error', 'Không thể lưu giao diện.');
            return;
        }
        showToast('success', theme === 'dark' ? 'Đã chuyển sang giao diện tối.' : 'Đã chuyển sang giao diện sáng.');
    }).catch(() => {
        showToast('error', 'Không thể lưu giao diện.');
    });
});

/* =============================================================
   Xem trước ảnh trước khi đăng
   ============================================================= */
const imageInput = document.getElementById('postImages');
const previewBox = document.getElementById('imagePreview');
if (imageInput && previewBox) {
    let selectedFiles = [];

    const syncInputFiles = () => {
        const transfer = new DataTransfer();
        selectedFiles.forEach(file => transfer.items.add(file));
        imageInput.files = transfer.files;
    };

    const renderPreview = () => {
        previewBox.innerHTML = '';
        previewBox.classList.toggle('d-none', selectedFiles.length === 0);
        selectedFiles.forEach((file, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'position-relative preview-item';

            const objectUrl = URL.createObjectURL(file);
            const img = document.createElement('img');
            img.src = objectUrl;
            img.alt = '';
            img.className = 'rounded border';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'preview-remove';
            removeBtn.setAttribute('aria-label', 'Xóa ảnh');
            removeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
            removeBtn.addEventListener('click', () => {
                URL.revokeObjectURL(objectUrl);
                selectedFiles.splice(index, 1);
                syncInputFiles();
                renderPreview();
            });

            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);
            previewBox.appendChild(wrapper);
        });
    };

    imageInput.addEventListener('change', () => {
        selectedFiles = Array.from(imageInput.files);
        renderPreview();
    });
}

/* =============================================================
   Like / Share / Copy link
   ============================================================= */
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-action]');
    if (!button) return;

    const action = button.dataset.action;
    const postId = button.dataset.postId;

    if (action === 'like') {
        const payload = await request(apiUrl(`/posts/${postId}/like`), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        });
        if (!payload.success) {
            showToast('error', payload.message ?? 'Không thể thực hiện.');
            return;
        }
        const card = button.closest('.post-card');
        button.classList.toggle('active', payload.data.liked);
        const icon = button.querySelector('i');
        if (icon) icon.className = payload.data.liked ? 'bi bi-hand-thumbs-up-fill' : 'bi bi-hand-thumbs-up';
        const counter = card?.querySelector('[data-like-count]');
        if (counter) counter.innerHTML = `<i class="bi bi-hand-thumbs-up me-1"></i>${payload.data.like_count}`;
        showToast('success', payload.data.liked ? 'Đã thích bài viết.' : 'Đã bỏ thích bài viết.');
    }

    if (action === 'share') {
        const content = window.prompt('Chia sẻ bài viết này (không bắt buộc):', '');
        if (content === null) return;
        const payload = await request(apiUrl(`/posts/${postId}/share`), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ content: content, privacy: 'public' }),
        });
        if (!payload.success) {
            showToast('error', payload.message ?? 'Không thể chia sẻ.');
            return;
        }
        const card = button.closest('.post-card');
        const counter = card?.querySelector('[data-share-count]');
        if (counter) counter.innerHTML = `<i class="bi bi-share me-1"></i>${payload.data.share_count}`;
        showToast('success', 'Đã chia sẻ bài viết lên trang cá nhân của bạn.');
    }

    if (action === 'copy-link') {
        const link = new URL(button.dataset.url, window.location.origin).href;
        try {
            await navigator.clipboard.writeText(link);
            showToast('success', 'Đã sao chép liên kết bài viết.');
        } catch (e) {
            window.prompt('Sao chép liên kết:', link);
        }
    }

    if (action === 'comment') {
        const card = button.closest('.post-card');
        const box = card?.querySelector('.post-comments');
        if (!box) return;
        const isHidden = box.classList.contains('d-none');
        box.classList.toggle('d-none', !isHidden);
        if (isHidden) {
            await loadComments(postId, box);
            box.querySelector('input[name="content"]')?.focus();
        }
    }
});

/* =============================================================
   Điều hướng tới ô soạn bài viết
   ============================================================= */
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href$="#composer"]');
    if (!link) return;

    event.preventDefault();
    const composer = document.getElementById('composer');
    if (!composer) return;

    composer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    document.getElementById('postContent')?.focus();
});

/* =============================================================
   Follow
   ============================================================= */
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-action="follow"], .follow-btn');
    if (!button) return;

    const userId = button.dataset.userId;
    const payload = await request(apiUrl(`/follow/${userId}`), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    });
    if (!payload.success) {
        showToast('error', payload.message ?? 'Không thể theo dõi.');
        return;
    }

    const following = payload.data.following;
    if (button.classList.contains('follow-btn')) {
        button.textContent = following ? 'Đang theo dõi' : 'Theo dõi';
        button.classList.toggle('is-following', following);
    } else {
        const icon = button.querySelector('i');
        const label = button.querySelector('span');
        if (icon) icon.className = following ? 'bi bi-person-check' : 'bi bi-person-plus';
        if (label) label.textContent = following ? 'Đang theo dõi' : 'Theo dõi';
        button.classList.toggle('is-following', following);
    }

    showToast('success', following ? 'Đã theo dõi.' : 'Đã bỏ theo dõi.');
});

/* =============================================================
   Bình luận
   ============================================================= */
async function loadComments(postId, container) {
    const list = container.querySelector('[data-comment-list]');
    if (!list) return;
    const payload = await request(apiUrl(`/api/posts/${postId}/comments`));
    if (!payload.success) return;
    list.innerHTML = '';
    if (!payload.data.comments.length) {
        list.innerHTML = '<p class="fg-secondary small mb-0">Chưa có bình luận nào.</p>';
        return;
    }
    payload.data.comments.forEach(comment => list.appendChild(buildCommentNode(comment)));
}

function buildCommentNode(comment) {
    const node = document.createElement('div');
    node.className = 'comment-item d-flex gap-2 py-2';
    node.dataset.commentId = comment.id;

    const avatar = comment.author.avatar_url || apiUrl('/uploads/avatars/default-avatar.png');
    const isMine = comment.author.id === (window.KB_USER_ID || 0);

    node.innerHTML = `
        <a href="${comment.author.profile_url}"><img src="${avatar}" alt="" class="avatar avatar-sm"></a>
        <div class="flex-grow-1 min-w-0">
            <div class="comment-bubble">
                <a class="fw-semibold fg-body-emphasis text-decoration-none" href="${comment.author.profile_url}">${escapeHtml(comment.author.display_name)}</a>
                <p class="mb-0 comment-text">${escapeHtml(comment.content).replace(/\n/g, '<br>')}</p>
            </div>
            <div class="small fg-secondary mt-1 d-flex align-items-center gap-2">
                <span>${timeAgo(comment.created_at)}</span>
                <button type="button" class="btn btn-text btn-sm p-0 fg-secondary comment-reply-btn" data-comment-id="${comment.id}">Trả lời</button>
                ${isMine ? `<button type="button" class="btn btn-text btn-sm p-0 fg-secondary comment-edit-btn" data-comment-id="${comment.id}">Sửa</button>
                <button type="button" class="btn btn-text btn-sm p-0 fg-danger comment-delete-btn" data-comment-id="${comment.id}">Xóa</button>` : ''}
            </div>
            ${comment.replies.length ? '<div class="comment-replies mt-2"></div>' : ''}
        </div>`;

    const repliesBox = node.querySelector('.comment-replies');
    if (repliesBox) {
        comment.replies.forEach(reply => repliesBox.appendChild(buildCommentNode(reply)));
    }
    return node;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function timeAgo(value) {
    if (!value) return '';
    const diff = Math.floor((Date.now() - new Date(value.replace(' ', 'T')).getTime()) / 1000);
    if (diff < 60) return 'vừa xong';
    if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
    if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
    if (diff < 2592000) return Math.floor(diff / 86400) + ' ngày trước';
    return Math.floor(diff / 2592000) + ' tháng trước';
}

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.classList.contains('comment-form')) return;
    event.preventDefault();

    const postId = form.dataset.postId;
    const input = form.querySelector('input[name="content"], textarea[name="content"]');
    const parentInput = form.querySelector('input[name="parent_id"]');
    const content = input.value.trim();
    if (!content) return;

    const payload = await request(apiUrl(`/posts/${postId}/comment`), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({
            content: content,
            parent_id: parentInput && parentInput.value ? Number(parentInput.value) : 0,
        }),
    });

    if (!payload.success) {
        showToast('error', payload.message ?? 'Không thể gửi bình luận.');
        return;
    }

    const isReply = Boolean(parentInput && parentInput.value);

    input.value = '';
    if (parentInput) parentInput.value = '';
    clearFormDraft(form);
    const container = form.closest('.post-comments');
    if (container) {
        await loadComments(postId, container);
        container.classList.remove('d-none');
    }
    const card = form.closest('.post-card');
    const counter = card?.querySelector('[data-comment-count]');
    if (counter) counter.innerHTML = `<i class="bi bi-chat me-1"></i>${payload.data.comment_count}`;
    showToast('success', isReply ? 'Đã trả lời bình luận.' : 'Đã gửi bình luận.');
});

document.addEventListener('click', async (event) => {
    const replyBtn = event.target.closest('.comment-reply-btn');
    if (replyBtn) {
        const card = replyBtn.closest('.post-card');
        const form = card?.querySelector('.comment-form');
        if (!form) return;
        const parentInput = form.querySelector('input[name="parent_id"]');
        if (parentInput) parentInput.value = replyBtn.dataset.commentId;
        const input = form.querySelector('input[name="content"], textarea[name="content"]');
        if (input) {
            input.focus();
            input.placeholder = 'Trả lời bình luận...';
        }
        return;
    }

    const editBtn = event.target.closest('.comment-edit-btn');
    if (editBtn) {
        const item = editBtn.closest('.comment-item');
        const textNode = item.querySelector('[data-comment-text]');
        if (!textNode) return;
        const original = textNode.innerHTML;
        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex gap-2 mt-2';
        wrapper.innerHTML = `
            <input type="text" class="form-control form-control-sm" value="${(textNode.textContent || '').replace(/"/g, '&quot;')}">
            <button class="btn btn-sm btn-solid" type="button">Lưu</button>
            <button class="btn btn-sm btn-outline" type="button">Hủy</button>`;
        textNode.replaceWith(wrapper);

        wrapper.querySelector('button:nth-of-type(2)').addEventListener('click', () => {
            wrapper.replaceWith(textNode);
            textNode.innerHTML = original;
        });
        wrapper.querySelector('button:nth-of-type(1)').addEventListener('click', async () => {
            const value = wrapper.querySelector('input').value.trim();
            if (!value) return;
            const payload = await request(apiUrl(`/comments/${editBtn.dataset.commentId}/update`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ content: value }),
            });
            if (!payload.success) {
                showToast('error', payload.message ?? 'Không thể sửa bình luận.');
                return;
            }
            textNode.innerHTML = escapeHtml(payload.data.comment.content).replace(/\n/g, '<br>');
            wrapper.replaceWith(textNode);
            showToast('success', 'Đã cập nhật bình luận.');
        });
        return;
    }

    const deleteBtn = event.target.closest('.comment-delete-btn');
    if (deleteBtn) {
        if (!window.confirm('Bạn có chắc muốn xóa bình luận này?')) return;
        const payload = await request(apiUrl(`/comments/${deleteBtn.dataset.commentId}/delete`), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        });
        if (!payload.success) {
            showToast('error', payload.message ?? 'Không thể xóa bình luận.');
            return;
        }
        const card = deleteBtn.closest('.post-card');
        const counter = card?.querySelector('[data-comment-count]');
        if (counter) counter.innerHTML = `<i class="bi bi-chat me-1"></i>${payload.data.comment_count}`;
        const postId = card?.dataset.postId;
        const container = card?.querySelector('.post-comments');
        if (postId && container) await loadComments(postId, container);
        showToast('success', 'Đã xóa bình luận.');
    }
});

/* =============================================================
   Chat — gửi tin nhắn + nền tảng realtime

   `window.KBChat` là hợp đồng giữa app.js và assets/js/realtime.js:
   - realtime.js dùng KBChat.appendMessage() để đẩy tin nhắn mới vào DOM
   - realtime.js dùng KBChat.send() để gửi tin nhắn qua WebSocket
   - realtime.js đặt KBChat.realtime = true khi kết nối thành công để tắt polling
   ============================================================= */
const chatMessages = document.querySelector('.chat-messages');
const chatForm = document.getElementById('chatForm');

if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

if (chatForm && chatMessages) {
    const conversationId = Number(chatForm.dataset.conversationId);
    const chatState = {
        conversationId,
        lastMessageId: Number(chatMessages.querySelector('[data-message-id]')?.dataset.messageId ?? 0),
        realtime: false,
        sending: false,
    };

    function formatMessageTime(value) {
        const time = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(time.getTime())
            ? ''
            : time.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }

    function appendMessage(message) {
        const id = Number(message.id) || 0;
        if (id && id <= chatState.lastMessageId) return;
        if (id) chatState.lastMessageId = id;

        const isMine = message.is_mine !== undefined
            ? Boolean(message.is_mine)
            : Number(message.sender_id) === window.KB_USER_ID;

        const row = document.createElement('div');
        row.className = 'chat-bubble-row' + (isMine ? ' mine' : '');
        row.dataset.messageId = id;
        row.innerHTML = `
            <div class="chat-bubble">
                <div class="chat-text">${escapeHtml(message.body).replace(/\n/g, '<br>')}</div>
                <small class="chat-time">${formatMessageTime(message.created_at)}${
                    isMine ? ' <i class="bi bi-check2" data-read-mark="1"></i>' : ''
                }</small>
            </div>`;
        chatMessages.appendChild(row);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    /** Đánh dấu tin nhắn của mình đã được người kia đọc. */
    function markMessagesRead(ids) {
        const list = Array.isArray(ids) ? ids : [ids];
        list.forEach((id) => {
            const row = chatMessages.querySelector(`[data-message-id="${id}"]`);
            const icon = row?.querySelector('[data-read-mark]');
            if (icon) {
                icon.classList.remove('bi-check2');
                icon.classList.add('bi-check2-all', 'fg-primary');
                icon.title = 'Đã xem';
            }
        });
    }

    /** Gửi tin nhắn qua HTTP (fallback khi WebSocket không khả dụng). */
    async function sendViaHttp(body) {
        const payload = await request(apiUrl(`/chat/${conversationId}/send`), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ body }),
        });

        if (!payload.success) {
            throw new Error(payload.message ?? 'Không thể gửi tin nhắn.');
        }

        appendMessage(payload.data);
    }

    chatForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = chatForm.querySelector('input[name="body"]');
        const body = input.value.trim();
        if (!body || chatState.sending) return;

        chatState.sending = true;
        try {
            // Ưu tiên WebSocket, chỉ dùng HTTP khi chưa kết nối được.
            if (chatState.realtime && typeof window.KBChatSend === 'function') {
                window.KBChatSend(body);
            } else {
                await sendViaHttp(body);
            }

            input.value = '';
            clearFormDraft(chatForm);
            showToast('success', 'Đã gửi tin nhắn.');
        } catch (error) {
            showToast('error', error.message || 'Không thể gửi tin nhắn.');
        } finally {
            chatState.sending = false;
        }
    });

    window.KBChat = {
        state: chatState,
        conversationId,
        appendMessage,
        markMessagesRead,
        formatMessageTime,
        sendViaHttp,
        chatMessages,
    };

    // Polling dự phòng: chỉ chạy khi WebSocket chưa kết nối thành công.
    setInterval(async () => {
        if (chatState.realtime) return;

        const payload = await request(apiUrl(`/chat/${conversationId}/messages?after_id=${chatState.lastMessageId}`));
        if (!payload.success) return;
        payload.data.messages.forEach(appendMessage);
        refreshBadges();
    }, 5000);
}

/* =============================================================
   Badge cập nhật
   ============================================================= */
const badgeSources = {
    messages: '/api/messages/unread',
    notifications: '/api/notifications/unread',
};

async function refreshBadges() {
    for (const [key, endpoint] of Object.entries(badgeSources)) {
        const payload = await request(apiUrl(endpoint));
        if (!payload.success) continue;
        const value = payload.data.unread ?? 0;
        document.querySelectorAll(`[data-badge="${key}"]`).forEach(el => {
            el.textContent = value > 99 ? '99+' : value;
            el.classList.toggle('d-none', value === 0);
        });
    }
}

if (Object.keys(badgeSources).length && document.querySelector('[data-badge]')) {
    setInterval(refreshBadges, 15000);
}

/* =============================================================
   Tìm kiếm AJAX
   ============================================================= */
const searchInput = document.querySelector('.search-form input[name="q"]');
if (searchInput) {
    let searchTimer = null;
    const searchResults = document.createElement('div');
    searchResults.className = 'search-results card surface-card d-none';
    searchInput.parentElement.appendChild(searchResults);

    const renderResults = (users) => {
        if (!users.length) {
            searchResults.classList.add('d-none');
            searchResults.innerHTML = '';
            return;
        }
        searchResults.innerHTML = '<ul class="list-unified">' + users.map(user => `
            <li class="person-row">
                <img src="${user.avatar_url || apiUrl('/uploads/avatars/default-avatar.png')}" alt="" class="avatar avatar-sm">
                <div class="person-meta">
                    <a class="person-name" href="${user.profile_url}">${escapeHtml(user.display_name)}</a>
                    <span class="person-handle">@${escapeHtml(user.username)}</span>
                </div>
            </li>`).join('') + '</ul>';
        searchResults.classList.remove('d-none');
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const keyword = searchInput.value.trim();
        if (keyword.length < 2) {
            searchResults.classList.add('d-none');
            return;
        }
        searchTimer = setTimeout(async () => {
            const payload = await request(apiUrl('/api/search/users?q=' + encodeURIComponent(keyword)));
            if (payload.success) renderResults(payload.data.users);
        }, 300);
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.search-form')) {
            searchResults.classList.add('d-none');
        }
    });
}

/* =============================================================
   Tìm kiếm người dùng để bắt đầu chat mới
   ============================================================= */
const chatSearchInput = document.getElementById('chatUserSearch');
const chatSearchResults = document.getElementById('chatSearchResults');

if (chatSearchInput && chatSearchResults) {
    let chatSearchTimer = null;

    chatSearchInput.addEventListener('input', () => {
        clearTimeout(chatSearchTimer);
        const keyword = chatSearchInput.value.trim();
        if (keyword.length < 2) {
            chatSearchResults.classList.add('d-none');
            chatSearchResults.innerHTML = '';
            return;
        }
        chatSearchTimer = setTimeout(async () => {
            const payload = await request(apiUrl('/api/search/users?q=' + encodeURIComponent(keyword)));
            if (!payload.success) return;
            const users = payload.data.users ?? [];
            if (!users.length) {
                chatSearchResults.classList.add('d-none');
                chatSearchResults.innerHTML = '';
                return;
            }
            chatSearchResults.innerHTML = '<ul class="list-unified">' + users.map(user => `
                <li class="person-row">
                    <a class="d-flex align-items-center gap-2 text-decoration-none" href="${user.chat_url}">
                        <img src="${user.avatar_url || apiUrl('/uploads/avatars/default-avatar.png')}" alt="" class="avatar avatar-sm">
                        <div class="person-meta">
                            <span class="person-name">${escapeHtml(user.display_name)}</span>
                            <span class="person-handle">@${escapeHtml(user.username)}</span>
                        </div>
                    </a>
                </li>`).join('') + '</ul>';
            chatSearchResults.classList.remove('d-none');
        }, 300);
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('#chatSearchWrap')) {
            chatSearchResults.classList.add('d-none');
        }
    });
}

/* =============================================================
   Đánh dấu thông báo đã đọc
   ============================================================= */
const markAllBtn = document.getElementById('markAllRead');
if (markAllBtn) {
    markAllBtn.addEventListener('click', async () => {
        const payload = await request(apiUrl('/notifications/read-all'), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        });
        if (!payload.success) return;
        document.querySelectorAll('.notification-item.unread').forEach(el => el.classList.remove('unread'));
        markAllBtn.remove();
        showToast('success', 'Đã đánh dấu tất cả thông báo đã đọc.');
    });
}

document.querySelectorAll('.notification-item').forEach(item => {
    item.querySelector('a')?.addEventListener('click', () => {
        request(apiUrl(`/notifications/${item.dataset.notificationId}/read`), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        });
    });
});

/* =============================================================
   Cảnh báo rời trang khi còn nội dung chưa gửi
   ------------------------------------------------------------
   Không phải trang nào cũng cần cảnh báo. Nguyên tắc:

   - Nội dung ĐÃ được lưu nháp (localStorage) → không mất khi rời trang,
     chỉ hiện chỉ báo nhẹ "đã lưu tự động".
   - Nội dung KHÔNG lưu nháp được (mật khẩu, ảnh đã chọn...) →
     thực sự mất, nên hiện hộp thoại liệt kê cụ thể những gì sẽ mất.

   Mỗi form khai báo:
     data-draft="<key>"    → nội dung được lưu nháp, khôi phục lại khi tải trang
     data-leave="keep"     → rời trang vẫn giữ nháp (nội dung dài, đáng giữ)
     data-leave="clear"    → rời trang là xoá sạch nháp, không hỏi (nội dung ngắn)
     data-lose-fields="a,b"→ các field này không khôi phục được, sẽ hỏi trước
   ============================================================= */
const LEAVE_FIELD_LABELS = {
    content: 'Nội dung',
    privacy: 'Quyền riêng tư',
    'images[]': 'Hình ảnh',
    display_name: 'Tên hiển thị',
    username: 'Username',
    email: 'Email',
    password: 'Mật khẩu',
    'password_confirmation': 'Xác nhận mật khẩu',
    bio: 'Tiêu sử',
    body: 'Nội dung tin nhắn',
};

/** Field nhạy cảm: hiển thị trong hộp thoại thì che bớt, không ghi ra nháp. */
const LEAVE_SECRET_FIELDS = new Set(['password', 'password_confirmation', 'current_password', 'new_password']);

/**
 * Form đang chờ người dùng xác nhận rời đi.
 */
let pendingNavigation = null;
let allowUnload = false;

/** Ảnh chụp giá trị ban đầu của các field để phát hiện thay đổi. */
const initialValues = new WeakMap();

function guardForms() {
    return Array.from(document.querySelectorAll('form[data-guard]'));
}

/** Form có nội dung được lưu nháp — khôi phục lại khi tải trang. */
function draftForms() {
    return Array.from(document.querySelectorAll('form[data-draft]'));
}

/**
 * Form không chứa dữ liệu nhưng thao tác của nó làm mất dữ liệu ở form khác
 * (ví dụ đăng xuất khi đang soạn bài viết).
 */
function leaveGuardForms() {
    return Array.from(document.querySelectorAll('form[data-leave-guard]'));
}

function snapshotInitialValues(form) {
    const values = new Map();

    form.querySelectorAll('input[name], textarea[name], select[name]').forEach(el => {
        const type = (el.type || '').toLowerCase();
        if (type === 'checkbox' || type === 'radio') {
            values.set(el, el.checked);
            return;
        }
        values.set(el, el.value);
    });

    initialValues.set(form, values);
}

function isFieldDirty(form, el) {
    const type = (el.type || '').toLowerCase();

    if (type === 'hidden' || type === 'submit' || type === 'button' || type === 'reset') {
        return false;
    }

    if (type === 'file') {
        return Boolean(el.files && el.files.length);
    }

    if (type === 'checkbox' || type === 'radio') {
        const initial = initialValues.get(form)?.get(el);

        return typeof initial === 'boolean' ? el.checked !== initial : el.checked;
    }

    const initial = initialValues.get(form)?.get(el);
    if (typeof initial !== 'string') {
        return String(el.value || '').trim() !== '';
    }

    // Field rỗng thì chưa có gì để mất.
    if (el.value.trim() === '') return false;

    // Đã đổi so với lúc mở trang.
    if (el.value !== initial) return true;

    // Không đổi, nhưng là giá trị server render lại từ lần gửi lỗi trước
    // (data-old-fields) — rời trang lúc này thì mất.
    return serverFieldNames(form).includes(el.name);
}

/** Danh sách field do server render lại từ giá trị cũ. */
function serverFieldNames(form) {
    return (form.dataset.oldFields || '')
        .split(',')
        .map(item => item.trim())
        .filter(Boolean);
}

function fieldLabel(el) {
    if (el.id) {
        const label = document.querySelector('label[for="' + CSS.escape(el.id) + '"]');
        if (label && label.textContent.trim()) {
            return label.textContent.trim();
        }
    }

    const wrapping = el.closest('label');
    if (wrapping && wrapping.textContent.trim()) {
        return wrapping.textContent.trim();
    }

    if (el.placeholder) {
        return el.placeholder;
    }

    return LEAVE_FIELD_LABELS[el.name] || el.name;
}

/**
 * Tóm tắt nội dung sẽ mất để hiển thị trong hộp thoại.
 */
function fieldPreview(el) {
    const type = (el.type || '').toLowerCase();

    if (type === 'file') {
        const count = el.files ? el.files.length : 0;
        return count > 0 ? `${count} tệp đã chọn` : '';
    }

    if (type === 'checkbox' || type === 'radio') {
        return el.checked ? 'đã chọn' : '';
    }

    // Mật khẩu không bao giờ hiện giá trị ra ngoài.
    if (LEAVE_SECRET_FIELDS.has(el.name)) {
        return el.value ? '••••••' : '';
    }

    const value = String(el.value || '').trim();
    if (value === '') return '';

    return value.length > 60 ? value.slice(0, 60) + '…' : value;
}

/**
 * Nội dung nào thực sự mất khi rời trang.
 *
 * Chính sách theo từng form:
 *   data-leave="keep"  → nháp được giữ, rời trang không mất gì.
 *                        Chỉ field không lưu nháp được (data-lose-fields, file)
 *                        mới thực sự mất và cần hỏi.
 *   data-leave="clear" → nháp bị xoá khi rời trang, không hỏi (nội dung ngắn).
 *   data-leave="warn"  → nháp bị xoá khi rời trang, hỏi trước (nội dung dài).
 *
 * Form không lưu nháp (đổi mật khẩu, OTP...) mọi nội dung đều mất nên luôn hỏi.
 *
 * @returns {Array<{form: HTMLFormElement, label: string, items: Array<{name: string, preview: string}>}>}
 */
function losingForms() {
    const candidates = [];

    draftForms().forEach(form => {
        const policy = form.dataset.leave || 'clear';

        if (policy === 'clear') return;

        const only = (form.dataset.loseFields || '')
            .split(',')
            .map(item => item.trim())
            .filter(Boolean);

        candidates.push({
            form,
            label: form.dataset.loseLabel || 'Nội dung đã nhập',
            only: only.length > 0 ? only : null,
        });
    });

    // Form không lưu nháp (đổi mật khẩu, OTP...) mọi nội dung đều mất.
    guardForms().forEach(form => {
        candidates.push({
            form,
            label: form.dataset.guard || 'Nội dung đã nhập',
            only: null,
        });
    });

    return candidates.map(({ form, label, only }) => {
        const items = [];

        form.querySelectorAll('input[name], textarea[name], select[name]').forEach(el => {
            if (only !== null && !only.includes(el.name)) return;
            if (!isFieldDirty(form, el)) return;

            items.push({ name: fieldLabel(el), preview: fieldPreview(el) });
        });

        return { form, label, items };
    }).filter(item => item.items.length > 0);
}

/**
 * Xoá sạch nội dung nháp khi rời trang.
 *
 * Chính sách theo từng form:
 *   data-leave="keep"  → giữ nháp
 *   data-leave="clear" → xoá nháp
 *   data-leave="warn"  → xoá nháp (sau khi người dùng đã xác nhận)
 *   (không khai báo)   → xoá nháp (mặc định: rời trang là sạch)
 */
function purgeEphemeralDrafts() {
    draftForms().forEach(form => {
        if (form.dataset.leave === 'keep') return;

        removeDraftEverywhere(form.dataset.draft);

        const hint = form.querySelector('[data-draft-hint]');
        if (hint) hint.hidden = true;
    });
}

/* ---------------- Hộp thoại ---------------- */
function ensureLeaveModal() {
    if (document.getElementById('kbLeaveModal')) {
        return document.getElementById('kbLeaveModal');
    }

    const modal = document.createElement('div');
    modal.className = 'leave-modal';
    modal.id = 'kbLeaveModal';
    modal.hidden = true;
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'kbLeaveModalTitle');
    modal.innerHTML = `
        <div class="leave-modal-card">
            <h2 class="leave-modal-title" id="kbLeaveModalTitle">
                <i class="bi bi-exclamation-triangle"></i>Bạn sắp rời trang
            </h2>
            <p class="leave-modal-text">Bạn có nội dung chưa gửi. Những nội dung sau sẽ mất nếu bạn rời trang này:</p>
            <ul class="leave-modal-list" data-leave-list></ul>
            <div class="leave-modal-actions">
                <button type="button" class="btn btn-outline btn-sm" data-leave-stay>Ở lại trang</button>
                <button type="button" class="btn btn-solid btn-sm" data-leave-go>Rời đi</button>
            </div>
        </div>`;
    document.body.appendChild(modal);

    modal.querySelector('[data-leave-stay]').addEventListener('click', () => {
        pendingNavigation = null;
        modal.hidden = true;
    });

    modal.querySelector('[data-leave-go]').addEventListener('click', () => {
        const target = pendingNavigation;
        pendingNavigation = null;
        modal.hidden = true;
        allowUnload = true;

        // Rời đi là mất nội dung: xoá sạch nháp theo chính sách từng form.
        purgeEphemeralDrafts();

        if (!target) return;

        if (target.type === 'link') {
            window.location.href = target.url;
            return;
        }

        target.form.requestSubmit(target.submitter || undefined);
    });

    modal.addEventListener('click', event => {
        if (event.target === modal) {
            pendingNavigation = null;
            modal.hidden = true;
        }
    });

    return modal;
}

function showLeaveModal(dirty, navigation) {
    const modal = ensureLeaveModal();
    const list = modal.querySelector('[data-leave-list]');
    list.innerHTML = '';

    dirty.forEach(item => {
        const li = document.createElement('li');
        li.className = 'leave-modal-item';

        const icon = document.createElement('i');
        icon.className = 'bi bi-pencil-square';

        const text = document.createElement('span');
        text.innerHTML = '<strong></strong> · <span></span>';
        text.querySelector('strong').textContent = item.label;
        text.querySelector('span').textContent = item.items
            .map(entry => (entry.preview ? `${entry.name}: ${entry.preview}` : entry.name))
            .join(' — ');

        li.appendChild(icon);
        li.appendChild(text);
        list.appendChild(li);
    });

    pendingNavigation = navigation;
    modal.hidden = false;
    modal.querySelector('[data-leave-stay]').focus();
}

/* ---------------- Chặn điều hướng ra ngoài ---------------- */
function isInternalLink(link) {
    if (!link || link.target === '_blank' || link.hasAttribute('download')) return false;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return false;
    if (href.startsWith('mailto:') || href.startsWith('tel:')) return false;

    try {
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return false;
        // Chỉ cảnh báo khi rời khỏi trang hiện tại.
        return url.pathname + url.search !== window.location.pathname + window.location.search;
    } catch (e) {
        return false;
    }
}

document.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) {
        return;
    }

    const link = event.target.closest('a[href]');
    if (!isInternalLink(link)) return;

    const losing = losingForms();
    if (losing.length === 0) {
        // Không có gì để hỏi: rời trang là xoá sạch nội dung tạm.
        purgeEphemeralDrafts();
        return;
    }

    event.preventDefault();
    showLeaveModal(losing, { type: 'link', url: link.href });
});

// Gửi form là hành động có chủ ý nên không bị chặn: dữ liệu được gửi đi, không mất.
// Form gửi lỗi validate thì giá trị đã nhập vẫn được giữ qua cơ chế old().

document.addEventListener('submit', event => {
    if (allowUnload || event.defaultPrevented) return;

    const form = event.target;
    if (!form.dataset.leaveGuard) return;

    // Chỉ cảnh báo nếu có nội dung thực sự mất ở form khác.
    const losing = losingForms();
    if (losing.length === 0) {
        purgeEphemeralDrafts();
        return;
    }

    event.preventDefault();
    showLeaveModal(losing, { type: 'form', form, submitter: event.submitter });
});

window.addEventListener('beforeunload', event => {
    if (allowUnload) return;

    // Đóng tab / tải lại trang: nội dung tạm bị xoá sạch.
    purgeEphemeralDrafts();

    const losing = losingForms();
    if (losing.length === 0) return;

    event.preventDefault();
    event.returnValue = '';
});

// Quay lại trang từ bộ nhớ đệm: cho phép rời trang bình thường trở lại.
window.addEventListener('pageshow', () => {
    allowUnload = false;
});

function initGuards() {
    [...guardForms(), ...draftForms()].forEach(snapshotInitialValues);
}

initGuards();

/* =============================================================
   Lưu nháp form
   ------------------------------------------------------------
   Mỗi form đánh dấu bằng data-draft="<key>" sẽ được lưu vào localStorage mỗi khi
   người dùng nhập, khôi phục lại khi tải trang (kể cả khi quay lại từ bộ nhớ đệm
   của trình duyệt) và xoá sau khi form được xử lý thành công.

   Thứ tự ưu tiên khi khôi phục: giá trị do server render (old()) luôn thắng,
   nháp chỉ dùng cho field còn trống.
   ============================================================= */
const DRAFT_PREFIX = 'kb:draft:';
const DRAFT_DEBOUNCE = 200;
const DRAFT_SKIP_TYPES = new Set(['file', 'hidden', 'submit', 'button', 'password', 'reset']);

/**
 * Id người dùng hiện tại, dùng để tách nháp của các tài khoản khác nhau trên
 * cùng một máy. Khách chưa đăng nhập dùng uid 0.
 */
const DRAFT_USER_ID = Number(document.body.dataset.userId || 0);

/**
 * Khoá lưu nháp. Có gắn uid để nháp của tài khoản này không lẫn sang tài khoản
 * khác trên cùng máy.
 */
function draftStorageKey(key) {
    return DRAFT_PREFIX + DRAFT_USER_ID + ':' + key;
}

/**
 * Xoá nháp ở mọi phạm vi người dùng.
 *
 * Nháp đăng nhập/đăng ký được tạo khi chưa đăng nhập (uid 0) nên sau khi đăng
 * nhập thành công vẫn cần xoá được nháp cũ ở phạm vi đó.
 */
function removeDraftEverywhere(key) {
    removeDraft(key);
    removeDraft(DRAFT_PREFIX + '0:' + key);
}

function readDraft(key) {
    try {
        const raw = window.localStorage.getItem(draftStorageKey(key));
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : null;
    } catch (e) {
        return null;
    }
}

function writeDraft(key, fields) {
    try {
        window.localStorage.setItem(draftStorageKey(key), JSON.stringify(fields));
    } catch (e) {
        // localStorage bị chặn (chế độ riêng tư) - bỏ qua, form vẫn hoạt động bình thường.
    }
}

function removeDraft(key) {
    try {
        window.localStorage.removeItem(draftStorageKey(key));
    } catch (e) {
        // Bỏ qua.
    }
}

/** Xoá nháp đã lưu của một form cụ thể. */
function clearFormDraft(form) {
    if (!form || !form.dataset.draft) return;
    removeDraftEverywhere(form.dataset.draft);

    const hint = form.querySelector('[data-draft-hint]');
    if (hint) hint.hidden = true;
}

function collectDraftFields(form) {
    const fields = [];

    form.querySelectorAll('input[name], textarea[name], select[name]').forEach(el => {
        const type = (el.type || '').toLowerCase();

        // Field ẩn (vd _token, parent_id) không lưu: token chỉ dùng một lần,
        // còn parent_id phụ thuộc bình luận đang trả lời nên không có ý nghĩa khi tải lại trang.
        if (DRAFT_SKIP_TYPES.has(type)) return;

        if (type === 'checkbox' || type === 'radio') {
            fields.push({ name: el.name, kind: type, value: el.value, checked: el.checked });
            return;
        }

        fields.push({ name: el.name, kind: 'value', value: el.value });
    });

    return fields;
}

/**
 * Điền giá trị nháp vào form.
 *
 * Field đã được server render từ giá trị cũ (data-old-fields) được giữ nguyên vì
 * đó mới là dữ liệu người dùng vừa gửi lên. Các field còn lại lấy giá trị nháp
 * vì đó là trạng thái mới nhất người dùng đang nhập.
 */
function applyDraft(form, draft) {
    const serverFields = (form.dataset.oldFields || '')
        .split(',')
        .map(item => item.trim())
        .filter(Boolean);

    draft.forEach(entry => {
        if (serverFields.includes(entry.name)) return;

        const targets = form.querySelectorAll('[name="' + CSS.escape(entry.name) + '"]');
        targets.forEach(el => {
            if (entry.kind === 'value') {
                el.value = entry.value;
                return;
            }

            if (entry.kind === 'checkbox') {
                el.checked = entry.checked;
                return;
            }

            if (entry.kind === 'radio' && !form.querySelector('[name="' + CSS.escape(entry.name) + '"]:checked')) {
                el.checked = entry.checked;
            }
        });
    });
}

/* =============================================================
   Chỉ báo nháp đã lưu
   ------------------------------------------------------------
   Người dùng cần biết nội dung của họ đã được an toàn. Chỉ báo nhỏ xuất hiện
   cạnh form ngay sau khi nội dung được lưu nháp, tự tắt khi form trống lại.
   ============================================================= */
function ensureDraftHint(form) {
    if (form.dataset.draftHint === '1') return form.querySelector('[data-draft-hint]');

    const hint = document.createElement('small');
    hint.className = 'draft-hint';
    hint.dataset.draftHint = '';
    hint.setAttribute('role', 'status');
    hint.hidden = true;

    form.appendChild(hint);
    form.dataset.draftHint = '1';

    return hint;
}

function updateDraftHint(form) {
    const hint = ensureDraftHint(form);
    const hasContent = form.querySelector('input[name], textarea[name], select[name]') !== null
        && Array.from(form.querySelectorAll('input[name], textarea[name], select[name]'))
            .some(el => {
                const type = (el.type || '').toLowerCase();
                if (DRAFT_SKIP_TYPES.has(type)) return false;
                if (type === 'file') return Boolean(el.files && el.files.length);
                if (type === 'checkbox' || type === 'radio') return el.checked;
                return String(el.value || '').trim() !== '';
            });

    if (!hasContent) {
        hint.hidden = true;
        return;
    }

    hint.innerHTML = '<i class="bi bi-cloud-check"></i>Đã lưu tự động — bạn có thể rời trang mà không mất nội dung';
    hint.hidden = true;
}

function initDrafts() {
    // Danh sách nháp cần xoá do server báo (form đã xử lý thành công).
    (document.body.dataset.clearedDrafts || '')
        .split(',')
        .map(item => item.trim())
        .filter(Boolean)
        .forEach(removeDraftEverywhere);

    document.querySelectorAll('form[data-draft]').forEach(form => {
        const key = form.dataset.draft;

        let timer = null;
        const scheduleSave = () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                writeDraft(key, collectDraftFields(form));
                updateDraftHint(form);
            }, DRAFT_DEBOUNCE);
        };

        form.addEventListener('input', scheduleSave);
        form.addEventListener('change', () => {
            writeDraft(key, collectDraftFields(form));
            updateDraftHint(form);
        });

        const draft = readDraft(key);
        if (draft) {
            applyDraft(form, draft);
            updateDraftHint(form);
        }

        // Khôi phục lại khi người dùng quay lại trang từ bộ nhớ đệm (bfcache).
        window.addEventListener('pageshow', event => {
            if (!event.persisted) return;
            const cached = readDraft(key);
            if (cached) applyDraft(form, cached);
        });
    });
}

initDrafts();
