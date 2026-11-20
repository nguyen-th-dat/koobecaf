<?php
/**
 * Trang chi tiết một bài viết.
 *
 * Bố cục: thanh tiến độ đọc + thẻ bài viết (tác giả / nội dung / ảnh / hành động)
 * rồi tới khu bình luận. Cột phụ bên phải do layout dựng (partials/post-rail.php).
 *
 * @var array $post
 * @var array $images
 * @var array $comments
 * @var bool $isOwner
 * @var array|null $author
 * @var bool $isFollowing
 * @var array $likers
 * @var array $related
 */
$viewerId = auth_id();
$data = post_view_data($post, $viewerId);
$postId = (int) $post['id'];
$author = $author ?? null;
$authorName = $author['display_name'] ?? $post['display_name'];
$authorBio = trim((string) ($author['bio'] ?? ''));
$isSelf = $viewerId !== null && $viewerId === (int) $post['user_id'];
?>
<div class="post-detail-progress" data-read-progress aria-hidden="true"><span data-read-bar></span></div>

<nav class="post-detail-crumb" aria-label="Đường dẫn">
    <a class="post-detail-back" href="<?= url('/') ?>"><i class="bi bi-arrow-left"></i><span>Bảng tin</span></a>
    <span class="post-detail-crumb-sep">/</span>
    <a href="<?= url('/u/' . $post['username']) ?>">@<?= e($post['username']) ?></a>
    <span class="post-detail-crumb-sep">/</span>
    <span class="post-detail-crumb-current">Bài viết #<?= $postId ?></span>
</nav>

