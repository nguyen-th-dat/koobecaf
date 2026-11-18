<?php

declare(strict_types=1);

/**
 * Các hàm helper dùng chung cho toàn hệ thống.
 */

if (!function_exists('config')) {
    /** Đọc cấu hình từ thư mục config. */
    function config(string $key, mixed $default = null): mixed
    {
        return \App\Core\Config::get($key, $default);
    }
}

if (!function_exists('view_include')) {
    /** Render một file view với dữ liệu truyền vào, trả về nội dung đã render. */
    function view_include(string $file, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}

if (!function_exists('view_partial')) {
    /**
     * Render một partial trong phạm vi biến riêng.
     *
     * Partial được require trực tiếp sẽ dùng chung biến với view cha, khiến các
     * vòng lặp lồng nhau (ví dụ reply của bình luận) có thể ghi đè biến đang dùng.
     * Hàm này cô lập phạm vi để partial tự tránh xung đột tên biến.
     */
    function view_partial(string $file, array $partialData = []): string
    {
        return (static function () use ($file, $partialData): string {
            extract($partialData, EXTR_OVERWRITE);
            ob_start();
            require $file;

            return (string) ob_get_clean();
        })();
    }
}

if (!function_exists('e')) {
    /** Escape dữ liệu đầu ra chống XSS. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** Tạo URL nội bộ kèm base path. */
    function url(string $path = '/'): string
    {
        $base = rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/');
        $path = '/' . ltrim($path, '/');

        return $base . ($path === '/' ? '/' : rtrim($path, '/'));
    }
}

if (!function_exists('asset')) {
    /** URL của tài nguyên tĩnh trong thư mục assets. */
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('asset_versioned')) {
    /**
     * URL tài nguyên kèm phiên bản theo mtime của file.
     *
     * Trình duyệt cache mạnh (JS/CSS) sẽ không dùng lại bản cũ khi file thay
     * đổi — nhờ vậy không phải xoá cache thủ công sau mỗi lần sửa.
     */
    function asset_versioned(string $path): string
    {
        $relative = ltrim($path, '/');
        $file = dirname(__DIR__, 2) . '/assets/' . $relative;

        $version = is_file($file) ? (string) filemtime($file) : '';

        return asset($path) . ($version === '' ? '' : '?v=' . $version);
    }
}

if (!function_exists('absolute_url')) {
    /**
     * URL tuyệt đối (có scheme + host) — dùng cho link gửi qua email.
     *
     * Lấy host từ APP_URL chứ không từ header Host của request, tránh host header
     * injection (kẻ xấn có thể đổi Host để link đặt lại mật khẩu trỏ về domain
     * của họ). Phần path lấy từ url() nên vẫn đúng khi app nằm trong thư mục con.
     */
    function absolute_url(string $path = '/'): string
    {
        $appUrl = rtrim((string) config('app.url', ''), '/');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME);
        $host = parse_url($appUrl, PHP_URL_HOST);

        if (!is_string($scheme) || !is_string($host) || $host === '') {
            $requestHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
            if ($requestHost === '') {
                return url($path);
            }
            $secure = ($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off';
            $scheme = $secure ? 'https' : 'http';
            $host = $requestHost;
        }

        return $scheme . '://' . $host . url($path);
    }
}

if (!function_exists('upload_url')) {
    /** URL hiển thị file đã tải lên. */
    function upload_url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url('/uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('old')) {
    /**
     * Giá trị form cũ sau khi validate thất bại.
     *
     * Trả về giá trị thô (chưa escape) nếu có, ngược lại trả về giá trị mặc định
     * do view truyền vào. View phải tự escape bằng e().
     */
    function old(string $key, mixed $default = ''): mixed
    {
        $value = \App\Core\OldInput::get($key, $default);

        return $value === null ? $default : $value;
    }
}

if (!function_exists('old_fields')) {
    /**
     * Danh sách field trong form đã được server render từ giá trị cũ.
     *
     * Trình duyệt dùng danh sách này để biết field nào đã có giá trị chắc chắn
     * từ server nên không được đè bằng nháp cũ trong localStorage.
     *
     * @param array<int,string> $names
     */
    function old_fields(array $names): string
    {
        $matched = array_values(array_intersect($names, \App\Core\OldInput::keys()));

        return implode(',', $matched);
    }
}

if (!function_exists('flash')) {
    /**
     * Lấy và xóa một giá trị flash đơn lẻ.
     *
     * @deprecated Dùng setFlashMessage() + flash_messages() của hệ thống Toast.
     */
    function flash(string $key): ?string
    {
        $value = \App\Core\Session::get('_flash_' . $key);
        \App\Core\Session::forget('_flash_' . $key);

        return is_string($value) ? $value : null;
    }
}

if (!function_exists('setFlashMessage')) {
    /**
     * Lưu một thông báo để hiển thị ở request kế tiếp (sau redirect).
     *
     * @param string $type    success | error | warning | info
     */
    function setFlashMessage(string $type, string $message): void
    {
        \App\Core\Flash::add($type, $message);
    }
}

if (!function_exists('flash_messages')) {
    /**
     * Lấy toàn bộ thông báo flash đã nhặt (mỗi thông báo chỉ hiển thị một lần).
     *
     * @return array<int,array{type:string,message:string}>
     */
    function flash_messages(): array
    {
        return \App\Core\Flash::pull();
    }
}

if (!function_exists('toast_icon')) {
    /** Icon Bootstrap Icons tương ứng với từng loại thông báo. */
    function toast_icon(string $type): string
    {
        return match (strtolower(trim($type))) {
            'success' => 'bi-check-circle-fill',
            'error', 'danger' => 'bi-exclamation-octagon-fill',
            'warning' => 'bi-exclamation-triangle-fill',
            default    => 'bi-info-circle-fill',
        };
    }
}

if (!function_exists('toast_title')) {
    /** Nhãn ngắn gợi ý loại thông báo, dùng cho screen reader. */
    function toast_title(string $type): string
    {
        return match (strtolower(trim($type))) {
            'success' => 'Thành công',
            'error', 'danger' => 'Lỗi',
            'warning' => 'Cảnh báo',
            default    => 'Thông tin',
        };
    }
}

if (!function_exists('auth_user')) {
    /** Người dùng hiện tại (array) hoặc null. */
    function auth_user(): ?array
    {
        static $cached = false;
        static $user = null;

        if (!$cached) {
            $cached = true;
            $id = \App\Core\Session::get('user_id');
            if ($id) {
                $user = \App\Models\User::find((int) $id);
            }
        }

        return $user;
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        $id = \App\Core\Session::get('user_id');

        return $id ? (int) $id : null;
    }
}

if (!function_exists('is_auth')) {
    function is_auth(): bool
    {
        return auth_id() !== null;
    }
}

if (!function_exists('time_ago')) {
    /** Định dạng thời gian tương đối. */
    function time_ago(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '';
        }
        $diff = time() - $timestamp;
        if ($diff < 0) {
            $diff = 0;
        }
        if ($diff < 60) {
            return 'vừa xong';
        }
        if ($diff < 3600) {
            $minutes = (int) floor($diff / 60);

            return $minutes . ' phút trước';
        }
        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);

            return $hours . ' giờ trước';
        }
        if ($diff < 2592000) {
            $days = (int) floor($diff / 86400);

            return $days . ' ngày trước';
        }
        if ($diff < 31536000) {
            $months = (int) floor($diff / 2592000);

            return $months . ' tháng trước';
        }

        return (int) floor($diff / 31536000) . ' năm trước';
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $datetime, string $format = 'd/m/Y H:i'): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '';
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('avatar_url')) {
    /** URL ảnh đại diện, dùng ảnh mặc định khi người dùng chưa tải avatar lên. */
    function avatar_url(?string $path): string
    {
        return upload_url($path) ?? upload_url('avatars/default-avatar.png');
    }
}

