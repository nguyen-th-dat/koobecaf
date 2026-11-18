<?php
/**
 * Trang trò chuyện (/chat/{id}).
 *
 * @var int $conversationId
 * @var array|null $other
 * @var array $messages
 * @var array $conversations
 */
$viewerId = auth_id();

/**
 * Gom tin nhắn theo nhóm liên tiếp cùng người gửi trong 5 phút để hiển thị
 * dạng khối liền mạch thay vì mỗi tin một bong bóng rời rạc.
 *
 * @param array $messages
 * @return array<int,array>
 */
$grouped = [];
$previous = null;
foreach ($messages as $message) {
    $senderId = (int) $message['sender_id'];
    $timestamp = strtotime((string) $message['created_at']) ?: 0;

    $sameGroup = $previous !== null
        && (int) $previous['sender_id'] === $senderId
        && $timestamp - (strtotime((string) $previous['created_at']) ?: 0) <= 300;

    $grouped[] = [
        'message'   => $message,
        'is_mine'   => $senderId === $viewerId,
        'grouped'   => $sameGroup,
        'group_end' => true, // sẽ sửa ở bước sau
    ];
    $previous = $message;
}

// Đánh dấu tin cuối cùng của mỗi nhóm.
for ($i = count($grouped) - 1; $i >= 0; $i--) {
    $next = $grouped[$i + 1] ?? null;
    if ($next !== null && $next['grouped']) {
        $grouped[$i]['group_end'] = false;
    }
}

/** Nhãn ngày cho dải phân cách giữa các ngày. */
$dayLabel = static function (string $datetime): string {
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return '';
    }
    $today = strtotime('today');
    $yesterday = strtotime('yesterday');

    if ($timestamp >= $today) {
        return 'Hôm nay';
    }
    if ($timestamp >= $yesterday) {
        return 'Hôm qua';
    }

    return date('d/m/Y', $timestamp);
};

$previousDay = null;
?>
<div class="chat-layout" data-view="conversation">
    <aside class="chat-sidebar card surface-card">
        <div class="chat-sidebar-head">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="rail-title mb-0">Hội thoại</div>
                <a class="btn btn-subtle btn-sm md:d-none" href="<?= url('/chat') ?>" aria-label="Quay lại danh sách hội thoại">
                    <i class="bi bi-arrow-left"></i>
                </a>
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
            <?= view_partial(dirname(__DIR__) . '/partials/conversation-list.php', [
                'conversations' => $conversations,
                'activeId'      => $conversationId,
            ]) ?>
        </div>
    </aside>

    <section class="chat-main card surface-card">
        <header class="chat-header d-flex align-items-center gap-3 p-3 border-bottom">
            <a class="btn btn-subtle btn-sm md:d-none flex-shrink-0" href="<?= url('/chat') ?>" aria-label="Quay lại danh sách hội thoại">
                <i class="bi bi-arrow-left"></i>
            </a>

            <?php if ($other !== null): ?>
                <a class="d-flex align-items-center gap-3 min-w-0 text-decoration-none" href="<?= url('/u/' . $other['username']) ?>">
                    <img src="<?= e(avatar_url($other['avatar_path'])) ?>" alt="" class="avatar avatar-md avatar-ring flex-shrink-0">
                    <div class="min-w-0">
                        <div class="chat-header-name text-truncate"><?= e($other['display_name']) ?></div>
                        <div class="chat-header-status">
                            <span class="chat-status-dot" data-chat-peer-status title="Chưa xác định"></span>
                            <span class="text-truncate">@<?= e($other['username']) ?></span>
                        </div>
                    </div>
                </a>

                <a class="btn icon-btn bordered ms-auto flex-shrink-0" href="<?= url('/u/' . $other['username']) ?>" title="Xem trang cá nhân" aria-label="Xem trang cá nhân">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            <?php else: ?>
                <div class="min-w-0">
                    <div class="chat-header-name text-truncate">Trò chuyện</div>
                </div>
            <?php endif; ?>
        </header>

        <div class="chat-messages" data-conversation-id="<?= (int) $conversationId ?>" id="chatMessages">
            <?php if ($grouped === []): ?>
                <div class="chat-empty">
                    <span class="chat-empty-mark"><i class="bi bi-chat-heart"></i></span>
                    <h2>Chưa có tin nhắn nào</h2>
                    <p>Hãy bắt đầu cuộc trò chuyện với <?= $other !== null ? e($other['display_name']) : 'bạn bè' ?>.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped as $entry): ?>
                    <?php
                    $message = $entry['message'];
                    $isMine = $entry['is_mine'];
                    $day = $dayLabel((string) $message['created_at']);
                    ?>
                    <?php if ($day !== $previousDay): ?>
                        <div class="chat-day-divider"><span><?= e($day) ?></span></div>
                        <?php $previousDay = $day; ?>
                    <?php endif; ?>

                    <div class="chat-bubble-row<?= $isMine ? ' mine' : '' ?><?= $entry['grouped'] ? ' grouped' : ' group-start' ?><?= $entry['group_end'] ? ' group-end' : '' ?>"
                         data-message-id="<?= (int) $message['id'] ?>">
                        <?php if (!$isMine): ?>
                            <img src="<?= e(avatar_url($message['avatar_path'])) ?>" alt="" class="avatar avatar-sm flex-shrink-0">
                        <?php endif; ?>
                        <div class="chat-bubble">
                            <div class="chat-text"><?= nl2br(e($message['body'])) ?></div>
                            <small class="chat-time">
                                <?= e(format_date($message['created_at'], 'H:i')) ?>
                                <?php if ($isMine): ?>
                                    <i class="bi <?= $message['read_at'] !== null ? 'bi-check2-all fg-primary' : 'bi-check2' ?>" data-read-mark="1" title="<?= $message['read_at'] !== null ? 'Đã xem' : 'Đã gửi' ?>"></i>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-typing d-none" id="chatTyping" aria-live="polite">
            <span class="typing-dots"><i></i><i></i><i></i></span>
            <span>đang gõ…</span>
        </div>

        <div class="chat-composer">
            <form class="chat-composer-form" id="chatForm" data-conversation-id="<?= (int) $conversationId ?>" data-draft="chat-<?= (int) $conversationId ?>">
                <div class="chat-composer-inner">
                    <textarea name="body" id="chatInput" rows="1" maxlength="5000" autocomplete="off" required
                              placeholder="Nhắn một lời chúc…" aria-label="Nội dung tin nhắn"></textarea>
                    <button class="chat-send" type="submit" aria-label="Gửi tin nhắn" title="Gửi (Enter)">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
                <div class="chat-composer-meta">
                    <span><kbd>Enter</kbd> để gửi · <kbd>Shift</kbd>+<kbd>Enter</kbd> xuống dòng</span>
                    <span class="chat-counter" data-chat-counter>0/5000</span>
                </div>
            </form>
        </div>
    </section>
</div>
