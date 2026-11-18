<div class="auth-title">
    <h1>Đăng ký</h1>
    <p class="fg-secondary mb-0">Tạo tài khoản để kết nối với bạn bè.</p>
</div>

<form action="<?= url('/register') ?>" method="post" novalidate data-draft="register" data-guard="Thông tin đăng ký" data-old-fields="<?= e(old_fields(['display_name', 'username', 'email'])) ?>">
    <?= \App\Core\Csrf::field() ?>

    <div class="mb-3">
        <label class="form-label" for="display_name">Tên hiển thị</label>
        <input type="text" class="form-control" id="display_name" name="display_name" value="<?= e(old('display_name')) ?>" required maxlength="100">
    </div>

    <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input type="text" class="form-control" id="username" name="username" value="<?= e(old('username')) ?>" required maxlength="30" pattern="[a-zA-Z0-9_]{3,30}">
        <div class="form-text">Chỉ chữ, số và dấu gạch dưới (3-30 ký tự).</div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
    </div>

    <div class="mb-3">
        <label class="form-label" for="password">Mật khẩu</label>
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

    <button class="btn btn-solid w-100" type="submit">Tạo tài khoản</button>
</form>

<hr class="my-4">

<p class="text-center mb-0">
    Đã có tài khoản? <a href="<?= url('/login') ?>" class="fw-semibold">Đăng nhập</a>
</p>
