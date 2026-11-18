<?php
/**
 * Trang cá nhân.
 *
 * @var array $user
 * @var array $stats
 * @var array $posts
 * @var array $comments
 * @var array $shared
 * @var array $photos
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

/* Nhóm số liệu: nhóm chính (bài viết/bạn bè/người theo dõi) và nhóm phụ. */
$primaryStats = [
    ['key' => 'posts', 'label' => 'Bài viết', 'icon' => 'bi-journal-text', 'tab' => 'posts'],
    ['key' => 'friends', 'label' => 'Bạn bè', 'icon' => 'bi-people', 'tab' => 'friends'],
    ['key' => 'followers', 'label' => 'Người theo dõi', 'icon' => 'bi-person-heart', 'tab' => 'followers'],
    ['key' => 'following', 'label' => 'Đang theo dõi', 'icon' => 'bi-person-check', 'tab' => 'following'],
];

$secondaryStats = [
    ['key' => 'comments', 'label' => 'Bình luận', 'icon' => 'bi-chat-dots'],
    ['key' => 'shares', 'label' => 'Chia sẻ', 'icon' => 'bi-share'],
    ['key' => 'likes', 'label' => 'Lượt thích', 'icon' => 'bi-hand-thumbs-up'],
    ['key' => 'photos', 'label' => 'Ảnh', 'icon' => 'bi-images'],
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
<div class="profile-hero card surface-card">
    <!-- Ảnh bìa -->
    <div class="profile-cover<?= empty($user['cover_path']) ? ' cover-empty' : '' ?>"
         <?php if (!empty($user['cover_path'])): ?>style="background-image:url('<?= e(upload_url($user['cover_path'])) ?>')"<?php endif; ?>>
        <span class="profile-cover-texture" aria-hidden="true"></span>
    </div>

    <!-- Lớp thông tin đè lên mép dưới ảnh bìa -->
    <div class="profile-hero-body">
        <div class="profile-identity">
            <div class="profile-avatar-wrap">
                <img src="<?= e(avatar_url($user['avatar_path'])) ?>" alt="<?= e($user['display_name']) ?>" class="profile-avatar">
                <span class="profile-avatar-ring" aria-hidden="true"></span>
            </div>

            <div class="profile-identity-text">
                <div class="profile-name-row">
                    <h1 class="profile-name"><?= e($user['display_name']) ?></h1>
                </div>
                <div class="profile-handle">@<?= e($user['username']) ?></div>

                <?php if ($bio !== ''): ?>
                    <p class="profile-bio"><?= nl2br(e($bio)) ?></p>
                <?php else: ?>
                    <p class="profile-bio profile-bio-empty">
                        <?= $isSelf ? 'Thêm vài dòng về bạn để mọi người hiểu hơn.' : 'Người dùng này chưa viết tiểu sử.' ?>
                    </p>
                <?php endif; ?>

                <div class="profile-meta">
                    <span><i class="bi bi-calendar3"></i>Tham gia <?= e(format_date($user['created_at'], 'd/m/Y')) ?></span>
                </div>
            </div>

            <!-- Nút hành động -->
            <div class="profile-actions">
                <?php if ($isSelf): ?>
                    <a class="btn btn-outline profile-btn" href="<?= url('/settings/profile') ?>">
                        <i class="bi bi-pencil-square"></i><span>Sửa hồ sơ</span>
                    </a>
                <?php elseif (!is_auth()): ?>
                    <a class="btn btn-solid profile-btn" href="<?= url('/login') ?>">
                        <i class="bi bi-person-plus"></i><span>Kết bạn</span>
                    </a>
                    <button class="btn btn-outline profile-btn" data-action="follow" data-user-id="<?= (int) $user['id'] ?>">
                        <i class="bi bi-person-plus"></i><span>Theo dõi</span>
                    </button>
                    <a class="btn btn-outline profile-btn-icon" href="<?= url('/chat/start/' . (int) $user['id']) ?>" title="Nhắn tin" aria-label="Nhắn tin">
                        <i class="bi bi-chat-dots"></i>
                    </a>
                <?php else: ?>
                    <?php if ($friendStatus === 'accepted'): ?>
                        <form action="<?= url('/friends/' . (int) $user['id'] . '/remove') ?>" method="post" class="profile-form-action">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-outline profile-btn" type="submit"><i class="bi bi-person-check"></i><span>Bạn bè</span></button>
                        </form>
                    <?php elseif ($friendStatus === 'pending_outgoing'): ?>
                        <form action="<?= url('/friends/' . (int) $user['id'] . '/cancel') ?>" method="post" class="profile-form-action">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-outline profile-btn" type="submit"><i class="bi bi-hourglass-split"></i><span>Đã gửi lời mời</span></button>
                        </form>
                    <?php elseif ($friendStatus === 'pending_incoming'): ?>
                        <form action="<?= url('/friends/request/' . (int) $user['id']) ?>" method="post" class="profile-form-action">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-solid profile-btn" type="submit"><i class="bi bi-person-check"></i><span>Chấp nhận lời mời</span></button>
                        </form>
                    <?php else: ?>
                        <form action="<?= url('/friends/request/' . (int) $user['id']) ?>" method="post" class="profile-form-action">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-solid profile-btn" type="submit"><i class="bi bi-person-plus"></i><span>Kết bạn</span></button>
                        </form>
                    <?php endif; ?>

                    <button class="btn btn-outline profile-btn<?= $isFollowing ? ' is-following' : '' ?>" data-action="follow" data-user-id="<?= (int) $user['id'] ?>">
                        <i class="bi bi-person<?= $isFollowing ? '-check' : '-plus' ?>"></i>
                        <span><?= $isFollowing ? 'Đang theo dõi' : 'Theo dõi' ?></span>
                    </button>

                    <a class="btn btn-outline profile-btn-icon" href="<?= url('/chat/start/' . (int) $user['id']) ?>" title="Nhắn tin" aria-label="Nhắn tin">
                        <i class="bi bi-chat-dots"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="profile-stats-row">
            <?php foreach ($primaryStats as $stat): ?>
                <a class="profile-stat<?= $tab === $stat['tab'] ? ' is-active' : '' ?>"
                   href="<?= e($profileBase) ?>?tab=<?= e($stat['tab']) ?>">
                    <span class="profile-stat-icon"><i class="bi <?= e($stat['icon']) ?>"></i></span>
                    <span class="profile-stat-body">
                        <strong><?= (int) ($stats[$stat['key']] ?? 0) ?></strong>
                        <span><?= e($stat['label']) ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Tab điều hướng -->
<div class="profile-tabs-wrap">
    <ul class="nav profile-tabs" role="tablist">
        <?php foreach ($tabs as $key => $meta): ?>
            <li class="nav-item">
                <a class="nav-link<?= $tab === $key ? ' active' : '' ?>"
                   href="<?= e($profileBase) ?><?= $key === 'posts' ? '' : '?tab=' . e($key) ?>"
                   role="tab" aria-selected="<?= $tab === $key ? 'true' : 'false' ?>">
                    <i class="bi <?= e($meta['icon']) ?>"></i><span><?= e($meta['label']) ?></span>
                    <?php if ($key === 'posts'): ?>
                        <em class="profile-tab-count"><?= (int) ($stats['posts'] ?? 0) ?></em>
                    <?php elseif ($key === 'photos'): ?>
                        <em class="profile-tab-count"><?= (int) ($stats['photos'] ?? 0) ?></em>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Nội dung theo tab -->
<?php if ($tab === 'posts'): ?>
    <?php if ($posts === []): ?>
        <div class="card surface-card">
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-journal-text"></i></span>
                <h3>Chưa có bài viết nào</h3>
                <p><?= $isSelf ? 'Hãy chia sẻ khoảnh khắc đầu tiên của bạn.' : e($user['display_name']) . ' chưa đăng bài viết nào.' ?></p>
                <?php if ($isSelf): ?>
                    <a class="btn btn-solid mt-2" href="<?= url('/posts/create') ?>"><i class="bi bi-pencil-square me-1"></i>Viết bài ngay</a>
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
    <?php if ($photos === []): ?>
        <div class="card surface-card">
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-images"></i></span>
                <h3>Chưa có ảnh nào</h3>
                <p><?= $isSelf ? 'Hãy đăng bài kèm hình ảnh để xuất hiện tại đây.' : e($user['display_name']) . ' chưa đăng ảnh nào.' ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="profile-gallery">
            <?php foreach ($photos as $photo): ?>
                <a class="profile-gallery-item" href="<?= url('/p/' . (int) $photo['post_id']) ?>" title="<?= e(excerpt($photo['content'] ?: 'Xem bài viết', 60)) ?>">
                    <img src="<?= e(upload_url($photo['path'])) ?>" alt="" loading="lazy">
                    <span class="profile-gallery-overlay"><i class="bi bi-chat-dots"></i> Xem bài viết</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php elseif ($tab === 'comments'): ?>
    <div class="card surface-card">
        <div class="card-body">
            <?php if ($comments === []): ?>
                <p class="fg-secondary mb-0">Chưa có bình luận nào.</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="person-row align-items-start">
                        <img src="<?= e(avatar_url($comment['avatar_path'])) ?>" alt="" class="avatar avatar-sm">
                        <div class="person-meta">
                            <div class="d-flex align-items-baseline gap-2">
                                <a class="person-name" href="<?= url('/u/' . $comment['username']) ?>"><?= e($comment['display_name']) ?></a>
                                <small class="fg-secondary">@<?= e($comment['username']) ?></small>
                            </div>
                            <p class="mb-1 mt-1 small"><?= nl2br(e($comment['content'])) ?></p>
                            <small class="fg-secondary">
                                <a class="fg-secondary" href="<?= url('/p/' . (int) $comment['post_id']) ?>">Bài viết #<?= (int) $comment['post_id'] ?></a>
                                · <?= e(time_ago($comment['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($tab === 'shared'): ?>
    <div class="card surface-card">
        <div class="card-body">
            <?php if ($shared === []): ?>
                <p class="fg-secondary mb-0">Chưa chia sẻ bài viết nào.</p>
            <?php else: ?>
                <?php foreach ($shared as $share): ?>
                    <div class="profile-share-item">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <img src="<?= e(avatar_url($share['author_avatar_path'])) ?>" alt="" class="avatar avatar-sm avatar-ring">
                            <div class="min-w-0">
                                <a class="fw-semibold fg-body-emphasis text-decoration-none d-block text-truncate" href="<?= url('/u/' . $share['author_username']) ?>">
                                    <?= e($share['author_display_name']) ?>
                                </a>
                                <small class="fg-secondary">@<?= e($share['author_username']) ?> · <?= e(time_ago($share['created_at'])) ?></small>
                            </div>
                        </div>
                        <?php if (trim((string) $share['content']) !== ''): ?>
                            <p class="mb-2 small"><?= nl2br(e($share['content'])) ?></p>
                        <?php endif; ?>
                        <div class="shared-original p-2 small">
                            <?= e(excerpt($share['post_content'], 160)) ?>
                        </div>
                        <a class="btn btn-outline btn-sm mt-2" href="<?= url('/p/' . (int) $share['post_id']) ?>">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Xem bài viết gốc
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($tab === 'friends' || $tab === 'followers' || $tab === 'following'): ?>
    <?php
    $people = match ($tab) {
        'friends'   => $friends,
        'followers' => $followers,
        default     => $following,
    };
    $peopleTab = ['friends' => 'Bạn bè', 'followers' => 'Người theo dõi', 'following' => 'Đang theo dõi'][$tab];
    ?>
    <div class="card surface-card">
        <div class="card-body">
            <div class="profile-people-head">
                <h2 class="h6 mb-0"><?= e($peopleTab) ?></h2>
                <span class="profile-people-count"><?= count($people) ?></span>
            </div>
            <?php if ($people === []): ?>
                <p class="fg-secondary mb-0">Chưa có dữ liệu.</p>
            <?php else: ?>
                <div class="profile-people-grid">
                    <?php foreach ($people as $person): ?>
                        <div class="profile-person-card">
                            <a class="profile-person-link" href="<?= url('/u/' . $person['username']) ?>">
                                <img src="<?= e(avatar_url($person['avatar_path'])) ?>" alt="" class="avatar avatar-lg avatar-ring">
                                <span class="profile-person-name"><?= e($person['display_name']) ?></span>
                                <span class="profile-person-handle">@<?= e($person['username']) ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>