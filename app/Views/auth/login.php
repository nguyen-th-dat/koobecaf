<div class="auth-title">
    <h1>Đăng nhập</h1>
    <p class="text-secondary mb-0">Chào mừng trở lại với Koobecaf.</p>
</div>

<form action="<?= url('/login') ?>" method="post" novalidate>
    <?= \App\Core\Csrf::field() ?>

    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required autofocus autocomplete="email">
    </div>

    <div class="mb-3">
        <label class="form-label" for="password">Mật khẩu</label>
        <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
            <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password" aria-label="Hiện mật khẩu">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
</form>

<hr class="my-4">

<p class="text-center mb-0">
    Chưa có tài khoản? <a href="<?= url('/register') ?>" class="fw-semibold">Đăng ký ngay</a>
</p>
