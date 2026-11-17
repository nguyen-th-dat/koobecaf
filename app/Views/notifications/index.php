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
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0"><i class="bi bi-bell me-2"></i>Thông báo</h1>
    <?php if ($unread > 0): ?>
        <button class="btn btn-sm btn-outline-secondary" id="markAllRead"><i class="bi bi-check2-all me-1"></i>Đánh dấu đã đọc</button>
    <?php endif; ?>
</div>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($notifications === []): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-bell-slash display-6 d-block mb-2"></i>
                Bạn chưa có thông báo nào.
            </div>
        <?php else: ?>
            <ul class="list-unified notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <li class="notification-item d-flex gap-2 py-2<?= (int) $notification['is_read'] === 0 ? ' unread' : '' ?>"
                        data-notification-id="<?= (int) $notification['id'] ?>">
                        <img src="<?= e(upload_url($notification['actor_avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-md">
                        <div class="flex-grow-1 min-w-0">
                            <div class="small">
                                <a class="fw-semibold text-body text-decoration-none" href="<?= url('/u/' . $notification['actor_username']) ?>">
                                    <?= e($notification['actor_display_name']) ?>
                                </a>
                                <?= e($labels[$notification['type']] ?? 'đã tương tác với bạn') ?>
                            </div>
                            <small class="text-secondary"><?= e(time_ago($notification['created_at'])) ?></small>
                        </div>
                        <a class="btn btn-sm btn-light align-self-center" href="<?= e(url(\App\Controllers\NotificationController::targetUrl($notification))) ?>">
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
            <a class="btn btn-outline-secondary btn-sm" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i> Trước</a>
        <?php endif; ?>
        <span class="btn btn-light btn-sm disabled">Trang <?= (int) $page ?>/<?= (int) $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a class="btn btn-outline-secondary btn-sm" href="?page=<?= $page + 1 ?>">Sau <i class="bi bi-chevron-right"></i></a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
