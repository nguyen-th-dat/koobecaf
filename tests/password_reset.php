<?php

declare(strict_types=1);

/**
 * Kiểm thử luồng quên mật khẩu (PHPMailer).
 *
 * Chạy (cần server đang chạy, MAIL_DRIVER=log):
 *   php -S 127.0.0.1:8931 -t . router.php
 *   php tests/password_reset.php http://127.0.0.1:8931
 */

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8931', '/');
$passed = 0;
$failed = 0;
$cookieJar = tempnam(sys_get_temp_dir(), 'kbcookie');

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

    if (isset($options['json'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json'], JSON_UNESCAPED_UNICODE));
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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

/** Đọc token đặt lại mật khẩu từ file log của MAIL_DRIVER=log. */
function latestResetToken(string $email): ?string
{
    $block = latestMailBlock($email);

    if ($block === null) {
        return null;
    }

    if (preg_match('#reset-password\?token=([a-f0-9]{64})#', $block, $m)) {
        return $m[1];
    }

    return null;
}

/** Đọc mã 6 số đặt lại mật khẩu từ file log của MAIL_DRIVER=log. */
function latestResetCode(string $email): ?string
{
    $block = latestMailBlock($email);

    if ($block === null) {
        return null;
    }

    if (preg_match('/Mã đặt lại mật khẩu (\d{6})/u', $block, $m)) {
        return $m[1];
    }

    return null;
}

/** Lấy block email gần nhất chứa địa chỉ email cần tìm. */
function latestMailBlock(string $email): ?string
{
    $logDir = dirname(__DIR__) . '/storage/logs';
    $files = glob($logDir . '/app-*.log') ?: [];
    if ($files === []) {
        return null;
    }

    // Đọc từ file cũ tới file mới để block cuối cùng là email gửi gần nhất.
    sort($files);
    $content = '';
    foreach ($files as $file) {
        $content .= (string) file_get_contents($file);
    }

    $pos = strrpos($content, 'To: ');
    if ($pos === false) {
        return null;
    }

    $block = substr($content, $pos);
    if (!str_contains($block, $email)) {
        return null;
    }

    return $block;
}

$base = $baseUrl;

// ------------------------------------------------------------------
// 1. Trang quên mật khẩu hiển thị được
// ------------------------------------------------------------------
$res = request('GET', $base . '/forgot-password');
check('Trang quên mật khẩu trả về 200', $res['status'] === 200, 'status=' . $res['status']);
check('Trang quên mật khẩu có form email', str_contains($res['body'], 'name="email"'));

// ------------------------------------------------------------------
// 2. Trang đăng nhập có link quên mật khẩu
// ------------------------------------------------------------------
$res = request('GET', $base . '/login');
check('Trang đăng nhập có link quên mật khẩu', str_contains($res['body'], '/forgot-password'));

// ------------------------------------------------------------------
// 3. Đăng ký tài khoản để có email hợp lệ
// ------------------------------------------------------------------
$username = 'pwd' . random_int(1000, 9999);
$email = $username . '@koobecaf.test';
$oldPassword = 'Password@123';
$newPassword = 'NewPassword@456';

$res = request('GET', $base . '/register');
request('POST', $base . '/register', ['form' => [
    '_token'                   => csrfToken($res['body']),
    'username'                 => $username,
    'email'                    => $email,
    'display_name'             => 'Pwd User',
    'password'                 => $oldPassword,
    'password_confirmation'    => $oldPassword,
]]);

$res = request('GET', $base . '/');
check('Đăng ký tài khoản thành công', str_contains($res['body'], 'Pwd User'), 'status=' . $res['status']);

// Đăng xuất để các trang khách (quên/đặt lại mật khẩu) truy cập được.
$logoutToken = csrfToken($res['body']);
request('POST', $base . '/logout', ['form' => ['_token' => $logoutToken]]);

$res = request('GET', $base . '/forgot-password');
check('Sau khi đăng xuất mới vào được trang quên mật khẩu', $res['status'] === 200, 'status=' . $res['status']);

// ------------------------------------------------------------------
// 4. Gửi yêu cầu quên mật khẩu
// ------------------------------------------------------------------
$res = request('GET', $base . '/forgot-password');
$res = request('POST', $base . '/forgot-password', ['form' => [
    '_token' => csrfToken($res['body']),
    'email'   => $email,
]]);

check('Gửi yêu cầu quên mật khẩu redirect', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/forgot-password');
check('Hiển thị thông báo đã gửi email', str_contains($res['body'], 'đã gửi link đặt lại mật khẩu'));

$token = latestResetToken($email);
check('Email có chứa token đặt lại mật khẩu', $token !== null, 'token=' . var_export($token, true));

// ------------------------------------------------------------------
// 5. Mở form đặt lại mật khẩu bằng token
// ------------------------------------------------------------------
if ($token !== null) {
    $res = request('GET', $base . '/reset-password?token=' . $token);
    check('Trang đặt lại mật khẩu trả về 200', $res['status'] === 200, 'status=' . $res['status']);
    check('Trang đặt lại mật khẩu có 2 ô mật khẩu', substr_count($res['body'], 'type="password"') === 2);

    // ------------------------------------------------------------------
    // 6. Mật khẩu xác nhận không khớp bị từ chối
    // ------------------------------------------------------------------
    $res = request('POST', $base . '/reset-password', ['form' => [
        '_token'                => csrfToken($res['body']),
        'token'                 => $token,
        'password'              => $newPassword,
        'password_confirmation' => 'KhacMatKhau@789',
    ]]);
    check('Mật khẩu xác nhận không khớp bị từ chối', $res['status'] === 302, 'status=' . $res['status']);

    $res = request('GET', $base . '/reset-password?token=' . $token);
    check('Hiển thị lỗi mật khẩu xác nhận', str_contains($res['body'], 'Mật khẩu xác nhận không khớp'));

    // ------------------------------------------------------------------
    // 7. Đặt lại mật khẩu thành công
    // ------------------------------------------------------------------
    $res = request('POST', $base . '/reset-password', ['form' => [
        '_token'                => csrfToken($res['body']),
        'token'                 => $token,
        'password'              => $newPassword,
        'password_confirmation' => $newPassword,
    ]]);
    check('Đặt lại mật khẩu redirect về đăng nhập', $res['status'] === 302, 'status=' . $res['status']);

    $res = request('GET', $base . '/login');
    check('Hiển thị thông báo đặt lại thành công', str_contains($res['body'], 'Đặt lại mật khẩu thành công'));

    // ------------------------------------------------------------------
    // 8. Đăng nhập bằng mật khẩu mới, mật khẩu cũ không còn dùng được
    // ------------------------------------------------------------------
    $res = request('POST', $base . '/login', ['form' => [
        '_token'   => csrfToken($res['body']),
        'email'    => $email,
        'password' => $newPassword,
    ]]);
    check('Đăng nhập được bằng mật khẩu mới', $res['status'] === 302, 'status=' . $res['status']);

    $res = request('GET', $base . '/');
    check('Vào được trang chủ sau khi đổi mật khẩu', str_contains($res['body'], 'Pwd User'));

    // ------------------------------------------------------------------
    // 9. Token đã dùng không tái sử dụng được
    // ------------------------------------------------------------------
    $res = request('GET', $base . '/reset-password?token=' . $token);
    check('Token đã dùng bị từ chối', $res['status'] === 302, 'status=' . $res['status']);
}

// ------------------------------------------------------------------
// 10. Email không tồn tại vẫn trả thông báo chung (không tiết lộ)
// ------------------------------------------------------------------
$res = request('GET', $base . '/');
request('POST', $base . '/logout', ['form' => ['_token' => csrfToken($res['body'])]]);

$res = request('GET', $base . '/forgot-password');
$res = request('POST', $base . '/forgot-password', ['form' => [
    '_token' => csrfToken($res['body']),
    'email'   => 'khong-ton-tai-' . random_int(1000, 9999) . '@koobecaf.test',
]]);
check('Email không tồn tại vẫn báo gửi thành công', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/forgot-password');
check('Thông báo không tiết lộ email tồn tại', str_contains($res['body'], 'Nếu email này đã được đăng ký'));

// ------------------------------------------------------------------
// 11. Email không hợp lệ bị từ chối
// ------------------------------------------------------------------
$res = request('GET', $base . '/forgot-password');
$res = request('POST', $base . '/forgot-password', ['form' => [
    '_token' => csrfToken($res['body']),
    'email'   => 'khong-hop-le',
]]);
$res = request('GET', $base . '/forgot-password');
check('Email không hợp lệ báo lỗi', str_contains($res['body'], 'Email không hợp lệ'));

// ------------------------------------------------------------------
// 12. Token không tồn tại bị từ chối
// ------------------------------------------------------------------
$res = request('GET', $base . '/reset-password?token=' . str_repeat('a', 64));
check('Token không tồn tại bị từ chối', $res['status'] === 302, 'status=' . $res['status']);

// ------------------------------------------------------------------
// 13. Nhập mã 6 số từ email để đặt lại mật khẩu
// ------------------------------------------------------------------
$codeEmail = 'code' . random_int(1000, 9999) . '@koobecaf.test';
$codeUsername = 'code' . random_int(1000, 9999);
$codeNewPassword = 'NewPassword@456';

$res = request('GET', $base . '/register');
request('POST', $base . '/register', ['form' => [
    '_token'                => csrfToken($res['body']),
    'username'              => $codeUsername,
    'email'                 => $codeEmail,
    'display_name'          => 'Code User',
    'password'              => 'Password@123',
    'password_confirmation' => 'Password@123',
]]);

$res = request('GET', $base . '/');
request('POST', $base . '/logout', ['form' => ['_token' => csrfToken($res['body'])]]);

$res = request('GET', $base . '/forgot-password');
request('POST', $base . '/forgot-password', ['form' => [
    '_token' => csrfToken($res['body']),
    'email'  => $codeEmail,
]]);

$code = latestResetCode($codeEmail);
check('Email có chứa mã đặt lại mật khẩu 6 số', $code !== null, 'code=' . var_export($code, true));

if ($code !== null) {
    $res = request('GET', $base . '/forgot-password');
    $csrf = csrfToken($res['body']);

    // Mã sai bị từ chối.
    $res = request('POST', $base . '/forgot-password/verify-code', [
        'json'    => ['code' => '000000'],
        'headers' => ['X-CSRF-TOKEN: ' . $csrf],
    ]);
    check('Mã 6 số sai bị từ chối', $res['status'] === 404, 'status=' . $res['status']);

    // Mã đúng được chấp nhận.
    $res = request('POST', $base . '/forgot-password/verify-code', [
        'json'    => ['code' => $code],
        'headers' => ['X-CSRF-TOKEN: ' . $csrf],
    ]);
    check('Mã 6 số đúng được chấp nhận', $res['status'] === 200, 'status=' . $res['status']);

    // Sang trang đặt lại mật khẩu mới mà không cần token trong URL.
    $res = request('GET', $base . '/reset-password');
    check('Trang đặt lại mật khẩu mở được bằng mã 6 số', $res['status'] === 200, 'status=' . $res['status']);
    check('Trang đặt lại mật khẩu có 2 ô mật khẩu', substr_count($res['body'], 'type="password"') === 2);

    $res = request('POST', $base . '/reset-password', ['form' => [
        '_token'                => csrfToken($res['body']),
        'password'              => $codeNewPassword,
        'password_confirmation' => $codeNewPassword,
    ]]);
    check('Đặt lại mật khẩu bằng mã 6 số redirect về đăng nhập', $res['status'] === 302, 'status=' . $res['status']);

    $res = request('GET', $base . '/login');
    check('Đăng nhập được bằng mật khẩu mới tạo từ mã 6 số', str_contains($res['body'], 'Đặt lại mật khẩu thành công'));

    $res = request('POST', $base . '/login', ['form' => [
        '_token'   => csrfToken($res['body']),
        'email'    => $codeEmail,
        'password' => $codeNewPassword,
    ]]);
    check('Đăng nhập bằng mật khẩu mới sau khi dùng mã 6 số', $res['status'] === 302, 'status=' . $res['status']);

    // Mã đã dùng không tái sử dụng được.
    $res = request('GET', $base . '/');
    request('POST', $base . '/logout', ['form' => ['_token' => csrfToken($res['body'])]]);

    $res = request('GET', $base . '/forgot-password');
    $csrf = csrfToken($res['body']);
    $res = request('POST', $base . '/forgot-password/verify-code', [
        'json'    => ['code' => $code],
        'headers' => ['X-CSRF-TOKEN: ' . $csrf],
    ]);
    check('Mã 6 số đã dùng bị từ chối', $res['status'] === 404, 'status=' . $res['status']);
}

echo "\nPASS: {$passed} | FAIL: {$failed}\n";
exit($failed === 0 ? 0 : 1);
