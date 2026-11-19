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
 * Kẹp nội dung dài và chèn nút "Xem thêm" nếu bị cắt.
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
        const wasClamped = el.classList.contains('is-clamped');
        el.classList.toggle('is-clamped', !wasClamped);
        button.textContent = wasClamped ? 'Thu gọn' : 'Xem thêm';
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

   Dùng hộp thoại dùng chung (#kbConfirmModal) thay cho `window.confirm()`
   của trình duyệt để giao diện đồng bộ với design system.
   ============================================================= */
const confirmModal = document.getElementById('kbConfirmModal');

if (confirmModal) {
    const confirmTitle = confirmModal.querySelector('[data-kb-confirm-title]');
    const confirmText = confirmModal.querySelector('[data-kb-confirm-text]');
    const confirmIcon = confirmModal.querySelector('[data-kb-confirm-icon]');
    const confirmAccept = confirmModal.querySelector('[data-kb-confirm-accept]');
    const confirmCancel = confirmModal.querySelector('[data-kb-confirm-cancel]');

    let confirmResolver = null;
    let confirmLastFocus = null;

    const closeConfirm = (result) => {
        confirmModal.hidden = true;
        document.body.style.overflow = '';

        const resolver = confirmResolver;
        confirmResolver = null;

        confirmLastFocus?.focus();
        confirmLastFocus = null;

        resolver?.(result);
    };

    const openConfirm = ({ title = 'Thông báo', message = '', acceptText = 'Đồng ý', cancelText = 'Huỷ', icon = 'bi-exclamation-triangle-fill' } = {}) =>
        new Promise((resolve) => {
            if (confirmTitle) confirmTitle.textContent = title;
            if (confirmText) confirmText.textContent = message;
            if (confirmAccept) confirmAccept.textContent = acceptText;
            if (confirmCancel) confirmCancel.textContent = cancelText;
            if (confirmIcon) confirmIcon.innerHTML = `<i class="bi ${icon}"></i>`;

            confirmResolver = resolve;
            confirmLastFocus = document.activeElement;

            confirmModal.hidden = false;
            document.body.style.overflow = 'hidden';
            confirmCancel?.focus();
        });

    confirmAccept?.addEventListener('click', () => closeConfirm(true));
    confirmCancel?.addEventListener('click', () => closeConfirm(false));
    confirmModal.querySelector('[data-kb-confirm-close]')?.addEventListener('click', () => closeConfirm(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !confirmModal.hidden) {
            closeConfirm(false);
        }
    });

    // API cho các đoạn mã khác gọi trực tiếp.
    window.kbConfirm = openConfirm;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        const message = form.dataset.confirm;
        if (!message) return;

        // Đã xác nhận rồi — gửi thẳng, không mở lại hộp thoại.
        if (form.dataset.confirmApproved === '1') {
            delete form.dataset.confirmApproved;
            return;
        }

        event.preventDefault();

        openConfirm({
            title: form.dataset.confirmTitle || 'Xác nhận',
            message,
            acceptText: form.dataset.confirmAccept || 'Xác nhận',
        }).then((accepted) => {
            if (!accepted) return;

            form.dataset.confirmApproved = '1';
            form.submit();
        });
    });
} else {
    // Không có hộp thoại dùng chung — rơi về hành xử mặc định của trình duyệt.
    document.addEventListener('submit', (event) => {
        const form = event.target;
        const message = form.dataset.confirm;
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
}

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
    const button = event.target.closest('.theme-btn, .theme-option');
    if (!button) return;
    const theme = button.dataset.theme;
    document.documentElement.setAttribute('data-theme', theme);
    const input = document.getElementById('themeInput');
    if (input) input.value = theme;
    document.querySelectorAll('.theme-btn, .theme-option').forEach(btn => btn.classList.remove('active'));
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
   Bộ soạn bài: đếm ký tự, xem trước, chọn quyền riêng tư
   ============================================================= */
(function composerEnhancements() {
    const textarea = document.getElementById('postContent');
    const counter = document.querySelector('[data-composer-count]');
    const counterBox = counter?.closest('.composer-counter');

    if (textarea && counter) {
        const limit = Number(textarea.getAttribute('maxlength')) || 5000;

        const syncCounter = () => {
            const length = textarea.value.length;
            counter.textContent = String(length);

            if (!counterBox) return;
            counterBox.classList.toggle('is-warning', length >= limit * 0.9 && length < limit);
            counterBox.classList.toggle('is-danger', length >= limit);
        };

        textarea.addEventListener('input', syncCounter);
        syncCounter();
    }

    // Xem trước: bật/ẩt khối xem trước và đồng bộ nội dung.
    const previewPanel = document.querySelector('[data-compose-preview-panel]');
    const previewText = document.querySelector('[data-compose-preview-text]');

    if (previewPanel && textarea) {
        const previewToggle = document.querySelector('[data-compose-preview]');

        previewToggle?.addEventListener('click', () => {
            previewPanel.hidden = !previewPanel.hidden;
            previewToggle.classList.toggle('is-active', !previewPanel.hidden);

            if (!previewPanel.hidden && previewText) {
                previewText.textContent = textarea.value.trim() || 'Bạn đang nghĩ gì?';
            }
        });

        textarea.addEventListener('input', () => {
            if (previewPanel.hidden || !previewText) return;
            previewText.textContent = textarea.value.trim() || 'Bạn đang nghĩ gì?';
        });
    }

    // Bộ chọn quyền riêng tư: đồng bộ trạng thái hiển thị với radio thật.
    const privacyGroups = [...document.querySelectorAll('.privacy-picker-options')];

    const syncPrivacyPickers = () => {
        privacyGroups.forEach((group) => {
            group.querySelectorAll('.privacy-option').forEach((option) => {
                const input = option.querySelector('.privacy-option-input');
                option.classList.toggle('is-active', Boolean(input?.checked));
            });
        });
    };

    privacyGroups.forEach((group) => {
        group.addEventListener('change', syncPrivacyPickers);
    });

    syncPrivacyPickers();

    // Nháp được khôi phục bằng cách gán thẳng giá trị, không sinh sự kiện
    // change — nên cần đồng bộ lại khi form nạp xong.
    window.KBSyncPrivacyPickers = syncPrivacyPickers;
})();

/* =============================================================
   Cập nhật số liệu tương tác (thích / bình luận / chia sẻ)

   Cột số liệu ở thẻ bài viết có sẵn biểu tượng, còn ở trang chi tiết thì
   biểu tượng nằm ở phần tử anh em — nên giữ nguyên cấu trúc đang có.
   ============================================================= */
function setCounter(el, value, iconClass) {
    if (!el) {
        return;
    }
    if (el.querySelector('i')) {
        el.innerHTML = `<i class="bi ${iconClass} me-1"></i>${value}`;
    } else {
        el.textContent = value;
    }
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
        const card = button.closest('.post-card, .post-detail');
        button.classList.toggle('active', payload.data.liked);
        const icon = button.querySelector('i');
        if (icon) icon.className = payload.data.liked ? 'bi bi-hand-thumbs-up-fill' : 'bi bi-hand-thumbs-up';
        setCounter(card?.querySelector('[data-like-count]'), payload.data.like_count, 'bi-hand-thumbs-up');
        showToast('success', payload.data.liked ? 'Đã thích bài viết.' : 'Đã bỏ thích bài viết.');
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

    // Trang đăng bài đã là trang riêng — chuyển thẳng tới đó.
    if (window.location.pathname !== link.pathname) {
        return;
    }

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

/** Làm mới danh sách bình luận ở trang chi tiết bài viết. */
async function refreshDetailComments(list) {
    const postId = list.dataset.commentList;
    if (!postId) return;
    const payload = await request(apiUrl(`/api/posts/${postId}/comments`));
    if (!payload.success) return;
    list.innerHTML = '';
    if (!payload.data.comments.length) {
        list.innerHTML = '<div class="empty-state post-detail-empty"><span class="empty-icon"><i class="bi bi-chat-heart"></i></span><h3>Chưa có bình luận nào</h3><p>Hãy là người đầu tiên trò chuyện về bài viết này.</p></div>';
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
                <p class="mb-0 comment-text" data-comment-text>${escapeHtml(comment.content).replace(/\n/g, '<br>')}</p>
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
        (comment.replies || []).forEach(reply => repliesBox.appendChild(buildCommentNode(reply)));
    }
    return node;
}

// Cho realtime.js dựng lại node bình luận khi có bình luận mới.
window.KBBuildCommentNode = buildCommentNode;

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

/** Ảnh đại diện dự phòng khi tin realtime không kèm ảnh. */
function peerAvatarFallback() {
    const img = document.querySelector('.chat-header-peer img.avatar');
    return img ? img.getAttribute('src') || '' : '';
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

    const card = form.closest('.post-card, .post-detail');
    const container = form.closest('.post-comments');
    if (container) {
        await loadComments(postId, container);
        container.classList.remove('d-none');
    } else {
        // Trang chi tiết: danh sách bình luận render sẵn từ server.
        const list = form.closest('.post-detail-comments')?.querySelector('[data-comment-list]');
        if (list) {
            await refreshDetailComments(list);
        }
    }
    setCounter(card?.querySelector('[data-comment-count]'), payload.data.comment_count, 'bi-chat');
    showToast('success', isReply ? 'Đã trả lời bình luận.' : 'Đã gửi bình luận.');
});

document.addEventListener('click', async (event) => {
    const replyBtn = event.target.closest('.comment-reply-btn');
    if (replyBtn) {
        // Ô nhập nằm trong khung bình luận chứ không phải trong thẻ bài viết.
        const scope = replyBtn.closest('.post-comments, .post-detail-comments, .post-card, .post-detail');
        const form = scope?.querySelector('.comment-form');
        if (!form) return;
        const parentInput = form.querySelector('input[name="parent_id"]');
        if (parentInput) parentInput.value = replyBtn.dataset.commentId;
        const input = form.querySelector('input[name="content"], textarea[name="content"]');
        if (input) {
            input.focus();
            input.placeholder = 'Trả lời bình luận...';
        }
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
        const confirmed = typeof window.kbConfirm === 'function'
            ? await window.kbConfirm({
                title: 'Xóa bình luận',
                message: 'Bạn có chắc muốn xóa bình luận này? Hành động này không thể hoàn tác.',
                acceptText: 'Xóa bình luận',
            })
            : window.confirm('Bạn có chắc muốn xóa bình luận này?');

        if (!confirmed) return;
        const payload = await request(apiUrl(`/comments/${deleteBtn.dataset.commentId}/delete`), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        });
        if (!payload.success) {
            showToast('error', payload.message ?? 'Không thể xóa bình luận.');
            return;
        }
        const card = deleteBtn.closest('.post-card, .post-detail');
        setCounter(card?.querySelector('[data-comment-count]'), payload.data.comment_count, 'bi-chat');
        const postId = card?.dataset.postId;
        const container = card?.querySelector('.post-comments');
        if (postId && container) {
            await loadComments(postId, container);
        } else {
            // Trang chi tiết: danh sách bình luận nằm ở khu bình luận riêng.
            const list = deleteBtn.closest('.post-detail-comments')?.querySelector('[data-comment-list]');
            if (list) await refreshDetailComments(list);
        }
        showToast('success', 'Đã xóa bình luận.');
    }
});

/* =============================================================
   Trang chi tiết bài viết
   - Thanh tiến độ đọc
   - Lightbox xem ảnh
   - Hộp chia sẻ nhanh
   ============================================================= */

/* ---- Thanh tiến độ đọc ---- */
const readProgress = document.querySelector('[data-read-progress]');
if (readProgress) {
    const readBar = readProgress.querySelector('[data-read-bar]');
    const updateReadProgress = () => {
        const total = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = total > 0 ? Math.min(1, Math.max(0, window.scrollY / total)) : 0;
        if (readBar) readBar.style.width = (ratio * 100).toFixed(2) + '%';
    };
    updateReadProgress();
    window.addEventListener('scroll', updateReadProgress, { passive: true });
    window.addEventListener('resize', updateReadProgress);
}

/* ---- Lightbox xem ảnh ---- */
const lightbox = document.querySelector('[data-lightbox-view]');
if (lightbox) {
    const lightboxImage = lightbox.querySelector('[data-lightbox-image]');
    const lightboxCaption = lightbox.querySelector('[data-lightbox-caption]');
    const triggers = Array.from(document.querySelectorAll('[data-lightbox]'));
    let currentIndex = 0;

    const showImage = (index) => {
        if (triggers.length === 0) return;
        currentIndex = (index + triggers.length) % triggers.length;
        const src = triggers[currentIndex].dataset.lightbox;
        if (lightboxImage) lightboxImage.src = src;
        if (lightboxCaption) {
            lightboxCaption.textContent = `${currentIndex + 1} / ${triggers.length}`;
        }
    };

    const openLightbox = (index) => {
        showImage(index);
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
    };

    const closeLightbox = () => {
        lightbox.hidden = true;
        document.body.style.overflow = '';
    };

    triggers.forEach((trigger, index) => {
        trigger.addEventListener('click', () => openLightbox(index));
    });

    lightbox.querySelector('[data-lightbox-close]')?.addEventListener('click', closeLightbox);
    lightbox.querySelector('[data-lightbox-prev]')?.addEventListener('click', () => showImage(currentIndex - 1));
    lightbox.querySelector('[data-lightbox-next]')?.addEventListener('click', () => showImage(currentIndex + 1));

    lightbox.addEventListener('click', (event) => {
        if (event.target === lightbox) closeLightbox();
    });

    document.addEventListener('keydown', (event) => {
        if (lightbox.hidden) return;
        if (event.key === 'Escape') closeLightbox();
        if (event.key === 'ArrowLeft') showImage(currentIndex - 1);
        if (event.key === 'ArrowRight') showImage(currentIndex + 1);
    });
}

/* =============================================================
   HỘP THOẠI CHIA SẺ

   Một modal duy nhất phục vụ mọi thẻ bài viết trong feed lẫn trang chi tiết.
   Ba tab: chia sẻ lên trang cá nhân, gửi qua tin nhắn, sao chép liên kết.
   ============================================================= */
const shareModal = document.getElementById('kbShareModal');

if (shareModal) {
    const shareState = {
        postId: 0,
        privacy: 'public',
        people: [],
        loaded: false,
    };

    const sharePeople = shareModal.querySelector('[data-share-people]');
    const shareSearch = shareModal.querySelector('[data-share-search]');
    const shareText = shareModal.querySelector('[data-share-text]');
    const shareLink = shareModal.querySelector('[data-share-link]');
    const sharePreviewAuthor = shareModal.querySelector('[data-share-preview-author]');
    const sharePreviewExcerpt = shareModal.querySelector('[data-share-preview-excerpt]');
    const shareSubmit = shareModal.querySelector('[data-share-submit]');

    const setShareTab = (name) => {
        shareModal.querySelectorAll('[data-share-tab]').forEach(tab => {
            const active = tab.dataset.shareTab === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        shareModal.querySelectorAll('[data-share-panel]').forEach(panel => {
            panel.classList.toggle('is-active', panel.dataset.sharePanel === name);
        });

        if (name === 'message' && !shareState.loaded) {
            loadShareTargets();
        }
    };

    const renderSharePeople = (people) => {
        if (!sharePeople) return;

        if (!people || people.length === 0) {
            sharePeople.innerHTML = '<div class="share-modal-empty"><i class="bi bi-person-x"></i><span>Không tìm thấy ai phù hợp.</span></div>';
            return;
        }

        sharePeople.innerHTML = '';
        people.forEach(person => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'share-person';
            item.dataset.userId = person.id;

            const avatar = document.createElement('img');
            avatar.src = person.avatar_url;
            avatar.alt = '';
            avatar.className = 'avatar avatar-sm';

            const text = document.createElement('span');
            text.className = 'share-person-text';

            const name = document.createElement('span');
            name.className = 'share-person-name';
            name.textContent = person.display_name;

            const handle = document.createElement('span');
            handle.className = 'share-person-handle';
            handle.textContent = '@' + person.username;

            text.append(name, handle);

            const action = document.createElement('span');
            action.className = 'share-person-action';
            action.innerHTML = '<i class="bi bi-send"></i>';

            item.append(avatar, text, action);
            sharePeople.appendChild(item);
        });
    };

    const loadShareTargets = async (keyword = '') => {
        if (!sharePeople) return;

        if (!shareState.loaded && keyword === '') {
            sharePeople.innerHTML = '<div class="share-modal-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Đang tải danh sách...</span></div>';
        }

        const payload = await request(apiUrl(`/posts/${shareState.postId}/share/targets?q=${encodeURIComponent(keyword)}`));
        if (!payload.success) {
            sharePeople.innerHTML = '<div class="share-modal-empty"><span>Không tải được danh sách.</span></div>';
            return;
        }

        shareState.loaded = true;
        shareState.people = payload.data.people;
        renderSharePeople(payload.data.people);
    };

    const openShareModal = async (postId) => {
        shareState.postId = Number(postId);
        shareState.privacy = 'public';
        shareState.loaded = false;

        shareModal.hidden = false;
        document.body.style.overflow = 'hidden';

        setShareTab('feed');

        if (shareText) shareText.value = '';
        shareModal.querySelectorAll('[data-share-privacy]').forEach(option => {
            const active = option.dataset.sharePrivacy === 'public';
            option.classList.toggle('is-active', active);
            option.setAttribute('aria-checked', active ? 'true' : 'false');
        });

        const payload = await request(apiUrl(`/posts/${shareState.postId}/share/targets`));
        if (!payload.success) {
            showToast('error', payload.message ?? 'Không tải được thông tin bài viết.');
            closeShareModal();
            return;
        }

        const post = payload.data.post;
        if (sharePreviewAuthor) sharePreviewAuthor.textContent = post.author.display_name;
        if (sharePreviewExcerpt) {
            sharePreviewExcerpt.textContent = post.content.trim() !== ''
                ? post.content.trim()
                : 'Bài viết không có nội dung chữ.';
        }
        if (shareLink) shareLink.value = new URL(post.permalink, window.location.origin).href;

        shareText?.focus();
    };

    const closeShareModal = () => {
        shareModal.hidden = true;
        document.body.style.overflow = '';
    };

    // Mở modal khi bấm nút chia sẻ ở thẻ bài viết hoặc trang chi tiết.
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-action="share"]');
        if (!trigger) return;
        event.preventDefault();
        openShareModal(trigger.dataset.postId);
    });

    shareModal.querySelectorAll('[data-share-modal-close]').forEach(button => {
        button.addEventListener('click', closeShareModal);
    });

    shareModal.querySelectorAll('[data-share-tab]').forEach(tab => {
        tab.addEventListener('click', () => setShareTab(tab.dataset.shareTab));
    });

    shareModal.querySelectorAll('[data-share-privacy]').forEach(option => {
        option.addEventListener('click', () => {
            shareState.privacy = option.dataset.sharePrivacy;
            shareModal.querySelectorAll('[data-share-privacy]').forEach(item => {
                const active = item === option;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-checked', active ? 'true' : 'false');
            });
        });
    });

    let shareSearchTimer = null;
    shareSearch?.addEventListener('input', () => {
        window.clearTimeout(shareSearchTimer);
        const keyword = shareSearch.value.trim();
        shareSearchTimer = window.setTimeout(() => loadShareTargets(keyword), 250);
    });

    // Gửi bài viết cho một người qua tin nhắn.
    sharePeople?.addEventListener('click', async (event) => {
        const person = event.target.closest('.share-person');
        if (!person) return;

        const targetId = Number(person.dataset.userId);
        const target = shareState.people.find(item => item.id === targetId);
        if (!target) return;

        person.classList.add('is-sent');
        const action = person.querySelector('.share-person-action');
        if (action) action.innerHTML = '<i class="bi bi-check-lg"></i>';

        const payload = await request(apiUrl(`/posts/${shareState.postId}/share/message`), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                user_id: targetId,
                body: shareText?.value.trim() ?? '',
            }),
        });

        if (!payload.success) {
            person.classList.remove('is-sent');
            if (action) action.innerHTML = '<i class="bi bi-send"></i>';
            showToast('error', payload.message ?? 'Không gửi được tin nhắn.');
            return;
        }

        showToast('success', `Đã gửi bài viết tới ${target.display_name}.`);
    });

    // Chia sẻ lên trang cá nhân của người dùng.
    shareSubmit?.addEventListener('click', async () => {
        const content = shareText?.value.trim() ?? '';

        shareSubmit.disabled = true;
        const payload = await request(apiUrl(`/posts/${shareState.postId}/share`), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ content: content, privacy: shareState.privacy }),
        });
        shareSubmit.disabled = false;

        if (!payload.success) {
            showToast('error', payload.message ?? 'Không thể chia sẻ.');
            return;
        }

        const counter = document.querySelector(`.post-card[data-post-id="${shareState.postId}"], .post-detail[data-post-id="${shareState.postId}"]`)
            ?.querySelector('[data-share-count]');
        setCounter(counter, payload.data.share_count, 'bi-share');

        closeShareModal();
        showToast('success', 'Đã chia sẻ bài viết lên trang cá nhân của bạn.');
    });

    // Sao chép liên kết.
    shareModal.querySelector('[data-share-copy]')?.addEventListener('click', async () => {
        const link = shareLink?.value ?? '';
        if (link === '') return;

        try {
            await navigator.clipboard.writeText(link);
            showToast('success', 'Đã sao chép liên kết bài viết.');
        } catch (e) {
            shareLink?.select();
            showToast('warning', 'Nhấn Ctrl + C để sao chép liên kết.');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (shareModal.hidden) return;
        if (event.key === 'Escape') closeShareModal();
    });
}

