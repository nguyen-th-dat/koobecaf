<?php
/**
 * @var array $user
 */
$avatarUrl = avatar_url($user['avatar_path']);
$coverUrl = !empty($user['cover_path']) ? upload_url($user['cover_path']) : null;
?>
<div class="settings-head">
    <div>
        <h1 class="settings-title">Cài đặt tài khoản</h1>
        <p class="settings-subtitle">Quản lý thông tin hiển thị, ảnh đại diện và bảo mật.</p>
    </div>
    <a class="btn btn-outline" href="<?= url('/u/' . $user['username']) ?>">
        <i class="bi bi-person-circle me-1"></i>Xem trang cá nhân
    </a>
</div>

<!-- ================= Thông tin cá nhân ================= -->
<div class="panel settings-card mb-3">
    <div class="panel-body">
        <h2 class="settings-card-title">Thông tin cá nhân</h2>

        <!-- Xem trước ảnh bìa + đại diện -->
        <div class="profile-preview<?= $coverUrl === null ? ' is-empty' : '' ?>"
             style="<?= $coverUrl !== null ? 'background-image:url(\'' . e($coverUrl) . '\')' : '' ?>">
            <span class="profile-cover-texture" aria-hidden="true"></span>
            <div class="profile-preview-body">
                <div class="profile-preview-avatar">
                    <img src="<?= e($avatarUrl) ?>" alt="" id="avatarPreview">
                </div>
                <div class="profile-preview-text">
                    <strong><?= e($user['display_name']) ?></strong>
                    <span>@<?= e($user['username']) ?></span>
                </div>
            </div>
        </div>

        <form action="<?= url('/settings/profile') ?>" method="post" enctype="multipart/form-data"
              data-draft="profile" data-leave="keep" data-lose-fields="avatar,cover"
              data-lose-label="Ảnh đã chọn" data-old-fields="<?= e(old_fields(['display_name', 'bio'])) ?>">
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="md:col-6">
                    <label class="form-label" for="display_name">Tên hiển thị</label>
                    <input type="text" class="form-control" id="display_name" name="display_name"
                           value="<?= e(old('display_name', $user['display_name'])) ?>" maxlength="100" required>
                </div>
                <div class="md:col-6">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control is-disabled" id="username" value="<?= e($user['username']) ?>" disabled>
                    <div class="form-text">Username không thể thay đổi.</div>
                </div>
                <div class="md:col-6">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control is-disabled" id="email" value="<?= e($user['email']) ?>" disabled>
                    <div class="form-text">Email liên hệ để khôi phục tài khoản.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="bio">Tiểu sử</label>
                    <textarea class="form-control" id="bio" name="bio" rows="4" maxlength="500"
                              placeholder="Giới thiệu về bạn, sở thích, công việc..."><?= e(old('bio', $user['bio'])) ?></textarea>
                    <div class="form-text form-text-counter"><span data-bio-count>0</span>/500 ký tự</div>
                </div>
            </div>

            <hr class="settings-divider">

            <div class="row g-3">
                <div class="md:col-6">
                    <label class="form-label" for="avatar">Ảnh đại diện</label>
                    <input class="form-control" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">Tối đa 5MB. JPG, PNG, GIF hoặc WEBP.</div>
                </div>
                <div class="md:col-6">
                    <label class="form-label" for="cover">Ảnh bìa</label>
                    <input class="form-control" type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">Tối đa 5MB. Nên chọn ảnh rộng khoảng 1500×400px.</div>
                </div>
            </div>

            <div class="settings-actions">
                <button class="btn btn-solid" type="submit">Lưu thay đổi</button>
                <a class="btn btn-text" href="<?= url('/u/' . $user['username']) ?>">Huỷ</a>
            </div>
        </form>
    </div>
</div>

<!-- ================= Đổi mật khẩu ================= -->
<div class="card surface-card settings-card mb-3">
    <div class="card-body">
        <h2 class="settings-card-title">Đổi mật khẩu</h2>
        <form action="<?= url('/settings/password') ?>" method="post" data-guard="Mật khẩu đang đổi">
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="current_password">Mật khẩu hiện tại</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="md:col-6">
                    <label class="form-label" for="new_password">Mật khẩu mới</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                    <div class="form-text">Tối thiểu 8 ký tự.</div>
                </div>
                <div class="md:col-6">
                    <label class="form-label" for="new_password_confirmation">Xác nhận mật khẩu mới</label>
                    <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required minlength="8" autocomplete="new-password">
                </div>
            </div>

            <div class="settings-actions">
                <button class="btn btn-solid" type="submit">Đổi mật khẩu</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= Giao diện ================= -->
<div class="card surface-card settings-card mb-3">
    <div class="card-body">
        <h2 class="settings-card-title">Giao diện</h2>
        <form action="<?= url('/settings/theme') ?>" method="post" id="themeForm">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="theme" id="themeInput" value="<?= e($user['theme']) ?>">
            <div class="theme-picker" role="group" aria-label="Chọn giao diện">
                <button type="button" class="theme-option<?= $user['theme'] === 'light' ? ' active' : '' ?>" data-theme="light">
                    <span class="theme-option-swatch theme-swatch-light"><i class="bi bi-sun"></i></span>
                    <span class="theme-option-label">Sáng</span>
                </button>
                <button type="button" class="theme-option<?= $user['theme'] === 'dark' ? ' active' : '' ?>" data-theme="dark">
                    <span class="theme-option-swatch theme-swatch-dark"><i class="bi bi-moon-stars"></i></span>
                    <span class="theme-option-label">Tối</span>
                </button>
            </div>
            <div class="form-text mt-2">Giao diện được lưu theo tài khoản của bạn.</div>
        </form>
    </div>
</div>