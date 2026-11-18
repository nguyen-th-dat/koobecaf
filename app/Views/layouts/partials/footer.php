<?php
/**
 * Chân trang nền tối — điểm nhấn cuối trang.
 */
?>
<footer class="site-footer">
    <div class="container-fluid">
        <div class="row g-3">
            <div class="col-12 md:col-6 lg:col-6">
                <a class="brand mb-3" href="<?= url('/') ?>">
                    <img src="<?= asset('images/logo.png') ?>" alt="Koobecaf" class="brand-logo">
                    <span class="brand-text">Koobecaf</span>
                </a>
                <p class="mb-0" style="max-width: 380px;">
                    Nơi kết nối bạn bè, chia sẻ khoảnh khắc và trò chuyện trực tiếp.
                </p>
            </div>
            <div class="col-6 md:col-3 lg:col-2">
                <h4>Khám phá</h4>
                <ul>
                    <li><a href="<?= url('/') ?>">Bảng tin</a></li>
                    <li><a href="<?= url('/search') ?>">Tìm kiếm</a></li>
                    <?php if (is_auth()): ?>
                        <li><a href="<?= url('/chat') ?>">Trò chuyện</a></li>
                    <?php else: ?>
                        <li><a href="<?= url('/login') ?>">Đăng nhập</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-6 md:col-3 lg:col-2">
                <h4>Tài khoản</h4>
                <ul>
                    <?php if (is_auth()): ?>
                        <li><a href="<?= url('/settings/profile') ?>">Cài đặt</a></li>
                        <li><a href="<?= url('/friends') ?>">Bạn bè</a></li>
                        <li><a href="<?= url('/notifications') ?>">Thông báo</a></li>
                    <?php else: ?>
                        <li><a href="<?= url('/login') ?>">Đăng nhập</a></li>
                        <li><a href="<?= url('/register') ?>">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <hr class="my-4" style="border-color: rgba(250, 249, 245, .14);">
        <div class="d-flex justify-content-between flex-wrap gap-2 small">
            <span>© <?= date('Y') ?> Koobecaf</span>
            <span>Chia sẻ khoảnh khắc, kết nối bạn bè.</span>
        </div>
    </div>
</footer>