/* ---- Gửi bình luận nhanh bằng Ctrl + Enter ---- */
document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey) || event.key !== 'Enter') return;
    const textarea = event.target;
    if (!textarea || textarea.tagName !== 'TEXTAREA') return;
    const form = textarea.closest('.comment-form');
    if (form) form.requestSubmit();
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
const chatInput = document.getElementById('chatInput');
const chatCounter = document.querySelector('[data-chat-counter]');
const chatScrollBottom = document.querySelector('[data-chat-scroll-bottom]');
const chatNewMessages = document.querySelector('[data-chat-new-messages]');
const chatNewMessagesCount = document.querySelector('[data-chat-new-messages-count]');
const chatEmptyState = chatMessages?.querySelector('.chat-empty');

const CHAT_MAX_LENGTH = 5000;
/** Khoảng cách (px) dưới đáy để coi là "đang ở cuối đoạn chat". */
const CHAT_BOTTOM_THRESHOLD = 80;

/* -------------------------------------------------------------
   Ô nhập: tự giãn theo nội dung + bộ đếm ký tự
   ------------------------------------------------------------- */
if (chatInput) {
    const autoGrow = () => {
        chatInput.style.height = 'auto';
        chatInput.style.height = Math.min(chatInput.scrollHeight, 160) + 'px';
        // Ẩn thanh cuộn khi không cần cuộn
        chatInput.style.overflowY = chatInput.scrollHeight > 160 ? 'auto' : 'hidden';
    };

    const updateCounter = () => {
        if (!chatCounter) return;

        const length = chatInput.value.length;
        chatCounter.textContent = `${length}/${CHAT_MAX_LENGTH}`;
        chatCounter.classList.toggle('is-warning', length >= CHAT_MAX_LENGTH * 0.85 && length < CHAT_MAX_LENGTH);
        chatCounter.classList.toggle('is-danger', length >= CHAT_MAX_LENGTH);
    };

    chatInput.addEventListener('input', () => {
        autoGrow();
        updateCounter();
    });

    // Khôi phục nội dung nháp: chiều cao ô nhập phải khớp lại.
    chatInput.addEventListener('focus', autoGrow);
    requestAnimationFrame(() => {
        autoGrow();
        updateCounter();
    });

    // Enter gửi tin, Shift+Enter xuống dòng.
    chatInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.metaKey || event.isComposing) {
            return;
        }

        event.preventDefault();
        chatForm?.requestSubmit();
    });
}

