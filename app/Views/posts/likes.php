<?php
/**
 * @var array $post
 * @var array $users
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0"><i class="bi bi-hand-thumbs-up me-2"></i>Người đã thích</h1>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/post/' . (int) $post['id']) ?>"><i class="bi bi-arrow-left"></i> Trở lại</a>
</div>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($users === []): ?>
            <p class="text-secondary mb-0">Chưa có ai thích bài viết này.</p>
        <?php else: ?>
            <ul class="list-unified">
                <?php foreach ($users as $person): ?>
                    <li class="d-flex align-items-center gap-2 py-2">
                        <img src="<?= e(upload_url($person['avatar_path']) ?? asset('images/default.svg')) ?>" alt="" class="avatar avatar-md">
                        <div class="flex-grow-1 min-w-0">
                            <a class="d-block fw-semibold text-body text-decoration-none text-truncate" href="<?= url('/u/' . $person['username']) ?>">
                                <?= e($person['display_name']) ?>
                            </a>
                            <small class="text-secondary">@<?= e($person['username']) ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
