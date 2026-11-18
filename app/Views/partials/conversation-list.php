<?php
/**
 * Danh sách hội thoại — dùng chung cho trang /chat và trang /chat/{id}.
 *
 * @var array $conversations
 * @var int|null $activeId
 */
$activeId = $activeId ?? null;
?>
<?php if ($conversations === []): ?>
    <p class="fg-secondary small text-center px-3 py-4 mb-0">Chưa có cuộc trò chuyện nào.</p>
<?php else: ?>
    <?php foreach ($conversations as $conversation): ?>
        <a class="chat-conversation<?= (int) $conversation['id'] === $activeId ? ' active' : '' ?>"
           href="<?= url('/chat/' . (int) $conversation['id']) ?>">
            <img src="<?= e(avatar_url($conversation['other_avatar_path'])) ?>" alt="" class="avatar avatar-md">
            <div class="person-meta">
                <div class="d-flex justify-content-between gap-2">
                    <span class="person-name"><?= e($conversation['other_display_name']) ?></span>
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
