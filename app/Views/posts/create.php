<?php
/**
 * Trang đăng bài viết (riêng biệt khỏi bảng tin).
 *
 * @var array|null $user Người dùng đang đăng nhập
 */
?>
<div class="page-head">
    <h1><i class="bi bi-pencil-square"></i>Đăng bài viết</h1>
    <a class="btn btn-outline btn-sm" href="<?= url('/') ?>"><i class="bi bi-arrow-left me-1"></i>Về bảng tin</a>
</div>

<div class="card surface-card" id="composer">
    <div class="card-body">
        <form action="<?= url('/posts') ?>" method="post" enctype="multipart/form-data" id="postForm"
              data-draft="composer" data-leave="keep" data-guard="Bài viết đang soạn"
              data-lose-fields="images[]" data-lose-label="Ảnh đã chọn"
              data-old-fields="<?= e(old_fields(['content', 'privacy'])) ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="d-flex gap-3">
                <img src="<?= e(avatar_url($user['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-md avatar-ring">
                <textarea name="content" class="form-control composer-input" rows="6"
                          placeholder="Bạn đang nghĩ gì?" maxlength="5000" id="postContent"><?= e(old('content')) ?></textarea>
            </div>

            <div class="image-preview d-flex flex-wrap gap-2 mt-2 d-none" id="imagePreview"></div>

            <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top">
                <label class="btn btn-subtle btn-sm mb-0" for="postImages">
                    <i class="bi bi-image me-1"></i>Ảnh
                    <input type="file" id="postImages" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="d-none">
                </label>

                <div class="d-flex align-items-center gap-2">
                    <select name="privacy" class="form-control form-control-sm w-auto" aria-label="Quyền riêng tư">
                        <option value="public" <?= old('privacy', 'public') === 'public' ? 'selected' : '' ?>>Công khai</option>
                        <option value="followers" <?= old('privacy', 'public') === 'followers' ? 'selected' : '' ?>>Người theo dõi</option>
                        <option value="private" <?= old('privacy', 'public') === 'private' ? 'selected' : '' ?>>Riêng tư</option>
                    </select>
                    <button class="btn btn-solid btn-sm" type="submit"><i class="bi bi-send me-1"></i>Đăng bài</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card surface-card mt-3">
    <div class="card-body">
        <h2 class="h5 mb-2"><i class="bi bi-lightbulb me-1"></i>Gợi ý khi viết bài</h2>
        <ul class="mb-0 fg-secondary small">
            <li>Chia sẻ khoảnh khắc, câu chuyện hoặc điều bạn đang học.</li>
            <li>Chọn đúng quyền riêng tư trước khi đăng.</li>
            <li>Bạn có thể sửa hoặc xoá bài viết của mình bất cứ lúc nào.</li>
        </ul>
    </div>
</div>
