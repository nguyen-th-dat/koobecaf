<?php
/**
 * @var array $conversations
 */
?>
<div class="chat-layout">
    <aside class="chat-sidebar card surface-card">
        <div class="card-body p-0 d-flex flex-column">
            <div class="p-3 border-bottom">
                <div class="rail-title mb-0">Hội thoại</div>
            </div>
            <div class="chat-conversation-list flex-grow-1">
                <?= view_partial(dirname(__DIR__) . '/partials/conversation-list.php', ['conversations' => $conversations]) ?>
            </div>
        </div>
    </aside>

    <section class="chat-main card surface-card">
        <div class="card-body d-flex flex-column justify-content-center">
            <div class="mx-auto text-center" style="max-width: 420px;">
                <span class="empty-icon mx-auto"><i class="bi bi-chat-heart"></i></span>
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
