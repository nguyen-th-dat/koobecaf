<?php
/**
 * @var array $post
 * @var array $images
 */
?>
<div class="page-head">
    <h1><i class="bi bi-pencil-square"></i>Chỉnh sửa bài viết</h1>
    <a class="btn btn-outline btn-sm" href="<?= url('/post/' . (int) $post['id']) ?>"><i class="bi bi-arrow-left me-1"></i>Trở lại</a>
</div>

<div class="card surface-card composer-card">
    <div class="card-body">
        <form action="<?= url('/posts/' . (int) $post['id'] . '/update') ?>" method="post" enctype="multipart/form-data"
              data-draft="post-edit-<?= (int) $post['id'] ?>" data-leave="keep"
              data-lose-fields="images[]" data-lose-label="Ảnh đã chọn"
              data-old-fields="<?= e(old_fields(['content', 'privacy'])) ?>">
            <?= \App\Core\Csrf::field() ?>

            <div class="d-flex gap-3">
                <img src="<?= e(avatar_url(auth_user()['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-md avatar-ring">
                <textarea name="content" class="form-control composer-input" id="postContent" rows="5"
                          placeholder="Nội dung bài viết..." maxlength="5000" required><?= e(old('content', $post['content'])) ?></textarea>
            </div>

            <div class="composer-counter">
                <span data-composer-count>0</span>/5000 ký tự
            </div>

            <?php if ($images !== []): ?>
                <div class="edit-media mt-4">
                    <div class="edit-media-head">
                        <span class="edit-media-title"><i class="bi bi-images me-1"></i>Hình ảnh hiện tại</span>
                        <span class="edit-media-hint">Bỏ chọn ảnh bạn muốn xoá</span>
                    </div>
                    <div class="edit-media-grid">
                        <?php foreach ($images as $image): ?>
                            <label class="edit-media-item" for="rm<?= (int) $image['id'] ?>">
                                <img src="<?= e(upload_url($image['path'])) ?>" alt="" loading="lazy">
                                <span class="edit-media-check">
                                    <input class="check" type="checkbox" name="remove_images[]" value="<?= (int) $image['id'] ?>" id="rm<?= (int) $image['id'] ?>">
                                </span>
                                <span class="edit-media-remove"><i class="bi bi-trash"></i>Xoá</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="edit-media mt-4">
                <div class="edit-media-head">
                    <span class="edit-media-title"><i class="bi bi-plus-square me-1"></i>Thêm hình ảnh</span>
                    <span class="edit-media-hint">Tối đa 10 ảnh</span>
                </div>
                <label class="upload-drop" for="postImages">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <strong>Chọn ảnh từ máy</strong>
                    <span>JPG, PNG, GIF hoặc WEBP</span>
                    <input type="file" id="postImages" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="d-none">
                </label>
                <div class="image-preview d-flex flex-wrap gap-2 mt-2 d-none" id="imagePreview"></div>
            </div>

            <div class="mt-4">
                <?= view_partial(dirname(__DIR__) . '/partials/privacy-picker.php', [
                    'name' => 'privacy',
                    'value' => old('privacy', $post['privacy']),
                ]) ?>
            </div>

            <div class="composer-toolbar mt-4">
                <div class="composer-toolbar-left">
                    <a class="btn btn-text btn-sm" href="<?= url('/post/' . (int) $post['id']) ?>">Huỷ</a>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline btn-sm" type="button" data-compose-preview>
                        <i class="bi bi-eye me-1"></i>Xem trước
                    </button>
                    <button class="btn btn-solid btn-sm" type="submit"><i class="bi bi-check-lg me-1"></i>Lưu thay đổi</button>
                </div>
            </div>
        </form>

        <!-- Xoá bài viết: form riêng, không lồng trong form cập nhật -->
        <div class="composer-danger">
            <form action="<?= url('/posts/' . (int) $post['id'] . '/delete') ?>" method="post"
                  data-confirm="Bạn có chắc muốn xóa bài viết này? Hành động này không thể hoàn tác."
                  data-confirm-title="Xóa bài viết" data-confirm-accept="Xóa bài viết">
                <?= \App\Core\Csrf::field() ?>
                <div class="composer-danger-text">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Xóa bài viết này sẽ mất vĩnh viễn, kể cả bình luận và lượt thích.</span>
                </div>
                <button class="btn btn-outline btn-danger btn-sm" type="submit">
                    <i class="bi bi-trash me-1"></i>Xóa bài viết
                </button>
            </form>
        </div>

        <div class="composer-preview" data-compose-preview-panel hidden>
            <div class="composer-preview-label">
                <i class="bi bi-eye"></i>Xem trước bài viết
            </div>
            <article class="card surface-card composer-preview-card">
                <div class="post-body d-flex gap-3 p-3">
                    <img src="<?= e(avatar_url(auth_user()['avatar_path'] ?? null)) ?>" alt="" class="avatar avatar-md avatar-ring">
                    <div class="flex-grow-1 min-w-0">
                        <div class="post-author"><?= e(auth_user()['display_name'] ?? '') ?></div>
                        <div class="post-meta">@<?= e(auth_user()['username'] ?? '') ?></div>
                        <p class="post-text mb-0 mt-2" data-compose-preview-text></p>
                    </div>
                </div>
            </article>
        </div>
    </div>
</div>
