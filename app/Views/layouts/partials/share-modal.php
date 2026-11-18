<?php
/**
 * Hộp thoại chia sẻ dùng chung cho toàn bộ website.
 *
 * Một modal duy nhất phục vụ mọi thẻ bài viết trong feed lẫn trang chi tiết.
 * Nội dung bài viết được nạp động qua API nên partial này không cần biết
 * trước bài viết nào đang được chia sẻ.
 *
 * Chỉ người đã đăng nhập mới có hộp thoại này.
 */
if (!is_auth()) {
    return;
}
?>
<div class="share-modal" id="kbShareModal" hidden role="dialog" aria-modal="true" aria-labelledby="kbShareModalTitle">
    <div class="share-modal-backdrop" data-share-modal-close></div>

    <div class="share-modal-card" role="document">
        <header class="share-modal-head">
            <h2 class="share-modal-title" id="kbShareModalTitle">
                <i class="bi bi-share"></i>
                <span>Chia sẻ</span>
            </h2>
            <button class="btn icon-btn share-modal-close" type="button" data-share-modal-close aria-label="Đóng">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <div class="share-modal-preview" data-share-preview>
            <span class="share-modal-preview-icon"><i class="bi bi-file-earmark-text"></i></span>
            <div class="share-modal-preview-text">
                <strong data-share-preview-author></strong>
                <span data-share-preview-excerpt></span>
            </div>
        </div>

        <nav class="share-modal-tabs" role="tablist">
            <button class="share-modal-tab is-active" type="button" role="tab" data-share-tab="feed" aria-selected="true">
                <i class="bi bi-house"></i><span>Trang cá nhân</span>
            </button>
            <button class="share-modal-tab" type="button" role="tab" data-share-tab="message" aria-selected="false">
                <i class="bi bi-chat-dots"></i><span>Tin nhắn</span>
            </button>
            <button class="share-modal-tab" type="button" role="tab" data-share-tab="link" aria-selected="false">
                <i class="bi bi-link-45deg"></i><span>Sao chép link</span>
            </button>
        </nav>

        <!-- Chia sẻ lên trang cá nhân của người dùng -->
        <div class="share-modal-panel is-active" data-share-panel="feed">
            <div class="share-modal-composer">
                <img src="<?= e(avatar_url(auth_user()['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-sm">
                <textarea class="form-control" data-share-text rows="3" maxlength="1000"
                          placeholder="Thêm lời nhắn của bạn (không bắt buộc)..."></textarea>
            </div>

            <div class="share-modal-privacy" role="radiogroup" aria-label="Quyền riêng tư">
                <button class="share-privacy-option is-active" type="button" role="radio" aria-checked="true" data-share-privacy="public">
                    <i class="bi bi-globe2"></i>
                    <span class="share-privacy-label">Công khai</span>
                    <span class="share-privacy-desc">Mọi người đều xem được</span>
                </button>
                <button class="share-privacy-option" type="button" role="radio" aria-checked="false" data-share-privacy="followers">
                    <i class="bi bi-people"></i>
                    <span class="share-privacy-label">Người theo dõi</span>
                    <span class="share-privacy-desc">Chỉ người theo dõi bạn</span>
                </button>
                <button class="share-privacy-option" type="button" role="radio" aria-checked="false" data-share-privacy="private">
                    <i class="bi bi-lock"></i>
                    <span class="share-privacy-label">Riêng tư</span>
                    <span class="share-privacy-desc">Chỉ bạn mới thấy</span>
                </button>
            </div>

            <div class="share-modal-actions">
                <button class="btn btn-subtle btn-sm" type="button" data-share-modal-close>Hủy</button>
                <button class="btn btn-solid btn-sm" type="button" data-share-submit>
                    <i class="bi bi-send me-1"></i>Chia sẻ ngay
                </button>
            </div>
        </div>

        <!-- Gửi bài viết cho bạn bè qua tin nhắn -->
        <div class="share-modal-panel" data-share-panel="message">
            <div class="share-modal-search">
                <i class="bi bi-search"></i>
                <input type="search" class="form-control" data-share-search placeholder="Tìm bạn bè hoặc người theo dõi...">
            </div>

            <div class="share-modal-people" data-share-people>
                <div class="share-modal-loading">
                    <span class="share-spinner" aria-hidden="true"></span>
                    <span>Đang tải danh sách...</span>
                </div>
            </div>
        </div>

        <!-- Sao chép liên kết -->
        <div class="share-modal-panel" data-share-panel="link">
            <p class="share-modal-hint">
                <i class="bi bi-info-circle me-1"></i>
                Sao chép liên kết để gửi bài viết này cho bất kỳ ai qua kênh khác.
            </p>
            <div class="share-modal-link">
                <input type="text" class="form-control" data-share-link readonly>
                <button class="btn btn-solid btn-sm" type="button" data-share-copy>
                    <i class="bi bi-clipboard me-1"></i>Sao chép
                </button>
            </div>
        </div>
    </div>
</div>
