<?php
/**
 * @var string $content
 * @var string|null $title
 */
$currentUser = auth_user();
$theme = $currentUser['theme'] ?? (\App\Core\Session::get('theme') ?? 'light');
?>
<!DOCTYPE html>
<html lang="vi" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <title><?= e($title ?? 'Koobecaf') ?> · <?= e(config('app.name', 'Koobecaf')) ?></title>
    <link rel="icon" href="<?= asset('images/logo.png') ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_versioned('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset_versioned('css/toast.css') ?>">
</head>
<body class="auth-body" data-base-url="<?= e(rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/')) ?>" data-user-id="<?= (int) (auth_id() ?? 0) ?>" data-cleared-drafts="<?= e(implode(',', \App\Core\OldInput::clearedDrafts())) ?>">

<div class="auth-shell">
    <aside class="auth-panel">
        <a class="brand brand-lg" href="<?= url('/') ?>">
            <img src="<?= asset('images/logo.png') ?>" alt="Koobecaf" class="brand-logo">
            <span class="brand-text">Koobecaf</span>
        </a>

        <h2 class="auth-panel-title">Nơi kết nối bạn bè<br>và những khoảnh khắc.</h2>

        <ul class="auth-panel-list">
            <li><i class="bi bi-camera"></i> Chia sẻ khoảnh khắc mỗi ngày</li>
            <li><i class="bi bi-chat-heart"></i> Trò chuyện riêng tư với bạn bè</li>
            <li><i class="bi bi-people"></i> Kết nối cộng đồng sở thích chung</li>
        </ul>

        <p class="auth-panel-quote">"Cà phê đầu tiên trong ngày, cùng một nhóm bạn."</p>
    </aside>

    <main class="auth-main">
        <div class="auth-wrapper">
            <a class="brand brand-lg auth-brand-mobile lg:d-none mb-4" href="<?= url('/') ?>">
                <img src="<?= asset('images/logo.png') ?>" alt="Koobecaf" class="brand-logo">
                <span class="brand-text">Koobecaf</span>
            </a>

            <div class="card surface-card auth-card">
                <div class="card-body">
                    <?= $content ?>
                </div>
            </div>

            <p class="text-center fg-secondary small mt-3 mb-0">
                <a href="<?= url('/') ?>" class="fg-secondary"><i class="bi bi-arrow-left me-1"></i>Về trang chủ</a>
            </p>
        </div>
    </main>
</div>

<?php require __DIR__ . '/partials/toast.php'; ?>

<script src="<?= asset_versioned('js/toast.js') ?>"></script>
<script src="<?= asset_versioned('js/app.js') ?>"></script>
</body>
</html>
