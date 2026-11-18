# PROGRESS — Koobecaf

Tài liệu này ghi lại tiến độ triển khai theo từng giai đoạn, để có thể tiếp tục công việc trong phiên sau mà không cần phân tích lại toàn bộ dự án.

**Trạng thái hiện tại: Phase 1 – Phase 8 đã hoàn tất.** Tất cả bộ kiểm thử đều đạt 0 lỗi.

---

## Tổng quan

| Phase | Nội dung | Trạng thái |
|---|---|---|
| 1 | Analysis & Design (kiến trúc, DB, ERD) | ✅ Hoàn thành |
| 2 | Project Foundation (Composer, .env, PDO, Router, MVC, Layout) | ✅ Hoàn thành |
| 3 | Authentication & Profile (+ upload avatar/cover, quên/đặt lại mật khẩu qua email) | ✅ Hoàn thành |
| 4 | Social Features (Feed, Post, Like, Comment, Share, Friend, Follow) | ✅ Hoàn thành |
| 5 | Communication (Chat + Notifications + AJAX polling) | ✅ Hoàn thành |
| 6 | Search & Notifications (search user/post, unread count) | ✅ Hoàn thành |
| 7 | UI/UX & Responsive + Light/Dark mode | ✅ Hoàn thành |
| 8 | Testing, Optimization, README, PROGRESS | ✅ Hoàn thành |

---

## Phase 1 — Analysis & Design

### Đã triển khai
- Phân tích 14 nhóm yêu cầu, xác định 12 module chức năng.
- Thiết kế kiến trúc MVC thuần: `Core` (Router, Request, Response, Database, Session, Csrf, Validator, View, Config, Env, Logger), `Middleware`, `Models`, `Controllers`, `Services`, `Views`.
- Thiết kế database 12 bảng InnoDB `utf8mb4_unicode_ci`.
- Vẽ ERD bằng Mermaid tại `database/erd/erd.md` (sơ đồ + bảng ràng buộc).
- Xác định luồng nghiệp vụ: feed → tương tác → notification → chat.

### Ràng buộc dữ liệu thiết kế
- UNIQUE `(post_id, user_id)` trên `likes`, `(post_id, user_id)` trên `shares`.
- UNIQUE `(user_id, friend_id)` trên `friendships`, `(follower_id, following_id)` trên `follows`.
- UNIQUE `(conversation_id, user_id)` trên `conversation_members`.
- CHECK tại tầng ứng dụng: cấm tự follow, cấm tự kết bạn.
- FK ON DELETE CASCADE cho dữ liệu phụ thuộc (post_images, likes, comments, shares, messages…).

---

## Phase 2 — Project Foundation

