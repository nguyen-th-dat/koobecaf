<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=koobecaf;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

function looksMojibake(string $s): bool {
    if ($s === '') {
        return false;
    }
    if (!mb_check_encoding($s, 'UTF-8')) {
        return true;
    }
    return (bool) preg_match('/[\x{2500}-\x{259F}\x{25A0}-\x{25FF}\x{221A}\x{0192}\x{00B5}]/u', $s);
}

$tables = [
    'users'         => ['display_name', 'bio'],
    'posts'         => ['content', 'share_content'],
    'comments'      => ['content'],
    'messages'      => ['body'],
    'shares'        => ['content'],
    'notifications' => ['data'],
];

$totalBad = 0;
foreach ($tables as $table => $cols) {
    foreach ($cols as $col) {
        $rows = $pdo->query("SELECT id, `$col` AS v FROM `$table`")->fetchAll();
        $bad = [];
        foreach ($rows as $r) {
            if (looksMojibake((string) $r['v'])) {
                $bad[] = $r['id'];
            }
        }
        $totalBad += count($bad);
        printf("%-14s %-15s rows=%-4d mojibake=%d %s\n", $table, $col, count($rows), count($bad),
            $bad ? '[' . implode(',', $bad) . ']' : '');
    }
}
echo "\nTổng số giá trị hỏng encoding: {$totalBad}\n";

echo "\n--- Kiểm tra byte thực tế (posts.content id=1) ---\n";
$c = (string) $pdo->query('SELECT content FROM posts WHERE id=1')->fetchColumn();
echo 'hex   : ' . bin2hex(substr($c, 0, 24)) . PHP_EOL;
echo 'text  : ' . $c . PHP_EOL;
echo 'kỳ vọng: ' . bin2hex(substr('Chào mừng đến với Koobecaf!', 0, 24)) . PHP_EOL;
echo 'khớp  : ' . ($c === 'Chào mừng đến với Koobecaf! 🎉 Hãy đăng bài viết, thích và bình luận để bắt đầu.' ? 'YES' : 'NO') . PHP_EOL;

echo "\n--- Đếm users ---\n";
foreach ($pdo->query('SELECT id, username, display_name, bio FROM users ORDER BY id') as $r) {
    echo "  {$r['id']} {$r['username']} | {$r['display_name']} | {$r['bio']}" . PHP_EOL;
}
