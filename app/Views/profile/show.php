<?php
/**
 * Trang cá nhân — phong cách Koobecaf Modern.
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
 * @var int $page
 * @var int $total
 * @var int $perPage
 * @var bool $isFollowing
 * @var string $friendStatus
 * @var bool $isSelf
 */

$viewerId = auth_id();
$isFollowing = $isFollowing ?? false;
$friendStatus = $friendStatus ?? 'none';
$page = (int) ($page ?? 1);
$perPage = (int) ($perPage ?? 10);
$total = (int) ($total ?? 0);
$totalPages = max(1, (int) ceil($total / max(1, $perPage)));

$profileBase = url('/u/' . $user['username']);
$bio = trim((string) ($user['bio'] ?? ''));
$coverUrl = !empty($user['cover_path']) ? upload_url($user['cover_path']) : null;
$avatarUrl = avatar_url($user['avatar_path'] ?? null);

// Sắc nhấn sinh từ username: mỗi người dùng có một dải màu riêng biệt
$hue = abs(crc32($user['username'])) % 360;

// Danh sách các tab hiển thị
$tabs = [
    'posts'     => ['label' => 'Bài viết',        'icon' => 'bi-journal-text',  'count' => $stats['posts'] ?? 0],
    // 'photos'    => ['label' => 'Ảnh',             'icon' => 'bi-images',        'count' => $stats['photos'] ?? 0],
    // 'friends'   => ['label' => 'Bạn bè',          'icon' => 'bi-people',        'count' => $stats['friends'] ?? 0],
    // 'followers' => ['label' => 'Người theo dõi',  'icon' => 'bi-person-heart',  'count' => $stats['followers'] ?? 0],
    // 'following' => ['label' => 'Đang theo dõi',   'icon' => 'bi-person-check',  'count' => $stats['following'] ?? 0],
    'comments'  => ['label' => 'Bình luận',       'icon' => 'bi-chat-dots',     'count' => $stats['comments'] ?? 0],
    'shared'    => ['label' => 'Đã chia sẻ',      'icon' => 'bi-share',         'count' => $stats['shares'] ?? 0],
];
?>

