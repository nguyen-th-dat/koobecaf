<?php
/**
 * @var string $content
 * @var string|null $title
 */
$currentUser = auth_user();
$theme = $currentUser['theme'] ?? (\App\Core\Session::get('theme') ?? 'light');
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="vi" data-theme="<?= e($theme) ?>">
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
    <link rel="icon" href="<?= asset('images/logo.png') ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body data-base-url="<?= e(rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/')) ?>" data-user-id="<?= (int) (auth_id() ?? 0) ?>">
<?php require __DIR__ . '/partials/topbar.php'; ?>

<div class="app-shell">
    <div class="app-grid<?= is_auth() ? '' : ' is-guest' ?><?= (!empty($wide) && is_auth()) ? ' is-wide' : '' ?>">
        <?php if (is_auth()): ?>
            <aside class="app-rail app-rail-left">
                <?php require __DIR__ . '/partials/sidebar-left.php'; ?>
            </aside>
        <?php endif; ?>

        <main class="app-main">
            <?php require __DIR__ . '/partials/flash.php'; ?>
            <?= $content ?>
        </main>

        <?php if (is_auth() && empty($wide)): ?>
            <aside class="app-rail app-rail-right">
                <?php require __DIR__ . '/partials/sidebar-right.php'; ?>
            </aside>
        <?php endif; ?>
    </div>
</div>

<?php if (is_auth()): ?>
    <?php require __DIR__ . '/partials/mobile-nav.php'; ?>
<?php else: ?>
    <?php require __DIR__ . '/partials/mobile-nav-guest.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
