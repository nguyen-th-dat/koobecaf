<?php
/**
 * @var string|null $title
 * @var int|null $code
 */
?>
<div class="error-page">
    <span class="error-code"><?= (int) ($code ?? 403) ?></span>
    <h1 class="error-title"><?= e($title ?? 'Bạn không có quyền truy cập') ?></h1>
    <p class="fg-secondary">Nội dung này thuộc về người dùng khác hoặc yêu cầu không hợp lệ.</p>
    <a class="btn btn-solid mt-3" href="<?= url('/') ?>"><i class="bi bi-house me-1"></i>Về trang chủ</a>
</div>
