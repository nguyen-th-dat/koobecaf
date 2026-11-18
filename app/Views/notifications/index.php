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
<div class="page-head">
    <h1><i class="bi bi-bell"></i>Thông báo</h1>
    <?php if ($unread > 0): ?>
        <button class="btn btn-outline btn-sm" id="markAllRead"><i class="bi bi-check2-all me-1"></i>Đánh dấu đã đọc</button>
    <?php endif; ?>
</div>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($notifications === []): ?>
            <div class="text-center fg-secondary py-4">
                <i class="bi bi-bell-slash d-block mb-2 fs-2xl"></i>
                Bạn chưa có thông báo nào.
            </div>
        <?php else: ?>
            <ul class="list-unified notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <li class="notification-item d-flex gap-3 py-3<?= (int) $notification['is_read'] === 0 ? ' unread' : '' ?>"
                        data-notification-id="<?= (int) $notification['id'] ?>">
                        <img src="<?= e(avatar_url($notification['actor_avatar_path'])) ?>" alt="" class="avatar avatar-md">
                        <div class="person-meta">
                            <div class="small">
                                <a class="fw-semibold fg-body-emphasis text-decoration-none" href="<?= url('/u/' . $notification['actor_username']) ?>">
                                    <?= e($notification['actor_display_name']) ?>
                                </a>
                                <?= e($labels[$notification['type']] ?? 'đã tương tác với bạn') ?>
                            </div>
                            <small class="fg-secondary"><?= e(time_ago($notification['created_at'])) ?></small>
                        </div>
                        <a class="btn btn-subtle btn-sm align-self-center" href="<?= e(url(\App\Controllers\NotificationController::targetUrl($notification))) ?>">
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
