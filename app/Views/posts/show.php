<?php
/**
 * @var array $post
 * @var array $images
 * @var array $comments
 * @var bool $isOwner
 */
$viewerId = auth_id();
$data = post_view_data($post, $viewerId);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-chat-square-text me-2"></i>Bài viết</h1>
    <a class="btn btn-outline btn-sm" href="<?= url('/') ?>"><i class="bi bi-arrow-left"></i> Trở lại</a>
</div>

<?= view_partial(dirname(__DIR__) . '/partials/post-card.php', ['post' => $post, 'data' => $data]) ?>

<div class="card surface-card mb-3" id="comments">
    <div class="card-body">
        <h2 class="h6 mb-3"><i class="bi bi-chat-left-text me-2"></i>Bình luận (<?= count($comments) ?>)</h2>

        <?php if (is_auth()): ?>
            <form class="comment-form d-flex gap-2 mb-3" data-post-id="<?= (int) $post['id'] ?>">
                <img src="<?= e(avatar_url(auth_user()['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-sm">
                <div class="flex-grow-1">
                    <textarea name="content" class="form-control form-control-sm" rows="2" placeholder="Viết bình luận..." maxlength="2000" required></textarea>
                    <input type="hidden" name="parent_id" value="">
                </div>
                <button class="btn btn-solid btn-sm align-self-start" type="submit">Gửi</button>
            </form>
        <?php else: ?>
            <div class="comment-login-prompt mb-3">
                <p class="fg-secondary small mb-2">Đăng nhập để tham gia thảo luận với bài viết này.</p>
                <a class="btn btn-solid btn-sm" href="<?= url('/login') ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập</a>
            </div>
        <?php endif; ?>

        <div class="comment-list">
            <?php if ($comments === []): ?>
                <p class="fg-secondary small mb-0">Chưa có bình luận nào.</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <?= view_partial(dirname(__DIR__) . '/partials/comment-item.php', ['comment' => $comment]) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
