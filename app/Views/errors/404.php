<?php
/**
 * @var string|null $title
 * @var int|null $code
 */
?>
<div class="error-page">
    <span class="error-code"><?= (int) ($code ?? 404) ?></span>
    <h1 class="error-title"><?= e($title ?? 'Không tìm thấy trang bạn yêu cầu') ?></h1>
    <p class="fg-secondary">Đường dẫn không tồn tại hoặc nội dung đã bị xóa.</p>
    <a class="btn btn-solid mt-3" href="<?= url('/') ?>"><i class="bi bi-house me-1"></i>Về trang chủ</a>
</div>
