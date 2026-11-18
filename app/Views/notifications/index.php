<?php
/**
 * @var array $notifications
 * @var int $unread
 * @var int $page
 * @var int $totalPages
 */
$labels = [
    'like'           => 'đã thích bài viết của bạn',
    'comment'        => 'đã bình luận bài viết của bạn',
    'reply'          => 'đã trả lời bình luận của bạn',
    'share'          => 'đã chia sẻ bài viết của bạn',
    'friend_request' => 'đã gửi lời mời kết bạn',
    'friend_accepted' => 'đã chấp nhận lời mời kết bạn',
    'message'        => 'đã gửi cho bạn một tin nhắn',
    'follow'         => 'đã bắt đầu theo dõi bạn',
];

// Biểu tượng gắn với từng loại tương tác
$icons = [
    'like'           => 'bi-hand-thumbs-up-fill',
    'comment'        => 'bi-chat-fill',
    'reply'          => 'bi-reply-fill',
    'share'          => 'bi-share-fill',
    'friend_request' => 'bi-person-plus-fill',
    'friend_accepted' => 'bi-person-check-fill',
    'message'        => 'bi-envelope-fill',
    'follow'         => 'bi-person-heart',
];
?>
<div class="page-head">
    <h1><i class="bi bi-bell"></i>Thông báo</h1>
    <?php if ($unread > 0): ?>
        <button class="btn btn-outline btn-sm" id="markAllRead"><i class="bi bi-check2-all me-1"></i>Đánh dấu đã đọc</button>
    <?php endif; ?>
</div>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($notifications === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-bell-slash"></i></span>
                <h3>Bạn chưa có thông báo nào</h3>
                <p>Khi có người thích, bình luận hoặc nhắn tin cho bạn, thông báo sẽ xuất hiện ở đây.</p>
            </div>
        <?php else: ?>
            <ul class="list-unified notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <?php $isUnread = (int) $notification['is_read'] === 0; ?>
                    <li class="notification-item d-flex align-items-center gap-3 py-3<?= $isUnread ? ' unread' : '' ?>"
                        data-notification-id="<?= (int) $notification['id'] ?>">
                        <span class="notification-icon">
                            <i class="bi <?= e($icons[$notification['type']] ?? 'bi-bell-fill') ?>"></i>
                        </span>
                        <img src="<?= e(avatar_url($notification['actor_avatar_path'])) ?>" alt="" class="avatar avatar-sm">
                        <div class="person-meta">
                            <div class="small">
                                <a class="fw-semibold fg-body-emphasis text-decoration-none" href="<?= url('/u/' . $notification['actor_username']) ?>">
                                    <?= e($notification['actor_display_name']) ?>
                                </a>
                                <?= e($labels[$notification['type']] ?? 'đã tương tác với bạn') ?>
                            </div>
                            <small class="fg-secondary"><?= e(time_ago($notification['created_at'])) ?></small>
                        </div>
                        <?php if ($isUnread): ?>
                            <span class="unread-dot ms-auto" title="Chưa đọc"></span>
                        <?php endif; ?>
                        <a class="btn btn-subtle btn-sm" href="<?= e(url(\App\Controllers\NotificationController::targetUrl($notification))) ?>">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav aria-label="Phân trang" class="d-flex justify-content-center gap-2 my-3">
        <?php if ($page > 1): ?>
            <a class="btn btn-outline btn-sm" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i> Trước</a>
        <?php endif; ?>
        <span class="btn btn-subtle btn-sm disabled">Trang <?= (int) $page ?>/<?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a class="btn btn-outline btn-sm" href="?page=<?= $page + 1 ?>">Sau <i class="bi bi-chevron-right"></i></a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
