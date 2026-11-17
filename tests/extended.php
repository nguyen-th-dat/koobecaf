<?php

declare(strict_types=1);

/**
 * Kiểm thử bổ sung: upload ảnh, đăng bài có ảnh, phân quyền, đổi mật khẩu, giao diện tối.
 *
 * Chạy: php tests/extended.php http://127.0.0.1:8931
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

function token(): string
{
    global $baseUrl;

    return csrfToken(request('GET', $baseUrl . '/')['body']);
}

function ajaxHeaders(): array
{
    return ['X-CSRF-TOKEN: ' . token(), 'X-Requested-With: XMLHttpRequest'];
}

$base = $baseUrl;

// ------------------------------------------------------------------
// Tao du lieu hinh anh 1x1 PNG
// ------------------------------------------------------------------
$pngPath = sys_get_temp_dir() . '/kb_test_' . random_int(1000, 9999) . '.png';
$pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
file_put_contents($pngPath, $pngData);

// ------------------------------------------------------------------
// 1. Dang ky + dang nhap
// ------------------------------------------------------------------
$username = 'ext' . random_int(1000, 9999);
$email = $username . '@koobecaf.test';
$password = 'Password@123';

$res = request('GET', $base . '/register');
request('POST', $base . '/register', ['form' => [
    '_token'                => csrfToken($res['body']),
    'username'              => $username,
    'email'                 => $email,
    'display_name'          => 'Ext User',
    'password'              => $password,
    'password_confirmation' => $password,
]]);

$res = request('GET', $base . '/');
check('Dang ky thanh cong', str_contains($res['body'], 'Ext User'), 'status=' . $res['status']);

// ------------------------------------------------------------------
// 2. Dang bai co hinh anh
// ------------------------------------------------------------------
$token = csrfToken($res['body']);

$res = request('POST', $base . '/posts', ['form' => [
    '_token'    => $token,
    'content'   => 'Bai viet co hinh anh',
    'privacy'   => 'public',
    'images[]'  => new CURLFile($pngPath, 'image/png', 'test.png'),
]]);
check('Dang bai viet co hinh anh', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/');
check('Hinh anh duoc hien thi trong feed', str_contains($res['body'], '/uploads/posts/'), 'khong thay duong dan anh');
check('Ten file upload duoc doi ngau nhien', (bool) preg_match('#/uploads/posts/[a-f0-9]{32}\.png#', $res['body']));

// ------------------------------------------------------------------
// 3. Upload file khong hop le (khong phai image)
// ------------------------------------------------------------------
$txtPath = sys_get_temp_dir() . '/kb_test_' . random_int(1000, 9999) . '.php';
file_put_contents($txtPath, "<?php echo 'hacked'; ?>");

request('POST', $base . '/posts', ['form' => [
    '_token'    => token(),
    'content'   => 'Thu upload file PHP',
    'privacy'   => 'public',
    'images[]'  => new CURLFile($txtPath, 'application/x-php', 'shell.php'),
]]);
$res = request('GET', $base . '/');
check('Upload file khong phai image bi tu choi', !str_contains($res['body'], 'shell.php') && !str_contains($res['body'], 'hacked'));

$uploads = dirname(__DIR__) . '/public/uploads/posts';
$phpFiles = glob($uploads . '/*.php') ?: [];
check('Khong co file PHP nao trong thu muc uploads', count($phpFiles) === 0, implode(',', $phpFiles));

@unlink($pngPath);
@unlink($txtPath);

// ------------------------------------------------------------------
// 4. Avatar upload
// ------------------------------------------------------------------
$pngPath2 = sys_get_temp_dir() . '/kb_avatar_' . random_int(1000, 9999) . '.png';
file_put_contents($pngPath2, $pngData);

$res = request('POST', $base . '/settings/profile', ['form' => [
    '_token'      => token(),
    'display_name' => 'Ext User',
    'bio'          => '',
    'avatar'      => new CURLFile($pngPath2, 'image/png', 'avatar.png'),
]]);
check('Upload avatar thanh cong', $res['status'] === 302, 'status=' . $res['status']);
@unlink($pngPath2);

// ------------------------------------------------------------------
// 5. Phan quyen: user B khong sua/xoa binh luan cua user A
// ------------------------------------------------------------------
$res = request('GET', $base . '/');
preg_match('/data-post-id="(\d+)"/', $res['body'], $m);
$postId = (int) ($m[1] ?? 0);

$res = request('POST', $base . "/posts/{$postId}/comment", ['json' => ['content' => 'Comment cua A'], 'headers' => ajaxHeaders()]);
$commentJson = json_decode($res['body'], true);
$commentId = (int) ($commentJson['data']['comment']['id'] ?? 0);
check('User A tao binh luan', $commentId > 0, $res['body']);

$cookieJarB = tempnam(sys_get_temp_dir(), 'kbcookieB');
$jarA = $cookieJar;
$cookieJar = $cookieJarB;
$res = request('GET', $base . '/login');
request('POST', $base . '/login', ['form' => [
    '_token'   => csrfToken($res['body']),
    'email'    => 'binh@koobecaf.test',
    'password' => 'Password@123',
]]);
$res = request('GET', $base . '/');
check('Dang nhap user B', str_contains($res['body'], 'data-user-id="2"'));

$res = request('POST', $base . "/comments/{$commentId}/update", ['json' => ['content' => 'Hack comment'], 'headers' => ajaxHeaders()]);
check('User B khong sua duoc binh luan cua A', $res['status'] === 403, 'status=' . $res['status']);

$res = request('POST', $base . "/comments/{$commentId}/delete", ['headers' => ajaxHeaders()]);
check('User B khong xoa duoc binh luan cua A', $res['status'] === 403, 'status=' . $res['status']);

$res = request('POST', $base . "/posts/{$postId}/delete", ['headers' => ajaxHeaders()]);
check('User B khong xoa duoc bai viet cua A', $res['status'] === 403, 'status=' . $res['status']);

$res = request('GET', $base . "/posts/{$postId}/edit");
check('User B khong mo duoc form sua bai viet cua A', $res['status'] === 403, 'status=' . $res['status']);

// ------------------------------------------------------------------
// 6. Bai viet followers
// ------------------------------------------------------------------
// Quay lai dang nhap voi user A de tao bai viet
$cookieJar = $jarA;

$token = csrfToken(request('GET', $base . '/')['body']);
$followersContent = 'Bai viet cho nguoi theo doi ' . random_int(10000, 99999);
request('POST', $base . '/posts', ['form' => [
    '_token'  => $token,
    'content' => $followersContent,
    'privacy' => 'followers',
]]);

$res = request('GET', $base . '/u/' . $username);
preg_match('#data-user-id="(\d+)"#', $res['body'], $m);
$userAId = (int) ($m[1] ?? 0);
check('Lay duoc id user A', $userAId > 0);

// Chuyen sang user B de kiem tra quyen rieng tu
$cookieJar = $cookieJarB;
$res = request('GET', $base . '/');
check('User B khong thay bai viet followers khi khong theo doi', !str_contains($res['body'], $followersContent));

request('POST', $base . "/follow/{$userAId}", ['headers' => ajaxHeaders()]);
$res = request('GET', $base . '/');
check('Sau khi theo doi thi thay bai viet followers', str_contains($res['body'], $followersContent));

// ------------------------------------------------------------------
// 7. Doi mat khau
// ------------------------------------------------------------------
$cookieJar = $jarA;
$newPassword = 'NewPassword@456';
$res = request('POST', $base . '/settings/password', ['form' => [
    '_token'                    => token(),
    'current_password'          => $password,
    'new_password'              => $newPassword,
    'new_password_confirmation' => $newPassword,
]]);
check('Doi mat khau thanh cong', $res['status'] === 302, 'status=' . $res['status']);

$cookieJarC = tempnam(sys_get_temp_dir(), 'kbcookieC');
$jar = $cookieJar;
$cookieJar = $cookieJarC;
$res = request('GET', $base . '/login');
request('POST', $base . '/login', ['form' => [
    '_token'   => csrfToken($res['body']),
    'email'    => $email,
    'password' => $newPassword,
]]);
$res = request('GET', $base . '/');
check('Dang nhap bang mat khau moi', str_contains($res['body'], 'Ext User'));
$cookieJar = $jar;

$cookieJarD = tempnam(sys_get_temp_dir(), 'kbcookieD');
$jar = $cookieJar;
$cookieJar = $cookieJarD;
$res = request('GET', $base . '/login');
request('POST', $base . '/login', ['form' => [
    '_token'   => csrfToken($res['body']),
    'email'    => $email,
    'password' => $password,
]]);
$res = request('GET', $base . '/');
check('Mat khau cu khong con dung', str_contains($res['body'], 'Đăng nhập'));
$cookieJar = $jar;
@unlink($cookieJarD);

// ------------------------------------------------------------------
// 8. Giao dien toi
// ------------------------------------------------------------------
$res = request('POST', $base . '/settings/theme', ['json' => ['theme' => 'dark'], 'headers' => ajaxHeaders()]);
$themeJson = json_decode($res['body'], true);
check('Doi giao dien toi', ($themeJson['success'] ?? false) === true && ($themeJson['data']['theme'] ?? '') === 'dark', $res['body']);

$res = request('GET', $base . '/');
check('Trang hien thi data-bs-theme=dark', str_contains($res['body'], 'data-bs-theme="dark"'));

// ------------------------------------------------------------------
// 9. Trang loi moi: chap nhan
// ------------------------------------------------------------------
$res = request('GET', $base . '/friends/requests');
check('Trang loi moi render', $res['status'] === 200);
preg_match('#/friends/(\d+)/accept#', $res['body'], $m);
if (isset($m[1])) {
    $res = request('POST', $base . '/friends/' . $m[1] . '/accept', ['headers' => ajaxHeaders()]);
    check('Chap nhan loi moi ket ban', $res['status'] === 302, 'status=' . $res['status']);
}

// ------------------------------------------------------------------
// 10. Trang 404
// ------------------------------------------------------------------
$res = request('GET', $base . '/khong-ton-tai');
check('Trang 404 tra ve 404', $res['status'] === 404, 'status=' . $res['status']);

$res = request('GET', $base . '/u/khong-ton-tai-nguoi');
check('Trang ho so khong ton tai tra 404', $res['status'] === 404, 'status=' . $res['status']);

// ------------------------------------------------------------------
// 11. Tra loi binh luan hien duoc trong luoi (regression)
// ------------------------------------------------------------------
$cookieJar = $jarA;
$token = csrfToken(request('GET', $base . '/')['body']);

$res = request('POST', $base . '/posts', ['form' => [
    '_token'  => $token,
    'content' => 'Bai viet de test reply ' . random_int(10000, 99999),
    'privacy' => 'public',
]]);
$res = request('GET', $base . '/');
preg_match('/data-post-id="(\d+)"/', $res['body'], $m);
$replyPostId = (int) ($m[1] ?? 0);

$res = request('POST', $base . "/posts/{$replyPostId}/comment", [
    'json'    => ['content' => 'Binh luan goc'],
    'headers' => ajaxHeaders(),
]);
$rootCommentId = (int) (json_decode($res['body'], true)['data']['comment']['id'] ?? 0);
check('Tao binh luan goc thanh cong', $rootCommentId > 0, $res['body']);

// User B tra loi
$cookieJar = $cookieJarB;
$res = request('POST', $base . "/posts/{$replyPostId}/comment", [
    'json'    => ['content' => 'Tra loi tu user B', 'parent_id' => $rootCommentId],
    'headers' => ajaxHeaders(),
]);
check('User B tra loi binh luan thanh cong', $res['status'] === 201, 'status=' . $res['status']);

// API comments phai tra ve ca reply
$res = request('GET', $base . "/api/posts/{$replyPostId}/comments");
$commentsJson = json_decode($res['body'], true);
$comments = $commentsJson['data']['comments'] ?? [];
$replyFound = false;
foreach ($comments as $c) {
    if (!empty($c['replies'])) {
        $replyFound = true;
    }
}
check('Reply xuat hien trong API comments', $replyFound, json_encode($commentsJson, JSON_UNESCAPED_UNICODE));

// Trang bai viet phai render reply
$res = request('GET', $base . '/post/' . $replyPostId);
check('Reply xuat hien tren trang bai viet', str_contains($res['body'], 'Tra loi tu user B'), 'status=' . $res['status']);
check('Trang bai viet khong bao loi PHP', !str_contains($res['body'], 'Fatal error') && !str_contains($res['body'], 'Warning:'), 'status=' . $res['status']);

// ------------------------------------------------------------------
// 12. Notification "reply" tro ve dung bai viet (regression)
// ------------------------------------------------------------------
$cookieJar = $jarA;
$res = request('GET', $base . '/notifications');
$replyLink = '';
if (preg_match_all('#<li class="notification-item.*?</li>#s', $res['body'], $items)) {
    foreach ($items[0] as $item) {
        if (str_contains($item, 'trả lời')) {
            preg_match_all('#href="([^"]+)"#', $item, $lm);
            $links = $lm[1] ?? [];
            $replyLink = (string) end($links);
            break;
        }
    }
}
check('Notification reply tro ve bai viet dung', $replyLink === '/post/' . $replyPostId, 'link=' . $replyLink);

// ------------------------------------------------------------------
// 13. So bai viet tren ho so khop voi tab "Bai viet" (regression)
// ------------------------------------------------------------------
$res = request('GET', $base . '/u/' . $username);
preg_match('#<strong>(\d+)</strong><span>Bài viết</span>#u', $res['body'], $sm);
$statCount = (int) ($sm[1] ?? 0);

$res = request('GET', $base . '/u/' . $username . '?tab=posts');
$listedCount = preg_match_all('/<article class="card surface-card post-card/', $res['body'], $pm);
check('So bai viet thong ke khop voi danh sach', $statCount === $listedCount, "stat={$statCount} listed={$listedCount}");

// ------------------------------------------------------------------
// 14. Trang thong bao co phan trang
// ------------------------------------------------------------------
$res = request('GET', $base . '/notifications');
check('Trang thong bao render', $res['status'] === 200);
check('Trang thong bao khong bao loi PHP', !str_contains($res['body'], 'Fatal error') && !str_contains($res['body'], 'Warning:'), 'status=' . $res['status']);

// ------------------------------------------------------------------
// 15. Cac trang chinh render khong loi PHP (regression layout)
// ------------------------------------------------------------------
foreach (['/', '/search?q=koobecaf', '/notifications', '/chat', '/friends', '/friends/requests', '/settings/profile'] as $page) {
    $res = request('GET', $base . $page);
    check('Trang ' . $page . ' render khong loi PHP', $res['status'] === 200 && !str_contains($res['body'], 'Fatal error') && !str_contains($res['body'], 'Warning:'), 'status=' . $res['status']);
}

@unlink($cookieJar);
@unlink($cookieJarB);
@unlink($cookieJarC);

echo "\n================================\n";
echo "PASS: {$passed} | FAIL: {$failed}\n";
echo "================================\n";

exit($failed === 0 ? 0 : 1);
