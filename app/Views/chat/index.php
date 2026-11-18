<?php
/**
 * Trang danh sách hội thoại (/chat).
 *
 * @var array $conversations
 */
$unreadTotal = (int) \App\Models\Conversation::totalUnread(auth_id());
?>
<div class="chat-layout" data-view="list">
    <aside class="chat-sidebar card surface-card">
        <div class="chat-sidebar-head">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="rail-title mb-0">Hội thoại</div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill bg-primary d-none" data-chat-unread-total><?= $unreadTotal ?></span>
                    <button type="button" class="btn btn-subtle btn-sm chat-new-chat-btn" data-chat-new-chat aria-label="Bắt đầu cuộc trò chuyện mới" title="Nhắn tin mới">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </div>
            </div>

            <div class="chat-filter">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" class="form-control" id="chatListFilter" placeholder="Lọc hội thoại..." autocomplete="off" aria-label="Lọc hội thoại">
                <button type="button" class="chat-filter-clear" data-chat-filter-clear aria-label="Xoá bộ lọc" title="Xoá bộ lọc">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="chat-filter-tabs" role="tablist" aria-label="Bộ lọc hội thoại">
                <button type="button" class="active" data-chat-filter="all" role="tab" aria-selected="true">Tất cả</button>
                <button type="button" data-chat-filter="unread" role="tab" aria-selected="false">
                    Chưa đọc<span class="tab-count" data-chat-unread-count hidden></span>
                </button>
            </div>
        </div>

        <div class="chat-conversation-list" id="chatConversationList">
            <?= view_partial(dirname(__DIR__) . '/partials/conversation-list.php', ['conversations' => $conversations]) ?>
        </div>
    </aside>

    <section class="chat-main card surface-card">
        <div class="card-body d-flex flex-column justify-content-center h-100">
            <button type="button" class="btn btn-subtle btn-sm chat-start-close" data-chat-start-close aria-label="Đóng" title="Đóng">
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="chat-empty">
                <span class="chat-empty-mark"><i class="bi bi-chat-heart"></i></span>
                <h2 class="h4 mb-0">Bắt đầu cuộc trò chuyện mới</h2>
                <p class="fg-secondary mb-3">Nhập tên hoặc username của người bạn muốn nhắn tin. Hội thoại sẽ được lưu riêng cho từng cặp.</p>
                <div class="chat-start-search" id="chatSearchWrap">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="chatUserSearch" class="form-control" placeholder="Nhập tên hoặc username..." autocomplete="off"
                           aria-label="Tìm người dùng để nhắn tin">
                    <div class="search-results card surface-card d-none" id="chatSearchResults" role="listbox"></div>
                </div>
                <p class="mt-3 mb-0" style="font-size: 13px;">
                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Chỉ người bạn chọn mới có thể đọc tin nhắn này.
                </p>
            </div>
        </div>
    </section>
</div>
