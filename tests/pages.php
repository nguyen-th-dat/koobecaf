<?php

declare(strict_types=1);

/**
 * Kiểm tra tất cả các trang render không lỗi PHP.
 *
 * Chạy: php tests/pages.php http://127.0.0.1:8931
 */

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8931', '/');
$cookieJar = tempnam(sys_get_temp_dir(), 'kbcookie');
$failed = 0;

function get(string $url): array
{
    global $cookieJar;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $cookieJar,
        CURLOPT_COOKIEFILE     => $cookieJar,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = (string) curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'body' => $body];
}

function csrfToken(string $html): string
{
    if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) {
        return $m[1];
    }

    return '';
}

function post(string $url, array $form): array
{
    global $cookieJar;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $form,
        CURLOPT_COOKIEJAR      => $cookieJar,
        CURLOPT_COOKIEFILE     => $cookieJar,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = (string) curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'body' => $body];
}

$errors = ['Fatal error', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:', 'Undefined variable', 'Uncaught'];

/**
 * @param array<int,string> $expectStatus
 */
function assertPage(string $label, array $result, array $expectStatus = [200]): void
{
    global $errors, $failed;

    $problem = '';
    foreach ($errors as $needle) {
        if (str_contains($result['body'], $needle)) {
            $problem = $needle;
            break;
        }
    }

    $ok = in_array($result['status'], $expectStatus, true) && $problem === '';
    if ($ok) {
        echo "[PASS] {$label}\n";

        return;
    }

    $failed++;
    echo "[FAIL] {$label} - status={$result['status']}" . ($problem !== '' ? " loi={$problem}" : '') . "\n";
    if ($problem !== '') {
        $pos = strpos($result['body'], $problem);
        echo '       ' . trim(substr($result['body'], max(0, $pos - 120), 260)) . "\n";
    }
}

// --- Goc (khong dang nhap) ---
assertPage('Trang chu (khach)', get($baseUrl . '/'));
assertPage('Dang nhap', get($baseUrl . '/login'));
assertPage('Dang ky', get($baseUrl . '/register'));
assertPage('Tim kiem (trong)', get($baseUrl . '/search?q=an'));
assertPage('Trang ho so', get($baseUrl . '/u/an'));
assertPage('Chi tiet bai viet', get($baseUrl . '/post/1'));
assertPage('Permalink bai viet', get($baseUrl . '/p/1'));
assertPage('Trang 404', get($baseUrl . '/khong-co-that'), [404]);

// --- Dang nhap ---
$login = get($baseUrl . '/login');
post($baseUrl . '/login', [
    '_token'   => csrfToken($login['body']),
    'email'    => 'an@koobecaf.test',
    'password' => 'Password@123',
]);

assertPage('Trang chu (da dang nhap)', get($baseUrl . '/'));
assertPage('Feed', get($baseUrl . '/feed'));
assertPage('Trang ca nhan cua minh', get($baseUrl . '/u/an'));
assertPage('Tab bai viet', get($baseUrl . '/u/an?tab=posts'));
assertPage('Tab binh luan', get($baseUrl . '/u/an?tab=comments'));
assertPage('Tab da chia se', get($baseUrl . '/u/an?tab=shared'));
assertPage('Tab ban be', get($baseUrl . '/u/an?tab=friends'));
assertPage('Tab followers', get($baseUrl . '/u/an?tab=followers'));
assertPage('Tab following', get($baseUrl . '/u/an?tab=following'));
assertPage('Ban be', get($baseUrl . '/friends'));
assertPage('Loi moi ket ban', get($baseUrl . '/friends/requests'));
assertPage('Tin nhan danh sach', get($baseUrl . '/chat'));
assertPage('Cuoc tro chuyen', get($baseUrl . '/chat/1'));
assertPage('Thong bao', get($baseUrl . '/notifications'));
assertPage('Cai dat ho so', get($baseUrl . '/settings/profile'));
assertPage('Chinh sua bai viet', get($baseUrl . '/posts/1/edit'));
assertPage('Danh sach nguoi da thich', get($baseUrl . '/posts/1/likes'));
assertPage('Tim kiem co ket qua', get($baseUrl . '/search?q=ca+phe'));
assertPage('API feed', get($baseUrl . '/api/feed'));
assertPage('API thong bao chua doc', get($baseUrl . '/api/notifications/unread'));
assertPage('API tin nhan chua doc', get($baseUrl . '/api/messages/unread'));
assertPage('API tim kiem nguoi dung', get($baseUrl . '/api/search/users?q=an'));
assertPage('API tim kiem bai viet', get($baseUrl . '/api/search/posts?q=ca+phe'));
assertPage('API binh luan', get($baseUrl . '/api/posts/1/comments'));

@unlink($cookieJar);

echo "\n================================\n";
echo "FAIL: {$failed}\n";
echo "================================\n";

exit($failed === 0 ? 0 : 1);
