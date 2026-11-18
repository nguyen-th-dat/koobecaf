<?php
/**
 * @var array $comment
 * @var array $replies
 */
$viewerId = auth_id();
$isMine = $viewerId !== null && (int) $comment['user_id'] === $viewerId;
?>
<div class="comment-item d-flex gap-2 py-2" data-comment-id="<?= (int) $comment['id'] ?>">
    <a href="<?= url('/u/' . $comment['username']) ?>">
        <img src="<?= e(avatar_url($comment['avatar_path'])) ?>" alt="" class="avatar avatar-sm">
    </a>
    <div class="flex-grow-1 min-w-0">
        <div class="comment-bubble">
            <a class="fw-semibold fg-body-emphasis text-decoration-none" href="<?= url('/u/' . $comment['username']) ?>"><?= e($comment['display_name']) ?></a>
            <p class="mb-0 comment-text" data-comment-text><?= nl2br(e($comment['content'])) ?></p>
        </div>
        <div class="small fg-secondary mt-1 d-flex align-items-center gap-2">
            <span><?= e(time_ago($comment['created_at'])) ?></span>
            <button type="button" class="btn btn-text btn-sm p-0 fg-secondary comment-reply-btn" data-comment-id="<?= (int) $comment['id'] ?>">Trả lời</button>
            <?php if ($isMine): ?>
                <button type="button" class="btn btn-text btn-sm p-0 fg-secondary comment-edit-btn" data-comment-id="<?= (int) $comment['id'] ?>">Sửa</button>
                <button type="button" class="btn btn-text btn-sm p-0 fg-danger comment-delete-btn" data-comment-id="<?= (int) $comment['id'] ?>">Xóa</button>
            <?php endif; ?>
        </div>

        <?php if (!empty($comment['replies'])): ?>
            <div class="comment-replies mt-2">
                <?php foreach ($comment['replies'] as $reply): ?>
                    <?= view_partial(__DIR__ . '/comment-item.php', ['comment' => $reply]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
