<?php
/**
 * @var int $conversationId
 * @var array|null $other
 * @var array $messages
 * @var array $conversations
 */
$viewerId = auth_id();
?>
<div class="chat-layout">
    <aside class="chat-sidebar card surface-card d-none md:d-block">
        <div class="card-body p-0">
            <div class="p-3 border-bottom">
                <h1 class="h6 mb-0"><i class="bi bi-chat-dots me-2"></i>Tin nhắn</h1>
            </div>
            <div class="chat-conversation-list">
                <?php foreach ($conversations as $conversation): ?>
                    <a class="chat-conversation<?= (int) $conversation['id'] === $conversationId ? ' active' : '' ?>"
                       href="<?= url('/chat/' . (int) $conversation['id']) ?>">
                        <img src="<?= e(upload_url($conversation['other_avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-md">
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold text-truncate"><?= e($conversation['other_display_name']) ?></span>
                                <small class="fg-secondary text-nowrap"><?= e(time_ago($conversation['last_message_at'] ?? $conversation['updated_at'])) ?></small>
                            </div>
                            <small class="fg-secondary d-block text-truncate"><?= e(excerpt($conversation['last_message'] ?? '', 40)) ?></small>
                        </div>
                        <?php if ((int) $conversation['unread_count'] > 0): ?>
                            <span class="badge rounded-pill bg-primary"><?= (int) $conversation['unread_count'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <section class="chat-main card surface-card">
        <div class="chat-header d-flex align-items-center gap-2 p-3 border-bottom">
            <a class="btn btn-subtle btn-sm md:d-none" href="<?= url('/chat') ?>"><i class="bi bi-arrow-left"></i></a>
            <?php if ($other !== null): ?>
                <img src="<?= e(upload_url($other['avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-md">
                <div class="min-w-0">
                    <div class="fw-semibold text-truncate"><?= e($other['display_name']) ?></div>
                    <small class="fg-secondary">@<?= e($other['username']) ?></small>
                </div>
            <?php endif; ?>
        </div>

        <div class="chat-messages flex-grow-1 p-3" data-conversation-id="<?= (int) $conversationId ?>">
            <?php if ($messages === []): ?>
                <p class="fg-secondary text-center small">Chưa có tin nhắn nào. Hãy bắt đầu cuộc trò chuyện!</p>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <?php $isMine = (int) $message['sender_id'] === $viewerId; ?>
                    <div class="chat-bubble-row <?= $isMine ? 'mine' : '' ?>" data-message-id="<?= (int) $message['id'] ?>">
                        <?php if (!$isMine): ?>
                            <img src="<?= e(upload_url($message['avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-sm">
                        <?php endif; ?>
                        <div class="chat-bubble">
                            <div class="chat-text"><?= nl2br(e($message['body'])) ?></div>
                            <small class="chat-time">
                                <?= e(format_date($message['created_at'], 'H:i')) ?>
                                <?php if ($isMine): ?>
                                    <i class="bi <?= $message['read_at'] !== null ? 'bi-check2-all fg-primary' : 'bi-check2' ?>" title="<?= $message['read_at'] !== null ? 'Đã xem' : 'Đã gửi' ?>"></i>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form class="chat-input d-flex gap-2 p-3 border-top" id="chatForm" data-conversation-id="<?= (int) $conversationId ?>">
            <input type="text" class="form-control" name="body" placeholder="Nhập tin nhắn..." maxlength="5000" autocomplete="off" required>
            <button class="btn btn-solid" type="submit"><i class="bi bi-send"></i><span class="d-none sm:d-inline ms-1">Gửi</span></button>
        </form>
    </section>
</div>
