<?php
/**
 * Trang cá nhân — phong cách Meta (Facebook / Instagram / Threads).
 * Dựa trên .cursor/DESIGN.md.
 *
 * @var array $user
 * @var array $stats
 * @var array $posts
 * @var array $comments
 * @var array $shared
 * @var array $photos
 * @var array $friends
 * @var array $followers
 * @var array $following
 * @var string $tab
 * @var bool $isFollowing
 * @var string $friendStatus
 * @var bool $isSelf
 */
$viewerId = auth_id();
$isFollowing = $isFollowing ?? false;
$friendStatus = $friendStatus ?? 'none';

$profileBase = url('/u/' . $user['username']);
$bio = trim((string) $user['bio']);
$coverUrl = upload_url($user['cover_path'] ?? null);

/* Nhóm số liệu: nhóm chính (bài viết/bạn bè/người theo dõi) và nhóm phụ. */
$primaryStats = [
    ['key' => 'posts', 'label' => 'bài viết', 'tab' => 'posts'],
    ['key' => 'friends', 'label' => 'bạn bè', 'tab' => 'friends'],
    ['key' => 'followers', 'label' => 'người theo dõi', 'tab' => 'followers'],
    ['key' => 'following', 'label' => 'đang theo dõi', 'tab' => 'following'],
];

$secondaryStats = [
    ['key' => 'comments', 'label' => 'bình luận'],
    ['key' => 'shares', 'label' => 'lượt chia sẻ'],
    ['key' => 'likes', 'label' => 'lượt thích'],
    ['key' => 'photos', 'label' => 'ảnh'],
];

