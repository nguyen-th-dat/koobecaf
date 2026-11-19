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

<div class="card surface-card composer-card" id="composer">
    <div class="card-body">
        <form action="<?= url('/posts') ?>" method="post" enctype="multipart/form-data" id="postForm"
              data-draft="composer" data-leave="keep"
              data-lose-fields="images[]" data-lose-label="Ảnh đã chọn"
              data-old-fields="<?= e(old_fields(['content', 'privacy'])) ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="d-flex gap-3">
                <img src="<?= e(avatar_url($user['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-md avatar-ring">
                <textarea name="content" class="form-control composer-input" rows="6"
                          placeholder="Bạn đang nghĩ gì?" maxlength="5000" id="postContent"><?= e(old('content')) ?></textarea>
            </div>

            <div class="composer-counter">
                <span data-composer-count>0</span>/5000 ký tự
            </div>

            <div class="image-preview d-flex flex-wrap gap-2 mt-2 d-none" id="imagePreview"></div>

            <div class="mt-4">
                <?= view_partial(dirname(__DIR__) . '/partials/privacy-picker.php', [
                    'name' => 'privacy',
                    'value' => old('privacy', 'public'),
                ]) ?>
            </div>

            <div class="composer-toolbar">
                <div class="composer-toolbar-left">
                    <label class="btn btn-subtle btn-sm mb-0" for="postImages">
                        <i class="bi bi-image me-1"></i>Ảnh
                        <input type="file" id="postImages" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="d-none">
                    </label>
                    <span class="composer-hint">Tối đa 10 ảnh · JPG, PNG, GIF, WEBP</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline btn-sm" type="button" data-compose-preview>
                        <i class="bi bi-eye me-1"></i>Xem trước
                    </button>
                    <button class="btn btn-solid btn-sm" type="submit"><i class="bi bi-send me-1"></i>Đăng bài</button>
                </div>
            </div>
        </form>

        <!-- Bản xem trước: cập nhật trực tiếp theo nội dung đang soạn -->
        <div class="composer-preview" data-compose-preview-panel hidden>
            <div class="composer-preview-label">
                <i class="bi bi-eye"></i>Xem trước bài viết
            </div>
            <article class="card surface-card composer-preview-card">
                <div class="post-body d-flex gap-3 p-3">
                    <img src="<?= e(avatar_url($user['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-md avatar-ring">
                    <div class="flex-grow-1 min-w-0">
                        <div class="post-author"><?= e($user['display_name'] ?? '') ?></div>
                        <div class="post-meta">@<?= e($user['username'] ?? '') ?> · Vừa xong</div>
                        <p class="post-text mb-0 mt-2" data-compose-preview-text>Bạn đang nghĩ gì?</p>
                        <div class="post-images count-1 d-none" data-compose-preview-image>
                            <img src="" alt="">
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</div>
<div class="card surface-card composer-tips mt-3">
    <div class="card-body">
        <h2 class="h5 mb-2"><i class="bi bi-lightbulb me-1"></i>Gợi ý khi viết bài</h2>
        <ul class="mb-0 fg-secondary small">
            <li>Chia sẻ khoảnh khắc, câu chuyện hoặc điều bạn đang học.</li>
            <li>Chọn đúng quyền riêng tư trước khi đăng.</li>
            <li>Bạn có thể sửa hoặc xoá bài viết của mình bất cứ lúc nào.</li>
        </ul>
    </div>
</div>
