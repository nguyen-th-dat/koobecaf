<?php
/**
 * @var array $user
 * @var array $stats
 * @var array $posts
 * @var array $comments
 * @var array $shared
 * @var string $tab
 * @var bool $isFollowing
 * @var string $friendStatus
 * @var bool $isSelf
 */
$viewerId = auth_id();
$isFollowing = $isFollowing ?? false;
$friendStatus = $friendStatus ?? 'none';
?>
<div class="card surface-card profile-card mb-3 overflow-hidden">
    <div class="profile-cover<?= empty($user['cover_path']) ? ' cover-empty' : '' ?>"
         <?php if (!empty($user['cover_path'])): ?>style="background-image:url('<?= e(upload_url($user['cover_path'])) ?>')"<?php endif; ?>>
    </div>
    <div class="card-body pt-0">
        <div class="d-flex align-items-end gap-3 profile-avatar-row">
            <img src="<?= e(avatar_url($user['avatar_path'])) ?>" alt="" class="avatar avatar-xl">
            <div class="flex-grow-1 min-w-0 pb-2">
                <h1 class="h2 mb-0 text-truncate"><?= e($user['display_name']) ?></h1>
                <div class="fg-secondary">@<?= e($user['username']) ?></div>
            </div>
            <div class="pb-2 d-flex gap-2">
                <?php if ($isSelf): ?>
                    <a class="btn btn-outline btn-sm" href="<?= url('/settings/profile') ?>"><i class="bi bi-pencil"></i> Sửa hồ sơ</a>
                <?php else: ?>
                    <?php if ($friendStatus === 'accepted'): ?>
                        <form action="<?= url('/friends/' . (int) $user['id'] . '/remove') ?>" method="post">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-outline btn-sm" type="submit">Bạn bè</button>
                        </form>
                    <?php elseif ($friendStatus === 'pending_outgoing'): ?>
                        <form action="<?= url('/friends/' . (int) $user['id'] . '/cancel') ?>" method="post">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-outline btn-sm" type="submit">Đã gửi lời mời</button>
                        </form>
                    <?php elseif ($friendStatus === 'pending_incoming'): ?>
                        <form action="<?= url('/friends/request/' . (int) $user['id']) ?>" method="post">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-solid btn-sm" type="submit">Chấp nhận lời mời</button>
                        </form>
                    <?php else: ?>
                        <form action="<?= url('/friends/request/' . (int) $user['id']) ?>" method="post">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-outline btn-primary btn-sm" type="submit">Kết bạn</button>
                        </form>
                    <?php endif; ?>

                    <button class="btn btn-outline btn-sm" data-action="follow" data-user-id="<?= (int) $user['id'] ?>">
                        <i class="bi bi-person<?= $isFollowing ? '-check' : '-plus' ?>"></i>
                        <span><?= $isFollowing ? 'Đang theo dõi' : 'Theo dõi' ?></span>
                    </button>

                    <a class="btn btn-outline btn-sm" href="<?= url('/chat/start/' . (int) $user['id']) ?>" title="Nhắn tin">
                        <i class="bi bi-chat-dots"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (trim((string) $user['bio']) !== ''): ?>
            <p class="mb-2 mt-3"><?= nl2br(e($user['bio'])) ?></p>
        <?php endif; ?>

        <div class="small fg-secondary mb-3">
            <i class="bi bi-calendar3 me-1"></i>Tham gia <?= e(format_date($user['created_at'], 'd/m/Y')) ?>
        </div>

        <div class="d-flex gap-4 profile-stats">
            <a class="profile-stat" href="<?= url('/u/' . $user['username'] . '?tab=posts') ?>"><strong><?= (int) $stats['posts'] ?></strong><span>Bài viết</span></a>
            <a class="profile-stat" href="<?= url('/u/' . $user['username'] . '?tab=friends') ?>"><strong><?= (int) $stats['friends'] ?></strong><span>Bạn bè</span></a>
            <a class="profile-stat" href="<?= url('/u/' . $user['username'] . '?tab=followers') ?>"><strong><?= (int) $stats['followers'] ?></strong><span>Người theo dõi</span></a>
            <a class="profile-stat" href="<?= url('/u/' . $user['username'] . '?tab=following') ?>"><strong><?= (int) $stats['following'] ?></strong><span>Đang theo dõi</span></a>
        </div>
    </div>
</div>

<ul class="nav profile-tabs">
    <li class="nav-item"><a class="nav-link<?= $tab === 'posts' ? ' active' : '' ?>" href="<?= url('/u/' . $user['username']) ?>">Bài viết</a></li>
    <li class="nav-item"><a class="nav-link<?= $tab === 'comments' ? ' active' : '' ?>" href="<?= url('/u/' . $user['username'] . '?tab=comments') ?>">Bình luận</a></li>
    <li class="nav-item"><a class="nav-link<?= $tab === 'shared' ? ' active' : '' ?>" href="<?= url('/u/' . $user['username'] . '?tab=shared') ?>">Đã chia sẻ</a></li>
    <li class="nav-item"><a class="nav-link<?= $tab === 'friends' ? ' active' : '' ?>" href="<?= url('/u/' . $user['username'] . '?tab=friends') ?>">Bạn bè</a></li>
    <li class="nav-item"><a class="nav-link<?= $tab === 'followers' ? ' active' : '' ?>" href="<?= url('/u/' . $user['username'] . '?tab=followers') ?>">Người theo dõi</a></li>
    <li class="nav-item"><a class="nav-link<?= $tab === 'following' ? ' active' : '' ?>" href="<?= url('/u/' . $user['username'] . '?tab=following') ?>">Đang theo dõi</a></li>
</ul>

<?php if ($tab === 'posts'): ?>
    <?php if ($posts === []): ?>
        <div class="card surface-card"><div class="card-body text-center fg-secondary py-5">Chưa có bài viết nào.</div></div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <?php $data = post_view_data($post, $viewerId); ?>
            <?= view_partial(dirname(__DIR__) . '/partials/post-card.php', ['post' => $post, 'data' => $data]) ?>
        <?php endforeach; ?>
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
                                    <a class="fg-secondary" href="<?= url('/post/' . (int) $comment['post_id']) ?>">Bài viết #<?= (int) $comment['post_id'] ?></a>
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
                    <div class="py-2 border-bottom">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <img src="<?= e(avatar_url($share['author_avatar_path'])) ?>" alt="" class="avatar avatar-sm">
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
                        <a class="btn btn-outline btn-sm mt-2" href="<?= url('/post/' . (int) $share['post_id']) ?>">
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
    ?>
    <div class="card surface-card">
        <div class="card-body">
            <?php if ($people === []): ?>
                <p class="fg-secondary mb-0">Chưa có dữ liệu.</p>
            <?php else: ?>
                <?php foreach ($people as $person): ?>
                    <?= view_partial(dirname(__DIR__) . '/partials/person-row.php', ['person' => $person]) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
