<?php
/**
 * Danh sách hội thoại — dùng chung cho trang /chat và trang /chat/{id}.
 *
 * @var array $conversations
 * @var int|null $activeId
 */
$activeId = $activeId ?? null;
$unreadTotal = 0;
foreach ($conversations as $conversation) {
    $unreadTotal += (int) $conversation['unread_count'];
}
?>
<?php if ($conversations === []): ?>
    <div class="empty-state">
        <span class="empty-icon"><i class="bi bi-chat-dots"></i></span>
        <h3>Chưa có cuộc trò chuyện</h3>
        <p>Các cuộc trò chuyện của bạn sẽ hiển thị ở đây.</p>
    </div>
<?php else: ?>
    <?php foreach ($conversations as $conversation): ?>
        <?php $unread = (int) $conversation['unread_count']; ?>
        <a class="chat-conversation<?= (int) $conversation['id'] === $activeId ? ' active' : '' ?><?= $unread > 0 ? ' unread' : '' ?>"
           href="<?= url('/chat/' . (int) $conversation['id']) ?>"
           data-conversation-id="<?= (int) $conversation['id'] ?>"
           data-unread="<?= $unread ?>"
           data-search="<?= e(mb_strtolower(trim($conversation['other_display_name'] . ' ' . $conversation['other_username']))) ?>">
            <span class="chat-conversation-avatar">
                <img src="<?= e(avatar_url($conversation['other_avatar_path'])) ?>" alt="" class="avatar avatar-md">
                <?php if ($unread > 0): ?>
                    <span class="chat-presence-dot" aria-hidden="true"></span>
                <?php endif; ?>
            </span>

            <span class="chat-conversation-body">
                <span class="chat-conversation-top">
                    <span class="chat-conversation-name"><?= e($conversation['other_display_name']) ?></span>
                    <small class="chat-conversation-time"><?= e(time_ago($conversation['last_message_at'] ?? $conversation['updated_at'])) ?></small>
                </span>
                <span class="chat-conversation-bottom">
                    <small class="chat-preview">
                        <?= e(excerpt($conversation['last_message'] ?? 'Bắt đầu cuộc trò chuyện', 60)) ?>
                    </small>
                    <?php if ($unread > 0): ?>
                        <span class="chat-unread"><?= $unread > 99 ? '99+' : $unread ?></span>
                    <?php endif; ?>
                </span>
            </span>
        </a>
    <?php endforeach; ?>
<?php endif; ?>
