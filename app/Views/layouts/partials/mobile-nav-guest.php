<?php
/**
 * Thanh điều hướng dưới màn hình dành cho khách chưa đăng nhập.
 *
 * Người đã đăng nhập dùng `mobile-nav.php` (5 mục + badge); khách chỉ cần
 * các đường dẫn công khai nên có thanh gọn hơn.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = '/' . trim($path, '/');
?>
<nav class="mobile-nav lg:d-none">
    <a class="<?= $path === '/' ? 'active' : '' ?>" href="<?= url('/') ?>"><i class="bi bi-house-door"></i><span>Trang chủ</span></a>
    <a class="<?= str_starts_with($path, '/search') ? 'active' : '' ?>" href="<?= url('/search') ?>"><i class="bi bi-search"></i><span>Khám phá</span></a>
    <a class="<?= str_starts_with($path, '/login') ? 'active' : '' ?>" href="<?= url('/login') ?>"><i class="bi bi-box-arrow-in-right"></i><span>Đăng nhập</span></a>
    <a class="<?= str_starts_with($path, '/register') ? 'active' : '' ?>" href="<?= url('/register') ?>"><i class="bi bi-person-plus"></i><span>Đăng ký</span></a>
</nav>