/* -------------------------------------------------------------
   Vùng đọc: cuộn, nút cuộn xuống đáy, bộ đếm tin nhắn mới
   ------------------------------------------------------------- */
if (chatMessages) {
    const isAtBottom = () => {
        return chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight <= CHAT_BOTTOM_THRESHOLD;
    };

    const scrollToBottom = (behavior = 'smooth') => {
        chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior });
        chatScrollBottom?.classList.remove('is-visible');
        chatNewMessages?.classList.remove('is-visible');
        if (chatNewMessagesCount) chatNewMessagesCount.textContent = 'Tin nhắn mới';
        if (window.KBChat?.state) window.KBChat.state.pendingCount = 0;
    };

    chatMessages.scrollTop = chatMessages.scrollHeight;

    chatMessages.addEventListener('scroll', () => {
        if (isAtBottom()) {
            chatScrollBottom?.classList.remove('is-visible');
            chatNewMessages?.classList.remove('is-visible');
            if (chatNewMessagesCount) chatNewMessagesCount.textContent = 'Tin nhắn mới';
            return;
        }

        chatScrollBottom?.classList.add('is-visible');
    });

    chatScrollBottom?.addEventListener('click', () => scrollToBottom());
    chatNewMessages?.addEventListener('click', () => scrollToBottom());

    window.KBChatScrollToBottom = () => scrollToBottom('auto');
}