if (!function_exists('initials')) {
    function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : '?';
    }
}

if (!function_exists('post_view_data')) {
    /**
     * Chuẩn bị dữ liệu hiển thị cho một bài viết (dùng chung giữa feed, trang chi tiết, tìm kiếm).
     *
     * @return array<string,mixed>
     */
    function post_view_data(array $post, ?int $viewerId): array
    {
        $postId = (int) $post['id'];
        $isShared = !empty($post['shared_post_id']);

        $original = null;
        if ($isShared) {
            $original = \App\Models\Post::find((int) $post['shared_post_id']);
        }

        $images = \App\Models\PostImage::all($postId);
        $originalImages = $original !== null ? \App\Models\PostImage::all((int) $original['id']) : [];

        return [
            'post'           => $post,
            'original'       => $original,
            'images'         => $images,
            'originalImages' => $originalImages,
            'isShared'       => $isShared,
            'likeCount'      => \App\Models\Post::likeCount($postId),
            'commentCount'   => \App\Models\Post::commentCount($postId),
            'shareCount'     => \App\Models\Post::shareCount($postId),
            'liked'          => $viewerId !== null && \App\Models\Post::isLiked($postId, $viewerId),
            'isOwner'        => $viewerId !== null && (int) $post['user_id'] === $viewerId,
            'permalink'      => url('/p/' . $postId),
        ];
    }
}

if (!function_exists('notification_label')) {
    /**
     * Nhãn mô tả hành động theo loại thông báo.
     */
    function notification_label(string $type): string
    {
        return match ($type) {
            'like'           => 'đã thích bài viết của bạn',
            'comment'        => 'đã bình luận bài viết của bạn',
            'reply'          => 'đã trả lời bình luận của bạn',
            'share'          => 'đã chia sẻ bài viết của bạn',
            'friend_request' => 'đã gửi lời mời kết bạn',
            'friend_accepted' => 'đã chấp nhận lời mời kết bạn',
            'message'        => 'đã gửi cho bạn một tin nhắn',
            'follow'         => 'đã bắt đầu theo dõi bạn',
            default          => 'đã tương tác với bạn',
        };
    }
}

if (!function_exists('notification_icon')) {
    /**
     * Biểu tượng Bootstrap Icons theo loại thông báo.
     */
    function notification_icon(string $type): string
    {
        return match ($type) {
            'like'           => 'bi-hand-thumbs-up-fill',
            'comment'        => 'bi-chat-fill',
            'reply'          => 'bi-reply-fill',
            'share'          => 'bi-share-fill',
            'friend_request' => 'bi-person-plus-fill',
            'friend_accepted' => 'bi-person-check-fill',
            'message'        => 'bi-envelope-fill',
            'follow'         => 'bi-person-heart',
            default          => 'bi-bell-fill',
        };
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $text, int $length = 120): string
    {
        $text = trim((string) $text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }
}
