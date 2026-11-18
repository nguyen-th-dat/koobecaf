<?php

declare(strict_types=1);

/**
 * Kiểm thử tích hợp mức HTTP cho toàn bộ luồng chính của Koobecaf.
 *
 * Chạy (cần server đang chạy):
 *   php -S 127.0.0.1:8931 -t . router.php
 *   php tests/smoke.php http://127.0.0.1:8931
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
// 1. Truy cap khi chua dang nhap
// ------------------------------------------------------------------
$res = request('GET', $base . '/');
check('Feed cong khai trai ve 200', $res['status'] === 200, 'status=' . $res['status']);

$res = request('GET', $base . '/settings/profile');
check('Trang can dang nhap chuyen huong', $res['status'] === 302 && str_contains((string) $res['location'], '/login'), 'status=' . $res['status']);

$res = request('GET', $base . '/login');
check('Trang dang nhap render ok', $res['status'] === 200 && str_contains($res['body'], 'Đăng nhập'));

// ------------------------------------------------------------------
// 2. Dang ky tai khoan moi
// ------------------------------------------------------------------
$username = 'tester' . random_int(1000, 9999);
$email = $username . '@koobecaf.test';
$password = 'Password@123';

$res = request('GET', $base . '/register');
$token = csrfToken($res['body']);

$res = request('POST', $base . '/register', ['form' => [
    '_token'                => $token,
    'username'              => $username,
    'email'                 => $email,
    'display_name'          => 'Nguoi Kiem Thu',
    'password'              => $password,
    'password_confirmation' => $password,
]]);
check('Dang ky thanh cong va chuyen huong', $res['status'] === 302 && str_contains((string) $res['location'], '/'), 'status=' . $res['status']);
check('Session ID duoc regenerate (Set-Cookie san sinh)', str_contains((string) $res['headers'], 'Set-Cookie'), (string) $res['headers']);

// ------------------------------------------------------------------
// 3. Xac thuc dang nhap
// ------------------------------------------------------------------
$res = request('GET', $base . '/');
check('Da dang nhap (khong con nut dang ky)', !str_contains($res['body'], '>Đăng ký<'), 'van thay nut dang ky');
preg_match('/data-user-id="(\d+)"/', $res['body'], $m);
$selfId = (int) ($m[1] ?? 0);
check('Lay duoc id nguoi dung hien tai', $selfId > 0, 'selfId=' . $selfId);

// ------------------------------------------------------------------
// 4. Tao bai viet
// ------------------------------------------------------------------
$content = 'Bai viet kiem thu ' . random_int(10000, 99999);

$res = request('POST', $base . '/posts', ['form' => [
    '_token'  => token(),
    'content' => $content,
    'privacy' => 'public',
]]);
check('Tao bai viet thanh cong', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/');
check('Bai viet xuat hien tren feed', str_contains($res['body'], $content));

preg_match('/data-post-id="(\d+)"/', $res['body'], $m);
$postId = (int) ($m[1] ?? 0);
check('Co duoc id bai viet', $postId > 0);

// ------------------------------------------------------------------
// 5. Like / Unlike
// ------------------------------------------------------------------
$res = request('POST', $base . "/posts/{$postId}/like", ['headers' => ajaxHeaders()]);
$likeJson = json_decode($res['body'], true);
check('Like bai viet thanh cong', ($likeJson['success'] ?? false) === true && ($likeJson['data']['liked'] ?? false) === true, $res['body']);
check('So like duoc cap nhat', ($likeJson['data']['like_count'] ?? 0) === 1, $res['body']);

$res = request('POST', $base . "/posts/{$postId}/like", ['headers' => ajaxHeaders()]);
$likeJson = json_decode($res['body'], true);
check('Unlike bai viet thanh cong', ($likeJson['data']['liked'] ?? true) === false, $res['body']);
check('Khong like trung (unique constraint)', ($likeJson['data']['like_count'] ?? -1) === 0, $res['body']);

// ------------------------------------------------------------------
// 6. Binh luan
// ------------------------------------------------------------------
$res = request('POST', $base . "/posts/{$postId}/comment", ['json' => ['content' => 'Binh luan kiem thu'], 'headers' => ajaxHeaders()]);
$commentJson = json_decode($res['body'], true);
check('Them binh luan thanh cong', ($commentJson['success'] ?? false) === true && ($commentJson['data']['comment_count'] ?? 0) === 1, $res['body']);
$commentId = (int) ($commentJson['data']['comment']['id'] ?? 0);
check('Co id binh luan', $commentId > 0);

$res = request('POST', $base . "/posts/{$postId}/comment", ['headers' => ajaxHeaders(), 'json' => [
    'content'   => 'Tra loi kiem thu',
    'parent_id' => $commentId,
]]);
$replyJson = json_decode($res['body'], true);
check('Tra loi binh luan thanh cong', ($replyJson['success'] ?? false) === true, $res['body']);

$res = request('POST', $base . "/comments/{$commentId}/update", ['json' => ['content' => 'Da sua binh luan'], 'headers' => ajaxHeaders()]);
$updateJson = json_decode($res['body'], true);
check('Sua binh luan cua chinh minh', ($updateJson['success'] ?? false) === true, $res['body']);

$res = request('POST', $base . "/comments/{$commentId}/delete", ['headers' => ajaxHeaders()]);
$deleteJson = json_decode($res['body'], true);
check('Xoa binh luan cua chinh minh', ($deleteJson['success'] ?? false) === true, $res['body']);

// ------------------------------------------------------------------
// 7. Chia se noi bo
// ------------------------------------------------------------------
$res = request('POST', $base . "/posts/{$postId}/share", ['headers' => ajaxHeaders(), 'json' => [
    'content' => 'Chia se kiem thu',
    'privacy' => 'public',
]]);
$shareJson = json_decode($res['body'], true);
check('Chia se noi bo thanh cong', ($shareJson['success'] ?? false) === true, $res['body']);
check('So luot chia se = 1', ($shareJson['data']['share_count'] ?? 0) === 1, $res['body']);

// ------------------------------------------------------------------
// 8. Tim kiem
// ------------------------------------------------------------------
$res = request('GET', $base . '/search?q=' . rawurlencode($username));
check('Tim kiem nguoi dung theo username', str_contains($res['body'], $username), 'status=' . $res['status']);

$res = request('GET', $base . '/api/search/users?q=' . rawurlencode($username));
$searchJson = json_decode($res['body'], true);
check('Ajax tim kiem nguoi dung', ($searchJson['success'] ?? false) === true && count($searchJson['data']['users'] ?? []) >= 1, $res['body']);

$res = request('GET', $base . '/api/search/posts?q=' . rawurlencode('kiem thu'));
check('Ajax tim kiem bai viet', str_contains($res['body'], '"success":true'));

// ------------------------------------------------------------------
// 9. Ho so
// ------------------------------------------------------------------
$res = request('GET', $base . '/u/' . $username);
check('Trang ca nhan render', $res['status'] === 200 && str_contains($res['body'], 'Nguoi Kiem Thu'), 'status=' . $res['status']);

$res = request('POST', $base . '/settings/profile', ['form' => [
    '_token'       => token(),
    'display_name' => 'Ten Da Cap Nhat',
    'bio'          => 'Tieu su kiem thu tu dong.',
]]);
check('Cap nhat ho so thanh cong', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/u/' . $username);
check('Ho so hien thi ten moi', str_contains($res['body'], 'Ten Da Cap Nhat'));

// ------------------------------------------------------------------
// 10. Theo doi
// ------------------------------------------------------------------
$res = request('POST', $base . '/follow/1', ['headers' => ajaxHeaders()]);
$followJson = json_decode($res['body'], true);
check('Theo doi nguoi dung', ($followJson['success'] ?? false) === true && ($followJson['data']['following'] ?? false) === true, $res['body']);

$res = request('POST', $base . '/follow/1', ['headers' => ajaxHeaders()]);
$followJson = json_decode($res['body'], true);
check('Unfollow nguoi dung', ($followJson['data']['following'] ?? true) === false, $res['body']);

$res = request('POST', $base . '/follow/' . $selfId, ['headers' => ajaxHeaders()]);
check('Follow chinh minh bi tu choi', $res['status'] === 422, 'status=' . $res['status']);

// ------------------------------------------------------------------
// 11. Ban be
// ------------------------------------------------------------------
$res = request('POST', $base . '/friends/request/2', ['headers' => ajaxHeaders()]);
check('Gui loi moi ket ban', $res['status'] === 302 || ($res['status'] === 200 && str_contains($res['body'], '"success":true')), 'status=' . $res['status']);

$res = request('GET', $base . '/friends/requests');
check('Trang loi moi ket ban render', $res['status'] === 200);

// ------------------------------------------------------------------
// 12. Chat
// ------------------------------------------------------------------
$res = request('POST', $base . '/chat/start/2', ['headers' => ajaxHeaders()]);
check('Tao cuoc tro chuyen chuyen huong', $res['status'] === 302 && str_contains((string) $res['location'], '/chat/'), 'status=' . $res['status'] . ' loc=' . (string) $res['location']);
preg_match('#/chat/(\d+)#', (string) $res['location'], $m);
$conversationId = (int) ($m[1] ?? 0);
check('Co id cuoc tro chuyen', $conversationId > 0);

$res = request('POST', $base . "/chat/{$conversationId}/send", ['json' => ['body' => 'Tin nhan kiem thu'], 'headers' => ajaxHeaders()]);
$msgJson = json_decode($res['body'], true);
check('Gui tin nhan thanh cong', ($msgJson['success'] ?? false) === true, $res['body']);

$res = request('GET', $base . "/chat/{$conversationId}/messages?after_id=0");
$msgJson = json_decode($res['body'], true);
check('Polling lay tin nhan', ($msgJson['success'] ?? false) === true && count($msgJson['data']['messages'] ?? []) >= 1, $res['body']);

$res = request('GET', $base . '/chat/999999');
check('Truy cap cuoc tro chuyen khong thuoc ve bi chan', $res['status'] === 403, 'status=' . $res['status']);

// Nut "Nhan tin" dung phuong thuc GET nen phai chuyen huong toi cuoc tro chuyen
$res = request('GET', $base . '/chat/start/2');
check('GET /chat/start chuyen huong toi cuoc tro chuyen', $res['status'] === 302 && str_contains((string) $res['location'], '/chat/'), 'status=' . $res['status'] . ' loc=' . (string) $res['location']);

// Trang tin nhan co o tim kiem de bat dau cuoc tro chuyen moi
$res = request('GET', $base . '/chat');
check('Trang tin nhan co o tim kiem nguoi dung', str_contains($res['body'], 'id="chatUserSearch"'), 'status=' . $res['status']);

$res = request('GET', $base . '/api/search/users?q=' . rawurlencode('chi'));
$searchJson = json_decode($res['body'], true);
$chatUrl = $searchJson['data']['users'][0]['chat_url'] ?? '';
check('API tim kiem nguoi dung tra ve link bat dau chat', str_contains((string) $chatUrl, '/chat/start/'), json_encode($searchJson, JSON_UNESCAPED_UNICODE));

$res = request('GET', $base . $chatUrl);
check('Link bat dau chat tu ket qua tim kiem hoat dong', $res['status'] === 302, 'status=' . $res['status'] . ' url=' . (string) $chatUrl);
$res = request('POST', $base . '/chat/start/' . $selfId, ['headers' => ajaxHeaders()]);
check('Khong the nham tin cho chinh minh', $res['status'] === 422, 'status=' . $res['status']);

// ------------------------------------------------------------------
// 13. Thong bao
// ------------------------------------------------------------------
$res = request('GET', $base . '/notifications');
check('Trang thong bao render', $res['status'] === 200);

$res = request('GET', $base . '/api/notifications/unread');
$notifJson = json_decode($res['body'], true);
check('API so thong bao chua doc', ($notifJson['success'] ?? false) === true, $res['body']);

$res = request('POST', $base . '/notifications/read-all', ['headers' => ajaxHeaders()]);
$notifJson = json_decode($res['body'], true);
check('Danh dau da doc tat ca', ($notifJson['data']['unread'] ?? -1) === 0, $res['body']);

// ------------------------------------------------------------------
// 14. Quyen rieng tu
// ------------------------------------------------------------------
$privateContent = 'Bai viet rieng tu ' . random_int(10000, 99999);
request('POST', $base . '/posts', ['form' => [
    '_token'  => token(),
    'content' => $privateContent,
    'privacy' => 'private',
]]);

$cookieJar2 = tempnam(sys_get_temp_dir(), 'kbcookie2');
$oldJar = $cookieJar;
$cookieJar = $cookieJar2;
$res = request('GET', $base . '/login');
request('POST', $base . '/login', ['form' => [
    '_token'  => csrfToken($res['body']),
    'email'   => 'an@koobecaf.test',
    'password' => 'Password@123',
]]);
$res = request('GET', $base . '/');
check('Bai viet rieng tu khong hien tren feed nguoi khac', !str_contains($res['body'], $privateContent));

// Bai viet cong khai cua user khac van thay
$publicContent = 'Bai viet cong khai kiem thu ' . random_int(10000, 99999);
request('POST', $base . '/posts', ['form' => [
    '_token'  => token(),
    'content' => $publicContent,
    'privacy' => 'public',
]]);

$res = request('GET', $base . '/');
check('Bai viet cong khai cua user khac van thay', str_contains($res['body'], $publicContent), 'status=' . $res['status']);

$cookieJar = $oldJar;

// ------------------------------------------------------------------
// 15. CSRF
// ------------------------------------------------------------------
$res = request('POST', $base . '/posts', ['form' => [
    'content' => 'Khong co token',
    'privacy' => 'public',
]]);
check('Request thieu CSRF token bi tu choi', $res['status'] === 419, 'status=' . $res['status']);

// ------------------------------------------------------------------
// 16. XSS
// ------------------------------------------------------------------
request('POST', $base . '/posts', ['form' => [
    '_token'  => token(),
    'content' => '<script>alert(1)</script>',
    'privacy' => 'public',
]]);
$res = request('GET', $base . '/');
check('Noi dung XSS duoc escape', !str_contains($res['body'], '<script>alert(1)</script>') && str_contains($res['body'], '&lt;script&gt;'));

// ------------------------------------------------------------------
// 17. SQL Injection
// ------------------------------------------------------------------
$res = request('GET', $base . "/search?q=" . rawurlencode("' OR '1'='1"));
check('SQL injection trong tim kiem khong lam loi', $res['status'] === 200 && !str_contains($res['body'], 'Fatal error'), 'status=' . $res['status']);

// ------------------------------------------------------------------
// 18. Xoa bai viet
// ------------------------------------------------------------------
$res = request('POST', $base . "/posts/{$postId}/delete", ['headers' => ajaxHeaders()]);
check('Xoa bai viet cua chinh minh', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/post/' . $postId);
check('Bai viet da xoa trai ve 404', $res['status'] === 404, 'status=' . $res['status']);

// ------------------------------------------------------------------
// 19. Dang xuat
// ------------------------------------------------------------------
$res = request('POST', $base . '/logout', ['form' => ['_token' => token()]]);
check('Dang xuat chuyen huong', $res['status'] === 302, 'status=' . $res['status']);

$res = request('GET', $base . '/settings/profile');
check('Sau dang xuat yeu cau dang nhap lai', $res['status'] === 302 && str_contains((string) $res['location'], '/login'));

@unlink($cookieJar);
@unlink($cookieJar2);

echo "\n================================\n";
echo "PASS: {$passed} | FAIL: {$failed}\n";
echo "================================\n";

exit($failed === 0 ? 0 : 1);
