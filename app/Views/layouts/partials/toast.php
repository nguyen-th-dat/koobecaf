<?php
/**
 * Hệ thống thông báo Toast dùng chung cho toàn bộ website.
 *
 * Partial này được include trong mọi layout nên chỉ tồn tại ở một chỗ duy nhất.
 * Toast hiển thị ở góc dưới bên phải; thông báo flash từ PHP được render thẳng
 * vào cùng container với toast do client tạo nên giao diện luôn đồng nhất.
 *
 * assets/js/toast.js sẽ tiếp quản vòng đời (đếm ngược, nút đóng, tạm dừng
 * khi rê chuột) cho cả hai nguồn thông báo.
 *
 * @var array<int,array{type:string,message:string}> $flashMessages
 */
$flashMessages = $flashMessages ?? flash_messages();
?>
<div id="kbToastContainer" class="toast-container" aria-live="polite" aria-atomic="false">
    <?php foreach ($flashMessages as $flashMessage): ?>
        <div class="toast is-<?= e($flashMessage['type']) ?>"
             role="<?= $flashMessage['type'] === 'error' ? 'alert' : 'status' ?>"
             data-toast>
            <i class="bi <?= e(toast_icon($flashMessage['type'])) ?> toast-icon" aria-hidden="true"></i>
            <div class="toast-body"><?= e($flashMessage['message']) ?></div>
            <button type="button" class="btn-close toast-close" aria-label="Đóng thông báo"></button>
        </div>
    <?php endforeach; ?>
</div>
