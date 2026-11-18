<?php
/**
 * @var array $conversations
 */
?>
<h1 class="h3 mb-3"><i class="bi bi-chat-dots me-2"></i>Tin nhắn</h1>

<div class="card surface-card mb-3">
    <div class="card-body">
        <label class="form-label" for="chatUserSearch"><i class="bi bi-person-plus me-1"></i>Bắt đầu cuộc trò chuyện mới</label>
        <div class="position-relative" id="chatSearchWrap">
            <input type="search" id="chatUserSearch" class="form-control" placeholder="Nhập tên hoặc username..." autocomplete="off"
                   aria-label="Tìm người dùng để nhắn tin">
            <div class="search-results card surface-card d-none" id="chatSearchResults" role="listbox"></div>
        </div>
    </div>
</div>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($conversations === []): ?>
            <div class="text-center fg-secondary py-4">
                <i class="bi bi-chat-square-dots d-block mb-2 fs-2xl"></i>
                Chưa có cuộc trò chuyện nào. Hãy tìm một người ở ô bên trên để bắt đầu.
            </div>
        <?php else: ?>
            <ul class="list-unified">
                <?php foreach ($conversations as $conversation): ?>
                    <li>
                        <a class="d-flex align-items-center gap-2 py-2 fg-body text-decoration-none" href="<?= url('/chat/' . (int) $conversation['id']) ?>">
                            <img src="<?= e(avatar_url($conversation['other_avatar_path'])) ?>" alt="" class="avatar avatar-md">
                            <div class="flex-grow-1 min-w-0">
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
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
