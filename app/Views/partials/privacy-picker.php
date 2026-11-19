<?php
/**
 * Bộ chọn quyền riêng tư dạng pill.
 *
 * Dùng chung cho trang đăng bài, chỉnh sửa bài viết và hộp thoại chia sẻ.
 * Radio thật nằm bên trong để form vẫn gửi đúng giá trị `privacy`; lớp
 * vỏ bọc chỉ là điều khiển trực quan.
 *
 * @var string $name   Tên field (mặc định: privacy)
 * @var string $value  Giá trị đang chọn (mặc định: public)
 */
$name = $name ?? 'privacy';
$value = $value ?? 'public';

$privacyOptions = [
    'public' => ['icon' => 'bi-globe2', 'label' => 'Công khai', 'desc' => 'Mọi người đều xem được'],
    'followers' => ['icon' => 'bi-people', 'label' => 'Người theo dõi', 'desc' => 'Chỉ người theo dõi bạn'],
    'private' => ['icon' => 'bi-lock', 'label' => 'Riêng tư', 'desc' => 'Chỉ bạn mới thấy'],
];
?>
<fieldset class="privacy-picker">
    <legend class="form-label mb-0">Quyền riêng tư</legend>
    <div class="privacy-picker-options">
        <?php foreach ($privacyOptions as $key => $meta): ?>
            <label class="privacy-option<?= $value === $key ? ' is-active' : '' ?>">
                <input type="radio" name="<?= e($name) ?>" value="<?= e($key) ?>"
                       class="privacy-option-input"<?= $value === $key ? ' checked' : '' ?>>
                <span class="privacy-option-icon"><i class="bi <?= e($meta['icon']) ?>"></i></span>
                <span class="privacy-option-label"><?= e($meta['label']) ?></span>
                <span class="privacy-option-desc"><?= e($meta['desc']) ?></span>
            </label>
        <?php endforeach; ?>
    </div>
</fieldset>
