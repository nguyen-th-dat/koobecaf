<?php

/**
 * Bài viết trong luồng — phong cách Threads.
 *
 * Bố cục: avatar + tên/handle/thời gian → nội dung → ảnh → hành động.
 * Không dùng thẻ có viền; các bài được tách bằng đường kẻ hairline.
 *
 * @var array $post
 * @var array $data Dữ liệu chuẩn bị từ post_view_data()
 */
$meta = $data ?? post_view_data($post, auth_id());
$images = $meta['images'];
$original = $meta['original'];
$originalImages = $meta['originalImages'];
?>
<article class="card surface-card post-card" data-post-id="<?= (int) $post['id'] ?>">
    <?php if ($meta['isShared'] && $original !== null): ?>
        <div class="shared-header d-flex align-items-center gap-2">
            <img src="<?= e(avatar_url($post['avatar_path'])) ?>" alt="" class="avatar avatar-sm">
            <div class="min-w-0">
                <a class="post-author" href="<?= url('/u/' . $post['username']) ?>"><?= e($post['display_name']) ?></a>
                <small class="post-meta d-block">đã chia sẻ · <?= e(time_ago($post['created_at'])) ?></small>
            </div>
        </div>
    <?php endif; ?>

    <div class="post-body d-flex gap-3">
        <?php if (!$meta['isShared'] || $original === null): ?>
            <a href="<?= url('/u/' . $post['username']) ?>">
                <img src="<?= e(avatar_url($post['avatar_path'])) ?>" alt="" class="avatar avatar-md">
            </a>
        <?php endif; ?>

        <div class="flex-grow-1 min-w-0">
            <?php if (!$meta['isShared'] || $original === null): ?>
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <a class="post-author" href="<?= url('/u/' . $post['username']) ?>">
                            <?= e($post['display_name']) ?>
                        </a>
                        <div class="post-meta">
                            <span>@<?= e($post['username']) ?></span> ·
                            <span><?= e(time_ago($post['created_at'])) ?></span>
                            <?php if ($post['privacy'] !== 'public'): ?>
                                · <i class="bi bi-<?= $post['privacy'] === 'private' ? 'lock' : 'people' ?>" title="Bài viết riêng tư/người theo dõi"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($meta['isOwner']): ?>
                        <button class="btn icon-btn post-menu-btn" type="button" data-menu-toggle data-menu-placement="bottom-end" aria-label="Tuỳ chọn">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="menu">
                            <!-- Chỉnh sửa -->
                            <li>
                                <a class="menu-item" href="<?= url('/posts/' . (int) $post['id'] . '/edit') ?>">
                                    <i class="bi bi-pencil me-2"></i>Chỉnh sửa
                                </a>
                            </li>

                            <!-- Sao chép link -->
                            <li>
                                <a
                                    class="menu-item"
                                    href="#"
                                    data-action="copy-link"
                                    data-url="<?= e($meta['permalink']) ?>">
                                    <i class="bi bi-link-45deg me-2"></i>Sao chép link
                                </a>
                            </li>

                            <li>
                                <hr class="menu-divider">
                            </li>

                            <!-- Xóa bài viết -->
                            <li>
                                <form
                                    action="<?= url('/posts/' . (int) $post['id'] . '/delete') ?>"
                                    method="post"
                                    data-confirm="Bạn có chắc muốn xóa bài viết này? Hành động này không thể hoàn tác."
                                    data-confirm-title="Xóa bài viết"
                                    data-confirm-accept="Xóa bài viết">
                                    <?= \App\Core\Csrf::field() ?>

                                    <button type="submit" class="menu-item fg-danger w-100">
                                        <i class="bi bi-trash me-2"></i>Xóa bài viết
                                    </button>
                                </form>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($meta['isShared'] && $original !== null): ?>
                <div class="shared-original mt-2">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="<?= e(avatar_url($original['avatar_path'])) ?>" alt="" class="avatar avatar-sm">
                        <div class="min-w-0">
                            <a class="post-author" href="<?= url('/u/' . $original['username']) ?>"><?= e($original['display_name']) ?></a>
                            <small class="post-meta d-block">@<?= e($original['username']) ?> · <?= e(time_ago($original['created_at'])) ?></small>
                        </div>
                    </div>
                    <?php if (trim((string) $original['content']) !== ''): ?>
                        <p class="mb-2 post-text" data-clampable><?= nl2br(e($original['content'])) ?></p>
                    <?php endif; ?>
                    <?php if ($originalImages !== []): ?>
                        <div class="post-images count-<?= count($originalImages) ?>">
                            <?php foreach ($originalImages as $originalImage): ?>
                                <img src="<?= e(upload_url($originalImage['path'])) ?>" alt="" loading="lazy">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (trim((string) $post['content']) !== ''): ?>
                <p class="post-text mb-0 mt-1" data-clampable><?= nl2br(e($post['content'])) ?></p>
            <?php endif; ?>

            <?php if ($images !== []): ?>
                <div class="post-images count-<?= count($images) ?>">
                    <?php foreach ($images as $postImage): ?>
                        <img src="<?= e(upload_url($postImage['path'])) ?>" alt="" loading="lazy">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($meta['isShared'] && trim((string) $post['share_content']) !== ''): ?>
                <p class="post-text mb-0 mt-2"><?= nl2br(e($post['share_content'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="post-stats d-flex align-items-center gap-3">
        <span data-like-count><i class="bi bi-hand-thumbs-up me-1"></i><?= (int) $meta['likeCount'] ?></span>
        <span data-comment-count><i class="bi bi-chat me-1"></i><?= (int) $meta['commentCount'] ?></span>
        <span data-share-count><i class="bi bi-share me-1"></i><?= (int) $meta['shareCount'] ?></span>
        <?php if ($post['privacy'] !== 'public'): ?>
            <span class="ms-auto fg-muted">
                <i class="bi bi-<?= $post['privacy'] === 'private' ? 'lock' : 'people' ?>" me-1"></i><?= $post['privacy'] === 'private' ? 'Riêng tư' : 'Người theo dõi' ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="post-actions d-flex">

        <!-- Like -->
        <?php if (is_auth()): ?>
            <button
                class="btn btn-text flex-fill border-0 rounded-0 action-btn<?= $meta['liked'] ? ' active' : '' ?>"
                data-action="like"
                data-post-id="<?= (int) $post['id'] ?>">
                <i class="bi bi-hand-thumbs-up"></i>
                <span>Thích</span>
            </button>
        <?php else: ?>
            <a
                class="btn btn-text flex-fill border-0 rounded-0 action-btn"
                href="<?= url('/login') ?>"
                title="Đăng nhập để thích bài viết này">
                <i class="bi bi-hand-thumbs-up"></i>
                <span>Thích</span>
            </a>
        <?php endif; ?>

        <!-- Comment -->
        <?php if (is_auth()): ?>
            <button
                class="btn btn-text flex-fill border-0 rounded-0 action-btn"
                data-action="comment"
                data-post-id="<?= (int) $post['id'] ?>">
                <i class="bi bi-chat"></i>
                <span>Bình luận</span>
            </button>
        <?php else: ?>
            <a
                class="btn btn-text flex-fill border-0 rounded-0 action-btn"
                href="<?= e($meta['permalink']) ?>#comments"
                title="Xem bình luận">
                <i class="bi bi-chat"></i>
                <span>Bình luận</span>
            </a>
        <?php endif; ?>

        <!-- Share -->
        <?php if (is_auth()): ?>
            <button
                class="btn btn-text flex-fill border-0 rounded-0 action-btn"
                data-action="share"
                data-post-id="<?= (int) $post['id'] ?>">
                <i class="bi bi-share"></i>
                <span>Chia sẻ</span>
            </button>
        <?php else: ?>
            <a
                class="btn btn-text flex-fill border-0 rounded-0 action-btn"
                href="<?= url('/login') ?>"
                title="Đăng nhập để chia sẻ bài viết này">
                <i class="bi bi-share"></i>
                <span>Chia sẻ</span>
            </a>
        <?php endif; ?>

    </div>

    <?php if (is_auth()): ?>
        <div class="post-comments border-top d-none" data-comments-container="<?= (int) $post['id'] ?>">
            <form class="comment-form d-flex gap-2 p-2 border-bottom" data-post-id="<?= (int) $post['id'] ?>" data-draft="comment-<?= (int) $post['id'] ?>" data-leave="clear">
                <img src="<?= e(avatar_url(auth_user()['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-sm">
                <input type="text" name="content" class="form-control form-control-sm" placeholder="Viết bình luận..." maxlength="2000" required>
                <input type="hidden" name="parent_id" value="">
                <button class="btn btn-solid btn-sm" type="submit">Gửi</button>
            </form>
            <div class="comment-list p-2" data-comment-list="<?= (int) $post['id'] ?>"></div>
        </div>
    <?php endif; ?>
</article>
