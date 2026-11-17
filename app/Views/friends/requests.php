<?php
/**
 * @var array $requests
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0"><i class="bi bi-inbox me-2"></i>Lời mời kết bạn</h1>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/friends') ?>"><i class="bi bi-arrow-left"></i> Bạn bè</a>
</div>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($requests === []): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                Không có lời mời kết bạn nào.
            </div>
        <?php else: ?>
            <ul class="list-unified">
                <?php foreach ($requests as $request): ?>
                    <li class="d-flex align-items-center gap-2 py-2">
                        <img src="<?= e(upload_url($request['avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-md">
                        <div class="flex-grow-1 min-w-0">
                            <a class="d-block fw-semibold text-body text-decoration-none text-truncate" href="<?= url('/u/' . $request['username']) ?>">
                                <?= e($request['display_name']) ?>
                            </a>
                            <small class="text-secondary">@<?= e($request['username']) ?> · <?= e(time_ago($request['created_at'])) ?></small>
                        </div>
                        <form action="<?= url('/friends/' . (int) $request['id'] . '/accept') ?>" method="post" class="d-inline">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-sm btn-primary" type="submit">Chấp nhận</button>
                        </form>
                        <form action="<?= url('/friends/' . (int) $request['id'] . '/decline') ?>" method="post" class="d-inline">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-secondary" type="submit">Từ chối</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
