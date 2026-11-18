<?php
/**
 * @var array $requests
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-inbox me-2"></i>Lời mời kết bạn</h1>
    <a class="btn btn-outline btn-sm" href="<?= url('/friends') ?>"><i class="bi bi-arrow-left"></i> Bạn bè</a>
</div>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($requests === []): ?>
            <div class="text-center fg-secondary py-4">
                <i class="bi bi-inbox d-block mb-2 fs-2xl"></i>
                Không có lời mời kết bạn nào.
            </div>
        <?php else: ?>
            <ul class="list-unified">
                <?php foreach ($requests as $request): ?>
                    <li class="d-flex align-items-center gap-2 py-2">
                        <img src="<?= e(avatar_url($request['avatar_path'])) ?>" alt="" class="avatar avatar-md">
                        <div class="flex-grow-1 min-w-0">
                            <a class="d-block fw-semibold fg-body-emphasis text-decoration-none text-truncate" href="<?= url('/u/' . $request['username']) ?>">
                                <?= e($request['display_name']) ?>
                            </a>
                            <small class="fg-secondary">@<?= e($request['username']) ?> · <?= e(time_ago($request['created_at'])) ?></small>
                        </div>
                        <form action="<?= url('/friends/' . (int) $request['id'] . '/accept') ?>" method="post" class="d-inline">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-solid btn-sm" type="submit">Chấp nhận</button>
                        </form>
                        <form action="<?= url('/friends/' . (int) $request['id'] . '/decline') ?>" method="post" class="d-inline">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-outline btn-sm" type="submit">Từ chối</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