<div class="mp profile-page" style="--profile-hue: <?= (int) $hue ?>; --mp-hue: <?= (int) $hue ?>;">

    <!-- ================= 1. Masthead: Ảnh bìa ================= -->
    <header class="profile-masthead">
        <div class="profile-banner<?= $coverUrl === null ? ' is-generated' : '' ?>"
             <?= $coverUrl !== null ? 'style="background-image: url(\'' . e($coverUrl) . '\');"' : '' ?>>
            <?php if ($coverUrl === null): ?>
                <div class="profile-banner-mesh" aria-hidden="true"></div>
            <?php endif; ?>
            <div class="profile-banner-overlay" aria-hidden="true"></div>

            <?php if ($isSelf): ?>
                <a class="profile-banner-edit-btn" href="<?= url('/settings/profile') ?>" title="Đổi ảnh bìa">
                    <i class="bi bi-camera-fill"></i>
                    <span>Chỉnh sửa ảnh bìa</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Khối định danh cá nhân -->
        <div class="profile-identity-bar">
            <!-- Avatar -->
            <div class="profile-avatar-wrapper">
                <img src="<?= e($avatarUrl) ?>" alt="<?= e($user['display_name']) ?>" class="profile-avatar-img">
                <?php if ($isSelf): ?>
                    <a class="profile-avatar-badge" href="<?= url('/settings/profile') ?>" title="Đổi ảnh đại diện" aria-label="Đổi ảnh đại diện">
                        <i class="bi bi-camera-fill"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Chi tiết thông tin & Nút hành động -->
            <div class="profile-identity-main">
                <div class="profile-head-row">
                    <div class="profile-title-group">
                        <div class="profile-name-badge">
                            <h1 class="profile-display-name"><?= e($user['display_name']) ?></h1>
                            <?php if ($friendStatus === 'accepted'): ?>
                                <span class="profile-pill-badge profile-pill-friend" title="Bạn và <?= e($user['display_name']) ?> là bạn bè">
                                    <i class="bi bi-people-fill"></i> Bạn bè
                                </span>
                            <?php elseif ($isFollowing): ?>
                                <span class="profile-pill-badge profile-pill-following" title="Bạn đang theo dõi người này">
                                    <i class="bi bi-person-check-fill"></i> Đang theo dõi
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="profile-handle-row">
                            <span class="profile-handle">@<?= e($user['username']) ?></span>
                            <button type="button" class="profile-copy-link-btn" data-profile-copy="<?= e($profileBase) ?>" title="Sao chép liên kết trang cá nhân">
                                <i class="bi bi-link-45deg"></i>
                                <span>Sao chép liên kết</span>
                            </button>
                        </div>
                    </div>

                    <!-- Nhóm nút hành động chính -->
                    <div class="profile-actions-group">
                        <?php if ($isSelf): ?>
                            <a class="profile-btn profile-btn-primary" href="<?= url('/settings/profile') ?>">
                                <i class="bi bi-pencil-square"></i>
                                <span>Chỉnh sửa hồ sơ</span>
                            </a>
                            <a class="profile-btn profile-btn-secondary" href="<?= url('/posts/create') ?>">
                                <i class="bi bi-plus-circle"></i>
                                <span>Tạo bài viết</span>
                            </a>
                            <button type="button" class="profile-btn profile-btn-icon" data-profile-copy="<?= e($profileBase) ?>" title="Chia sẻ hồ sơ" aria-label="Chia sẻ hồ sơ">
                                <i class="bi bi-share"></i>
                            </button>
                        <?php elseif (!is_auth()): ?>
                            <a class="profile-btn profile-btn-primary" href="<?= url('/login') ?>">
                                <i class="bi bi-person-plus-fill"></i>
                                <span>Kết bạn</span>
                            </a>
                            <button class="profile-btn profile-btn-secondary" type="button" data-action="follow" data-user-id="<?= (int) $user['id'] ?>">
                                <i class="bi bi-person-plus"></i>
                                <span>Theo dõi</span>
                            </button>
                            <a class="profile-btn profile-btn-icon" href="<?= url('/login') ?>" title="Nhắn tin" aria-label="Nhắn tin">
                                <i class="bi bi-chat-dots-fill"></i>
                            </a>
                            <button type="button" class="profile-btn profile-btn-icon" data-profile-copy="<?= e($profileBase) ?>" title="Chia sẻ hồ sơ" aria-label="Chia sẻ hồ sơ">
                                <i class="bi bi-share"></i>
                            </button>
                        <?php else: ?>
                            <!-- Nút trạng thái Bạn bè -->
                            <?php if ($friendStatus === 'accepted'): ?>
                                <form action="<?= url('/friends/' . (int) $user['id'] . '/remove') ?>" method="post" class="profile-inline-form"
                                      onsubmit="return confirm('Bạn có chắc chắn muốn hủy kết bạn với <?= e(addslashes($user['display_name'])) ?>?');">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="profile-btn profile-btn-secondary profile-btn-friend-active" type="submit" title="Nhấn để hủy kết bạn">
                                        <i class="bi bi-person-check-fill"></i>
                                        <span>Bạn bè</span>
                                    </button>
                                </form>
                            <?php elseif ($friendStatus === 'pending_outgoing'): ?>
                                <form action="<?= url('/friends/' . (int) $user['id'] . '/cancel') ?>" method="post" class="profile-inline-form">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="profile-btn profile-btn-secondary" type="submit" title="Hủy lời mời kết bạn">
                                        <i class="bi bi-hourglass-split"></i>
                                        <span>Đã gửi lời mời</span>
                                    </button>
                                </form>
                            <?php elseif ($friendStatus === 'pending_incoming'): ?>
                                <div class="profile-request-actions">
                                    <form action="<?= url('/friends/request/' . (int) $user['id']) ?>" method="post" class="profile-inline-form">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button class="profile-btn profile-btn-primary" type="submit">
                                            <i class="bi bi-check-lg"></i>
                                            <span>Chấp nhận</span>
                                        </button>
                                    </form>
                                    <form action="<?= url('/friends/' . (int) $user['id'] . '/cancel') ?>" method="post" class="profile-inline-form">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button class="profile-btn profile-btn-secondary" type="submit" title="Từ chối lời mời">
                                            <i class="bi bi-x-lg"></i>
                                            <span>Từ chối</span>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <form action="<?= url('/friends/request/' . (int) $user['id']) ?>" method="post" class="profile-inline-form">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="profile-btn profile-btn-primary" type="submit">
                                        <i class="bi bi-person-plus-fill"></i>
                                        <span>Kết bạn</span>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Nút theo dõi -->
                            <button class="profile-btn profile-btn-secondary<?= $isFollowing ? ' is-following' : '' ?>"
                                    type="button" data-action="follow" data-user-id="<?= (int) $user['id'] ?>">
                                <i class="bi bi-person<?= $isFollowing ? '-check-fill' : '-plus' ?>"></i>
                                <span><?= $isFollowing ? 'Đang theo dõi' : 'Theo dõi' ?></span>
                            </button>

                            <!-- Nút nhắn tin -->
                            <a class="profile-btn profile-btn-secondary" href="<?= url('/chat/start/' . (int) $user['id']) ?>" title="Nhắn tin" aria-label="Nhắn tin">
                                <i class="bi bi-chat-dots-fill"></i>
                                <span>Nhắn tin</span>
                            </a>

                            <!-- Nút chia sẻ -->
                            <button type="button" class="profile-btn profile-btn-icon" data-profile-copy="<?= e($profileBase) ?>" title="Chia sẻ hồ sơ" aria-label="Chia sẻ hồ sơ">
                                <i class="bi bi-share"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tiểu sử (Bio) -->
                <?php if ($bio !== ''): ?>
                    <div class="profile-bio-text"><?= nl2br(e($bio)) ?></div>
                <?php else: ?>
                    <div class="profile-bio-text is-empty">
                        <?php if ($isSelf): ?>
                            <a href="<?= url('/settings/profile') ?>">
                                <i class="bi bi-plus-circle me-1"></i>Thêm tiểu sử để bạn bè hiểu rõ hơn về bạn...
                            </a>
                        <?php else: ?>
                            <span>Người dùng này chưa có tiểu sử giới thiệu.</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Thông tin meta: Ngày tham gia -->
                <div class="profile-facts-row">
                    <span class="profile-fact-item">
                        <i class="bi bi-calendar3"></i>
                        Tham gia <?= e(format_date($user['created_at'], 'd/m/Y')) ?>
                    </span>
                    <span class="profile-fact-item">
                        <i class="bi bi-heart-fill text-danger"></i>
                        Nhận được <?= number_format((int) ($stats['likes'] ?? 0)) ?> lượt thích
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- ================= 2. Dải số liệu nổi bật ================= -->
    <section class="profile-metrics-strip mp-metrics" aria-label="Thống kê hoạt động">
        <a class="profile-metric-tile mp-metric<?= $tab === 'posts' ? ' is-active' : '' ?>" href="<?= e($profileBase) ?>?tab=posts">
            <strong><?= (int) ($stats['posts'] ?? 0) ?></strong>
            <span>Bài viết</span>
        </a>
        <a class="profile-metric-tile mp-metric<?= $tab === 'friends' ? ' is-active' : '' ?>" href="<?= e($profileBase) ?>?tab=friends">
            <strong><?= (int) ($stats['friends'] ?? 0) ?></strong>
            <span>Bạn bè</span>
        </a>
        <a class="profile-metric-tile mp-metric<?= $tab === 'followers' ? ' is-active' : '' ?>" href="<?= e($profileBase) ?>?tab=followers">
            <strong><?= (int) ($stats['followers'] ?? 0) ?></strong>
            <span>Người theo dõi</span>
        </a>
        <a class="profile-metric-tile mp-metric<?= $tab === 'following' ? ' is-active' : '' ?>" href="<?= e($profileBase) ?>?tab=following">
            <strong><?= (int) ($stats['following'] ?? 0) ?></strong>
            <span>Đang theo dõi</span>
        </a>
        <a class="profile-metric-tile mp-metric<?= $tab === 'photos' ? ' is-active' : '' ?>" href="<?= e($profileBase) ?>?tab=photos">
            <strong><?= (int) ($stats['photos'] ?? 0) ?></strong>
            <span>Ảnh</span>
        </a>
        <a class="profile-metric-tile mp-metric<?= $tab === 'shared' ? ' is-active' : '' ?>" href="<?= e($profileBase) ?>?tab=shared">
            <strong><?= (int) ($stats['shares'] ?? 0) ?></strong>
            <span>Chia sẻ</span>
        </a>
    </section>

    <!-- ================= 3. Thanh tab điều hướng ================= -->
    <div class="profile-tabs-wrap">
        <nav class="profile-tabs mp-tabs" aria-label="Nội dung trang cá nhân">
            <?php foreach ($tabs as $key => $meta): ?>
                <a class="mp-tab profile-tab-item nav-link<?= $tab === $key ? ' active is-active' : '' ?>"
                   href="<?= e($profileBase) ?><?= $key === 'posts' ? '' : '?tab=' . e($key) ?>"
                   aria-current="<?= $tab === $key ? 'page' : 'false' ?>">
                    <i class="bi <?= e($meta['icon']) ?>"></i>
                    <span><?= e($meta['label']) ?></span>
                    <em class="mp-tab-count"><?= (int) ($meta['count'] ?? 0) ?></em>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- ================= 4. Nội dung theo tab ================= -->
    <main class="profile-main-content mp-content">

        <!-- TAB: BÀI VIẾT -->
        <?php if ($tab === 'posts'): ?>
            <?php if ($isSelf): ?>
                <!-- Hộp tạo bài viết nhanh khi xem hồ sơ chính mình -->
                <div class="profile-quick-composer">
                    <img src="<?= e($avatarUrl) ?>" alt="" class="quick-composer-avatar">
                    <a href="<?= url('/posts/create') ?>" class="quick-composer-input">
                        <span><?= e($user['display_name']) ?>, bạn đang nghĩ gì thế?</span>
                    </a>
                    <a href="<?= url('/posts/create') ?>" class="quick-composer-btn" title="Đăng ảnh">
                        <i class="bi bi-images text-success"></i>
                        <span>Ảnh/Video</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($posts === []): ?>
                <div class="profile-empty-state mp-empty">
                    <div class="profile-empty-ambient" aria-hidden="true"></div>
                    <div class="profile-empty-icon"><i class="bi bi-journal-x"></i></div>
                    <h3>Chưa có bài viết nào</h3>
                    <p><?= $isSelf ? 'Hãy chia sẻ những khoảnh khắc, suy nghĩ đầu tiên của bạn với mọi người.' : e($user['display_name']) . ' chưa đăng bài viết nào.' ?></p>
                    <?php if ($isSelf): ?>
                        <a class="profile-btn profile-btn-primary mt-2" href="<?= url('/posts/create') ?>">
                            <i class="bi bi-pencil-square"></i><span>Tạo bài viết mới</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="profile-posts-list mp-post-list">
                    <?php foreach ($posts as $post): ?>
                        <?php $data = post_view_data($post, $viewerId); ?>
                        <?= view_partial(dirname(__DIR__) . '/partials/post-card.php', ['post' => $post, 'data' => $data]) ?>
                    <?php endforeach; ?>
                </div>

                <!-- Phân trang danh sách bài viết -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Phân trang bài viết" class="profile-pagination my-4">
                        <?php if ($page > 1): ?>
                            <a class="profile-page-nav-btn" href="<?= e($profileBase) ?>?tab=posts&page=<?= $page - 1 ?>" title="Trang trước">
                                <i class="bi bi-chevron-left"></i><span>Trang trước</span>
                            </a>
                        <?php else: ?>
                            <span class="profile-page-nav-btn disabled" aria-disabled="true">
                                <i class="bi bi-chevron-left"></i><span>Trang trước</span>
                            </span>
                        <?php endif; ?>

                        <div class="profile-page-numbers">
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            if ($startPage > 1) {
                                echo '<a class="profile-page-num" href="' . e($profileBase) . '?tab=posts&page=1">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="profile-page-dots">…</span>';
                                }
                            }
                            for ($p = $startPage; $p <= $endPage; $p++) {
                                if ($p === $page) {
                                    echo '<span class="profile-page-num is-active" aria-current="page">' . $p . '</span>';
                                } else {
                                    echo '<a class="profile-page-num" href="' . e($profileBase) . '?tab=posts&page=' . $p . '">' . $p . '</a>';
                                }
                            }
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<span class="profile-page-dots">…</span>';
                                }
                                echo '<a class="profile-page-num" href="' . e($profileBase) . '?tab=posts&page=' . $totalPages . '">' . $totalPages . '</a>';
                            }
                            ?>
                        </div>

                        <?php if ($page < $totalPages): ?>
                            <a class="profile-page-nav-btn" href="<?= e($profileBase) ?>?tab=posts&page=<?= $page + 1 ?>" title="Trang sau">
                                <span>Trang sau</span><i class="bi bi-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="profile-page-nav-btn disabled" aria-disabled="true">
                                <span>Trang sau</span><i class="bi bi-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

        <!-- TAB: ẢNH -->
        <?php elseif ($tab === 'photos'): ?>
            <?php if ($photos === []): ?>
                <div class="profile-empty-state mp-empty">
                    <div class="profile-empty-ambient" aria-hidden="true"></div>
                    <div class="profile-empty-icon"><i class="bi bi-images"></i></div>
                    <h3>Chưa có hình ảnh nào</h3>
                    <p><?= $isSelf ? 'Những hình ảnh bạn đăng tải trong các bài viết sẽ được lưu giữ tại đây.' : e($user['display_name']) . ' chưa chia sẻ hình ảnh nào.' ?></p>
                    <?php if ($isSelf): ?>
                        <a class="profile-btn profile-btn-primary mt-2" href="<?= url('/posts/create') ?>">
                            <i class="bi bi-cloud-arrow-up"></i><span>Đăng ảnh mới</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="profile-photos-header">
                    <h2>Bộ sưu tập ảnh</h2>
                    <span class="profile-count-pill"><?= count($photos) ?> ảnh</span>
                </div>
                <div class="profile-photos-grid mp-grid-photos">
                    <?php foreach ($photos as $photo): ?>
                        <a class="profile-photo-card mp-photo" href="<?= url('/p/' . (int) $photo['post_id']) ?>" title="<?= e(excerpt($photo['content'] ?: 'Xem bài viết', 60)) ?>">
                            <img src="<?= e(upload_url($photo['path'])) ?>" alt="" loading="lazy">
                            <div class="profile-photo-overlay mp-photo-overlay">
                                <span class="photo-overlay-action"><i class="bi bi-box-arrow-up-right"></i> Xem bài viết</span>
                                <?php if (!empty($photo['created_at'])): ?>
                                    <span class="photo-overlay-date"><?= e(time_ago($photo['created_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- TAB: BÌNH LUẬN -->
        <?php elseif ($tab === 'comments'): ?>
            <?php if ($comments === []): ?>
                <div class="profile-empty-state mp-empty">
                    <div class="profile-empty-ambient" aria-hidden="true"></div>
                    <div class="profile-empty-icon"><i class="bi bi-chat-left-dots"></i></div>
                    <h3>Chưa có bình luận nào</h3>
                    <p><?= $isSelf ? 'Những cuộc trao đổi, bình luận của bạn sẽ được hiển thị tại đây.' : e($user['display_name']) . ' chưa để lại bình luận nào gần đây.' ?></p>
                </div>
            <?php else: ?>
                <div class="profile-activity-header">
                    <h2>Bình luận gần đây</h2>
                    <span class="profile-count-pill"><?= count($comments) ?></span>
                </div>
                <div class="profile-activity-list mp-activity">
                    <?php foreach ($comments as $comment): ?>
                        <article class="profile-activity-card mp-activity-item">
                            <img src="<?= e(avatar_url($comment['avatar_path'])) ?>" alt="" class="profile-activity-avatar mp-avatar-sm">
                            <div class="profile-activity-content mp-activity-body">
                                <div class="profile-activity-top mp-activity-head">
                                    <a class="profile-activity-author mp-activity-name" href="<?= url('/u/' . $comment['username']) ?>">
                                        <?= e($comment['display_name']) ?>
                                    </a>
                                    <span class="profile-activity-meta mp-activity-meta">
                                        @<?= e($comment['username']) ?> · <?= e(time_ago($comment['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="profile-activity-text mp-activity-text"><?= nl2br(e($comment['content'])) ?></div>
                                <div class="profile-activity-footer">
                                    <a class="profile-context-link" href="<?= url('/p/' . (int) $comment['post_id']) ?>">
                                        <i class="bi bi-reply-fill"></i>
                                        <span>Bình luận trên bài viết #<?= (int) $comment['post_id'] ?></span>
                                        <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- TAB: ĐÃ CHIA SẺ -->
        <?php elseif ($tab === 'shared'): ?>
            <?php if ($shared === []): ?>
                <div class="profile-empty-state mp-empty">
                    <div class="profile-empty-ambient" aria-hidden="true"></div>
                    <div class="profile-empty-icon"><i class="bi bi-share"></i></div>
                    <h3>Chưa chia sẻ bài viết nào</h3>
                    <p><?= $isSelf ? 'Bạn có thể chia sẻ những bài viết thú vị về trang cá nhân của mình.' : e($user['display_name']) . ' chưa chia sẻ bài viết nào.' ?></p>
                </div>
            <?php else: ?>
                <div class="profile-activity-header">
                    <h2>Bài viết đã chia sẻ</h2>
                    <span class="profile-count-pill"><?= count($shared) ?></span>
                </div>
                <div class="profile-activity-list mp-activity">
                    <?php foreach ($shared as $share): ?>
                        <article class="profile-activity-card mp-activity-item">
                            <img src="<?= e(avatar_url($share['author_avatar_path'])) ?>" alt="" class="profile-activity-avatar mp-avatar-sm">
                            <div class="profile-activity-content mp-activity-body">
                                <div class="profile-activity-top mp-activity-head">
                                    <a class="profile-activity-author mp-activity-name" href="<?= url('/u/' . $share['author_username']) ?>">
                                        <?= e($share['author_display_name']) ?>
                                    </a>
                                    <span class="profile-activity-meta mp-activity-meta">
                                        @<?= e($share['author_username']) ?> · <?= e(time_ago($share['created_at'])) ?>
                                    </span>
                                </div>

                                <?php if (trim((string) ($share['content'] ?? '')) !== ''): ?>
                                    <div class="profile-activity-text mp-activity-text"><?= nl2br(e($share['content'])) ?></div>
                                <?php endif; ?>

                                <!-- Hộp trích dẫn bài viết gốc -->
                                <div class="profile-shared-quote mp-activity-quote">
                                    <div class="shared-quote-badge"><i class="bi bi-quote"></i> Bài viết gốc</div>
                                    <p class="shared-quote-text"><?= e(excerpt($share['post_content'], 200)) ?></p>
                                    <a class="profile-quote-link mp-link" href="<?= url('/p/' . (int) $share['post_id']) ?>">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Xem trọn vẹn bài viết gốc
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- TAB: BẠN BÈ / NGƯỜI THEO DÕI / ĐANG THEO DÕI -->
        <?php elseif ($tab === 'friends' || $tab === 'followers' || $tab === 'following'): ?>
            <?php
            $people = match ($tab) {
                'friends'   => $friends,
                'followers' => $followers,
                default     => $following,
            };
            $peopleMeta = [
                'friends'   => ['title' => 'Danh sách bạn bè',     'icon' => 'bi-people',        'empty' => 'Chưa có bạn bè nào.'],
                'followers' => ['title' => 'Người đang theo dõi',  'icon' => 'bi-person-heart',  'empty' => 'Chưa có người theo dõi nào.'],
                'following' => ['title' => 'Đang theo dõi người khác', 'icon' => 'bi-person-check', 'empty' => 'Chưa theo dõi người dùng nào.'],
            ][$tab];
            ?>
            <div class="profile-people-section mp-people">
                <div class="profile-people-header mp-people-head">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= e($peopleMeta['icon']) ?> fs-5"></i>
                        <h2 class="profile-people-title mp-people-title"><?= e($peopleMeta['title']) ?></h2>
                    </div>
                    <span class="profile-count-pill mp-people-count"><?= count($people) ?></span>
                </div>

                <?php if ($people === []): ?>
                    <div class="profile-empty-state mp-empty">
                        <div class="profile-empty-ambient" aria-hidden="true"></div>
                        <div class="profile-empty-icon"><i class="bi <?= e($peopleMeta['icon']) ?>"></i></div>
                        <h3>Chưa có dữ liệu</h3>
                        <p><?= e($peopleMeta['empty']) ?></p>
                    </div>
                <?php else: ?>
                    <div class="profile-users-grid">
                        <?php foreach ($people as $person): ?>
                            <div class="profile-user-card">
                                <a href="<?= url('/u/' . $person['username']) ?>" class="user-card-main-link">
                                    <img src="<?= e(avatar_url($person['avatar_path'])) ?>" alt="" class="user-card-avatar" loading="lazy">
                                    <div class="user-card-info">
                                        <span class="user-card-name"><?= e($person['display_name']) ?></span>
                                        <span class="user-card-handle">@<?= e($person['username']) ?></span>
                                        <?php if (!empty(trim((string) ($person['bio'] ?? '')))): ?>
                                            <p class="user-card-bio"><?= e(excerpt($person['bio'], 70)) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="user-card-action-bar">
                                    <a href="<?= url('/u/' . $person['username']) ?>" class="user-card-btn user-card-btn-profile">
                                        <i class="bi bi-person"></i>
                                        <span>Xem hồ sơ</span>
                                    </a>
                                    <?php if (is_auth() && (int) $person['id'] !== (int) $viewerId): ?>
                                        <a href="<?= url('/chat/start/' . (int) $person['id']) ?>" class="user-card-btn user-card-btn-chat" title="Nhắn tin với <?= e($person['display_name']) ?>" aria-label="Nhắn tin">
                                            <i class="bi bi-chat-dots-fill"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- Script hỗ trợ tương tác trên trang cá nhân -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Xử lý sao chép liên kết trang cá nhân
    document.querySelectorAll('[data-profile-copy]').forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const link = button.dataset.profileCopy || window.location.href;
            try {
                await navigator.clipboard.writeText(link);
                if (typeof showToast === 'function') {
                    showToast('success', 'Đã sao chép liên kết trang cá nhân vào bộ nhớ tạm.');
                } else {
                    alert('Đã sao chép liên kết: ' + link);
                }
            } catch (err) {
                window.prompt('Sao chép liên kết trang cá nhân:', link);
            }
        });
    });
});
</script>
