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
<?php if (is_auth()): ?>
    <div class="card surface-card mb-3">
        <div class="card-body">
            <form action="<?= url('/posts') ?>" method="post" enctype="multipart/form-data" id="postForm">
                <?= \App\Core\Csrf::field() ?>
                <div class="d-flex gap-2">
                    <img src="<?= e(upload_url($user['avatar_path'] ?? null) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-md">
                    <textarea name="content" class="form-control" rows="3" placeholder="Bạn đang nghĩ gì?" maxlength="5000" id="postContent"></textarea>
                </div>

                <div class="image-preview d-flex flex-wrap gap-2 mt-2 d-none" id="imagePreview"></div>

                <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
                    <label class="btn btn-light btn-sm mb-0" for="postImages">
                        <i class="bi bi-image me-1"></i>Ảnh
                        <input type="file" id="postImages" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="d-none">
                    </label>

                    <div class="d-flex align-items-center gap-2">
                        <select name="privacy" class="form-select form-select-sm w-auto" aria-label="Quyền riêng tư">
                            <option value="public">Công khai</option>
                            <option value="followers">Người theo dõi</option>
                            <option value="private">Riêng tư</option>
                        </select>
                        <button class="btn btn-primary btn-sm" type="submit">Đăng</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card surface-card mb-3 text-center">
        <div class="card-body py-4">
            <h1 class="h4 mb-2">Chào mừng đến Koobecaf</h1>
            <p class="text-secondary">Kết nối với bạn bè, chia sẻ khoảnh khắc và trò chuyện trực tiếp.</p>
            <a class="btn btn-primary" href="<?= url('/register') ?>">Tạo tài khoản</a>
            <a class="btn btn-outline-secondary" href="<?= url('/login') ?>">Đăng nhập</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($posts === []): ?>
    <div class="card surface-card">
        <div class="card-body text-center text-secondary py-5">
            <i class="bi bi-inbox display-6 d-block mb-2"></i>
            Chưa có bài viết nào. Hãy là người đầu tiên đăng!
        </div>
    </div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?php $data = post_view_data($post, $viewerId); ?>
        <?= view_partial(dirname(__DIR__) . '/partials/post-card.php', ['post' => $post, 'data' => $data]) ?>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
        <nav aria-label="Phân trang" class="d-flex justify-content-center gap-2 my-3">
            <?php if ($page > 1): ?>
                <a class="btn btn-outline-secondary btn-sm" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i> Trước</a>
            <?php endif; ?>
            <span class="btn btn-light btn-sm disabled">Trang <?= (int) $page ?>/<?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a class="btn btn-outline-secondary btn-sm" href="?page=<?= $page + 1 ?>">Sau <i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
