<?php
/**
 * @var array $conversations
 */
?>
<div class="chat-layout">
    <aside class="chat-sidebar card surface-card">
        <div class="card-body p-0 d-flex flex-column">
            <div class="p-3 border-bottom">
                <h1 class="h6 mb-0"><i class="bi bi-chat-dots me-2"></i>Tin nhắn</h1>
            </div>
            <div class="chat-conversation-list flex-grow-1">
                <?php if ($conversations === []): ?>
                    <p class="fg-secondary small text-center px-3 py-4 mb-0">Chưa có cuộc trò chuyện nào.</p>
                <?php else: ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <a class="chat-conversation" href="<?= url('/chat/' . (int) $conversation['id']) ?>">
                            <img src="<?= e(avatar_url($conversation['other_avatar_path'])) ?>" alt="" class="avatar avatar-md">
                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="fw-semibold text-truncate"><?= e($conversation['other_display_name']) ?></span>
                                    <small class="fg-secondary text-nowrap"><?= e(time_ago($conversation['last_message_at'] ?? $conversation['updated_at'])) ?></small>
                                </div>
                                <small class="fg-secondary d-block text-truncate">
                                    <?= e(excerpt($conversation['last_message'] ?? 'Bắt đầu cuộc trò chuyện', 60)) ?>
                                </small>
                            </div>
                            <?php if ((int) $conversation['unread_count'] > 0): ?>
                                <span class="badge rounded-pill bg-primary"><?= (int) $conversation['unread_count'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <section class="chat-main card surface-card">
        <div class="card-body d-flex flex-column justify-content-center">
            <div class="mx-auto text-center" style="max-width: 420px;">
                <h2 class="h4 mb-3">Bắt đầu cuộc trò chuyện mới</h2>
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
