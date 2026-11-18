<?php
/**
 * @var array $notifications
 * @var int $unread
 * @var int $page
 * @var int $totalPages
 */
?>
<div class="page-head">
    <h1><i class="bi bi-bell"></i>Thông báo</h1>
    <?php if ($unread > 0): ?>
        <button class="btn btn-outline btn-sm" id="markAllRead"><i class="bi bi-check2-all me-1"></i>Đánh dấu đã đọc</button>
    <?php endif; ?>
</div>

<div class="card surface-card">
    <div class="card-body">
        <ul class="list-unified notification-list" data-notification-list>
            <?php foreach ($notifications as $notification): ?>
                <?= view_partial(dirname(__DIR__) . '/partials/notification-item.php', ['notification' => $notification]) ?>
            <?php endforeach; ?>
        </ul>

        <?php if ($notifications === []): ?>
            <div class="empty-state" data-notification-empty>
                <span class="empty-icon"><i class="bi bi-bell-slash"></i></span>
                <h3>Bạn chưa có thông báo nào</h3>
                <p>Khi có người thích, bình luận hoặc nhắn tin cho bạn, thông báo sẽ xuất hiện ở đây.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav aria-label="Phân trang" class="pagination my-3">
        <?php if ($page > 1): ?>
            <a class="btn btn-outline btn-sm" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i> Trước</a>
        <?php endif; ?>
        <span class="btn btn-subtle btn-sm disabled">Trang <?= (int) $page ?>/<?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a class="btn btn-outline btn-sm" href="?page=<?= $page + 1 ?>">Sau <i class="bi bi-chevron-right"></i></a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