/* -------------------------------------------------------------
   Danh sách hội thoại: lọc theo tên + tab Tất cả / Chưa đọc
   ------------------------------------------------------------- */
const chatListFilter = document.getElementById('chatListFilter');
const chatListContainer = document.getElementById('chatConversationList');

if (chatListContainer) {
    let chatFilterMode = 'all';

    const conversations = Array.from(chatListContainer.querySelectorAll('.chat-conversation'));

    const updateUnreadCount = () => {
        const total = conversations.reduce((sum, item) => sum + Number(item.dataset.unread || 0), 0);
        const label = document.querySelector('[data-chat-unread-count]');
        if (label) {
            label.textContent = total > 0 ? `(${total})` : '';
            label.hidden = total === 0;
        }

        const badge = document.querySelector('[data-chat-unread-total]');
        if (badge) {
            badge.textContent = total > 99 ? '99+' : total;
            badge.classList.toggle('d-none', total === 0);
        }
    };

    const applyFilter = () => {
        const keyword = (chatListFilter?.value || '').trim().toLowerCase();
        let visible = 0;

        conversations.forEach((item) => {
            const matchesText = keyword === '' || (item.dataset.search || '').includes(keyword);
            const matchesMode = chatFilterMode === 'all' || Number(item.dataset.unread || 0) > 0;
            const show = matchesText && matchesMode;

            item.hidden = !show;
            item.classList.toggle('d-none', !show);
            if (show) visible += 1;
        });

        let empty = chatListContainer.querySelector('[data-chat-list-empty]');
        if (visible === 0) {
            if (!empty) {
                empty = document.createElement('div');
                empty.className = 'chat-list-empty';
                empty.dataset.chatListEmpty = '';
                empty.innerHTML = '<i class="bi bi-chat-square-dots" aria-hidden="true"></i>Không có hội thoại phù hợp.';
                chatListContainer.appendChild(empty);
            }
            empty.hidden = false;
        } else if (empty) {
            empty.hidden = true;
        }
    };

    chatListFilter?.addEventListener('input', () => {
        chatListFilter.closest('.chat-filter')?.classList.toggle('has-value', chatListFilter.value !== '');
        applyFilter();
    });

    document.querySelector('[data-chat-filter-clear]')?.addEventListener('click', () => {
        if (!chatListFilter) return;
        chatListFilter.value = '';
        chatListFilter.closest('.chat-filter')?.classList.remove('has-value');
        applyFilter();
        chatListFilter.focus();
    });

    document.querySelectorAll('[data-chat-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            chatFilterMode = button.dataset.chatFilter || 'all';
            document.querySelectorAll('[data-chat-filter]').forEach((other) => {
                const active = other === button;
                other.classList.toggle('active', active);
                other.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            applyFilter();
        });
    });

    updateUnreadCount();
    applyFilter();

    window.KBChatRefreshList = updateUnreadCount;
}

