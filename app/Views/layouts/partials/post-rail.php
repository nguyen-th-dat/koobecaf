<?php
/**
 * Cột phụ của trang chi tiết bài viết: tác giả, người đã thích, bài viết liên quan.
 *
 * @var array $post
 * @var array|null $author
 * @var array $likers
 * @var array $related
 */
$author = $author ?? null;
$authorBio = trim((string) ($author['bio'] ?? ''));
$related = array_values(array_filter(
    $related ?? [],
    static fn (array $item): bool => (int) $item['id'] !== (int) $post['id']
));
?>
<?php if ($author !== null): ?>
    <div class="rail-card">
        <div class="rail-title">Tác giả</div>
        <div class="post-rail-author-head">
            <a href="<?= url('/u/' . $author['username']) ?>">
                <img src="<?= e(avatar_url($author['avatar_path'])) ?>" alt="" class="avatar avatar-lg avatar-ring">
            </a>
            <div class="min-w-0">
                <a class="post-rail-author-name" href="<?= url('/u/' . $author['username']) ?>"><?= e($author['display_name']) ?></a>
                <div class="post-rail-author-handle">@<?= e($author['username']) ?></div>
            </div>
        </div>
        <?php if ($authorBio !== ''): ?>
            <p class="post-rail-author-bio"><?= nl2br(e($authorBio)) ?></p>
        <?php endif; ?>
        <a class="btn btn-outline btn-sm w-100" href="<?= url('/u/' . $author['username']) ?>">
            <i class="bi bi-person me-1"></i>Xem trang cá nhân
        </a>
    </div>
<?php endif; ?>

<?php if (!empty($likers)): ?>
    <div class="rail-card">
        <div class="rail-title">Người đã thích</div>
        <div class="post-rail-likers-list">
            <?php foreach ($likers as $liker): ?>
                <a class="post-rail-liker" href="<?= url('/u/' . $liker['username']) ?>">
                    <img src="<?= e(avatar_url($liker['avatar_path'])) ?>" alt="" class="avatar avatar-sm">
                    <span class="post-rail-liker-name"><?= e($liker['display_name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <a class="post-rail-more" href="<?= url('/posts/' . (int) $post['id'] . '/likes') ?>">
            Xem tất cả <i class="bi bi-chevron-right"></i>
        </a>
    </div>
<?php endif; ?>

<?php if ($related !== []): ?>
    <div class="rail-card">
        <div class="rail-title">Bài viết khác của tác giả</div>
        <?php foreach ($related as $item): ?>
            <a class="post-rail-related-item" href="<?= url('/p/' . (int) $item['id']) ?>">
                <span class="post-rail-related-text"><?= e(excerpt($item['content'] ?: 'Bài viết không có nội dung', 90)) ?></span>
                <span class="post-rail-related-time"><?= e(time_ago($item['created_at'])) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
