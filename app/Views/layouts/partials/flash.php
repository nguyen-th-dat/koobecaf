<?php
$success = flash('success');
$error = flash('error');
?>
<?php if ($success !== null): ?>
    <div class="alert alert-success mt-3" role="alert">
        <i class="bi bi-check-circle"></i><?= e($success) ?>
        <button type="button" class="btn-close" data-alert-dismiss aria-label="Đóng"></button>
    </div>
<?php endif; ?>

<?php if ($error !== null): ?>
    <div class="alert alert-danger mt-3" role="alert">
        <i class="bi bi-exclamation-triangle"></i><?= e($error) ?>
        <button type="button" class="btn-close" data-alert-dismiss aria-label="Đóng"></button>
    </div>
<?php endif; ?>
