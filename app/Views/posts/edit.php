<?php
/**
 * @var array $post
 * @var array $images
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa bài viết</h1>
    <a class="btn btn-outline btn-sm" href="<?= url('/post/' . (int) $post['id']) ?>"><i class="bi bi-arrow-left"></i> Trở lại</a>
</div>

<div class="card surface-card">
    <div class="card-body">
        <form action="<?= url('/posts/' . (int) $post['id'] . '/update') ?>" method="post" enctype="multipart/form-data" data-draft="post-edit-<?= (int) $post['id'] ?>" data-leave="keep" data-lose-fields="images[]" data-lose-label="Ảnh đã chọn" data-old-fields="<?= e(old_fields(['content', 'privacy'])) ?>">
            <?= \App\Core\Csrf::field() ?>

            <div class="mb-3">
                <label class="form-label" for="content">Nội dung</label>
                <textarea class="form-control" id="content" name="content" rows="5" maxlength="5000" required><?= e(old('content', $post['content'])) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label" for="privacy">Quyền riêng tư</label>
                <select class="form-control" id="privacy" name="privacy">
                    <option value="public" <?= old('privacy', $post['privacy']) === 'public' ? 'selected' : '' ?>>Công khai</option>
                    <option value="followers" <?= old('privacy', $post['privacy']) === 'followers' ? 'selected' : '' ?>>Người theo dõi</option>
                    <option value="private" <?= old('privacy', $post['privacy']) === 'private' ? 'selected' : '' ?>>Riêng tư</option>
                </select>
            </div>

            <?php if ($images !== []): ?>
                <div class="mb-3">
                    <label class="form-label">Hình ảnh hiện tại</label>
                    <div class="row g-2">
                        <?php foreach ($images as $image): ?>
                            <div class="col-6 md:col-3">
                                <div class="position-relative">
                                    <img src="<?= e(upload_url($image['path'])) ?>" alt="" class="img-fluid rounded border w-100">
                                    <div class="position-absolute top-0 end-0 m-1 bg-body rounded px-1">
                                        <input class="check" type="checkbox" name="remove_images[]" value="<?= (int) $image['id'] ?>" id="rm<?= (int) $image['id'] ?>">
                                        <label class="small" for="rm<?= (int) $image['id'] ?>">Xóa</label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label" for="images">Thêm hình ảnh</label>
                <input class="form-control" type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
            </div>

            <button class="btn btn-solid" type="submit">Lưu thay đổi</button>
        </form>
    </div>
</div>
