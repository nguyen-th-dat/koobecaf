<?php

declare(strict_types=1);

/**
 * Koobecaf — script cài đặt database gốc.
 *
 * Dựng lại database `koobecaf` từ database/schema.sql + database/seed.sql.
 * Chạy trực tiếp bằng PDO nên byte trong file .sql được gửi nguyên vẹn tới
 * MySQL, không đi qua console/pipe của PowerShell — tránh lỗi mã hoá tiếng
 * Việt (UTF-8 bị decode lại theo CP437/CP1252).
 *
 *   php database/install.php             # tạo (nếu chưa có) + nạp schema & dữ liệu mẫu
 *   php database/install.php --fresh     # xóa database cũ rồi tạo lại từ đầu
 *   php database/install.php --schema    # chỉ nạp schema, không có dữ liệu mẫu
 *
 * Thông tin kết nối đọc từ .env (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME,
 * DB_PASSWORD). Có thể ghi đè bằng biến môi trường.
 */

if (PHP_SAPI !== 'cli') {
    exit("Script này chỉ chạy được qua command line.\n");
}

$root = dirname(__DIR__);

require $root . '/app/Core/Env.php';

\App\Core\Env::load($root . '/.env');

$options = array_slice($argv, 1);
$fresh   = in_array('--fresh', $options, true);
$onlySchema = in_array('--schema', $options, true);

$host = (string) (\App\Core\Env::get('DB_HOST', '127.0.0.1'));
$port = (string) (\App\Core\Env::get('DB_PORT', '3306'));
$name = (string) (\App\Core\Env::get('DB_DATABASE', 'koobecaf'));
$user = (string) (\App\Core\Env::get('DB_USERNAME', 'root'));
$pass = (string) (\App\Core\Env::get('DB_PASSWORD', ''));

echo "Koobecaf installer\n";
echo "  host     : {$host}:{$port}\n";
echo "  database : {$name}\n";
echo "  user     : {$user}\n";
echo "  mode     : " . ($fresh ? 'fresh' : 'incremental') . ($onlySchema ? ' (schema only)' : '') . "\n\n";

// ---------------------------------------------------------------- PDO server
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    exit("Không kết nối được MySQL: " . $e->getMessage() . "\n");
}

$pdo->exec("SET NAMES utf8mb4");

// ----------------------------------------------------------- drop database
if ($fresh) {
    echo "[1/4] Xóa database `{$name}` nếu tồn tại...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `{$name}`");
}

// -------------------------------------------------------- create database
echo "[2/4] Tạo database `{$name}` (utf8mb4 / utf8mb4_unicode_ci)...\n";
$pdo->exec(
    "CREATE DATABASE IF NOT EXISTS `{$name}`"
    . ' DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci'
);
$pdo->exec("USE `{$name}`");

// ------------------------------------------------------------- run SQL file
/**
 * Tách file .sql thành từng câu lệnh.
 *
 * Bỏ qua comment `--` và `#`, bỏ qua câu lệnh rỗng. File của dự án không
 * chứa dấu `;` trong chuỗi nên tách theo dấu `;` là an toàn.
 *
 * Lưu ý: phải tách dòng bằng `\n` chứ không dùng `\R` — `\R` trong PCRE khớp
 * cả byte 0x85 (NEL), mà 0x85 lại là byte cuối của nhiều ký tự UTF-8 tiếng Việt
 * (`ễ` = E1 BB 85), nên dữ liệu sẽ bị cắt đôi.
 */
function splitStatements(string $sql): array
{
    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $sql));
    $clean = [];

    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $clean[] = $line;
    }

    $statements = [];
    foreach (explode(';', implode("\n", $clean)) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $statements[] = $statement;
        }
    }

    return $statements;
}

function runSqlFile(PDO $pdo, string $file, string $label): void
{
    if (!is_file($file)) {
        exit("Không tìm thấy file: {$file}\n");
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        exit("Không đọc được file: {$file}\n");
    }

    $statements = splitStatements($sql);
    echo "      {$label}: " . count($statements) . " câu lệnh\n";

    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            exit("Lỗi SQL trong {$label}:\n  " . substr($statement, 0, 200) . "\n  " . $e->getMessage() . "\n");
        }
    }
}

echo "[3/4] Nạp schema...\n";
runSqlFile($pdo, $root . '/database/schema.sql', 'schema.sql');

if ($onlySchema) {
    echo "\nHoàn tất (chỉ schema, không có dữ liệu mẫu).\n";
    exit(0);
}

echo "[4/4] Nạp dữ liệu mẫu...\n";
runSqlFile($pdo, $root . '/database/seed.sql', 'seed.sql');

// ------------------------------------------------------------------ verify
$tables = $pdo->query(
    'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME'
)->fetchAll(PDO::FETCH_COLUMN);

$columns = $pdo->query(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND CHARACTER_SET_NAME IS NOT NULL AND CHARACTER_SET_NAME <> \'utf8mb4\''
)->fetchColumn();

echo "\nKết quả:\n";
echo '  bảng      : ' . count($tables) . ' (' . implode(', ', $tables) . ")\n";
echo '  cột sai encoding : ' . $columns . " (0 = đều utf8mb4)\n";

foreach (['users' => 'display_name', 'posts' => 'content', 'comments' => 'content', 'messages' => 'body'] as $table => $column) {
    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    echo "  {$table}.{$column}: {$count} dòng\n";
}

echo "\nHoàn tất.\n";
