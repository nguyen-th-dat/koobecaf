<div class="auth-title">
    <h1>Đăng nhập</h1>
    <p class="fg-secondary mb-0">Chào mừng trở lại với Koobecaf.</p>
</div>

<form action="<?= url('/login') ?>" method="post" novalidate data-draft="login" data-guard="Thông tin đăng nhập" data-leave="clear" data-lose-fields="password" data-lose-label="Mật khẩu đăng nhập" data-old-fields="<?= e(old_fields(['email'])) ?>">
    <?= \App\Core\Csrf::field() ?>

    <div class="mb-3">
        <label class="form-label" for="email">Email hoặc username</label>
        <input type="text" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="Email hoặc username" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false">
        <div class="form-text">Nhập email đã đăng ký hoặc tên người dùng của bạn.</div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="password">Mật khẩu</label>
        <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
            <button class="btn btn-outline" type="button" data-toggle-password="#password" aria-label="Hiện mật khẩu">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <div class="text-end mt-1">
            <a href="<?= url('/forgot-password') ?>" class="small">Quên mật khẩu?</a>
        </div>
    </div>

    <button class="btn btn-solid w-100" type="submit">Đăng nhập</button>
</form>

<hr class="my-4">

<p class="text-center mb-0">
    Chưa có tài khoản? <a href="<?= url('/register') ?>" class="fw-semibold">Đăng ký ngay</a>
</p>
