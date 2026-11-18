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
   ============================================================= */
function toast(message, variant = 'success') {
    const container = document.getElementById('kbToastContainer') || (() => {
        const el = document.createElement('div');
        el.id = 'kbToastContainer';
        el.className = 'toast-container';
        document.body.appendChild(el);
        return el;
    })();

    const el = document.createElement('div');
    el.className = 'toast';
    el.setAttribute('role', 'alert');
    el.innerHTML = `
        <i class="bi bi-check-circle-fill"></i>
        <div class="toast-body"></div>
        <button type="button" class="btn-close" aria-label="Đóng"></button>`;
    el.querySelector('.toast-body').textContent = message;
    if (variant === 'danger') {
        el.style.backgroundColor = 'var(--kb-error)';
        el.style.color = '#fff';
    } else if (variant === 'info') {
        el.style.backgroundColor = 'var(--kb-surface-dark-elevated)';
    }
    container.appendChild(el);

    const remove = () => el.remove();
    el.querySelector('.btn-close').addEventListener('click', remove);
    setTimeout(remove, 3500);
}

window.kbToast = toast;

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
   Đóng cảnh báo
   ============================================================= */
document.addEventListener('click', (event) => {
    const dismiss = event.target.closest('[data-alert-dismiss]');
    if (!dismiss) return;
    dismiss.closest('.alert')?.remove();
});

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
            toast(payload.message ?? 'Không thể thực hiện.', 'danger');
            return;
        }
        const card = button.closest('.post-card');
        button.classList.toggle('active', payload.data.liked);
        const icon = button.querySelector('i');
        if (icon) icon.className = payload.data.liked ? 'bi bi-hand-thumbs-up-fill' : 'bi bi-hand-thumbs-up';
        const counter = card?.querySelector('[data-like-count]');
        if (counter) counter.innerHTML = `<i class="bi bi-hand-thumbs-up me-1"></i>${payload.data.like_count}`;
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
            toast(payload.message ?? 'Không thể chia sẻ.', 'danger');
            return;
        }
        const card = button.closest('.post-card');
        const counter = card?.querySelector('[data-share-count]');
        if (counter) counter.innerHTML = `<i class="bi bi-share me-1"></i>${payload.data.share_count}`;
        toast('Đã chia sẻ bài viết lên trang cá nhân của bạn.');
    }

    if (action === 'copy-link') {
        const link = new URL(button.dataset.url, window.location.origin).href;
        try {
            await navigator.clipboard.writeText(link);
            toast('Đã sao chép liên kết bài viết.');
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
        toast(payload.message ?? 'Không thể theo dõi.', 'danger');
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
        toast(payload.message ?? 'Không thể gửi bình luận.', 'danger');
        return;
    }

    input.value = '';
    if (parentInput) parentInput.value = '';
    const container = form.closest('.post-comments');
    if (container) {
        await loadComments(postId, container);
        container.classList.remove('d-none');
    }
    const card = form.closest('.post-card');
    const counter = card?.querySelector('[data-comment-count]');
    if (counter) counter.innerHTML = `<i class="bi bi-chat me-1"></i>${payload.data.comment_count}`;
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
                toast(payload.message ?? 'Không thể sửa bình luận.', 'danger');
                return;
            }
            textNode.innerHTML = escapeHtml(payload.data.comment.content).replace(/\n/g, '<br>');
            wrapper.replaceWith(textNode);
            toast('Đã cập nhật bình luận.');
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
            toast(payload.message ?? 'Không thể xóa bình luận.', 'danger');
            return;
        }
        const card = deleteBtn.closest('.post-card');
        const counter = card?.querySelector('[data-comment-count]');
        if (counter) counter.innerHTML = `<i class="bi bi-chat me-1"></i>${payload.data.comment_count}`;
        const postId = card?.dataset.postId;
        const container = card?.querySelector('.post-comments');
        if (postId && container) await loadComments(postId, container);
        toast('Đã xóa bình luận.');
    }
});

/* =============================================================
   Chat polling
   ============================================================= */
const chatMessages = document.querySelector('.chat-messages');
const chatForm = document.getElementById('chatForm');

if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

if (chatForm && chatMessages) {
    const conversationId = chatForm.dataset.conversationId;
    let lastMessageId = Number(chatMessages.querySelector('[data-message-id]')?.dataset.messageId ?? 0);

    chatForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = chatForm.querySelector('input[name="body"]');
        const body = input.value.trim();
        if (!body) return;

        const payload = await request(apiUrl(`/chat/${conversationId}/send`), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ body: body }),
        });

        if (!payload.success) {
            toast(payload.message ?? 'Không thể gửi tin nhắn.', 'danger');
            return;
        }

        input.value = '';
        appendMessage(payload.data);
    });

    function appendMessage(message) {
        if (message.id <= lastMessageId) return;
        lastMessageId = message.id;

        const row = document.createElement('div');
        row.className = 'chat-bubble-row' + (message.is_mine ? ' mine' : '');
        row.dataset.messageId = message.id;
        const time = new Date(message.created_at.replace(' ', 'T'));
        const label = Number.isNaN(time.getTime())
            ? ''
            : time.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        row.innerHTML = `
            <div class="chat-bubble">
                <div class="chat-text">${escapeHtml(message.body).replace(/\n/g, '<br>')}</div>
                <small class="chat-time">${label}</small>
            </div>`;
        chatMessages.appendChild(row);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    setInterval(async () => {
        const payload = await request(apiUrl(`/chat/${conversationId}/messages?after_id=${lastMessageId}`));
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
        toast('Đã đánh dấu tất cả thông báo đã đọc.');
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
