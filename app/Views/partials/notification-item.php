<?php
/**
 * Một dòng thông báo trong danh sách.
 *
 * Dùng chung cho trang /notifications và cho phần render realtime (API),
 * đảm bảo giao diện luôn giống nhau.
 *
 * @var array $notification Dòng từ Notification::forUser() (đã join users)
 */
$isUnread = (int) ($notification['is_read'] ?? 0) === 0;
$type = (string) ($notification['type'] ?? '');
$actorName = (string) ($notification['actor_display_name'] ?? '');
$actorUsername = (string) ($notification['actor_username'] ?? '');
?>
<li class="notification-item d-flex align-items-center gap-3 py-3<?= $isUnread ? ' unread' : '' ?>"
    data-notification-id="<?= (int) ($notification['id'] ?? 0) ?>">
    <span class="notification-icon">
        <i class="bi <?= e(notification_icon($type)) ?>"></i>
    </span>
    <img src="<?= e(avatar_url($notification['actor_avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-sm">
    <div class="person-meta">
        <div class="small">
            <a class="fw-semibold fg-body-emphasis text-decoration-none" href="<?= url('/u/' . $actorUsername) ?>">
                <?= e($actorName) ?>
            </a>
            <?= e(notification_label($type)) ?>
        </div>
        <small class="fg-secondary"><?= e(time_ago($notification['created_at'] ?? null)) ?></small>
    </div>
    <?php if ($isUnread): ?>
        <span class="unread-dot ms-auto" title="Chưa đọc"></span>
    <?php endif; ?>
    <a class="btn btn-subtle btn-sm" href="<?= e(url(\App\Controllers\NotificationController::targetUrl($notification))) ?>">
        <i class="bi bi-arrow-right"></i>
    </a>
</li>
