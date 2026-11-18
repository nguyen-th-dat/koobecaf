<?php
/**
 * @var string $content
 * @var string|null $title
 */
$currentUser = auth_user();
$theme = $currentUser['theme'] ?? (\App\Core\Session::get('theme') ?? 'light');
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <title><?= e($title ?? 'Koobecaf') ?> · <?= e(config('app.name', 'Koobecaf')) ?></title>
    <link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body" data-base-url="<?= e(rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/')) ?>" data-user-id="<?= (int) (auth_id() ?? 0) ?>">
<div class="auth-wrapper">
    <a class="brand brand-lg justify-content-center mb-4" href="<?= url('/') ?>">
        <span class="brand-mark"><i class="bi bi-cup-hot-fill"></i></span>
        <span class="brand-text">Koobecaf</span>
    </a>

    <div class="card surface-card auth-card">
        <div class="card-body">
            <?php require __DIR__ . '/partials/flash.php'; ?>
            <?= $content ?>
        </div>
    </div>

    <p class="text-center fg-secondary small mt-3 mb-0">
        <a href="<?= url('/') ?>" class="fg-secondary"><i class="bi bi-arrow-left me-1"></i>Về trang chủ</a>
    </p>
</div>

<script type="module" src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
