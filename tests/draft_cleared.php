<?php

declare(strict_types=1);

/**
 * Kiểm tra cơ chế báo xoá nháp cho trình duyệt sau khi form gửi thành công.
 *
 * Chạy: php tests/draft_cleared.php http://127.0.0.1:8931
 */

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8931', '/');
$passed = 0;
$failed = 0;
$cookieJar = tempnam(sys_get_temp_dir(), 'kbdraft');

function request(string $method, string $url, array $options = []): array
{
    global $cookieJar;

    $headers = $options['headers'] ?? [];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_COOKIEJAR      => $cookieJar,
        CURLOPT_COOKIEFILE     => $cookieJar,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER         => true,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    if (isset($options['form'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options['form']);
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $rawHeaders = substr((string) $response, 0, $headerSize);

    preg_match('/location:\s*(.+)/i', $rawHeaders, $m);

    return [
        'status'   => $status,
        'body'     => substr((string) $response, $headerSize),
        'headers'  => $rawHeaders,
        'location' => isset($m[1]) ? trim($m[1]) : null,
    ];
}

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "[PASS] {$name}\n";

        return;
    }

    $failed++;
    echo "[FAIL] {$name}" . ($detail !== '' ? " - {$detail}" : '') . "\n";
}

function csrfToken(string $html): string
{
    if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/name="_token" value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }

    return '';
}

function bodyAttr(string $html, string $attr): string
{
    if (preg_match('/data-cleared-drafts="([^"]*)"/', $html, $m)) {
        return $m[1];
    }

    return '';
}

$base = $baseUrl;

// ------------------------------------------------------------------
// 1. Dang ky that bai: trang tiep theo phai bao ten field co gia tri cu
// ------------------------------------------------------------------
$username = 'draft' . random_int(10000, 99999);
$email = $username . '@koobecaf.test';

$res = request('GET', $base . '/register');
$res = request('POST', $base . '/register', ['form' => [
    '_token'                => csrfToken($res['body']),
    'username'              => $username,
    'email'                 => $email,
    'display_name'          => 'Ten Draft',
    'password'              => 'short',
    'password_confirmation' => 'short',
]]);

$res = request('GET', $base . '/register');
check('Trang sau validate that bai bao field co gia tri cu', str_contains($res['body'], 'data-old-fields="display_name,username,email"'), 'data-old-fields=' . bodyAttr($res['body'], 'old'));

// ------------------------------------------------------------------
// 2. Dang nhap thanh cong: trang chu phai bao xoa nhap
// ------------------------------------------------------------------
$res = request('GET', $base . '/register');
request('POST', $base . '/register', ['form' => [
    '_token'                => csrfToken($res['body']),
    'username'              => $username,
    'email'                 => $email,
    'display_name'          => 'Ten Draft',
    'password'              => 'Password@123',
    'password_confirmation' => 'Password@123',
]]);

$res = request('GET', $base . '/');
check('Trang chu bao xoa nhap dang ky/dang nhap', str_contains(bodyAttr($res['body'], 'cleared'), 'login,register'), 'cleared=' . bodyAttr($res['body'], 'cleared'));

// ------------------------------------------------------------------
// 3. Dang bai that bai: nhieu nhat la 1 lan
// ------------------------------------------------------------------
$res = request('POST', $base . '/posts', ['form' => [
    '_token'  => csrfToken($res['body']),
    'content' => str_repeat('x', 5001),
    'privacy' => 'public',
]]);
check('Dang bai that bai chuyen huong', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/');
check('Trang chu bao field gia tri cu cua composer', str_contains($res['body'], 'data-old-fields="content,privacy"'), 'data-old-fields=' . bodyAttr($res['body'], 'old'));
check('Trang chu KHONG bao xoa nhap composer khi bai that bai', !str_contains(bodyAttr($res['body'], 'cleared'), 'composer'), 'cleared=' . bodyAttr($res['body'], 'cleared'));

// ------------------------------------------------------------------
// 4. Dang bai thanh cong: phai bao xoa nhap composer
// ------------------------------------------------------------------
$res = request('POST', $base . '/posts', ['form' => [
    '_token'  => csrfToken($res['body']),
    'content' => 'Bai viet thanh cong ' . random_int(10000, 99999),
    'privacy' => 'public',
]]);
check('Dang bai thanh cong chuyen huong', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/');
check('Trang chu bao xoa nhap composer sau khi dang thanh cong', str_contains(bodyAttr($res['body'], 'cleared'), 'composer'), 'cleared=' . bodyAttr($res['body'], 'cleared'));

// Danh sách xoá chỉ dùng một lần
$res = request('GET', $base . '/');
check('Danh sach xoa nhap chi dung mot lan', !str_contains(bodyAttr($res['body'], 'cleared'), 'composer'), 'cleared=' . bodyAttr($res['body'], 'cleared'));

// ------------------------------------------------------------------
// 5. Cap nhat ho so thanh cong: phai bao xoa nhap profile
// ------------------------------------------------------------------
$res = request('GET', $base . '/settings/profile');
request('POST', $base . '/settings/profile', ['form' => [
    '_token'       => csrfToken($res['body']),
    'display_name' => 'Ten Da Cap Nhat ' . random_int(1000, 9999),
    'bio'          => 'Tieu su moi',
]]);

$res = request('GET', $base . '/settings/profile');
check('Trang cai dat bao xoa nhap profile sau khi luu thanh cong', str_contains(bodyAttr($res['body'], 'cleared'), 'profile'), 'cleared=' . bodyAttr($res['body'], 'cleared'));

@unlink($cookieJar);

echo "\n================================\n";
echo "PASS: {$passed} | FAIL: {$failed}\n";
echo "================================\n";

exit($failed === 0 ? 0 : 1);
