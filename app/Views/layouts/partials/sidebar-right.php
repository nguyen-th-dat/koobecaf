<?php
$userId = auth_id();
$suggestions = $userId !== null ? \App\Models\User::suggestions($userId, 5) : [];
$following = $userId !== null ? \App\Models\User::followingSuggestions($userId, 5) : [];
?>
<div class="side-right">
    <div class="card surface-card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="bi bi-people me-2"></i>Gợi ý kết bạn</h6>
            <?php if ($suggestions === []): ?>
                <p class="fg-secondary small mb-0">Không có gợi ý mới.</p>
            <?php else: ?>
                <ul class="list-unified">
                    <?php foreach ($suggestions as $person): ?>
                        <li class="d-flex align-items-center gap-2 py-2">
                            <img src="<?= e(upload_url($person['avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-md">
                            <div class="flex-grow-1 min-w-0">
                                <a class="d-block text-truncate fw-semibold fg-body-emphasis text-decoration-none" href="<?= url('/u/' . $person['username']) ?>">
                                    <?= e($person['display_name']) ?>
                                </a>
                                <small class="fg-secondary">@<?= e($person['username']) ?></small>
                            </div>
                            <button class="btn btn-outline btn-primary btn-sm follow-btn" data-user-id="<?= (int) $person['id'] ?>">Theo dõi</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="card surface-card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="bi bi-bookmark me-2"></i>Đang theo dõi</h6>
            <?php if ($following === []): ?>
                <p class="fg-secondary small mb-0">Bạn chưa theo dõi ai.</p>
            <?php else: ?>
                <ul class="list-unified">
                    <?php foreach ($following as $person): ?>
                        <li class="d-flex align-items-center gap-2 py-2">
                            <img src="<?= e(upload_url($person['avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-sm">
                            <div class="min-w-0">
                                <a class="d-block text-truncate fg-body-emphasis text-decoration-none" href="<?= url('/u/' . $person['username']) ?>">
                                    <?= e($person['display_name']) ?>
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <p class="small fg-secondary px-2 mb-0">
        © <?= date('Y') ?> Koobecaf ·
        <a class="fg-secondary" href="<?= url('/') ?>">Trang chủ</a>
    </p>
</div>
