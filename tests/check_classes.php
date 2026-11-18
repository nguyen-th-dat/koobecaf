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

// Class do app.css định nghĩa
$defined = [];
if (preg_match_all('/\.([a-zA-Z][a-zA-Z0-9_-]*)/', $appCss, $m)) {
    $defined = array_unique($m[1]);
}
// Class do JavaScript gắn động
$defined = array_merge($defined, [
    'active', 'show', 'unread', 'is-following', 'd-none', 'comment-form',
    'comment-reply-btn', 'comment-edit-btn', 'comment-delete-btn',
    'count-1', 'count-2', 'count-3', 'count-4',
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
