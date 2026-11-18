<?php
/**
 * Kiểm tra các class dùng trong view có được định nghĩa trong app.css hay không.
 * Chạy: php tests/check_classes.php
 */
$root = dirname(__DIR__);
$appCss = file_get_contents($root . '/assets/css/app.css');
// CSS escape dấu hai chấm trong selector (vd .lg\:col-2) — bỏ backslash để so khớp
$appCss = str_replace('\\', '', $appCss);

// Hệ thống toast nằm ở file riêng nên cũng phải được kiểm tra.
$toastCss = is_file($root . '/assets/css/toast.css')
    ? str_replace('\\', '', file_get_contents($root . '/assets/css/toast.css'))
    : '';
$appCss .= $toastCss;

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

// Class do app.css định nghĩa (bao gồm selector có dấu hai chấm như .lg\:col-2)
$defined = [];
if (preg_match_all('/\.([a-zA-Z][a-zA-Z0-9_:-]*)/', $appCss, $m)) {
    $defined = array_unique($m[1]);
}
// Class do JavaScript gắn động hoặc hook chỉ mang tính ngữ nghĩa (không cần style)
$defined = array_merge($defined, [
    'active', 'unread', 'is-following', 'comment-form',
    'comment-reply-btn', 'comment-edit-btn', 'comment-delete-btn',
    'count-1', 'count-2', 'count-3', 'count-4',
    'chat-header', 'chat-input', 'image-preview', 'notification-list',
    'comment-item', 'comment-list', 'shared-header', 'post-body', 'post-stats',
    'post-comments', 'profile-card', 'brand-text', 'auth-card',
]);

$missing = [];
foreach ($classes as $cls => $files) {
    // Bỏ qua icon bootstrap-icons và các mảnh class nội suy từ PHP
    if (str_starts_with($cls, 'bi') || str_ends_with($cls, '-')) {
        continue;
    }
    if (in_array($cls, $defined, true)) {
        continue;
    }
    $missing[$cls] = array_unique($files);
}

if ($missing === []) {
    echo "OK: mọi class đều được định nghĩa trong app.css.\n";
    exit(0);
}

echo "Class không tìm thấy:\n";
foreach ($missing as $cls => $files) {
    echo "  - {$cls}  (" . implode(', ', $files) . ")\n";
}
exit(1);
