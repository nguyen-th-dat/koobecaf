<?php
/**
 * Trang quên mật khẩu — hai bước.
 *
 * Bước 1: nhập email hoặc tên đăng nhập → hệ thống kiểm tra tài khoản.
 * Bước 2: báo người dùng đã tìm thấy tài khoản, bấm "Tiếp tục" thì mới gửi
 *         email chứa link đặt lại mật khẩu, bấm "Không phải tôi" thì huỷ.
 *
 * @var string $email
 */
$email = (string) old('email');
?>
<div class="auth-title">
    <h1>Quên mật khẩu</h1>
    <p class="fg-secondary mb-0">Nhập email hoặc tên đăng nhập của bạn.</p>
</div>

<form action="<?= url('/forgot-password') ?>" method="post" novalidate data-draft="forgot-password" data-leave="clear" data-old-fields="<?= e(old_fields(['email'])) ?>" data-forgot-form>
    <?= \App\Core\Csrf::field() ?>

    <div class="mb-3" data-forgot-step="1">
        <label class="form-label" for="email">Email hoặc tên đăng nhập</label>
        <input type="text" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="Email hoặc username" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false">
        <div class="form-text">Chúng tôi sẽ kiểm tra tài khoản này có tồn tại không.</div>
    </div>

    <div class="mb-3 d-none" data-forgot-step="2">
        <div class="forgot-confirm" role="status">
            <i class="bi bi-person-check"></i>
            <div>
                <strong>Đã tìm thấy tài khoản</strong>
                <p class="mb-0"><span data-forgot-name></span> — <span data-forgot-email></span></p>
            </div>
        </div>
        <p class="form-text mb-0">Chọn "Tiếp tục" để chúng tôi gửi link đặt lại mật khẩu tới hộp thư này.</p>
    </div>

    <div class="d-none" data-forgot-step="2">
        <button class="btn btn-solid w-100 mb-2" type="button" data-forgot-send>
            <i class="bi bi-send me-1"></i>Tiếp tục
        </button>
        <button class="btn btn-outline w-100" type="button" data-forgot-cancel>
            Không phải tôi
        </button>
    </div>

    <button class="btn btn-solid w-100" type="submit" data-forgot-submit>
        Kiểm tra tài khoản
    </button>
</form>

<hr class="my-4">

<p class="text-center mb-0">
    <a href="<?= url('/login') ?>" class="fw-semibold"><i class="bi bi-arrow-left me-1"></i>Quay lại đăng nhập</a>
</p>
