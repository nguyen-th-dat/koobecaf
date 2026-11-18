<div class="auth-title">
    <h1>Quên mật khẩu</h1>
    <p class="fg-secondary mb-0">Nhập email của bạn, chúng tôi sẽ gửi link đặt lại mật khẩu.</p>
</div>

<form action="<?= url('/forgot-password') ?>" method="post" novalidate>
    <?= \App\Core\Csrf::field() ?>

    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required autofocus autocomplete="email">
    </div>

    <button class="btn btn-solid w-100" type="submit">Gửi link đặt lại mật khẩu</button>
</form>

<hr class="my-4">

<p class="text-center mb-0">
    <a href="<?= url('/login') ?>" class="fw-semibold"><i class="bi bi-arrow-left me-1"></i>Quay lại đăng nhập</a>
</p>