<article class="post-detail panel" data-post-id="<?= $postId ?>">
    <header class="post-detail-head">
        <a class="post-detail-avatar" href="<?= url('/u/' . $post['username']) ?>">
            <img src="<?= e(avatar_url($post['avatar_path'])) ?>" alt="<?= e($authorName) ?>" class="avatar avatar-lg avatar-ring">
        </a>

        <div class="post-detail-head-text">
            <div class="post-detail-name-row">
                <a class="post-detail-name" href="<?= url('/u/' . $post['username']) ?>"><?= e($authorName) ?></a>
                <?php if ($post['privacy'] !== 'public'): ?>
                    <span class="post-detail-privacy" title="<?= $post['privacy'] === 'private' ? 'Riêng tư' : 'Người theo dõi' ?>">
                        <i class="bi bi-<?= $post['privacy'] === 'private' ? 'lock' : 'people' ?>"></i>
                        <?= $post['privacy'] === 'private' ? 'Riêng tư' : 'Người theo dõi' ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="post-detail-meta">
                <span>@<?= e($post['username']) ?></span>
                <span class="post-detail-dot"></span>
                <time datetime="<?= e($post['created_at']) ?>"><?= e(time_ago($post['created_at'])) ?></time>
                <span class="post-detail-dot"></span>
                <span title="<?= e(format_date($post['created_at'], 'd/m/Y \lúc H:i')) ?>"><?= e(format_date($post['created_at'], 'd/m/Y')) ?></span>
            </div>
        </div>

        <div class="post-detail-head-actions">
            <?php if (is_auth() && !$isSelf): ?>
                <button class="btn <?= $isFollowing ? 'btn-solid' : 'btn-outline' ?> btn-sm post-detail-follow"
                        data-action="follow" data-user-id="<?= (int) $post['user_id'] ?>">
                    <i class="bi bi-person<?= $isFollowing ? '-check' : '-plus' ?>"></i>
                    <span><?= $isFollowing ? 'Đang theo dõi' : 'Theo dõi' ?></span>
                </button>
            <?php elseif (is_auth()): ?>
                <a class="btn btn-outline btn-sm" href="<?= url('/posts/' . $postId . '/edit') ?>">
                    <i class="bi bi-pencil"></i><span>Sửa</span>
                </a>
            <?php endif; ?>

            <button class="btn icon-btn" type="button" data-menu-toggle data-menu-placement="bottom-end" aria-label="Tuỳ chọn">
                <i class="bi bi-three-dots"></i>
            </button>
            <ul class="menu">
                <?php if ($isOwner): ?>
                    <li><a class="menu-item" href="<?= url('/posts/' . $postId . '/edit') ?>"><i class="bi bi-pencil me-2"></i>Chỉnh sửa</a></li>
                <?php endif; ?>
                <li>
                    <button type="button" class="menu-item" data-action="copy-link" data-url="<?= e($data['permalink']) ?>">
                        <i class="bi bi-link-45deg me-2"></i>Sao chép link
                    </button>
                </li>
                <?php if ($isOwner): ?>
                    <li><hr class="menu-divider"></li>
                    <li>
                        <form action="<?= url('/posts/' . $postId . '/delete') ?>" method="post" data-confirm="Bạn có chắc muốn xóa bài viết này? Hành động này không thể hoàn tác." data-confirm-title="Xóa bài viết" data-confirm-accept="Xóa bài viết">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="menu-item fg-danger w-100"><i class="bi bi-trash me-2"></i>Xóa bài viết</button>
                        </form>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <?php if ($data['isShared'] && $data['original'] !== null): ?>
        <?php
        $original = $data['original'];
        $originalImages = $data['originalImages'];
        ?>
        <div class="post-detail-shared">
            <div class="post-detail-shared-head">
                <img src="<?= e(avatar_url($original['avatar_path'])) ?>" alt="" class="avatar avatar-sm avatar-ring">
                <div class="min-w-0">
                    <a class="post-author" href="<?= url('/u/' . $original['username']) ?>"><?= e($original['display_name']) ?></a>
                    <small class="post-meta d-block">@<?= e($original['username']) ?> · <?= e(time_ago($original['created_at'])) ?></small>
                </div>
            </div>
            <?php if (trim((string) $original['content']) !== ''): ?>
                <p class="post-text mb-2"><?= nl2br(e($original['content'])) ?></p>
            <?php endif; ?>
            <?php if ($originalImages !== []): ?>
                <div class="post-images count-<?= count($originalImages) ?>">
                    <?php foreach ($originalImages as $originalImage): ?>
                        <img src="<?= e(upload_url($originalImage['path'])) ?>" alt="" loading="lazy">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (trim((string) $post['content']) !== ''): ?>
        <div class="post-detail-content">
            <p class="post-detail-text" data-clampable><?= nl2br(e($post['content'])) ?></p>
        </div>
    <?php elseif ($data['isShared'] && trim((string) $post['share_content']) !== ''): ?>
        <div class="post-detail-content">
            <p class="post-detail-text"><?= nl2br(e($post['share_content'])) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($images !== []): ?>
        <div class="post-detail-images count-<?= count($images) ?>">
            <?php foreach ($images as $postImage): ?>
                <button class="post-detail-image" type="button" data-lightbox="<?= e(upload_url($postImage['path'])) ?>">
                    <img src="<?= e(upload_url($postImage['path'])) ?>" alt="Ảnh của bài viết" loading="lazy">
                    <span class="post-detail-image-zoom"><i class="bi bi-arrows-fullscreen"></i></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="post-detail-stats">
        <a class="post-detail-stat" href="<?= url('/posts/' . $postId . '/likes') ?>">
            <span class="post-detail-stat-icon"><i class="bi bi-hand-thumbs-up"></i></span>
            <span data-like-count><?= (int) $data['likeCount'] ?></span>
            <span class="post-detail-stat-label">lượt thích</span>
        </a>
        <a class="post-detail-stat" href="#comments">
            <span class="post-detail-stat-icon"><i class="bi bi-chat"></i></span>
            <span data-comment-count><?= (int) $data['commentCount'] ?></span>
            <span class="post-detail-stat-label">bình luận</span>
        </a>
        <span class="post-detail-stat">
            <span class="post-detail-stat-icon"><i class="bi bi-share"></i></span>
            <span data-share-count><?= (int) $data['shareCount'] ?></span>
            <span class="post-detail-stat-label">lượt chia sẻ</span>
        </span>
    </div>

    <div class="post-detail-actions">
        <?php if (is_auth()): ?>
            <button class="post-detail-action<?= $data['liked'] ? ' active' : '' ?>" type="button" data-action="like" data-post-id="<?= $postId ?>">
                <i class="bi bi-hand-thumbs-up<?= $data['liked'] ? '-fill' : '' ?>"></i><span>Thích</span>
            </button>
            <button class="post-detail-action" type="button" data-action="comment" data-post-id="<?= $postId ?>">
                <i class="bi bi-chat"></i><span>Bình luận</span>
            </button>
            <button class="post-detail-action" type="button" data-action="share" data-post-id="<?= $postId ?>">
                <i class="bi bi-share"></i><span>Chia sẻ</span>
            </button>
        <?php else: ?>
            <a class="post-detail-action" href="<?= url('/login') ?>">
                <i class="bi bi-hand-thumbs-up"></i><span>Thích</span>
            </a>
            <a class="post-detail-action" href="#comments">
                <i class="bi bi-chat"></i><span>Bình luận</span>
            </a>
            <a class="post-detail-action" href="<?= url('/login') ?>">
                <i class="bi bi-share"></i><span>Chia sẻ</span>
            </a>
        <?php endif; ?>
        <button class="post-detail-action" type="button" data-action="copy-link" data-url="<?= e($data['permalink']) ?>">
            <i class="bi bi-link-45deg"></i><span>Sao chép link</span>
        </button>
    </div>
