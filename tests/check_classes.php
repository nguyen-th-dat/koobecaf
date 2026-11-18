<?php
/**
 * Kiểm tra các class dùng trong view có tồn tại trong Bootstrap 6 hay không.
 * Chạy: php tests/check_classes.php
 */
$root = dirname(__DIR__);
$css = file_get_contents($root . '/assets/vendor/bootstrap/css/bootstrap.min.css');
$appCss = file_get_contents($root . '/assets/css/app.css');

$classes = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app/Views'));
foreach ($rii as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $content = file_get_contents($file->getPathname());
    if (preg_match_all('/class="([^"]+)"/', $content, $m)) {
        foreach ($m[1] as $chunk) {
            // Bỏ qua các đoạn PHP chen vào class
            $chunk = preg_replace('/<\?.*?\?>/', ' ', $chunk);
            foreach (preg_split('/\s+/', trim($chunk)) as $cls) {
                if ($cls === '' || str_contains($cls, '<?')) {
                    continue;
                }
                $classes[$cls][] = str_replace($root . '/', '', $file->getPathname());
            }
        }
    }
}

// Class do app.css định nghĩa hoặc do PHP sinh ra
$defined = [];
if (preg_match_all('/\.([a-zA-Z][a-zA-Z0-9_-]*)/', $appCss, $m)) {
    $defined = array_unique($m[1]);
}
$defined = array_merge($defined, [
    'active', 'show', 'unread', 'd-none', 'min-w-0', 'list-unified', 'surface-card',
    'avatar', 'avatar-sm', 'avatar-md', 'avatar-lg', 'avatar-xl', 'avatar-fallback',
    'post-card', 'post-text', 'post-images', 'post-actions', 'post-stats', 'post-body',
    'post-comments', 'comment-list', 'comment-item', 'comment-bubble', 'comment-text',
    'comment-replies', 'shared-original', 'shared-header', 'image-preview',
    'profile-card', 'profile-cover', 'cover-empty', 'profile-avatar-row', 'profile-stats',
    'profile-stat', 'profile-tabs', 'chat-layout', 'chat-sidebar', 'chat-conversation-list',
    'chat-conversation', 'chat-main', 'chat-header', 'chat-messages', 'chat-bubble-row',
    'chat-bubble', 'chat-text', 'chat-time', 'chat-input', 'notification-item',
    'notification-list', 'auth-body', 'auth-wrapper', 'auth-title', 'auth-card',
    'mobile-nav', 'icon-btn', 'badge-dot', 'brand', 'brand-mark', 'brand-text', 'brand-lg',
    'search-form', 'search-results', 'side-nav', 'side-right', 'topbar', 'app-shell',
    'main-col', 'sidebar-col', 'follow-btn', 'theme-btn', 'surface-card', 'badge-dot',
    'input-group', 'btn-group', 'row', 'col', 'container-fluid', 'container',
]);

$missing = [];
foreach ($classes as $cls => $files) {
    if (in_array($cls, $defined, true)) {
        continue;
    }
    // Tìm trong CSS của Bootstrap (escape ký tự đặc biệt như ':')
    $needle = '.' . preg_quote($cls, '/');
    if (preg_match('/' . $needle . '(?![\w-])/', $css)) {
        continue;
    }
    $missing[$cls] = array_unique($files);
}

if ($missing === []) {
    echo "OK: mọi class đều tồn tại trong Bootstrap 6 hoặc app.css.\n";
    exit(0);
}

echo "Class không tìm thấy:\n";
foreach ($missing as $cls => $files) {
    echo "  - {$cls}  (" . implode(', ', $files) . ")\n";
}
exit(1);
