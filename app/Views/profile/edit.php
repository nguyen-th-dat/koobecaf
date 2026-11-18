<?php
/**
 * @var array $user
 */
?>
<h1 class="h3 mb-3"><i class="bi bi-gear me-2"></i>Cài đặt tài khoản</h1>

<div class="card surface-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3"><i class="bi bi-person-badge me-2"></i>Thông tin cá nhân</h2>
        <form action="<?= url('/settings/profile') ?>" method="post" enctype="multipart/form-data" data-draft="profile" data-guard="Thông tin hồ sơ" data-old-fields="<?= e(old_fields(['display_name', 'bio'])) ?>">
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="md:col-6">
                    <label class="form-label" for="display_name">Tên hiển thị</label>
                    <input type="text" class="form-control" id="display_name" name="display_name" value="<?= e(old('display_name', $user['display_name'])) ?>" maxlength="100" required>
                </div>
                <div class="md:col-6">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" value="<?= e($user['username']) ?>" disabled>
                    <div class="form-text">Username không thể thay đổi.</div>
                </div>
                <div class="md:col-6">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" value="<?= e($user['email']) ?>" disabled>
                </div>
                <div class="col-12">
                    <label class="form-label" for="bio">Tiểu sử</label>
                    <textarea class="form-control" id="bio" name="bio" rows="3" maxlength="500" placeholder="Giới thiệu về bạn..."><?= e(old('bio', $user['bio'])) ?></textarea>
                    <div class="form-text">Tối đa 500 ký tự.</div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="md:col-6">
                    <label class="form-label" for="avatar">Ảnh đại diện</label>
                    <input class="form-control" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">Tối đa 5MB.</div>
                </div>
                <div class="md:col-6">
                    <label class="form-label" for="cover">Ảnh bìa</label>
                    <input class="form-control" type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">Tối đa 5MB.</div>
                </div>
            </div>

            <button class="btn btn-solid mt-4" type="submit">Lưu thay đổi</button>
        </form>
    </div>
</div>

<div class="card surface-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3"><i class="bi bi-key me-2"></i>Đổi mật khẩu</h2>
        <form action="<?= url('/settings/password') ?>" method="post">
            <?= \App\Core\Csrf::field() ?>

            <div class="mb-3">
                <label class="form-label" for="current_password">Mật khẩu hiện tại</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="mb-3">
                <label class="form-label" for="new_password">Mật khẩu mới</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                <div class="form-text">Tối thiểu 8 ký tự.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="new_password_confirmation">Xác nhận mật khẩu mới</label>
                <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required minlength="8" autocomplete="new-password">
            </div>

            <button class="btn btn-outline btn-primary" type="submit">Đổi mật khẩu</button>
        </form>
    </div>
</div>

<div class="card surface-card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3"><i class="bi bi-palette me-2"></i>Giao diện</h2>
        <form action="<?= url('/settings/theme') ?>" method="post" id="themeForm">
            <?= \App\Core\Csrf::field() ?>
            <div class="btn-group" role="group" aria-label="Chọn giao diện">
                <input type="hidden" name="theme" id="themeInput" value="<?= e($user['theme']) ?>">
                <button type="button" class="btn btn-outline theme-btn<?= $user['theme'] === 'light' ? ' active' : '' ?>" data-theme="light"><i class="bi bi-sun me-1"></i>Sáng</button>
                <button type="button" class="btn btn-outline theme-btn<?= $user['theme'] === 'dark' ? ' active' : '' ?>" data-theme="dark"><i class="bi bi-moon-stars me-1"></i>Tối</button>
            </div>
            <div class="form-text mt-2">Giao diện được lưu theo tài khoản của bạn.</div>
        </form>
    </div>
</div>
