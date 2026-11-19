<?php
/**
 * Hộp thoại xác nhận dùng chung cho toàn bộ website.
 *
 * Thay thế `window.confirm()` của trình duyệt bằng giao diện đồng bộ với
 * design system: nút huỷ dạng pill phụ, nút xoá dạng pill đỏ phá huỷ.
 *
 * Kích hoạt bằng `data-confirm="<nội dung>"` trên form, hoặc gọi trực tiếp
 * `window.kbConfirm(options)` để trả về một Promise.
 *
 * Thuộc tính bên trong hộp thoại dùng tiền tố `data-kb-confirm-*` để không
 * trùng với `data-confirm-*` mà form khai báo.
 */
?>
<div class="kb-confirm" id="kbConfirmModal" hidden role="alertdialog" aria-modal="true" aria-labelledby="kbConfirmTitle" aria-describedby="kbConfirmText">
    <div class="kb-confirm-backdrop" data-kb-confirm-close></div>

    <div class="kb-confirm-card" role="document">
        <span class="kb-confirm-icon" data-kb-confirm-icon>
            <i class="bi bi-exclamation-triangle-fill"></i>
        </span>

        <h2 class="kb-confirm-title" id="kbConfirmTitle" data-kb-confirm-title>Thông báo</h2>
        <p class="kb-confirm-text" id="kbConfirmText" data-kb-confirm-text></p>

        <div class="kb-confirm-actions">
            <button class="btn btn-outline" type="button" data-kb-confirm-cancel>Huỷ</button>
            <button class="btn btn-danger-solid" type="button" data-kb-confirm-accept>Đồng ý</button>
        </div>
    </div>
</div>
