/**
 * Koobecaf — Hệ thống thông báo Toast.
 *
 * Một nơi duy nhất để hiển thị thông báo trên toàn bộ website:
 *   - Thông báo flash từ PHP được render sẵn trong container, script này
 *     tiếp quản vòng đời của chúng.
 *   - Thông báo phát sinh từ client gọi showToast(type, message).
 *
 * Không dùng alert()/prompt()/confirm() của trình duyệt.
 */

/* =============================================================
   Cấu hình
   ============================================================= */
const TOAST_TYPES = ['success', 'error', 'warning', 'info'];

const TOAST_ICONS = {
    success: 'bi-check-circle-fill',
    error: 'bi-exclamation-octagon-fill',
    warning: 'bi-exclamation-triangle-fill',
    info: 'bi-info-circle-fill',
};

/** Thời gian tự ẩn (ms) theo từng loại — nằm trong khoảng 4–5 giây. */
const TOAST_DURATION = {
    success: 4500,
    error: 5000,
    warning: 5000,
    info: 4000,
};

const TOAST_DEFAULT_DURATION = 4500;

/** Số toast tối đa hiển thị cùng lúc. */
const TOAST_MAX = 5;

/** Khoảng thời gian (ms) mà một thông báo trùng lặp sẽ bị bỏ qua. */
const TOAST_DUPLICATE_WINDOW = 2000;

/* =============================================================
   Trạng thái
   ============================================================= */
/** @type {Map<string, number>} type + message → timestamp gần nhất */
const toastHistory = new Map();

/* =============================================================
   Tiện ích
   ============================================================= */
function toastContainer() {
    let container = document.getElementById('kbToastContainer');
    if (container) return container;

    container = document.createElement('div');
    container.id = 'kbToastContainer';
    container.className = 'toast-container';
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-atomic', 'false');
    document.body.appendChild(container);

    return container;
}

function normalizeType(type) {
    const value = String(type ?? '').trim().toLowerCase();
    if (value === 'danger') return 'error';
    return TOAST_TYPES.includes(value) ? value : 'info';
}

function durationFor(type) {
    return TOAST_DURATION[type] ?? TOAST_DEFAULT_DURATION;
}

/**
 * Kiểm tra thông báo có trùng với thông báo vừa hiển thị hay không.
 *
 * Ngăn tình trạng thông báo giống nhau lặp liên tục (ví dụ bấm nút nhiều lần).
 */
function isDuplicate(type, message) {
    const key = `${type}::${message}`;
    const last = toastHistory.get(key);

    if (last !== undefined && Date.now() - last < TOAST_DUPLICATE_WINDOW) {
        return true;
    }

    toastHistory.set(key, Date.now());

    // Dọn bản ghi cũ để bản đồ không phình vô hạn.
    for (const [itemKey, time] of toastHistory) {
        if (Date.now() - time > TOAST_DUPLICATE_WINDOW) {
            toastHistory.delete(itemKey);
        }
    }

    return false;
}

/**
 * Xóa các toast cũ nhất khi vượt quá giới hạn.
 */
function trimToasts(container) {
    const toasts = Array.from(container.querySelectorAll('[data-toast]'));
    while (toasts.length > TOAST_MAX) {
        const oldest = toasts.shift();
        if (oldest) oldest.remove();
    }
}

/* =============================================================
   Vòng đời một toast
   ============================================================= */

/**
 * Gắn bộ đếm thời gian, nút đóng và hành vi tạm dừng khi rê chuột.
 *
 * @param {HTMLElement} el
 * @param {number} duration
 */
function attachToastBehaviour(el, duration) {
    let remaining = duration;
    let startedAt = 0;
    let timer = null;
    let paused = false;

    const start = () => {
        if (paused || remaining <= 0) return;
        startedAt = Date.now();
        timer = window.setTimeout(() => dismiss(el), remaining);
    };

    const pause = () => {
        if (timer !== null) {
            window.clearTimeout(timer);
            timer = null;
        }
        if (startedAt > 0) {
            remaining -= Date.now() - startedAt;
            startedAt = 0;
        }
        paused = true;
    };

    const resume = () => {
        if (!paused) return;
        paused = false;
        start();
    };

    el.addEventListener('mouseenter', pause);
    el.addEventListener('mouseleave', resume);
    el.addEventListener('focusin', pause);
    el.addEventListener('focusout', resume);

    // Đóng khi focus rời khỏi toast (hỗ trợ điều hướng bằng bàn phím).
    el.addEventListener('focusout', (event) => {
        if (!el.contains(event.relatedTarget)) {
            resume();
        }
    });

    const closeButton = el.querySelector('.toast-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => dismiss(el));
    }

    start();
}

/**
 * Ẩn một toast với hiệu ứng rời đi.
 *
 * @param {HTMLElement} el
 */
function dismiss(el) {
    if (!el || !el.isConnected || el.classList.contains('is-leaving')) {
        return;
    }

    el.classList.add('is-leaving');

    const remove = () => el.remove();
    el.addEventListener('animationend', remove, { once: true });
    // Dự phòng cho trình duyệt không hỗ trợ animation.
    window.setTimeout(remove, 400);
}

/* =============================================================
   API công khai
   ============================================================= */

/**
 * Hiển thị một toast.
 *
 * @param {string} type    success | error | warning | info
 * @param {string} message Nội dung thông báo
 * @param {number} [duration] Thời gian tự ẩn (ms), mặc định theo loại
 */
function showToast(type, message, duration) {
    const text = String(message ?? '').trim();
    if (text === '') return null;

    const toastType = normalizeType(type);

    if (isDuplicate(toastType, text)) {
        return null;
    }

    const container = toastContainer();

    const el = document.createElement('div');
    el.className = `toast is-${toastType}`;
    el.setAttribute('role', toastType === 'error' ? 'alert' : 'status');
    el.setAttribute('data-toast', '');

    const icon = document.createElement('i');
    icon.className = `bi ${TOAST_ICONS[toastType]} toast-icon`;
    icon.setAttribute('aria-hidden', 'true');

    const body = document.createElement('div');
    body.className = 'toast-body';
    // Dùng textContent để nội dung không bao giờ bị diễn giải thành HTML (chống XSS).
    body.textContent = text;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close toast-close';
    close.setAttribute('aria-label', 'Đóng thông báo');

    el.append(icon, body, close);

    // Chèn đầu danh sách → hiện ngay dưới cùng, đẩy các toast cũ lên trên.
    container.prepend(el);

    trimToasts(container);
    attachToastBehaviour(el, Number(duration) > 0 ? Number(duration) : durationFor(toastType));

    return el;
}

/**
 * Đóng toàn bộ toast đang hiển thị.
 */
function clearToasts() {
    const container = document.getElementById('kbToastContainer');
    if (!container) return;

    container.querySelectorAll('[data-toast]').forEach((el) => dismiss(el));
}

/* =============================================================
   Khởi tạo
   ============================================================= */
function initToasts() {
    const container = toastContainer();

    // Kích hoạt vòng đời cho các toast do PHP render sẵn trong trang.
    container.querySelectorAll('[data-toast]').forEach((el) => {
        const type = normalizeType(el.className.match(/is-(\w+)/)?.[1]);
        attachToastBehaviour(el, durationFor(type));
    });
}

// Toast đã render sẵn bởi PHP cần bộ đếm chạy ngay, không chờ DOMContentLoaded.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initToasts);
} else {
    initToasts();
}

// Công khai ra window để mọi trang / script khác dùng được.
window.showToast = showToast;
window.kbToast = showToast;
window.clearToasts = clearToasts;
