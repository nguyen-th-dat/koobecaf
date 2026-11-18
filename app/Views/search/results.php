<?php
/**
 * @var string $keyword
 * @var array $users
 * @var array $posts
 * @var int $page
 * @var int $perPage
 * @var int $totalPosts
 */
$viewerId = auth_id();
$totalPages = max(1, (int) ceil($totalPosts / $perPage));
?>
<h1 class="h3 mb-3">
    <i class="bi bi-search me-2"></i>
    <?= $keyword === '' ? 'Tìm kiếm' : 'Kết quả cho "' . e($keyword) . '"' ?>
</h1>

<?php if ($keyword === ''): ?>
    <div class="card surface-card"><div class="card-body text-center fg-secondary py-5">Nhập từ khóa để tìm kiếm người dùng và bài viết.</div></div>
<?php else: ?>
    <div class="card surface-card mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3"><i class="bi bi-people me-2"></i>Người dùng</h2>
            <?php if ($users === []): ?>
                <p class="fg-secondary small mb-0">Không tìm thấy người dùng nào.</p>
            <?php else: ?>
                <ul class="list-unified">
                    <?php foreach ($users as $person): ?>
                        <li class="d-flex align-items-center gap-2 py-2">
                            <img src="<?= e(avatar_url($person['avatar_path'])) ?>" alt="" class="avatar avatar-md">
                            <div class="flex-grow-1 min-w-0">
                                <a class="d-block fw-semibold fg-body-emphasis text-decoration-none text-truncate" href="<?= url('/u/' . $person['username']) ?>">
                                    <?= e($person['display_name']) ?>
                                </a>
                                <small class="fg-secondary">@<?= e($person['username']) ?></small>
                            </div>
                            <?php if (auth_id() !== null && (int) $person['id'] !== auth_id()): ?>
                                <a class="btn btn-outline btn-sm" href="<?= url('/chat/start/' . (int) $person['id']) ?>">
                                    <i class="bi bi-chat-dots"></i>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <h2 class="h6 mb-3"><i class="bi bi-file-text me-2"></i>Bài viết (<?= (int) $totalPosts ?>)</h2>

    <?php if ($posts === []): ?>
        <div class="card surface-card"><div class="card-body text-center fg-secondary py-4">Không tìm thấy bài viết nào.</div></div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <?php $data = post_view_data($post, $viewerId); ?>
            <?= view_partial(dirname(__DIR__) . '/partials/post-card.php', ['post' => $post, 'data' => $data]) ?>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Phân trang" class="d-flex justify-content-center gap-2 my-3">
                <?php if ($page > 1): ?>
                    <a class="btn btn-outline btn-sm" href="?q=<?= urlencode($keyword) ?>&page=<?= $page - 1 ?>">Trước</a>
                <?php endif; ?>
                <span class="btn btn-subtle btn-sm disabled">Trang <?= (int) $page ?>/<?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a class="btn btn-outline btn-sm" href="?q=<?= urlencode($keyword) ?>&page=<?= $page + 1 ?>">Sau</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
