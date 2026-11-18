<div class="auth-title">
    <h1>Đặt lại mật khẩu</h1>
    <p class="fg-secondary mb-0">Chọn mật khẩu mới cho tài khoản của bạn.</p>
</div>

<form action="<?= url('/reset-password') ?>" method="post" novalidate>
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

    <div class="mb-3">
        <label class="form-label" for="password">Mật khẩu mới</label>
        <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" required minlength="8" autocomplete="new-password">
            <button class="btn btn-outline" type="button" data-toggle-password="#password" aria-label="Hiện mật khẩu">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <div class="form-text">Tối thiểu 8 ký tự.</div>
    </div>

    <div class="mb-4">
        <label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
    </div>

    <button class="btn btn-solid w-100" type="submit">Lưu mật khẩu mới</button>
</form>

<hr class="my-4">

<p class="text-center mb-0">
    <a href="<?= url('/login') ?>" class="fw-semibold"><i class="bi bi-arrow-left me-1"></i>Quay lại đăng nhập</a>
</p>