/* -------------------------------------------------------------
   Gửi tin nhắn
   ------------------------------------------------------------- */
if (chatForm && chatMessages) {
    const conversationId = Number(chatForm.dataset.conversationId);
    const chatState = {
        conversationId,
        lastMessageId: Number(chatMessages.querySelector('[data-message-id]')?.dataset.messageId ?? 0),
        realtime: false,
        sending: false,
        pendingCount: 0,
    };

    function formatMessageTime(value) {
        const time = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(time.getTime())
            ? ''
            : time.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }

    /**
     * Gom tin nhắn mới vào nhóm liên tiếp của cùng người gửi để bong bóng
     * hiển thị liền mạch, giống hệt tin render sẵn từ PHP.
     */
    function applyGrouping(row) {
        const senderId = Number(row.dataset.senderId || 0);
        const rows = Array.from(chatMessages.querySelectorAll('.chat-bubble-row'));
        const index = rows.indexOf(row);
        const previous = index > 0 ? rows[index - 1] : null;

        let grouped = false;
        if (previous) {
            const sameSender = Number(previous.dataset.senderId || 0) === senderId;
            const previousTime = new Date(String(previous.dataset.time || '').replace(' ', 'T')).getTime();
            const currentTime = new Date(String(row.dataset.time || '').replace(' ', 'T')).getTime();
            const closeInTime = !Number.isNaN(previousTime) && !Number.isNaN(currentTime)
                && Math.abs(currentTime - previousTime) <= 300000;

            grouped = Boolean(sameSender && closeInTime);

            if (grouped) {
                // Tin trước không còn là cuối nhóm nữa.
                previous.classList.remove('group-end');
            }
        }

        row.classList.toggle('grouped', grouped);
        row.classList.toggle('group-start', !grouped);
        row.classList.add('group-end');
    }

    function appendMessage(message) {
        const id = Number(message.id) || 0;
        if (id && id <= chatState.lastMessageId) return;
        if (id) chatState.lastMessageId = id;

        const isMine = message.is_mine !== undefined
            ? Boolean(message.is_mine)
            : Number(message.sender_id) === window.KB_USER_ID;

        const wasAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight <= CHAT_BOTTOM_THRESHOLD;

        const row = document.createElement('div');
        row.className = 'chat-bubble-row' + (isMine ? ' mine' : '') + ' group-start group-end';
        row.dataset.messageId = id;
        row.dataset.senderId = Number(message.sender_id || 0);
        row.dataset.time = String(message.created_at || '');

        const avatar = !isMine
            ? `<img src="${escapeHtml(message.avatar_url || peerAvatarFallback())}" alt="" class="avatar avatar-sm flex-shrink-0">`
            : '';

        row.innerHTML = `
            ${avatar}
            <div class="chat-bubble">
                <div class="chat-text">${escapeHtml(message.body).replace(/\n/g, '<br>')}</div>
                <small class="chat-time">${formatMessageTime(message.created_at)}${
                    isMine ? ' <i class="bi bi-check2" data-read-mark="1" title="Đã gửi"></i>' : ''
                }</small>
            </div>
            <div class="chat-bubble-tools">
                <button type="button" data-chat-copy aria-label="Sao chép nội dung" title="Sao chép">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>`;

        chatMessages.appendChild(row);
        applyGrouping(row);

        // Bỏ trạng thái rỗng khi tin nhắn đầu tiên đến.
        chatEmptyState?.remove();

        if (wasAtBottom) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        } else {
            chatState.pendingCount += 1;
            if (chatNewMessages) {
                chatNewMessages.classList.add('is-visible');
                if (chatNewMessagesCount) {
                    chatNewMessagesCount.textContent = chatState.pendingCount > 1
                        ? `${chatState.pendingCount} tin nhắn mới`
                        : 'Tin nhắn mới';
                }
            }
        }
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
        const input = chatForm.querySelector('textarea[name="body"], input[name="body"]');
        const body = (input?.value || '').trim();
        if (!body || chatState.sending) return;

        chatState.sending = true;
        try {
            // Ưu tiên WebSocket, chỉ dùng HTTP khi chưa kết nối được.
            if (chatState.realtime && typeof window.KBChatSend === 'function') {
                try {
                    await window.KBChatSend(body);
                } catch (error) {
                    // WebSocket chết giữa lúc gửi — fallback sang HTTP.
                    await sendViaHttp(body);
                }
            } else {
                await sendViaHttp(body);
            }

            if (input) {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
            clearFormDraft(chatForm);
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

/* -------------------------------------------------------------
   Sao chép nội dung tin nhắn
   ------------------------------------------------------------- */
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-chat-copy]');
    if (!button) return;

    const text = button.closest('.chat-bubble')?.querySelector('.chat-text')?.textContent || '';
    if (!text) return;

    try {
        await navigator.clipboard.writeText(text);
        showToast('success', 'Đã sao chép nội dung tin nhắn.');
    } catch (error) {
        showToast('error', 'Không thể sao chép nội dung.');
    }
});

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

