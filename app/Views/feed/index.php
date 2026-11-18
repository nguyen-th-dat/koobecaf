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
    <div class="card surface-card mb-3" id="composer">
        <div class="card-body">
            <form action="<?= url('/posts') ?>" method="post" enctype="multipart/form-data" id="postForm" data-draft="composer" data-leave="keep" data-lose-fields="images[]" data-lose-label="Ảnh đã chọn" data-old-fields="<?= e(old_fields(['content', 'privacy'])) ?>">
                <?= \App\Core\Csrf::field() ?>
                <div class="d-flex gap-3">
                    <img src="<?= e(avatar_url($user['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-md avatar-ring">
                    <textarea name="content" class="form-control composer-input" rows="2" placeholder="Bạn đang nghĩ gì?" maxlength="5000" id="postContent"><?= e(old('content')) ?></textarea>
                </div>

                <div class="image-preview d-flex flex-wrap gap-2 mt-2 d-none" id="imagePreview"></div>

                <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top">
                    <label class="btn btn-subtle btn-sm mb-0" for="postImages">
                        <i class="bi bi-image me-1"></i>Ảnh
                        <input type="file" id="postImages" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="d-none">
                    </label>

                    <div class="d-flex align-items-center gap-2">
                        <select name="privacy" class="form-control form-control-sm w-auto" aria-label="Quyền riêng tư">
                            <option value="public" <?= old('privacy', 'public') === 'public' ? 'selected' : '' ?>>Công khai</option>
                            <option value="followers" <?= old('privacy', 'public') === 'followers' ? 'selected' : '' ?>>Người theo dõi</option>
                            <option value="private" <?= old('privacy', 'public') === 'private' ? 'selected' : '' ?>>Riêng tư</option>
                        </select>
                        <button class="btn btn-solid btn-sm" type="submit">Đăng</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card surface-card welcome-card mb-3">
        <div class="card-body">
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

<?php if ($posts === []): ?>
    <div class="card surface-card" data-feed-empty>
        <div class="empty-state">
            <span class="empty-icon"><i class="bi bi-inbox"></i></span>
            <h3>Chưa có bài viết nào</h3>
            <p>Hãy là người đầu tiên đăng khoảnh khắc của mình lên Koobecaf.</p>
            <?php if (is_auth()): ?>
                <a class="btn btn-solid mt-2" href="<?= url('/') ?>#composer"><i class="bi bi-pencil-square me-1"></i>Viết bài ngay</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div data-feed-list>
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
