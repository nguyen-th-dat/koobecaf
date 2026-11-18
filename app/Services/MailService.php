<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Gửi email qua PHPMailer.
 *
 * Hai chế độ được hỗ trợ qua biến MAIL_DRIVER:
 *   - smtp: gửi thật qua SMTP server.
 *   - log : ghi nội dung email vào storage/logs (môi trường dev, chưa cấu hình SMTP).
 */
final class MailService
{
    /**
     * Gửi email HTML.
     *
     * @return bool true nếu gửi (hoặc ghi log) thành công, false nếu có lỗi.
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        if ((string) config('mail.driver', 'smtp') === 'log') {
            return self::writeToLog($toEmail, $toName, $subject, $htmlBody);
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) config('mail.host', 'localhost');
            $mail->Port = (int) config('mail.port', 587);
            $mail->SMTPAuth = (string) config('mail.username', '') !== '';
            $mail->Username = (string) config('mail.username', '');
            $mail->Password = (string) config('mail.password', '');
            $mail->SMTPSecure = self::encryption((string) config('mail.encryption', 'tls'));
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->SMTPDebug = config('mail.debug', false) ? 2 : 0;

            $mail->setFrom((string) config('mail.from_email', 'no-reply@localhost'), (string) config('mail.from_name', 'Koobecaf'));
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = trim((string) preg_replace('/\s+/', ' ', strip_tags($htmlBody)));

            $mail->send();

            return true;
        } catch (MailException $e) {
            Logger::error('Gửi mail tới ' . $toEmail . ' thất bại: ' . $e->getMessage());

            return false;
        }
    }

    private static function encryption(string $value): string
    {
        return match (strtolower($value)) {
            'tls', 'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
            'ssl', 'smtps'    => PHPMailer::ENCRYPTION_SMTPS,
            default           => '',
        };
    }

    private static function writeToLog(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        Logger::info(sprintf(
            "Mail (driver=log)\n  To: %s <%s>\n  Subject: %s\n  Body: %s",
            $toName,
            $toEmail,
            $subject,
            preg_replace('/\s+/', ' ', strip_tags($htmlBody))
        ));

        return true;
    }
}
