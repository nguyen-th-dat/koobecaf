<?php
$unreadMessages = auth_id() !== null ? \App\Models\Conversation::totalUnread(auth_id()) : 0;
$unreadNotifications = auth_id() !== null ? \App\Models\Notification::unreadCount(auth_id()) : 0;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = '/' . trim($path, '/');
?>
<nav class="mobile-nav lg:d-none">
    <a class="<?= $path === '/' ? 'active' : '' ?>" href="<?= url('/') ?>"><i class="bi bi-house-door"></i><span>Trang chủ</span></a>
    <a class="<?= str_starts_with($path, '/u/') ? 'active' : '' ?>" href="<?= url('/u/' . (auth_user()['username'] ?? '')) ?>"><i class="bi bi-person"></i><span>Cá nhân</span></a>
    <a class="<?= $path === '/chat' ? 'active' : '' ?>" href="<?= url('/chat') ?>">
        <i class="bi bi-chat-dots"></i><span>Tin nhắn</span>
        <span class="badge-dot<?= $unreadMessages > 0 ? '' : ' d-none' ?>" data-badge="messages"><?= (int) $unreadMessages ?></span>
    </a>
    <a class="<?= $path === '/notifications' ? 'active' : '' ?>" href="<?= url('/notifications') ?>">
        <i class="bi bi-bell"></i><span>Thông báo</span>
        <span class="badge-dot<?= $unreadNotifications > 0 ? '' : ' d-none' ?>" data-badge="notifications"><?= (int) $unreadNotifications ?></span>
    </a>
    <a class="<?= str_starts_with($path, '/settings') ? 'active' : '' ?>" href="<?= url('/settings/profile') ?>"><i class="bi bi-gear"></i><span>Cài đặt</span></a>
</nav>

<div class="drawer drawer-start" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="drawer-header">
        <a class="brand" href="<?= url('/') ?>">
            <img src="<?= asset('images/logo.png') ?>" alt="Koobecaf" class="brand-logo">
            <span class="brand-text">Koobecaf</span>
        </a>
        <button type="button" class="btn-close" data-drawer-dismiss aria-label="Đóng"></button>
    </div>
    <div class="drawer-body">
        <?php require __DIR__ . '/sidebar-left.php'; ?>
    </div>
</div>
