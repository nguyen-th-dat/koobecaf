<?php
/**
 * @var string $content
 * @var string|null $title
 */
$currentUser = auth_user();
$theme = $currentUser['theme'] ?? (\App\Core\Session::get('theme') ?? 'light');
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';

/**
 * Dữ liệu realtime WebSocket.
 *
 * Trang chat tự truyền sẵn token + ticket. Mọi trang khác chỉ cần token đăng
 * nhập để nhận sự kiện feed và thông báo — token được cấp ở đây cho tất cả
 * người đã đăng nhập.
 */
$ws = $ws ?? [];

if (is_auth() && empty($ws['token']) && \App\Core\WebSocketToken::isEnabled()) {
    try {
        $ws['token'] = \App\Core\WebSocketToken::issue((int) auth_id());
        $ws['url'] = $ws['url'] ?? \App\Core\WebSocketToken::url();
        $ws['enabled'] = true;
    } catch (\Throwable $e) {
        \App\Core\Logger::error('Không thể cấp token WebSocket: ' . $e->getMessage());
    }
}

$ws['enabled'] = !empty($ws['enabled']) && !empty($ws['token']);
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&family=Montserrat:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_versioned('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset_versioned('css/toast.css') ?>">
<?php if (!empty($extraCss)): ?>
    <?php foreach ((array) $extraCss as $cssFile): ?>
    <link rel="stylesheet" href="<?= asset_versioned($cssFile) ?>">
    <?php endforeach; ?>
<?php endif; ?>
</head>
<body data-base-url="<?= e(rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/')) ?>" data-user-id="<?= (int) (auth_id() ?? 0) ?>" data-cleared-drafts="<?= e(implode(',', \App\Core\OldInput::clearedDrafts())) ?>"
      data-ws-enabled="<?= !empty($ws['enabled']) ? '1' : '0' ?>"
      data-ws-url="<?= e($ws['url'] ?? '') ?>"
      data-ws-token="<?= e($ws['token'] ?? '') ?>"
      data-ws-ticket="<?= e($ws['ticket'] ?? '') ?>"
      data-ws-conversation-id="<?= (int) ($ws['conversation_id'] ?? ($conversationId ?? 0)) ?>">
<?php require __DIR__ . '/partials/topbar.php'; ?>

<div class="app-shell">
    <div class="app-grid<?= is_auth() ? '' : ' is-guest' ?><?= (!empty($wide) && is_auth()) ? ' is-wide' : '' ?>">
        <?php if (is_auth()): ?>
            <aside class="app-rail app-rail-left">
                <?php require __DIR__ . '/partials/sidebar-left.php'; ?>
            </aside>
        <?php endif; ?>

        <main class="app-main">
            <?= $content ?>
        </main>

        <?php if (is_auth() && empty($wide)): ?>
            <aside class="app-rail app-rail-right">
                <?php if (!empty($rightRail)): ?>
                    <?php require __DIR__ . '/partials/' . $rightRail . '.php'; ?>
                <?php else: ?>
                    <?php require __DIR__ . '/partials/sidebar-right.php'; ?>
                <?php endif; ?>
            </aside>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($rightRail)): ?>
    <div class="app-rail-inline<?= is_auth() ? ' is-duplicate' : '' ?>">
        <?php require __DIR__ . '/partials/' . $rightRail . '.php'; ?>
    </div>
<?php endif; ?>

<?php if (is_auth()): ?>
    <?php require __DIR__ . '/partials/mobile-nav.php'; ?>
<?php else: ?>
    <?php require __DIR__ . '/partials/mobile-nav-guest.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

<?php require __DIR__ . '/partials/toast.php'; ?>

<?php require __DIR__ . '/partials/share-modal.php'; ?>

<script src="<?= asset_versioned('js/toast.js') ?>"></script>
<script src="<?= asset_versioned('js/app.js') ?>"></script>
<?php if (!empty($ws['enabled'])): ?>
<script src="<?= asset_versioned('js/realtime.js') ?>"></script>
<?php endif; ?>
</body>
</html>