// Cho realtime.js gọi lại khi có sự kiện mới (chat, thông báo…).
window.KBRefreshBadges = refreshBadges;

/* -------------------------------------------------------------
   Mở/đóng bảng tìm người (chỉ dùng trên mobile)
   ------------------------------------------------------------- */
const chatLayout = document.querySelector('.chat-layout');

if (chatLayout) {
    const openStart = () => {
        chatLayout.dataset.chatStartOpen = '1';
        document.getElementById('chatUserSearch')?.focus();
    };

    const closeStart = () => {
        delete chatLayout.dataset.chatStartOpen;
        document.getElementById('chatSearchResults')?.classList.add('d-none');
    };

    document.querySelector('[data-chat-new-chat]')?.addEventListener('click', openStart);
    document.querySelector('[data-chat-start-close]')?.addEventListener('click', closeStart);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && chatLayout.dataset.chatStartOpen === '1') {
            closeStart();
        }
    });
}

/* -------------------------------------------------------------
   Tìm kiếm AJAX
   ------------------------------------------------------------- */
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

/**
 * Form đang được gửi đi.
 *
 * Nội dung của chính form này không mất khi rời trang (nó đang được gửi lên
 * server), nên không được tính vào danh sách "sắp mất" — tránh hộp thoại
 * "Rời khỏi trang web…" của trình duyệt khi bấm nút Đăng bài.
 */
