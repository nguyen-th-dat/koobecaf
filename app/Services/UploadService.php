<?php

declare(strict_types=1);

namespace App\Services;

final class UploadService
{
    public const MAX_SIZE = 5 * 1024 * 1024; // 5MB

    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Lưu file tải lên và trả về đường dẫn tương đối trong thư mục uploads.
     *
     * @throws \RuntimeException khi file không hợp lệ
     */
    public static function image(array $file, string $folder): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::errorMessage($error));
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_SIZE) {
            throw new \RuntimeException('Kích thước file không được vượt quá 5MB.');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new \RuntimeException('Tệp tải lên không hợp lệ.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
        if (!isset(self::ALLOWED_MIMES[$mime])) {
            throw new \RuntimeException('Chỉ chấp nhận ảnh JPG, PNG, GIF hoặc WEBP.');
        }

        $extension = self::ALLOWED_MIMES[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        $baseDir = dirname(__DIR__, 2) . '/uploads/' . trim($folder, '/');
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu trữ.');
        }

        $target = $baseDir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $target)) {
            throw new \RuntimeException('Không thể lưu tệp đã tải lên.');
        }

        return trim($folder, '/') . '/' . $filename;
    }

    /** Xóa file đã tải lên (bỏ qua nếu không tồn tại). */
    public static function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        if (str_contains($relativePath, '..')) {
            return;
        }
        $full = dirname(__DIR__, 2) . '/uploads/' . ltrim($relativePath, '/');
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private static function errorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Kích thước file vượt quá giới hạn cho phép.',
            UPLOAD_ERR_PARTIAL    => 'Tệp tải lên không hoàn chỉnh.',
            UPLOAD_ERR_NO_FILE    => 'Không có tệp nào được chọn.',
            UPLOAD_ERR_NO_TMP_DIR => 'Máy chủ không có thư mục tạm.',
            UPLOAD_ERR_CANT_WRITE => 'Máy chủ không thể ghi tệp.',
            UPLOAD_ERR_EXTENSION  => 'Tệp bị chặn bởi tiện ích mở rộng của PHP.',
            default               => 'Tải lên tệp thất bại.',
        };
    }
}