</article>

<section class="post-detail-comments panel" id="comments">
    <div class="post-detail-comments-head">
        <h2 class="post-detail-comments-title">
            <i class="bi bi-chat-square-text"></i>
            Bình luận
            <span class="post-detail-comments-count">(<?= count($comments) ?>)</span>
        </h2>
    </div>

    <?php if (is_auth()): ?>
        <form class="comment-form post-detail-composer" data-post-id="<?= $postId ?>" data-draft="comment-detail-<?= $postId ?>">
            <img src="<?= e(avatar_url(auth_user()['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-sm">
            <div class="flex-grow-1 min-w-0">
                <textarea name="content" class="form-control" rows="2" placeholder="Viết bình luận của bạn..." maxlength="2000" required></textarea>
                <input type="hidden" name="parent_id" value="">
                <div class="post-detail-composer-actions">
                    <span class="post-detail-composer-hint">Ctrl + Enter để gửi nhanh</span>
                    <button class="btn btn-solid btn-sm" type="submit"><i class="bi bi-send me-1"></i>Gửi bình luận</button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="comment-login-prompt post-detail-guest">
            <p class="fg-secondary small mb-2">Đăng nhập để tham gia thảo luận với bài viết này.</p>
            <a class="btn btn-solid btn-sm" href="<?= url('/login') ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập</a>
        </div>
    <?php endif; ?>

    <div class="comment-list post-detail-comment-list" data-comment-list="<?= $postId ?>">
        <?php if ($comments === []): ?>
            <div class="empty-state post-detail-empty">
                <span class="empty-icon"><i class="bi bi-chat-heart"></i></span>
                <h3>Chưa có bình luận nào</h3>
                <p>Hãy là người đầu tiên trò chuyện về bài viết này.</p>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <?= view_partial(dirname(__DIR__) . '/partials/comment-item.php', ['comment' => $comment]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<div class="lightbox" data-lightbox-view hidden>
    <button class="lightbox-close" type="button" data-lightbox-close aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
    <button class="lightbox-nav lightbox-prev" type="button" data-lightbox-prev aria-label="Ảnh trước"><i class="bi bi-chevron-left"></i></button>
    <figure class="lightbox-figure">
        <img data-lightbox-image src="" alt="Ảnh bài viết">
    </figure>
    <button class="lightbox-nav lightbox-next" type="button" data-lightbox-next aria-label="Ảnh tiếp theo"><i class="bi bi-chevron-right"></i></button>
    <div class="lightbox-caption" data-lightbox-caption></div>
</div>
