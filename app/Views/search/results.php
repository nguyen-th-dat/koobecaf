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
<div class="page-head">
    <h1>
        <i class="bi bi-search"></i>
        <?= $keyword === '' ? 'Tìm kiếm' : 'Kết quả cho "' . e($keyword) . '"' ?>
    </h1>
</div>

<?php if ($keyword === ''): ?>
    <div class="card surface-card"><div class="card-body text-center fg-secondary py-5">Nhập từ khóa để tìm kiếm người dùng và bài viết.</div></div>
<?php else: ?>
    <div class="card surface-card mb-3">
        <div class="card-body">
            <div class="rail-title">Người dùng</div>
            <?php if ($users === []): ?>
                <p class="fg-secondary small mb-0">Không tìm thấy người dùng nào.</p>
            <?php else: ?>
                <?php foreach ($users as $person): ?>
                    <?php
                    $actions = '';
                    if (auth_id() !== null && (int) $person['id'] !== auth_id()) {
                        $actions = '<a class="btn icon-btn bordered" href="' . url('/chat/start/' . (int) $person['id']) . '" title="Nhắn tin"><i class="bi bi-chat-dots"></i></a>';
                    }
                    ?>
                    <?= view_partial(dirname(__DIR__) . '/partials/person-row.php', ['person' => $person, 'actionHtml' => $actions]) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="rail-title mb-2">Bài viết (<?= (int) $totalPosts ?>)</div>

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
