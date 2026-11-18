<?php
/**
 * Trang danh sách hội thoại (/chat).
 *
 * @var array $conversations
 */
?>
<div class="chat-layout" data-view="list">
    <aside class="chat-sidebar card surface-card">
        <div class="chat-sidebar-head">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="rail-title mb-0">Hội thoại</div>
                <span class="badge rounded-pill bg-primary d-none" data-chat-unread-total><?= (int) \App\Models\Conversation::totalUnread(auth_id()) ?></span>
            </div>

            <div class="chat-filter">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" class="form-control" id="chatListFilter" placeholder="Lọc hội thoại..." autocomplete="off" aria-label="Lọc hội thoại">
            </div>

            <div class="chat-filter-tabs" role="tablist" aria-label="Bộ lọc hội thoại">
                <button type="button" class="active" data-chat-filter="all" role="tab" aria-selected="true">Tất cả</button>
                <button type="button" data-chat-filter="unread" role="tab" aria-selected="false">Chưa đọc</button>
            </div>
        </div>

        <div class="chat-conversation-list" id="chatConversationList">
            <?= view_partial(dirname(__DIR__) . '/partials/conversation-list.php', ['conversations' => $conversations]) ?>
        </div>
    </aside>

    <section class="chat-main card surface-card">
        <div class="card-body d-flex flex-column justify-content-center">
            <div class="mx-auto text-center" style="max-width: 420px;">
                <span class="chat-empty-mark mx-auto"><i class="bi bi-chat-heart"></i></span>
                <h2 class="h4 mb-2">Bắt đầu cuộc trò chuyện mới</h2>
                <p class="fg-secondary mb-4">Nhập tên hoặc username của người bạn muốn nhắn tin. Hội thoại sẽ được lưu riêng cho từng cặp.</p>
                <div class="position-relative" id="chatSearchWrap">
                    <input type="search" id="chatUserSearch" class="form-control" placeholder="Nhập tên hoặc username..." autocomplete="off"
                           aria-label="Tìm người dùng để nhắn tin">
                    <div class="search-results card surface-card d-none" id="chatSearchResults" role="listbox"></div>
                </div>
            </div>
        </div>
    </section>
</div>