### Đã triển khai
- `composer.json` với PSR-4 autoload (`App\` → `app/`) + `files: app/Helpers/helpers.php`.
- `.env` / `.env.example` với parser tự viết (`app/Core/Env.php`).
- `app/Core/Database.php`: PDO singleton, `ATTR_ERRMODE=EXCEPTION`, `ATTR_DEFAULT_FETCH_MODE=ASSOC`, `ATTR_EMULATE_PREPARES=false`, hỗ trợ transaction.
- `app/Core/Router.php`: khớp regex, hỗ trợ tham số động `{id}` / `{username}`, group middleware.
- `app/Core/Request.php`: đọc `$_GET`, `$_POST`, JSON body (`php://input`), file upload, header (bao gồm `X-CSRF-TOKEN`).
- `app/Core/Response.php`: JSON thống nhất `{success, message, data}` + redirect + HTTP status code đúng.
- `app/Core/Session.php`: lifecycle, flash message, regenerate ID.
- `app/Core/Csrf.php`: sinh token, validate qua form field / JSON body / header.
- `app/Core/Validator.php`: required, email, username regex, min/max, matches.
- `app/Core/View.php`: render view + layout lồng nhau.
- `app/Helpers/helpers.php`: `e()`, `url()`, `asset()`, `upload_url()`, `old()`, `flash()`, `auth_user()`, `auth_id()`, `is_auth()`, `time_ago()`, `format_date()`, `post_view_data()`, `initials()`, `excerpt()`.
- `app/Middleware/`: `AuthMiddleware`, `GuestMiddleware`.
- Layout dùng chung: `layouts/main.php` (3 cột + topbar + mobile nav), `layouts/auth.php`.

---

## Phase 3 — Authentication & Profile

### Đã triển khai
- Đăng ký: validate username (3–30 ký tự, chữ/số/gạch dưới), email, mật khẩu ≥ 8 ký tự, xác nhận mật khẩu, kiểm tra email trùng.
- `password_hash()` / `password_verify()` — không lưu mật khẩu dạng rõ.
- Đăng nhập / đăng xuất, regenerate session ID sau đăng nhập và đăng ký.
- Trang hồ sơ `/u/{username}` với 6 tab (bài viết, bình luận, đã chia sẻ, bạn bè, người theo dõi, đang theo dõi).
- Sửa hồ sơ: tên hiển thị, tiểu sử, avatar, ảnh bìa.
- Đổi mật khẩu: yêu cầu mật khẩu hiện tại.
- Quên mật khẩu (`/forgot-password`) + đặt lại mật khẩu (`/reset-password?token=...`):
    - `app/Services/MailService.php` bọc PHPMailer 7.2; hỗ trợ `MAIL_DRIVER=smtp` (gửi thật) và `MAIL_DRIVER=log` (ghi vào `storage/logs/` khi dev).
    - `config/mail.php` đọc cấu hình SMTP từ `.env`.
    - Bảng `password_resets`: token random 32 byte, lưu bản hash SHA-256, hết hạn 60 phút, đánh dấu đã dùng sau khi đặt lại.
    - `app/Models/PasswordReset.php`: tạo token (vô hiệu hóa token cũ), tìm token hợp lệ, đánh dấu đã dùng.
    - `app/Views/emails/reset-password.php`: template HTML email (style inline, không phụ thuộc CSS ngoài).
    - Phản hồi luôn giống nhau dù email có tồn tại hay không, tránh tiết lộ tài khoản đã đăng ký.
    - Link trong email dựng từ `APP_URL` (`absolute_url()`), không dùng header `Host` để chống host header injection.
- Upload an toàn (`app/Services/UploadService.php`): MIME thực qua `finfo`, allowlist extension, giới hạn 5MB, tên file ngẫu nhiên, thư mục riêng `avatars/`, `covers/`, `posts/`.
- `uploads/.htaccess` chặn thực thi PHP + chặn autoindex.
- Xóa file cũ khi thay avatar/cover mới.

---

## Phase 4 — Social Features

### Đã triển khai
- **Feed**: bài của mình + người được theo dõi + bạn bè; sắp xếp mới nhất; phân trang; khách chưa đăng nhập xem bài công khai.
- **Bài viết**: tạo (chữ / ảnh 1-n / ảnh + link), sửa, xóa (có confirm), xem chi tiết `/post/{id}`, permalink `/p/{id}`.
- **Quyền riêng tư**: `public` / `followers` / `private`, thực thi bằng SQL trong `Post::visibilitySql()` + `Post::canView()`.
- **Like**: toggle AJAX, cập nhật số lượt + trạng thái nút; trang `/posts/{id}/likes`.
- **Bình luận**: thêm/sửa/xóa AJAX, reply lồng nhau, chỉ tác giả được sửa/xóa.
- **Chia sẻ nội bộ**: tạo bài viết mới với `shared_post_id` + `share_content`; hiển thị thẻ bài gốc.
- **Chia sẻ liên kết**: nút "Sao chép link" copy permalink.
- **Bạn bè**: gửi / chấp nhận / từ chối / hủy lời mời / hủy kết bạn; cấm tự kết bạn, cấm trùng lặp.
- **Follow**: toggle AJAX; cấm tự follow.
- Tự động lấy URL đầu tiên trong nội dung bài viết và lưu vào `posts.shared_url`.

---

## Phase 5 — Communication

### Đã triển khai
- Chat 1-1: tạo/lấy conversation, danh sách hội thoại, lịch sử tin nhắn.
- Bắt đầu cuộc trò chuyện mới: ô tìm kiếm người dùng trên trang `/chat`, gợi ý AJAX (`/api/search/users`) kèm link `chat_url` tới `/chat/start/{id}`.
- Gửi tin nhắn qua AJAX, không tải lại trang.
- Polling `/chat/{id}/messages?after_id=` mỗi 5 giây, tự cuộn xuống tin nhắn mới nhất.
- Trạng thái đã đọc: `messages.read_at`, icon ✓ (đã gửi) / ✓✓ (đã xem), `Message::markRead()` khi mở hội thoại và khi polling.
- Badge số tin nhắn chưa đọc cập nhật định kỳ (15s) qua `/api/messages/unread`.
- Kiểm tra thành viên hội thoại ở backend trước mọi thao tác đọc/gửi.
- 8 loại thông báo: like, comment, reply, share, friend_request, friend_accepted, message, follow.
- Đánh dấu đã đọc / đánh dấu tất cả đã đọc bằng AJAX, badge `/api/notifications/unread`.

---

## Phase 6 — Search & Notifications

### Đã triển khai
- Tìm người dùng theo tên / username: gợi ý tức thì AJAX (`/api/search/users`), có debounce.
- Tìm bài viết công khai theo từ khóa: `/search`, có phân trang, chỉ trả về bài `privacy = 'public'`.
- Escape `%` và `_` trong từ khóa LIKE để tránh khớp sai.
- Click thông báo điều hướng tới nội dung liên quan (`NotificationController::targetUrl()`).

---

## Phase 7 — UI/UX & Responsive

### Đã triển khai
- Design system tự viết trong `assets/css/app.css`: nền kem ấm `#faf9f5`, màu chủ đạo mint `#14806c`, bo góc 6–16px theo cấp, shadow nhẹ; dark mode bằng `data-theme="dark"`.
- Desktop 3 cột: sidebar trái (menu) · khu vực chính · sidebar phải (gợi ý, người đang theo dõi).
- Tablet: sidebar ẩn, dùng offcanvas.
- Mobile: bottom navigation cố định 5 mục, offcanvas menu, bài viết full-width, nút hành động chỉ còn icon, chat bubble max-width 85%.
- Trang chat dùng layout rộng (`wide`) — chiếm `col-lg-10` thay vì cột hẹp.
- Light/Dark mode: lưu vào `users.theme` + session; chuyển tức thì bằng AJAX không reload; nút trạng thái active đúng theo lựa chọn hiện tại.
- Icon trạng thái đọc trong chat bubble (✓ / ✓✓).
- Tên class trong CSS bám Bootstrap (`text-primary`, `btn-primary`, `btn-outline-primary` dùng biến `--kb-primary`) để dễ chuyển đổi.
- Thẻ bài viết dùng grid ảnh: 1 ảnh full, 2 ảnh cạnh nhau, 3 ảnh lưới 2-1, 4 ảnh 2x2.
- Hover/transition nhẹ, không lạm dụng hiệu ứng.

### Đợt nâng cấp giao diện (UI polish)

- **Hệ thống chuyển động**: token `--kb-dur-fast/base/slow` + `--kb-ease`; nút có phản hồi `translateY(1px)` khi nhấn, menu dropdown fade-in, icon like "pop", thanh cuộn tối giản, `focus-visible` thống nhất, hỗ trợ `prefers-reduced-motion`.
- **Thanh trên cùng**: nền `backdrop-filter` blur, đổ bóng khi cuộn (`.is-scrolled`), thêm 2 nút tròn Thông báo / Tin nhắn kèm badge số (ẩn ở ≤992px vì đã có trong bottom nav), ô tìm kiếm đổi màu icon khi focus.
- **Thẻ bài viết**: nội dung dài được kẹp 5 dòng kèm nút "Xem thêm / Thu gọn" (JS đo `scrollHeight`), avatar có vòng nhẹ, ảnh bo góc lớn hơn, nhãn quyền riêng tư hiển thị ở chân thẻ.
- **Trạng thái rỗng thống nhất** (`.empty-state`): icon tròn, tiêu đề, mô tả, nút hành động — áp dụng cho feed, thông báo, bạn bè, lời mời, tìm kiếm, chat, hồ sơ, trang 404/403.
- **Hồ sơ**: lớp chuyển sắc ở đáy ảnh bìa, avatar viền 4px, số liệu dạng chip nền mềm, tab chuyển sang gạch chân dưới.
- **Thông báo**: biểu tượng riêng cho 8 loại tương tác, chấm "chưa đọc", hàng hover đổi nền.
- **Chat**: bubble có đổ bóng nhẹ, ô nhập dính đáy có nền mờ, nút trở lại hồ sơ trong header.
- **Trang đăng nhập / đăng ký**: bố cục 2 cột — panel tối giới thiệu bên trái (desktop ≥992px), biểu mẫu bên phải; mobile hiện logo thu gọn.
- **Chi tiết nhỏ**: nút chọn tệp tự dựng, phân trang dùng class `.pagination`, chip `.chip`, nút tài khoản dạng pill.

### Đợt sửa trải nghiệm khách (chưa đăng nhập)

- **Trang chủ**: bỏ 2 cột phụ nên cột chính bị kéo giãn hết 1400px — thêm class `is-guest` trên `.app-grid` để giới hạn 680px và canh giữa (bề rộng đọc chuẩn, khớp với hồ sơ/bài viết).
- **Phân trang feed khách**: `Post::findPublic(1, $page * $perPage)` chỉ lấy 1 bài/trang và sai offset → sửa thành `Post::findPublic($perPage, ($page - 1) * $perPage)`.
- **Banner chào mừng**: thay khối chữ đơn giản bằng hero card — icon, tiêu đề, mô tả, 2 nút CTA và danh sách tính năng.
- **Nút hành động trên thẻ bài viết**: "Thích"/"Chia sẻ" của khách là link tới `/login` (trước đó gọi AJAX và báo lỗi 401); "Bình luận" dẫn tới `#comments` của trang bài viết.
- **Topbar**: ẩn nút hamburger (chỉ mở được ngăn kéo dành cho người đã đăng nhập), thêm mục điều hướng "Bảng tin / Khám phá" cho khách.
- **Điều hướng mobile**: thêm `mobile-nav-guest.php` — 4 mục công khai (Trang chủ, Khám phá, Đăng nhập, Đăng ký) thay cho bottom nav 5 mục cần đăng nhập.
- **API tìm kiếm**: `/api/search/users` và `/api/search/posts` chuyển ra khỏi nhóm `AuthMiddleware` (tìm kiếm là tính năng công khai) → gợi ý trong ô tìm kiếm topbar hoạt động với khách.
- **Hồ sơ người khác**: nút "Kết bạn / Theo dõi / Nhắn tin" của khách là link tới `/login` thay vì POST bị chặn kèm thông báo lỗi.
- **Trang bài viết**: khách thấy khung mời "Đăng nhập để tham gia thảo luận" thay cho ô viết bình luận; thêm anchor `#comments`.
- **Chân trang**: đổi các link cần đăng nhập (Cài đặt, Bạn bè, Thông báo, Trò chuyện) sang Đăng nhập/Đăng ký cho khách.

---

## Phase 8 — Testing, Optimization, Documentation

### Bộ kiểm thử

| File | Phạm vi | Kết quả |
|---|---|---|
| `tests/smoke.php` | 54 kiểm tra end-to-end: đăng ký, đăng nhập, đăng xuất, post CRUD, like/unlike, comment/reply, share nội bộ, copy link, friend request/accept/decline/cancel, follow/unfollow, chat send/read, **bắt đầu chat mới (tìm kiếm người dùng + `/chat/start` bằng GET)**, notifications, search, theme, profile | **PASS: 54 / FAIL: 0** |
| `tests/extended.php` | 40 kiểm tra biên: upload ảnh thật, MIME/extension sai bị từ chối, bài private/followers/public, sửa-xóa bài của người khác bị chặn, sửa bình luận người khác bị chặn, đổi mật khẩu, đổi theme, truy cập hội thoại không thuộc về mình, CSRF thiếu token bị từ chối, **regression**: reply lồng nhau, link thông báo reply, số bài viết thống kê khớp tab, các trang chính không lỗi PHP | **PASS: 40 / FAIL: 0** |
| `tests/password_reset.php` | 21 kiểm tra luồng quên mật khẩu: form gửi email, link quên mật khẩu trên trang login, email chứa token, mở form đặt lại mật khẩu, xác nhận mật khẩu không khớp bị từ chối, đặt lại thành công, đăng nhập được bằng mật khẩu mới, token đã dùng không tái sử dụng, email không tồn tại không bị tiết lộ, email sai bị từ chối, token giả bị từ chối | **PASS: 21 / FAIL: 0** |
| `tests/pages.php` | Render toàn bộ route (khách + đã đăng nhập) và toàn bộ endpoint API; bắt lỗi/warning PHP | **FAIL: 0** |

Tổng: **115 kiểm tra tự động — 0 lỗi.**

### Tối ưu & bảo trì
- Prepared statement cho toàn bộ truy vấn có dữ liệu người dùng.
- Index cho các cột truy vấn thường xuyên: `idx_posts_user_created(user_id, created_at)`, `idx_posts_created_at`, `idx_posts_privacy`, `idx_likes_user`, `idx_comments_post_created(post_id, created_at)`, `idx_messages_conversation(conversation_id, created_at)`, `idx_notifications_user_read(user_id, is_read, created_at)`.
- `Database::transaction()` cho thao tác nhiều bước (tạo bài viết + ảnh).
- `Post::visibilitySql()` gom điều kiện quyền xem vào một nơi, tránh lặp logic.
- Tái sử dụng partial `post-card.php` / `comment-item.php` cho feed, trang cá nhân, trang chi tiết.
- Partial được render qua `view_partial()` (phạm vi biến riêng) để tránh xung đột tên biến khi lồng nhau.
- Xóa file cũ khi thay avatar/cover; cascade xóa ảnh bài khi xóa bài.

### Tài liệu
- `README.md` (tiếng Việt): giới thiệu, mục tiêu, công nghệ, danh sách chức năng, cấu trúc thư mục, yêu cầu hệ thống, hướng dẫn XAMPP, tạo & import database, cấu hình `.env`, Composer, chạy dự án, tài khoản mẫu, kiến trúc request, bảng bảo mật, hướng phát triển tương lai.
- `PROGRESS.md` (file này).
- `database/erd/erd.md`: ERD Mermaid + bảng ràng buộc.

### Script cài đặt database
- `database/install.php`: script cài đặt gốc — tạo database `utf8mb4_unicode_ci`, nạp `schema.sql` + `seed.sql` qua PDO (byte-safe, không qua console).
  - `php database/install.php` — tạo (nếu chưa có) + nạp schema & dữ liệu mẫu.
  - `php database/install.php --fresh` — xóa database cũ rồi tạo lại từ đầu.
  - `php database/install.php --schema` — chỉ nạp schema, không có dữ liệu mẫu.
- `schema.sql` và `seed.sql` có `SET NAMES utf8mb4;` ở đầu để import bằng `mysql --default-character-set=utf8mb4` không bị vỡ tiếng Việt.

---

## Lỗi đã phát hiện & khắc phục

| # | Lỗi | Nguyên nhân | Cách khắc phục |
|---|---|---|---|
| 1 | Import SQL bằng PowerShell thất bại | PowerShell không hỗ trợ `mysql < file.sql` (stream redirection) | Dùng `cmd /c "mysql ... < file.sql"` hoặc `Get-Content -Raw \| mysql` |
| 2 | Controller nhận sai tham số | `Router::callHandler` truyền `($request, $params)` trong khi method nhận `($params)` | Sửa `Router.php` chỉ truyền `$params` |
| 3 | AJAX JSON bị báo "CSRF token không hợp lệ" | `Csrf::validate` chỉ kiểm tra `$_POST['_token']` | Thêm `Request::header()` + đọc `HTTP_X_CSRF_TOKEN` |
| 4 | JSON body request bị coi là rỗng | `Request::input()` chỉ đọc `$_POST`/`$_GET` | Decode và merge `php://input` trong `Request::__construct` |
| 5 | SQL error `Unknown column u.user_id` | Bảng `users` dùng cột `id` | Sửa thành `u.id AS author_id` trong `Post::find` |
| 6 | TypeError khi gọi `Friendship::requests()` | Type hint `$status` là `int` thay vì `string` | Sửa thành `string $status = self::PENDING` |
| 7 | Trang chat chiếm cột hẹp giữa layout | Layout chỉ có 1 biến thể cột | Thêm cờ `wide` cho layout → `col-lg-10`, bỏ sidebar phải |
| 8 | Feed hiển thị sai ảnh đại diện trong tab "Bình luận" | Query thiếu join `users` | Bổ sung `JOIN users u ON u.id = c.user_id` + hiển thị tên/username |
| 9 | Tab "Đã chia sẻ" chỉ hiện mã bài viết | Query thiếu thông tin bài gốc & tác giả | Bổ sung join `posts` + `users`, hiển thị nội dung & tác giả gốc |
| 10 | `shared_url` không được lưu | Cột tồn tại nhưng không có code ghi | Thêm `Post::extractUrl()` và ghi khi tạo/sửa bài viết |
| 11 | Reply bình luận không hiển thị trên trang bài viết | `comment-item.php` được `require` trực tiếp nên dùng chung biến với view cha; vòng lặp gán `$reply` nhưng partial lại đọc `$comment` → đệ quy vô hạn, hết bộ nhớ | Thêm helper `view_partial()` render trong phạm vi biến riêng; đổi toàn bộ nơi `require` partial sang `view_partial()` |
| 12 | Trang bài viết chỉ hiện `/p/{id}` thay vì nội dung | Vòng lặp meta Open Graph trong `layouts/main.php` dùng biến `$content`, ghi đè nội dung đã render của view | Đổi tên biến lặp thành `$metaContent` |
| 13 | Reply bình luận biến mất khỏi API | `Comment::forPost()` gán `$grouped[$parentId]['replies'][]` — copy-on-write của PHP nên sửa bản sao local, không sửa `$grouped[$parentId]` | Dựng cây reply bằng hàm đệ quy trên mảng `children` |
| 14 | Thông báo "trả lời" trỏ tới `/post/{commentId}` | `NotificationService::replied()` lưu `parentId` vào `entity_id`, còn `targetUrl()` lại coi `entity_id` là post id | Lưu `post_id` vào cột `data`, `targetUrl()` đọc từ `data` |
| 15 | Số bài viết ở hồ sơ lệch với tab "Bài viết" | `User::stats()` và `PostController` lọc `shared_post_id IS NULL` còn tab "Bài viết" thì không lọc | Tách `Post::countByUser()` và thống nhất dùng chung cho cả thống kê lẫn tab |
| 16 | Test "bài viết công khai của user khác vẫn thấy" fail khi dữ liệu tích tụ | Assertion phụ thuộc vào nội dung seed cố định (`Chào mừng đến với Koobecaf`) bị đẩy khỏi trang 1 của feed | Test tự tạo một bài public riêng rồi kiểm tra bài đó xuất hiện |
| 17 | Nút "Nhắn tin" ở hồ sơ / bạn bè / tìm kiếm trả về 404 | Link dùng phương thức `GET` nhưng route `/chat/start/{id}` chỉ đăng ký `POST` | Đăng ký thêm route `GET /chat/start/{id}` (giữ `POST` cho AJAX) |
| 18 | Trang `/chat` không có cách bắt đầu cuộc trò chuyện mới | Thiếu ô tìm kiếm người dùng trong trang chat, chỉ có danh sách hội thoại | Thêm panel "Bắt đầu cuộc trò chuyện mới" + gợi ý AJAX; API `/api/search/users` trả thêm `chat_url` |
| 19 | Dữ liệu tiếng Việt trong DB bị vỡ ký tự (`Ch├áo mß╗½ng` thay vì `Chào mừng`) | Import bằng `Get-Content -Raw \| mysql` trên PowerShell: file UTF-8 bị giải mã lại theo bảng mã console (CP437/CP1252) trước khi gửi tới MySQL | Thêm `SET NAMES utf8mb4;` vào `schema.sql`/`seed.sql`; tạo `database/install.php` gửi SQL qua PDO (byte-safe); không dùng pipe của PowerShell khi import |
| 20 | `database/install.php` cắt dòng sai làm mất ký tự `ễ` | Dùng `preg_split('/\R/')` — `\R` khớp cả byte `0x85` (NEL) trong khi `ễ` = `E1 BB 85` nên bị cắt đôi | Tách dòng bằng `explode("\n", str_replace(["\r\n","\r"], "\n", $sql))` |
| 21 | Feed của khách chỉ hiện 1 bài viết mỗi trang | `FeedController::index()` gọi `Post::findPublic(1, $page * $perPage)` — limit cứng 1 và offset nhân sai | Sửa thành `Post::findPublic($perPage, ($page - 1) * $perPage)` |
| 22 | Trang chủ của khách bị kéo giãn 1400px, khó đọc | Không render 2 cột phụ nên `.app-grid` rơi về `minmax(0, 1fr)` trong shell 1400px | Thêm class `is-guest` → `grid-template-columns: minmax(0, 680px); justify-content: center` |
| 23 | Nút "Thích/Chia sẻ" của khách báo lỗi 401 | Render thành `<button data-action="like">` dù API cần `AuthMiddleware` | Render thành `<a href="/login">` khi chưa đăng nhập |
| 24 | Nút hamburger trên mobile của khách không mở được gì | `#mobileSidebar` chỉ render trong `mobile-nav.php` (chỉ dành cho người đăng nhập) | Ẩn nút hamburger với khách; thêm `mobile-nav-guest.php` với các mục công khai |
| 25 | Gợi ý trong ô tìm kiếm không hoạt động với khách | `/api/search/users` nằm trong nhóm route `AuthMiddleware` | Chuyển 2 API tìm kiếm ra khỏi nhóm `AuthMiddleware` (tìm kiếm là tính năng công khai) |

---

## Việc cần tiếp tục (không bắt buộc, ngoài phạm vi MVP)

| Hạng mục | Mô tả |
|---|---|
| WebSocket chat | Nâng cấp từ AJAX polling (5s) sang realtime; giữ polling làm fallback |
| Like bình luận | Thêm bảng `comment_likes` + UI toggle |
| Hashtag / mention | Bảng `hashtags`, `mentions`; trang `/tag/{hashtag}` |
| Chat nhóm | Cho phép conversation nhiều thành viên |
| Báo cáo nội dung | Bảng `reports` + quy trình xử lý |
| FULLTEXT search | Thay `LIKE` bằng MySQL FULLTEXT index khi dữ liệu lớn |
| Cache | Redis cho session, unread count, feed |
| PHPUnit | Viết lại bộ test dưới dạng unit test chuẩn PHPUnit + CI |
| Cron dọn file mồ côi | Xóa file trong `uploads/` không còn tham chiếu trong `post_images` |
| Rate limit API | Chống spam like/comment/post bằng token bucket |

---

## Quy trình chạy lại kiểm thử

```bash
# Terminal 1 — khởi động server
php -S localhost:8000 -t . router.php

# Terminal 2
php tests/smoke.php           http://localhost:8000
php tests/extended.php        http://localhost:8000
php tests/password_reset.php  http://localhost:8000
php tests/form_persistence.php http://localhost:8000
php tests/draft_cleared.php   http://localhost:8000
node tests/draft_engine.js
php tests/pages.php           http://localhost:8000
```

Kết quả mong đợi: `PASS: 54 | FAIL: 0`, `PASS: 40 | FAIL: 0`, `PASS: 21 | FAIL: 0`, `PASS: 16 | FAIL: 0`, `PASS: 9 | FAIL: 0`, `PASS: 14 | FAIL: 0`, `FAIL: 0`.

> `tests/password_reset.php` cần `MAIL_DRIVER=log` trong `.env` vì đọc token từ `storage/logs/`.

## Quy trình cài đặt nhanh (từ đầu)

```bash
composer install
copy .env.example .env          # Windows: copy .env.example .env
php database/install.php         # tạo database + schema + dữ liệu mẫu
php -S localhost:8000 -t . router.php
```

Đăng nhập: `an` / `Password@123` (hoặc `binh`, `chi`, `dung`, `em` — cùng mật khẩu).

---

## Đợt dọn dẹp project

| Hạng mục | Xử lý |
|---|---|
| File tạm | Xoá `tmp/` (6 script debug), `temp/` (rỗng), `storage/logs/*.log` cũ |
| Upload mồ côi | Quét `post_images.path` + `users.avatar_path` + `users.cover_path` → xoá 36 file không còn tham chiếu (giữ `.htaccess` và `avatars/default-avatar.png`) |
| File trùng lặp | Xoá `logo.png` + `favicon.png` ở thư mục gốc (712KB mỗi file, không code nào tham chiếu — thực tế dùng `assets/images/logo.png`) và `assets/images/default.svg`, `assets/images/favicon.svg` (không dùng) |
| Dependency | `package-lock.json` khai báo `bootstrap ^6.0.0-alpha.1` lệch với `package.json` → sinh lại lock chỉ còn `bootstrap-icons@1.13.2`; xoá `playwright` + `playwright-core` (18MB, không nằm trong manifest); cài lại `node_modules` sạch |
| `.gitignore` | Bỏ `/tests/` khỏi danh sách ignore (file test đã được track nhưng vẫn bị ignore — dễ bị mất khi clone); dọn entry trùng lặp |
| `.htaccess` | Dọn rule chặn truy cập cho các thư mục đã xoá |
| `README.md` | Sửa mô tả công nghệ cho khớp thực tế: CSS tự viết (không dùng Bootstrap CSS), Bootstrap Icons 1.13.2, bổ sung `tests/check_classes.php`, `package.json` vào cây thư mục |

## Đợt loại bỏ tài liệu và công cụ của trợ lý AI

| Hạng mục | Xử lý |
|---|---|
| Thư mục công cụ | Xoá `.cursor/` (11.6MB, 451 file — rules + skills), `.agents/` (11 file bootstrap skills), `.claude/` |
| Tài liệu | Xoá `DESIGN.md` (590 dòng), `PROMT.md` (873 dòng), `skills-lock.json` |
| Mã nguồn | `assets/css/app.css`: bỏ 3 tên font thương hiệu có bản quyền khỏi font-stack dùng chung (vẫn giữ Cormorant Garamond + Inter làm font chính) và bỏ dòng "Nguồn thiết kế: DESIGN.md" |
| Tham chiếu | `layouts/partials/footer.php`: đổi "Thiết kế theo DESIGN.md" thành tagline của sản phẩm và bỏ comment trùng tên file; `.htaccess`: bỏ `skills-lock.json`, `DESIGN.md`, `PROMT.md` khỏi rule chặn tải về |
| `.gitignore` | Bỏ hẳn khối "AI tools" (`.claude/`, `.cursor/`, `.agents/`, `.aider-desk/`, `PROMT.md`) — các thư mục này không còn tồn tại trong project |

## Đợt giữ giá trị form (không mất dữ liệu khi nhập / tải trang / gửi lỗi)

| Hạng mục | Xử lý |
|---|---|
| Lỗi sẵn có | `AuthController::fail()` lưu key đã gắn tiền tố `_old.` vào `$_SESSION['_old']` trong khi `old()` đọc `$_SESSION['_old.' . $key]` → giá trị đã nhập ở form đăng nhập/đăng ký không bao giờ được khôi phục |
| Tầng server | Thêm `app/Core/OldInput.php`: lưu giá trị cũ vào session, "nhặt" sang bộ nhớ tạm ở request kế tiếp rồi xoá khỏi session (chỉ dùng một lần). `Controller::fail()` / `failBack()` thu thập field cần giữ lại; `old()` và `old_fields()` đọc từ đây |
| Áp dụng | `AuthController` (đăng nhập/đăng ký), `PasswordController` (quên mật khẩu), `PostController` (soạn & sửa bài viết), `ProfileController` (hồ sơ, đổi mật khẩu) — validate thất bại không còn làm mất dữ liệu đã nhập |
| Tầng trình duyệt | Thêm engine lưu nháp trong `assets/js/app.js`: form có `data-draft="<key>"` tự lưu `localStorage` (debounce 200ms), khôi phục khi tải trang và khi quay lại từ bfcache. Không lưu field password / file / hidden / CSRF token. `localStorage` bị chặn hoặc hỏng không gây lỗi |
| Ưu tiên | Field do server render từ `old()` thắng nháp cũ (đánh dấu qua `data-old-fields`); các field còn lại lấy giá trị nháp vì đó là trạng thái mới nhất |
| Xoá nháp | Form gửi thành công thì server gửi `data-cleared-drafts` xuống `<body>` để trình duyệt xoá nháp, tránh tự điền lại nội dung đã đăng |
| Kiểm thử | Thêm `tests/form_persistence.php` (16), `tests/draft_cleared.php` (9), `tests/draft_engine.js` (14 — chạy bằng Node, không cần server). Tổng 154 kiểm tra PHP + 14 kiểm tra JS, đều 0 lỗi |
