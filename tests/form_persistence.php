<?php

declare(strict_types=1);

/**
 * Kiểm tra tính năng giữ giá trị form: gửi lỗi validate thì field đã nhập
 * phải còn nguyên sau khi redirect.
 *
 * Chạy: php tests/form_persistence.php http://127.0.0.1:8931
 */

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8931', '/');
$passed = 0;
$failed = 0;
$cookieJar = tempnam(sys_get_temp_dir(), 'kbform');

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
    $body = substr((string) $response, $headerSize);

    preg_match('/location:\s*(.+)/i', $rawHeaders, $m);

    return [
        'status'   => $status,
        'body'     => $body,
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

/** Trích giá trị của một input theo name từ HTML. */
function fieldValue(string $html, string $name): ?string
{
    $pattern = '/<[^>]*name="' . preg_quote($name, '/') . '"[^>]*value="([^"]*)"/';
    if (preg_match($pattern, $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }

    $pattern = '/<textarea[^>]*name="' . preg_quote($name, '/') . '"[^>]*>(.*?)<\/textarea>/s';
    if (preg_match($pattern, $html, $m)) {
        return html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
    }

    return null;
}

$base = $baseUrl;

// ------------------------------------------------------------------
// 1. Form trong HTML có đánh dấu data-guard (chưa đăng nhập)
// ------------------------------------------------------------------
{
    $res = request('GET', $base . '/register');
    check('Form dang ky co danh dau data-guard', str_contains($res['body'], 'data-draft="register" data-guard="Thông tin đăng ký"'));

    $res = request('GET', $base . '/login');
    check('Form dang nhap co danh dau data-guard', str_contains($res['body'], 'data-draft="login" data-guard="Thông tin đăng nhập"'));
}

// ------------------------------------------------------------------
// 2. Dang ky that bai: giu lai username / email / ten hien thi
// ------------------------------------------------------------------
$username = 'keep' . random_int(10000, 99999);
$email = $username . '@koobecaf.test';

$res = request('GET', $base . '/register');
$token = csrfToken($res['body']);

$res = request('POST', $base . '/register', ['form' => [
    '_token'                => $token,
    'username'              => $username,
    'email'                 => $email,
    'display_name'          => 'Ten Giu Nguyen',
    'password'              => 'short',
    'password_confirmation' => 'short',
]]);
check('Dang ky that bai chuyen huong ve /register', $res['status'] === 302 && str_contains((string) $res['location'], '/register'), 'status=' . $res['status']);

$res = request('GET', $base . '/register');
check('Giu lai username sau khi validate that bai', fieldValue($res['body'], 'username') === $username, 'gia tri=' . var_export(fieldValue($res['body'], 'username'), true));
check('Giu lai email sau khi validate that bai', fieldValue($res['body'], 'email') === $email, 'gia tri=' . var_export(fieldValue($res['body'], 'email'), true));
check('Giu lai ten hien thi sau khi validate that bai', fieldValue($res['body'], 'display_name') === 'Ten Giu Nguyen', 'gia tri=' . var_export(fieldValue($res['body'], 'display_name'), true));
check('Khong giu lai mat khau', !str_contains($res['body'], 'value="short"'));

// Gia tri cu da duoc doc xoa khoi session (khong dung lai lan nua)
$res2 = request('GET', $base . '/register');
check('Gia tri cu chi dung cho mot lan', fieldValue($res2['body'], 'username') === '', 'gia tri=' . var_export(fieldValue($res2['body'], 'username'), true));

// ------------------------------------------------------------------
// 2. Dang nhap that bai: giu lai email
// ------------------------------------------------------------------
$res = request('POST', $base . '/register', ['form' => [
    '_token'                => csrfToken($res['body']),
    'username'              => $username,
    'email'                 => $email,
    'display_name'          => 'Ten Giu Nguyen',
    'password'              => 'Password@123',
    'password_confirmation' => 'Password@123',
]]);
check('Dang ky thanh cong', $res['status'] === 302, 'status=' . $res['status']);

request('POST', $base . '/logout', ['form' => ['_token' => csrfToken(request('GET', $base . '/')['body'])]]);

$res = request('GET', $base . '/login');
$res = request('POST', $base . '/login', ['form' => [
    '_token'   => csrfToken($res['body']),
    'email'    => 'khong-ton-tai@koobecaf.test',
    'password' => 'SaiMatKhau@123',
]]);
check('Dang nhap that bai chuyen huong ve /login', $res['status'] === 302 && str_contains((string) $res['location'], '/login'), 'status=' . $res['status']);

$res = request('GET', $base . '/login');
check('Giu lai email sau khi dang nhap that bai', fieldValue($res['body'], 'email') === 'khong-ton-tai@koobecaf.test', 'gia tri=' . var_export(fieldValue($res['body'], 'email'), true));

// ------------------------------------------------------------------
// 3. Ho so: giu lai ten hien thi va tieu su
// ------------------------------------------------------------------
$res = request('GET', $base . '/login');
request('POST', $base . '/login', ['form' => [
    '_token'   => csrfToken($res['body']),
    'email'    => $email,
    'password' => 'Password@123',
]]);

$res = request('GET', $base . '/settings/profile');
$token = csrfToken($res['body']);

$res = request('POST', $base . '/settings/profile', ['form' => [
    '_token'       => $token,
    'display_name' => '',
    'bio'          => 'Tieu su can giu lai',
]]);
check('Cap nhat ho so that bai chuyen huong', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/settings/profile');
check('Giu lai tieu su sau khi cap nhat ho so that bai', fieldValue($res['body'], 'bio') === 'Tieu su can giu lai', 'gia tri=' . var_export(fieldValue($res['body'], 'bio'), true));

// ------------------------------------------------------------------
// 4. Chinh sua bai viet: giu lai noi dung
// ------------------------------------------------------------------
$res = request('POST', $base . '/posts', ['form' => [
    '_token'  => csrfToken(request('GET', $base . '/')['body']),
    'content' => 'Bai viet de sua ' . random_int(10000, 99999),
    'privacy' => 'public',
]]);
preg_match('/data-post-id="(\d+)"/', request('GET', $base . '/')['body'], $m);
$postId = (int) ($m[1] ?? 0);
check('Co bai viet de sua', $postId > 0);

$res = request('GET', $base . '/posts/' . $postId . '/edit');
$token = csrfToken($res['body']);

$res = request('POST', $base . '/posts/' . $postId . '/update', ['form' => [
    '_token'  => $token,
    'content' => str_repeat('a', 5001),
    'privacy' => 'followers',
]]);
check('Sua bai viet that bai chuyen huong', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/posts/' . $postId . '/edit');
check('Giu lai quyen rieng tu sau khi sua that bai', str_contains($res['body'], '<option value="followers" selected>'), 'khong thay option selected');

// ------------------------------------------------------------------
// 5. Trang dang bai: giu lai noi dung composer khi dang bai that bai
// ------------------------------------------------------------------
$res = request('GET', $base . '/posts/create');
$token = csrfToken($res['body']);
check('Trang dang bai render khong loi', $res['status'] === 200, 'status=' . $res['status']);

$res = request('POST', $base . '/posts', ['form' => [
    '_token'  => $token,
    'content' => str_repeat('b', 5001),
    'privacy' => 'public',
]]);
check('Dang bai that bai chuyen huong', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/posts/create');
check('Giu lai noi dung composer sau khi dang that bai', str_contains($res['body'], str_repeat('b', 5001)), 'khong tim thay noi dung da nhap');

// ------------------------------------------------------------------
// 11. Trang dang bai (da dang nhap) co guard cho composer
// ------------------------------------------------------------------
{
    $res = request('GET', $base . '/posts/create');
    check('Composer co danh dau data-guard', str_contains($res['body'], 'data-guard="Bài viết đang soạn"'));
    check('Trang dang bai co nut ve bang tin', str_contains($res['body'], 'href="' . $base . '/'));
    check('Trang chu khong con form composer', !str_contains(request('GET', $base . '/')['body'], 'id="postForm"'));

    $res = request('GET', $base . '/');
    check('Form dang xuat co danh dau data-leave-guard', str_contains($res['body'], 'data-leave-guard="Đăng xuất"'));
}

@unlink($cookieJar);

echo "\n================================\n";
echo "PASS: {$passed} | FAIL: {$failed}\n";
echo "================================\n";

exit($failed === 0 ? 0 : 1);
