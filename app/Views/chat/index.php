<?php
/**
 * @var array $conversations
 */
?>
<h1 class="h5 mb-3"><i class="bi bi-chat-dots me-2"></i>Tin nhắn</h1>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($conversations === []): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-chat-square-dots display-6 d-block mb-2"></i>
                Chưa có cuộc trò chuyện nào. Hãy bắt đầu từ trang cá nhân của một người bạn.
            </div>
        <?php else: ?>
            <ul class="list-unified">
                <?php foreach ($conversations as $conversation): ?>
                    <li>
                        <a class="d-flex align-items-center gap-2 py-2 text-body text-decoration-none" href="<?= url('/chat/' . (int) $conversation['id']) ?>">
                            <img src="<?= e(upload_url($conversation['other_avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-md">
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="fw-semibold text-truncate"><?= e($conversation['other_display_name']) ?></span>
                                    <small class="text-secondary text-nowrap"><?= e(time_ago($conversation['last_message_at'] ?? $conversation['updated_at'])) ?></small>
                                </div>
                                <small class="text-secondary d-block text-truncate">
                                    <?= e(excerpt($conversation['last_message'] ?? 'Bắt đầu cuộc trò chuyện', 60)) ?>
                                </small>
                            </div>
                            <?php if ((int) $conversation['unread_count'] > 0): ?>
                                <span class="badge text-bg-danger rounded-pill"><?= (int) $conversation['unread_count'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
