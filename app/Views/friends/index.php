<?php
/**
 * @var array $friends
 */
$viewerId = auth_id();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0"><i class="bi bi-people me-2"></i>Bạn bè</h1>
    <a class="btn btn-sm btn-outline-primary" href="<?= url('/friends/requests') ?>">
        <i class="bi bi-inbox me-1"></i>Lời mời
    </a>
</div>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($friends === []): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-people display-6 d-block mb-2"></i>
                Bạn chưa có bạn bè nào. Hãy kết nối với những người thú vị!
            </div>
        <?php else: ?>
            <ul class="list-unified">
                <?php foreach ($friends as $person): ?>
                    <li class="d-flex align-items-center gap-2 py-2">
                        <img src="<?= e(upload_url($person['avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-md">
                        <div class="flex-grow-1 min-w-0">
                            <a class="d-block fw-semibold text-body text-decoration-none text-truncate" href="<?= url('/u/' . $person['username']) ?>">
                                <?= e($person['display_name']) ?>
                            </a>
                            <small class="text-secondary">@<?= e($person['username']) ?></small>
                        </div>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/chat/start/' . (int) $person['id']) ?>"><i class="bi bi-chat-dots"></i></a>
                        <form action="<?= url('/friends/' . (int) $person['id'] . '/remove') ?>" method="post" data-confirm="Bạn có chắc muốn hủy kết bạn?">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">Hủy bạn</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