let submittingForm = null;

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
    })
        // Form đang được gửi đi thì nội dung của nó không mất — nó đang được
        // gửi lên server (kể cả ảnh đã chọn).
        .filter(item => item.form !== submittingForm)
        .filter(item => item.items.length > 0);
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

    // Nội dung của chính form đang gửi đi không bị mất (kể cả ảnh đã chọn),
    // nên không được tính vào danh sách "sắp mất" — tránh hộp thoại
    // "Rời khỏi trang web…" của trình duyệt khi bấm nút Đăng bài.
    submittingForm = form;

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
    submittingForm = null;
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

    // Radio được gán giá trị trực tiếp nên không phát sự kiện change —
    // cần đồng bộ lại giao diện của bộ chọn quyền riêng tư.
    window.KBSyncPrivacyPickers?.();
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

/* =============================================================
   Trang cá nhân: bộ đếm tiểu sử + xem trước ảnh đại diện/ảnh bìa
   ============================================================= */
(function initProfileSettings() {
    // ---- Bộ đếm ký tự cho ô tiểu sử ----
    const bio = document.getElementById('bio');
    const bioCount = document.querySelector('[data-bio-count]');
    if (bio && bioCount) {
        const counter = bioCount.closest('.form-text');
        const update = () => {
            const length = bio.value.length;
            bioCount.textContent = String(length);
            if (counter) {
                counter.classList.toggle('is-warning', length >= 450 && length < 500);
            }
        };
        bio.addEventListener('input', update);
        update();
    }

    // ---- Xem trước ảnh đại diện ----
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', () => {
            const file = avatarInput.files && avatarInput.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            avatarPreview.src = url;
            avatarPreview.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
        });
    }

    // ---- Xem trước ảnh bìa ----
    const coverInput = document.getElementById('cover');
    const coverPreview = document.querySelector('.profile-preview');
    if (coverInput && coverPreview) {
        coverInput.addEventListener('change', () => {
            const file = coverInput.files && coverInput.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            coverPreview.style.backgroundImage = `url("${url}")`;
            coverPreview.classList.remove('is-empty');
            coverPreview.addEventListener('transitionend', () => URL.revokeObjectURL(url), { once: true });
        });
    }
})();

/* =============================================================
   Trang cá nhân: cuộn tab đang chọn vào khung nhìn
   ============================================================= */
(function initProfileTabs() {
    const wrap = document.querySelector('.profile-tabs-wrap');
    const tabs = document.querySelector('.profile-tabs');
    if (!wrap || !tabs) return;

    const syncMask = () => {
        const overflow = tabs.scrollWidth - tabs.clientWidth;
        const atEnd = tabs.scrollLeft >= overflow - 2;
        wrap.classList.toggle('is-scrollable', overflow > 2 && !atEnd);
    };

    const active = tabs.querySelector('.nav-link.active');
    if (active) {
        // Đặt tab đang chọn vào giữa khung nhìn khi vừa tải trang
        requestAnimationFrame(() => {
            const target = active.offsetLeft - (tabs.clientWidth - active.offsetWidth) / 2;
            tabs.scrollLeft = Math.max(0, target);
            syncMask();
        });
    }

    tabs.addEventListener('scroll', syncMask, { passive: true });
    window.addEventListener('resize', syncMask);
    syncMask();
})();
