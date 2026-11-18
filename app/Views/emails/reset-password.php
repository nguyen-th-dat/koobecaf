<?php
/**
 * Template email đặt lại mật khẩu.
 *
 * @var string $appName
 * @var string $userName
 * @var string $resetUrl
 * @var int    $minutes
 */
$primary = '#14806c';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt lại mật khẩu</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1f2933;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(16,24,40,.1);">
                <tr>
                    <td style="background-color:<?= $primary ?>;padding:28px 32px;">
                        <span style="display:inline-block;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:.5px;"><?= e($appName) ?></span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <h1 style="margin:0 0 16px;font-size:20px;line-height:1.3;color:#1f2933;">Xin chào <?= e($userName) ?>,</h1>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#52606d;">
                            Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
                            Nhấn nút bên dưới để chọn mật khẩu mới:
                        </p>
                        <p style="margin:0 0 24px;">
                            <a href="<?= e($resetUrl) ?>" style="display:inline-block;background-color:<?= $primary ?>;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:12px 24px;border-radius:10px;">
                                Đặt lại mật khẩu
                            </a>
                        </p>
                        <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#7b8794;">
                            Link có hiệu lực trong <strong><?= (int) $minutes ?> phút</strong> và chỉ dùng được một lần.
                        </p>
                        <p style="margin:0 0 24px;font-size:13px;line-height:1.6;color:#7b8794;">
                            Nếu nút không hoạt động, hãy sao chép địa chỉ sau vào trình duyệt:
                        </p>
                        <p style="margin:0 0 24px;font-size:13px;line-height:1.6;word-break:break-all;">
                            <a href="<?= e($resetUrl) ?>" style="color:<?= $primary ?>;"><?= e($resetUrl) ?></a>
                        </p>
                        <p style="margin:0;padding:14px 16px;background-color:#f0f4f4;border-radius:10px;font-size:13px;line-height:1.6;color:#52606d;">
                            Không phải bạn yêu cầu đặt lại mật khẩu? Bạn có thể bỏ qua email này — mật khẩu hiện tại vẫn giữ nguyên.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 32px;border-top:1px solid #e4e7eb;">
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#9aa5b1;">
                            © <?= date('Y') ?> <?= e($appName) ?>. Email này được gửi tự động, vui lòng không trả lời.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
