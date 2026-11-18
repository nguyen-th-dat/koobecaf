<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = '/' . trim($path, '/');
$pendingRequests = auth_id() !== null ? \App\Models\Friendship::countPending(auth_id()) : 0;

$menu = [
    ['url' => '/',            'icon' => 'bi-house-door',      'label' => 'Trang chủ',     'exact' => true],
    ['url' => '/u/' . (auth_user()['username'] ?? ''), 'icon' => 'bi-person', 'label' => 'Trang cá nhân'],
    ['url' => '/friends',     'icon' => 'bi-people',         'label' => 'Bạn bè'],
    ['url' => '/chat',        'icon' => 'bi-chat-dots',      'label' => 'Tin nhắn'],
    ['url' => '/notifications', 'icon' => 'bi-bell',         'label' => 'Thông báo'],
    ['url' => '/settings/profile', 'icon' => 'bi-gear',      'label' => 'Cài đặt'],
];
?>
<nav class="side-nav">
    <ul class="nav flex-column">
        <?php foreach ($menu as $item): ?>
            <?php
            $active = $item['exact'] ?? false
                ? $path === $item['url']
                : ($path === $item['url'] || str_starts_with($path, $item['url'] . '/'));
            ?>
            <li class="nav-item">
                <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= url($item['url']) ?>">
                    <i class="bi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                    <?php if ($item['label'] === 'Bạn bè' && $pendingRequests > 0): ?>
                        <span class="ms-auto badge rounded-pill bg-primary"><?= (int) $pendingRequests ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