/* Thẻ điều hướng theo tab. */
$tabs = [
    'posts'     => ['label' => 'Bài viết', 'icon' => 'bi-journal-text'],
    'photos'    => ['label' => 'Ảnh', 'icon' => 'bi-images'],
    'comments'  => ['label' => 'Bình luận', 'icon' => 'bi-chat-dots'],
    'shared'    => ['label' => 'Đã chia sẻ', 'icon' => 'bi-share'],
    'friends'   => ['label' => 'Bạn bè', 'icon' => 'bi-people'],
    'followers' => ['label' => 'Người theo dõi', 'icon' => 'bi-person-heart'],
    'following' => ['label' => 'Đang theo dõi', 'icon' => 'bi-person-check'],
];
?>
<div class="meta-profile">

    <!-- Thẻ hồ sơ: ảnh bìa bo góc 32px, thẻ thông tin đè lên mép dưới ảnh bìa -->
    <div class="mp-card">
        <div class="mp-cover<?= $coverUrl === null ? ' is-empty' : '' ?>">
            <?php if ($coverUrl !== null): ?>
                <img src="<?= e($coverUrl) ?>" alt="Ảnh bìa của <?= e($user['display_name']) ?>">
            <?php else: ?>
                <span class="mp-cover-note"><i class="bi bi-image"></i>Thêm ảnh bìa</span>
            <?php endif; ?>
        </div>

        <div class="mp-card-body">
            <div class="mp-identity">
                <div class="mp-avatar-wrap<?= $isSelf ? ' is-self' : '' ?>">
                    <img src="<?= e(avatar_url($user['avatar_path'])) ?>" alt="<?= e($user['display_name']) ?>" class="mp-avatar">
                </div>

                <div class="mp-identity-text">
                    <div class="mp-name-row">
                        <h1 class="mp-name"><?= e($user['display_name']) ?></h1>
                    </div>
                    <div class="mp-handle">@<?= e($user['username']) ?></div>

                    <?php if ($bio !== ''): ?>
                        <p class="mp-bio"><?= nl2br(e($bio)) ?></p>
                    <?php else: ?>
                        <p class="mp-bio is-empty">
                            <?= $isSelf ? 'Thêm vài dòng về bạn để mọi người hiểu hơn.' : 'Người dùng này chưa viết tiểu sử.' ?>
                        </p>
                    <?php endif; ?>

                    <div class="mp-meta-list">
                        <span><i class="bi bi-calendar3"></i>Tham gia <?= e(format_date($user['created_at'], 'd/m/Y')) ?></span>
                    </div>
                </div>

                <!-- Nút hành động -->
                <div class="mp-actions">
                    <?php if ($isSelf): ?>
                        <a class="mp-btn mp-btn-primary" href="<?= url('/settings/profile') ?>">
                            <i class="bi bi-pencil-square"></i><span>Sửa hồ sơ</span>
                        </a>
                    <?php elseif (!is_auth()): ?>
                        <a class="mp-btn mp-btn-primary" href="<?= url('/login') ?>">
                            <i class="bi bi-person-plus"></i><span>Kết bạn</span>
                        </a>
                        <button class="mp-btn mp-btn-secondary" type="button" data-action="follow" data-user-id="<?= (int) $user['id'] ?>">
                            <i class="bi bi-person-plus"></i><span>Theo dõi</span>
                        </button>
                        <a class="mp-btn mp-btn-icon" href="<?= url('/chat/start/' . (int) $user['id']) ?>" title="Nhắn tin" aria-label="Nhắn tin">
                            <i class="bi bi-chat-dots"></i>
                        </a>
                    <?php else: ?>
                        <?php if ($friendStatus === 'accepted'): ?>
                            <form action="<?= url('/friends/' . (int) $user['id'] . '/remove') ?>" method="post" class="mp-form-action">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="mp-btn mp-btn-secondary" type="submit"><i class="bi bi-person-check"></i><span>Bạn bè</span></button>
                            </form>
                        <?php elseif ($friendStatus === 'pending_outgoing'): ?>
                            <form action="<?= url('/friends/' . (int) $user['id'] . '/cancel') ?>" method="post" class="mp-form-action">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="mp-btn mp-btn-secondary" type="submit"><i class="bi bi-hourglass-split"></i><span>Đã gửi lời mời</span></button>
                            </form>
                        <?php elseif ($friendStatus === 'pending_incoming'): ?>
                            <form action="<?= url('/friends/request/' . (int) $user['id']) ?>" method="post" class="mp-form-action">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="mp-btn mp-btn-primary" type="submit"><i class="bi bi-person-check"></i><span>Chấp nhận lời mời</span></button>
                            </form>
                        <?php else: ?>
                            <form action="<?= url('/friends/request/' . (int) $user['id']) ?>" method="post" class="mp-form-action">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="mp-btn mp-btn-primary" type="submit"><i class="bi bi-person-plus"></i><span>Kết bạn</span></button>
                            </form>
                        <?php endif; ?>

                        <button class="mp-btn mp-btn-secondary<?= $isFollowing ? ' is-following' : '' ?>" type="button" data-action="follow" data-user-id="<?= (int) $user['id'] ?>">
                            <i class="bi bi-person<?= $isFollowing ? '-check' : '-plus' ?>"></i>
                            <span><?= $isFollowing ? 'Đang theo dõi' : 'Theo dõi' ?></span>
                        </button>

                        <a class="mp-btn mp-btn-icon" href="<?= url('/chat/start/' . (int) $user['id']) ?>" title="Nhắn tin" aria-label="Nhắn tin">
                            <i class="bi bi-chat-dots"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Số liệu -->
            <div class="mp-stats">
                <?php foreach ($primaryStats as $stat): ?>
                    <a class="mp-stat<?= $tab === $stat['tab'] ? ' is-active' : '' ?>"
                       href="<?= e($profileBase) ?>?tab=<?= e($stat['tab']) ?>">
                        <strong><?= (int) ($stats[$stat['key']] ?? 0) ?></strong>
                        <span><?= e($stat['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($secondaryStats !== []): ?>
                <div class="mp-stats mp-stats-sub">
                    <?php foreach ($secondaryStats as $stat): ?>
                        <span class="mp-stat">
                            <strong><?= (int) ($stats[$stat['key']] ?? 0) ?></strong>
                            <span><?= e($stat['label']) ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab điều hướng dạng pill -->
    <nav class="mp-tabs" aria-label="Nội dung trang cá nhân">
        <?php foreach ($tabs as $key => $meta): ?>
            <a class="mp-tab<?= $tab === $key ? ' is-active' : '' ?>"
               href="<?= e($profileBase) ?><?= $key === 'posts' ? '' : '?tab=' . e($key) ?>"
               aria-current="<?= $tab === $key ? 'page' : 'false' ?>">
                <i class="bi <?= e($meta['icon']) ?>"></i><span><?= e($meta['label']) ?></span>
                <?php if ($key === 'posts'): ?>
                    <em class="mp-tab-count"><?= (int) ($stats['posts'] ?? 0) ?></em>
                <?php elseif ($key === 'photos'): ?>
                    <em class="mp-tab-count"><?= (int) ($stats['photos'] ?? 0) ?></em>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Nội dung theo tab -->
    <div class="mp-content">
        <?php if ($tab === 'posts'): ?>
            <?php if ($posts === []): ?>
                <div class="mp-panel">
                    <div class="mp-empty">
                        <span class="mp-empty-icon"><i class="bi bi-journal-text"></i></span>
                        <h3>Chưa có bài viết nào</h3>
                        <p><?= $isSelf ? 'Hãy chia sẻ khoảnh khắc đầu tiên của bạn.' : e($user['display_name']) . ' chưa đăng bài viết nào.' ?></p>
                        <?php if ($isSelf): ?>
                            <a class="mp-btn mp-btn-primary" href="<?= url('/posts/create') ?>"><i class="bi bi-pencil-square"></i>Viết bài ngay</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php $data = post_view_data($post, $viewerId); ?>
                    <?= view_partial(dirname(__DIR__) . '/partials/post-card.php', ['post' => $post, 'data' => $data]) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php elseif ($tab === 'photos'): ?>
            <div class="mp-panel">
                <?php if ($photos === []): ?>
                    <div class="mp-empty">
                        <span class="mp-empty-icon"><i class="bi bi-images"></i></span>
                        <h3>Chưa có ảnh nào</h3>
                        <p><?= $isSelf ? 'Hãy đăng bài kèm hình ảnh để xuất hiện tại đây.' : e($user['display_name']) . ' chưa đăng ảnh nào.' ?></p>
                    </div>
                <?php else: ?>
                    <div class="mp-grid-photos">
                        <?php foreach ($photos as $photo): ?>
                            <a class="mp-photo" href="<?= url('/p/' . (int) $photo['post_id']) ?>" title="<?= e(excerpt($photo['content'] ?: 'Xem bài viết', 60)) ?>">
                                <img src="<?= e(upload_url($photo['path'])) ?>" alt="" loading="lazy">
                                <span class="mp-photo-overlay"><i class="bi bi-chat-dots"></i>Xem bài viết</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($tab === 'comments'): ?>
            <div class="mp-panel">
                <?php if ($comments === []): ?>
                    <div class="mp-empty">
                        <span class="mp-empty-icon"><i class="bi bi-chat-dots"></i></span>
                        <h3>Chưa có bình luận nào</h3>
                        <p>Hoạt động bình luận của bạn sẽ hiển thị ở đây.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="mp-comment">
                            <img src="<?= e(avatar_url($comment['avatar_path'])) ?>" alt="" class="mp-avatar-sm">
                            <div class="min-w-0">
                                <div class="d-flex align-items-baseline gap-2">
                                    <a class="mp-comment-name" href="<?= url('/u/' . $comment['username']) ?>"><?= e($comment['display_name']) ?></a>
                                    <span class="mp-comment-meta">@<?= e($comment['username']) ?></span>
                                </div>
                                <p class="mp-comment-text"><?= nl2br(e($comment['content'])) ?></p>
                                <span class="mp-comment-meta">
                                    <a class="mp-comment-meta" href="<?= url('/p/' . (int) $comment['post_id']) ?>">Bài viết #<?= (int) $comment['post_id'] ?></a>
                                    · <?= e(time_ago($comment['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php elseif ($tab === 'shared'): ?>
            <div class="mp-panel">
                <?php if ($shared === []): ?>
                    <div class="mp-empty">
                        <span class="mp-empty-icon"><i class="bi bi-share"></i></span>
                        <h3>Chưa chia sẻ bài viết nào</h3>
                        <p>Những bài bạn chia sẻ sẽ xuất hiện ở đây.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($shared as $share): ?>
                        <div class="mp-share">
                            <img src="<?= e(avatar_url($share['author_avatar_path'])) ?>" alt="" class="mp-avatar-sm">
                            <div class="mp-share-main">
                                <div class="d-flex align-items-baseline gap-2 flex-wrap">
                                    <a class="mp-comment-name" href="<?= url('/u/' . $share['author_username']) ?>">
                                        <?= e($share['author_display_name']) ?>
                                    </a>
                                    <span class="mp-comment-meta">@<?= e($share['author_username']) ?> · <?= e(time_ago($share['created_at'])) ?></span>
                                </div>
                                <?php if (trim((string) $share['content']) !== ''): ?>
                                    <p class="mp-comment-text"><?= nl2br(e($share['content'])) ?></p>
                                <?php endif; ?>
                                <div class="mp-share-original"><?= e(excerpt($share['post_content'], 160)) ?></div>
                                <a class="mp-btn mp-btn-ghost mt-2" href="<?= url('/p/' . (int) $share['post_id']) ?>">
                                    <i class="bi bi-box-arrow-up-right"></i>Xem bài viết gốc
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php elseif ($tab === 'friends' || $tab === 'followers' || $tab === 'following'): ?>
            <?php
            $people = match ($tab) {
                'friends'   => $friends,
                'followers' => $followers,
                default     => $following,
            };
            $peopleTitle = ['friends' => 'Bạn bè', 'followers' => 'Người theo dõi', 'following' => 'Đang theo dõi'][$tab];
            ?>
            <div class="mp-panel">
                <div class="mp-people-head">
                    <h2 class="mp-people-title"><?= e($peopleTitle) ?></h2>
                    <span class="mp-people-count"><?= count($people) ?></span>
                </div>
                <?php if ($people === []): ?>
                    <div class="mp-empty">
                        <span class="mp-empty-icon"><i class="bi bi-people"></i></span>
                        <h3>Chưa có dữ liệu</h3>
                        <p>Danh sách này đang trống.</p>
                    </div>
                <?php else: ?>
                    <div class="mp-people-grid">
                        <?php foreach ($people as $person): ?>
                            <a class="mp-person" href="<?= url('/u/' . $person['username']) ?>">
                                <img src="<?= e(avatar_url($person['avatar_path'])) ?>" alt="" class="mp-person-avatar" loading="lazy">
                                <span class="mp-person-name"><?= e($person['display_name']) ?></span>
                                <span class="mp-person-handle">@<?= e($person['username']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
