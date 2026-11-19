<?php
/**
 * @var array $posts
 * @var int $page
 * @var int $total
 * @var int $perPage
 */
$viewerId = auth_id();
$totalPages = max(1, (int) ceil($total / $perPage));
?>
<div class="page-head">
    <h1>Bảng tin</h1>
    <?php if (is_auth()): ?>
        <a class="btn btn-solid btn-sm" href="<?= url('/posts/create') ?>"><i class="bi bi-pencil-square me-1"></i>Đăng bài</a>
    <?php endif; ?>
</div>

<?php if (!is_auth()): ?>
    <div class="panel welcome-card mb-3">
        <div class="panel-body">
            <span class="welcome-mark"><i class="bi bi-cup-hot-fill"></i></span>
            <h1 class="welcome-title">Chào mừng đến Koobecaf</h1>
            <p class="welcome-lead">
                Nơi kết nối bạn bè, chia sẻ khoảnh khắc và trò chuyện trực tiếp —
                tất cả trong một nơi duy nhất.
            </p>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <a class="btn btn-solid" href="<?= url('/register') ?>"><i class="bi bi-person-plus me-1"></i>Tạo tài khoản</a>
                <a class="btn btn-outline" href="<?= url('/login') ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập</a>
            </div>

            <ul class="welcome-features">
                <li><i class="bi bi-camera"></i> Chia sẻ khoảnh khắc</li>
                <li><i class="bi bi-chat-heart"></i> Trò chuyện riêng tư</li>
                <li><i class="bi bi-people"></i> Kết nối bạn bè</li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php if (is_auth()): ?>
    <div class="feed-quick-composer mb-3">
        <img src="<?= e(avatar_url(auth_user()['avatar_path'] ?? null)) ?>" alt="" class="quick-composer-avatar">
        <a href="<?= url('/posts/create') ?>" class="quick-composer-input">
            <span><?= e(auth_user()['display_name'] ?? '') ?>, bạn đang nghĩ gì thế?</span>
        </a>
        <a href="<?= url('/posts/create') ?>" class="quick-composer-btn" title="Đăng ảnh">
            <i class="bi bi-images text-success"></i>
            <span>Ảnh/Video</span>
        </a>
    </div>
<?php endif; ?>

<?php if ($posts === []): ?>
    <div class="panel">
        <div class="empty-state">
            <span class="empty-icon"><i class="bi bi-inbox"></i></span>
            <h3>Chưa có bài viết nào</h3>
            <p>Hãy là người đầu tiên đăng khoảnh khắc của mình lên Koobecaf.</p>
            <?php if (is_auth()): ?>
                <a class="btn btn-solid mt-2" href="<?= url('/posts/create') ?>"><i class="bi bi-pencil-square me-1"></i>Viết bài ngay</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="feed-stream" data-feed-list>
        <?php foreach ($posts as $post): ?>
            <?php $data = post_view_data($post, $viewerId); ?>
            <?= view_partial(dirname(__DIR__) . '/partials/post-card.php', ['post' => $post, 'data' => $data]) ?>
        <?php endforeach; ?>
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
<?php endif; ?>
