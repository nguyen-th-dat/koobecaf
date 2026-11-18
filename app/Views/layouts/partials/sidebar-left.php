<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = '/' . trim($path, '/');
$pendingRequests = auth_id() !== null ? \App\Models\Friendship::countPending(auth_id()) : 0;
$unreadMessages = auth_id() !== null ? \App\Models\Conversation::totalUnread(auth_id()) : 0;
$unreadNotifications = auth_id() !== null ? \App\Models\Notification::unreadCount(auth_id()) : 0;

$menu = [
    ['url' => '/',            'icon' => 'bi-house-door',  'label' => 'Trang chủ', 'exact' => true],
    ['url' => '/posts/create', 'icon' => 'bi-pencil-square', 'label' => 'Đăng bài'],
    ['url' => '/u/' . (auth_user()['username'] ?? ''), 'icon' => 'bi-person', 'label' => 'Trang cá nhân'],
    ['url' => '/friends',     'icon' => 'bi-people',     'label' => 'Bạn bè', 'badge' => $pendingRequests],
    ['url' => '/chat',        'icon' => 'bi-chat-dots',  'label' => 'Tin nhắn', 'badge' => $unreadMessages, 'badgeKey' => 'messages'],
    ['url' => '/notifications', 'icon' => 'bi-bell',     'label' => 'Thông báo', 'badge' => $unreadNotifications, 'badgeKey' => 'notifications'],
    ['url' => '/settings/profile', 'icon' => 'bi-gear',  'label' => 'Cài đặt'],
];
?>
<nav class="side-nav">
    <ul class="nav flex-column gap-1">
        <?php foreach ($menu as $item): ?>
            <?php
            $active = $item['exact'] ?? false
                ? $path === $item['url']
                : ($path === $item['url'] || str_starts_with($path, $item['url'] . '/'));
            $badge = $item['badge'] ?? 0;
            ?>
            <li class="nav-item">
                <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= url($item['url']) ?>">
                    <i class="bi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                    <?php if ($badge > 0): ?>
                        <span class="ms-auto badge rounded-pill bg-primary"
                              <?= isset($item['badgeKey']) ? 'data-badge="' . e($item['badgeKey']) . '"' : '' ?>><?= $badge > 99 ? '99+' : (int) $badge ?></span>
                    <?php else: ?>
                        <span class="ms-auto badge rounded-pill bg-primary d-none"
                              <?= isset($item['badgeKey']) ? 'data-badge="' . e($item['badgeKey']) . '"' : '' ?>><?= (int) $badge ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
