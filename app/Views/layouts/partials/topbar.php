<?php
/**
 * @var array|null $currentUser
 */
$unreadNotifications = auth_id() !== null ? \App\Models\Notification::unreadCount(auth_id()) : 0;
$unreadMessages = auth_id() !== null ? \App\Models\Conversation::totalUnread(auth_id()) : 0;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = '/' . trim($path, '/');

/** Đánh dấu mục điều hướng ngang đang active */
$navActive = static function (string $prefix) use ($path): string {
    return ($prefix === '/' ? $path === '/' : str_starts_with($path, $prefix)) ? ' active' : '';
};
?>
<header class="topbar sticky-top">
    <div class="container-fluid">
        <div class="topbar-inner">
            <a class="brand" href="<?= url('/') ?>">
                <img src="<?= asset('images/logo.png') ?>" alt="Koobecaf" class="brand-logo">
                <span class="brand-text">Koobecaf</span>
            </a>

            <nav class="topbar-nav" aria-label="Điều hướng chính">
                <a class="nav-link<?= $navActive('/') ?>" href="<?= url('/') ?>">Bảng tin</a>
                <a class="nav-link<?= $navActive('/search') ?>" href="<?= url('/search') ?>">Khám phá</a>
                <?php if (is_auth()): ?>
                    <a class="nav-link<?= $navActive('/chat') ?>" href="<?= url('/chat') ?>">Trò chuyện</a>
                <?php endif; ?>
            </nav>

            <div class="topbar-actions ms-auto">
                <form class="search-form" action="<?= url('/search') ?>" method="get" role="search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" class="form-control" placeholder="Tìm kiếm" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Tìm kiếm">
                </form>

                <?php if (is_auth()): ?>
                    <a class="btn btn-solid btn-sm topbar-cta" href="<?= url('/posts/create') ?>" title="Đăng bài">
                        <i class="bi bi-pencil-square"></i><span>Đăng bài</span>
                    </a>

                    <a class="topbar-icon-btn" href="<?= url('/notifications') ?>" title="Thông báo" aria-label="Thông báo">
                        <i class="bi bi-bell"></i>
                        <span class="badge-dot<?= $unreadNotifications > 0 ? '' : ' d-none' ?>" data-badge="notifications"><?= $unreadNotifications > 99 ? '99+' : (int) $unreadNotifications ?></span>
                    </a>

                    <a class="topbar-icon-btn" href="<?= url('/chat') ?>" title="Tin nhắn" aria-label="Tin nhắn">
                        <i class="bi bi-chat-dots"></i>
                        <span class="badge-dot<?= $unreadMessages > 0 ? '' : ' d-none' ?>" data-badge="messages"><?= $unreadMessages > 99 ? '99+' : (int) $unreadMessages ?></span>
                    </a>

                    <button class="btn icon-btn" type="button" data-menu-toggle data-menu-placement="bottom-end" aria-expanded="false" aria-label="Tài khoản">
                        <img src="<?= e(avatar_url($currentUser['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-sm">
                    </button>
                    <ul class="menu">
                        <li class="menu-header">
                            <div class="fw-semibold fg-body-emphasis"><?= e($currentUser['display_name'] ?? '') ?></div>
                            <div class="small fg-secondary">@<?= e($currentUser['username'] ?? '') ?></div>
                        </li>
                        <li><hr class="menu-divider"></li>
                        <li><a class="menu-item" href="<?= url('/u/' . ($currentUser['username'] ?? '')) ?>"><i class="bi bi-person me-2"></i>Trang cá nhân</a></li>
                        <li><a class="menu-item" href="<?= url('/notifications') ?>"><i class="bi bi-bell me-2"></i>Thông báo<?= $unreadNotifications > 0 ? ' (' . $unreadNotifications . ')' : '' ?></a></li>
                        <li><a class="menu-item" href="<?= url('/settings/profile') ?>"><i class="bi bi-gear me-2"></i>Cài đặt</a></li>
                        <li><hr class="menu-divider"></li>
                        <li>
                            <form action="<?= url('/logout') ?>" method="post" class="px-2" data-leave-guard="Đăng xuất">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="menu-item fg-danger w-100" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</button>
                            </form>
                        </li>
                    </ul>
                <?php else: ?>
                    <a class="btn btn-outline btn-sm" href="<?= url('/login') ?>">Đăng nhập</a>
                    <a class="btn btn-solid btn-sm d-none sm:d-inline-flex" href="<?= url('/register') ?>">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
