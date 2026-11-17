<?php
/**
 * @var string $content
 * @var string|null $title
 */
$currentUser = auth_user();
$theme = $currentUser['theme'] ?? (\App\Core\Session::get('theme') ?? 'light');
$unreadNotifications = auth_id() !== null ? \App\Models\Notification::unreadCount(auth_id()) : 0;
$unreadMessages = auth_id() !== null ? \App\Models\Conversation::totalUnread(auth_id()) : 0;
$pendingRequests = auth_id() !== null ? \App\Models\Friendship::countPending(auth_id()) : 0;
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <title><?= e($title ?? 'Koobecaf') ?> · <?= e(config('app.name', 'Koobecaf')) ?></title>
    <meta name="description" content="Nền tảng mạng xã hội Koobecaf - kết nối bạn bè, chia sẻ khoảnh khắc và trò chuyện.">
<?php if (!empty($metaTags)): ?>
<?php foreach ($metaTags as $property => $metaContent): ?>
    <meta property="<?= e($property) ?>" content="<?= e($metaContent) ?>">
<?php endforeach; ?>
    <link rel="canonical" href="<?= e($canonicalUrl ?? url('/')) ?>">
<?php endif; ?>
    <link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body data-base-url="<?= e(rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/')) ?>" data-user-id="<?= (int) (auth_id() ?? 0) ?>">
<?php require __DIR__ . '/partials/topbar.php'; ?>

<div class="container-fluid app-shell">
    <div class="row g-0">
        <?php if (is_auth()): ?>
            <aside class="col-lg-2 sidebar-col d-none d-lg-block">
                <?php require __DIR__ . '/partials/sidebar-left.php'; ?>
            </aside>
        <?php endif; ?>

        <main class="<?= is_auth() ? (empty($wide) ? 'col-lg-6 col-xl-7' : 'col-lg-10') : 'col-12' ?> main-col">
            <?php require __DIR__ . '/partials/flash.php'; ?>
            <?= $content ?>
        </main>

        <?php if (is_auth() && empty($wide)): ?>
            <aside class="col-lg-3 col-xl-3 d-none d-lg-block sidebar-col">
                <?php require __DIR__ . '/partials/sidebar-right.php'; ?>
            </aside>
        <?php endif; ?>
    </div>
</div>

<?php if (is_auth()): ?>
    <?php require __DIR__ . '/partials/mobile-nav.php'; ?>
<?php endif; ?>

<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
