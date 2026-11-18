# Koobecaf — Nền tảng mạng xã hội (PHP thuần + MySQL)

>Tôi thấy có trend tạo một mạng xã hội để thay thế các mạng xã hội lớn. Dù nó là bất khả thi nhưng tôi vẫn muốn tự tạo ra trang mạng xã hội theo cá nhân hóa của mình.

Koobecaf là một nền tảng mạng xã hội hoàn chỉnh ở mức cơ bản, được xây dựng bằng **PHP 8.2 thuần** (không dùng Laravel), **MySQL/MariaDB (InnoDB)** và **CSS tự viết theo design system**. Toàn bộ Backend, Frontend và Database đều là mã nguồn thật, có thể triển khai và chạy trực tiếp trên XAMPP.

---

## 1. Giới thiệu

### 1.1. Mục tiêu dự án

- Xây dựng ứng dụng mạng xã hội **hoạt động thực tế**, mọi chức năng đều giao tiếp với MySQL.
- Kiến trúc **MVC rõ ràng**, dễ đọc, dễ bảo trì, dễ mở rộng.
- Giao diện **hiện đại, tối giản, responsive** trên Desktop / Tablet / Mobile.
- Ưu tiên **bảo mật, tính toàn vẹn dữ liệu** hơn tốc độ phát triển.
- Không dùng giao diện giả, không có nút bấm không chức năng, không hardcode dữ liệu mẫu thay cho dữ liệu thật.

### 1.2. Công nghệ sử dụng

| Lớp | Công nghệ |
|---|---|
| Backend | PHP 8.2+, MVC thuần, PDO (prepared statements), PHP Session, Composer (PSR-4 autoload) |
| Frontend | HTML5, CSS3 (design system tự viết trong `assets/css/app.css`), JavaScript ES6+ (Fetch API), Bootstrap Icons 1.13.2 |
| Database | MySQL 8.0+ / MariaDB 10.4+, InnoDB, `utf8mb4_unicode_ci` |
| Môi trường | XAMPP (Apache + PHP + MySQL/MariaDB) |
| Công cụ | Composer, `php -S` (dev server), bộ test tích hợp tự viết |
| Realtime | Node.js 18+, `ws` (WebSocket server), `mysql2` (connection pool) |

> Bootstrap Icons được **vendor sẵn** trong `assets/vendor/` nên không cần tải qua CDN — ứng dụng chạy được cả khi môi trường không có Internet. Phần CSS không dùng Bootstrap: toàn bộ token, layout và component được viết tay trong `assets/css/app.css` (nền kem `#faf9f5`, nhấn mint `#14806c`, Cormorant Garamond cho tiêu đề, Inter cho nội dung).

---

## 2. Danh sách chức năng

### 2.1. Xác thực (Authentication)
- Đăng ký, đăng nhập, đăng xuất.
- Kiểm tra email trùng lặp, validate dữ liệu đầu vào (tên, username, email, mật khẩu tối thiểu 8 ký tự).
- Mã hóa mật khẩu bằng `password_hash()` (bcrypt), xác thực bằng `password_verify()`.
- Quản lý phiên bằng PHP Session, **regenerate Session ID** sau khi đăng nhập/đăng ký.
- Cookie phiên an toàn (`HttpOnly`, `SameSite=Lax`, `Secure` khi bật `SESSION_SECURE`).
- Đổi mật khẩu (yêu cầu xác nhận mật khẩu hiện tại).
- **Quên mật khẩu**: gửi email qua PHPMailer chứa link đặt lại mật khẩu (token random 32 byte, lưu dạng SHA-256, hết hạn sau 60 phút, dùng một lần).
- **Đặt lại mật khẩu**: token hợp lệ mới cho đổi mật khẩu; phản hồi luôn giống nhau dù email có tồn tại hay không để không tiết lộ tài khoản đã đăng ký.

### 2.2. Hồ sơ cá nhân (Profile)
- Trang cá nhân theo username: `/u/{username}`.
- Hiển thị avatar, ảnh bìa, tên hiển thị, username, tiểu sử, ngày tham gia.
- Thống kê: số bài viết, số bạn bè, số người theo dõi, số đang theo dõi.
- Sửa hồ sơ, đổi avatar, đổi ảnh bìa (upload nhiều ảnh, MIME + extension + dung lượng, tên file ngẫu nhiên).
- 6 tab: Bài viết · Bình luận · Đã chia sẻ · Bạn bè · Người theo dõi · Đang theo dõi.

### 2.3. Bảng tin (News Feed)
- Feed gồm bài của chính mình + người được theo dõi + bạn bè.
- Sắp xếp mới nhất, có phân trang.
- Bố cục 3 cột: sidebar trái (menu), khu vực chính (feed), sidebar phải (gợi ý, người đang theo dõi).
- Khách chưa đăng nhập xem feed bài công khai.
- **Realtime** (WebSocket): bài viết mới từ người theo dõi / bạn bè được chèn lên đầu feed kèm hiệu ứng nổi; số liệu like / bình luận / chia sẻ và bình luận mới cập nhật tức thì trên bài đang xem.

### 2.4. Bài viết (Posts)
- **Trang đăng bài riêng** (`/posts/create`): soạn nội dung, chọn ảnh, chọn quyền riêng tư — tách khỏi bảng tin.
- Tạo bài viết chỉ có chữ, có ảnh (1–n ảnh), có ảnh kèm link.
- Xem trước ảnh trước khi đăng (client-side).
- Sửa / xóa bài viết của mình, có hộp thoại xác nhận trước khi xóa.
- Quyền riêng tư: **Public / Followers / Private**, thực thi ở tầng SQL (không chỉ ẩn/hiện giao diện).
- URL riêng cho mỗi bài viết: `/p/{id}` (permalink), kèm Open Graph metadata + canonical URL.
- Trang chi tiết bài viết: `/post/{id}`.

### 2.5. Like
- Toggle Like/Unlike bằng AJAX, cập nhật số lượt và trạng thái nút không tải lại trang.
- Unique constraint `(post_id, user_id)` chặn Like trùng ở tầng DB.
- Trang danh sách người đã Like: `/posts/{id}/likes`.

### 2.6. Bình luận (Comments)
- Thêm, sửa, xóa bình luận của mình bằng AJAX.
- Trả lời bình luận (reply lồng nhau, hiển thị dạng thread).
- Chỉ tác giả bình luận mới được sửa/xóa (kiểm tra ở backend).
- Số bình luận cập nhật đồng bộ.

### 2.7. Chia sẻ (Shares)
- **Chia sẻ nội bộ**: tạo bài viết mới tham chiếu bài gốc (`shared_post_id`), kèm nội dung người chia sẻ; hiển thị thẻ bài gốc.
- **Chia sẻ liên kết**: nút "Sao chép link" copy permalink `/p/{id}`.
- Hai cơ chế này tách biệt hoàn toàn, không lẫn lộn.

### 2.8. Bạn bè & Theo dõi
- Gửi / chấp nhận / từ chối / hủy lời mời kết bạn, hủy kết bạn.
- Follow / Unfollow bằng AJAX.
- Cấm tự kết bạn / tự follow, cấm quan hệ trùng lặp (unique constraint).

### 2.9. Nhắn tin (Chat)
- Danh sách cuộc trò chuyện 1-1, **bắt đầu cuộc trò chuyện mới** (ô tìm kiếm người dùng ngay trên trang `/chat`, gợi ý AJAX).
- Gửi/nhận tin nhắn, lịch sử tin nhắn, thời gian gửi, trạng thái đã đọc/chưa đọc (icon ✓ / ✓✓).
- AJAX polling mỗi 5 giây, badge số tin nhắn chưa đọc cập nhật tự động.
- Chỉ thành viên cuộc trò chuyện mới đọc được tin nhắn (kiểm tra backend).
- **WebSocket realtime** (tuỳ chọn): nhận tin nhắn tức thì, chỉ báo đang gõ, tự đánh dấu đã đọc, tự kết nối lại khi mất mạng. Tự fallback về polling khi WebSocket server không chạy — xem [mục 5.8](#58-websocket-chat--feed--thông-báo-realtime).

### 2.10. Thông báo (Notifications)
8 loại thông báo: `like`, `comment`, `reply`, `share`, `friend_request`, `friend_accepted`, `message`, `follow`.
- Danh sách, đánh dấu đã đọc, đánh dấu tất cả đã đọc (AJAX), badge số chưa đọc.
- Click vào thông báo sẽ điều hướng tới đúng nội dung liên quan.
- **Realtime**: thông báo mới tới tức thì trên mọi trang — badge cập nhật ngay, dòng thông báo được chèn lên đầu danh sách khi đang ở trang `/notifications`, kèm toast ở góc phải dưới.

### 2.11. Tìm kiếm (Search)
- Tìm người dùng theo tên / username — gợi ý tức thì bằng AJAX.
- Tìm bài viết công khai theo từ khóa — có phân trang.
- Không hiển thị nội dung ngoài quyền truy cập của người xem.

### 2.12. Giao diện
- Light Mode / Dark Mode, lưu theo tài khoản (`users.theme`) và trong session.
- Responsive: sidebar → offcanvas + bottom navigation trên mobile.

### 2.13. Giữ giá trị form (Form persistence)
Ba tầng bảo vệ dữ liệu người dùng đã nhập, không mất khi tải lại trang hoặc gửi lỗi:

1. **Nháp phía trình duyệt** — form có `data-draft="<key>"` tự lưu vào `localStorage` mỗi khi nhập (debounce 200ms) và khôi phục lại khi tải trang, kể cả khi quay lại từ bộ nhớ đệm của trình duyệt (bfcache).
   - Form mật khẩu, file, hidden và CSRF token không được lưu.
   - `localStorage` bị chặn (chế độ riêng tư) hoặc dữ liệu hỏng không gây lỗi.
2. **Giá trị cũ phía server** — khi validate thất bại, `Controller::fail()` lưu field đã nhập vào session; request kế tiếp render lại bằng `old()` rồi xoá khỏi session (chỉ dùng một lần).
   - Áp dụng cho: đăng nhập, đăng ký, quên mật khẩu, soạn bài viết, sửa bài viết, cập nhật hồ sơ.
3. **Xoá nháp khi thành công** — server gửi `data-cleared-drafts` xuống trình duyệt để xoá nháp cũ, tránh tự động điền lại nội dung đã đăng.

Ưu tiên khi khôi phục: giá trị do server render (`old()`) thắng nháp cũ — field được đánh dấu qua `data-old-fields`.

### 2.14. Cảnh báo rời trang khi còn nội dung chưa gửi
- Form có `data-guard="<nhãn>"` được kiểm tra trước khi người dùng rời trang.
- Bấm link nội bộ → hiện hộp thoại liệt kê chính xác những gì sẽ mất (tên field + xem trước nội dung, cắt ngắn 60 ký tự), kèm hai lựa chọn **Ở lại trang** / **Rời đi**.
- Đóng tab hoặc tải lại trang → trình duyệt hiện hộp thoại xác nhận chuẩn (`beforeunload`).
- Form `data-leave-guard` (ví dụ đăng xuất) cũng được cảnh báo nếu đang có dữ liệu chưa gửi ở form khác.
- Chỉ cảnh báo khi thực sự có dữ liệu: field rỗng, field ẩn, và giá trị mặc định chưa sửa đều không bị tính.
---

## 3. Cấu trúc thư mục

```text
koobecaf/
│
├── app/
│   ├── bootstrap.php              # Khởi tạo ứng dụng: autoload, env, config, session, error handler
│   ├── Core/                      # Lõi framework
│   │   ├── Config.php             # Nạp cấu hình từ config/*.php
│   │   ├── Controller.php         # Base controller (view, redirect, guard)
│   │   ├── Csrf.php               # Sinh & kiểm tra CSRF token
│   │   ├── Database.php           # PDO singleton, prepared statements, transaction
│   │   ├── Env.php                # Bộ đọc file .env
│   │   ├── Logger.php             # Ghi log lỗi vào storage/logs
│   │   ├── OldInput.php           # Giữ giá trị form cũ sau khi validate thất bại
│   │   ├── Request.php            # GET/POST/JSON/files/headers
│   │   ├── Response.php           # JSON response & redirect
│   │   ├── Router.php             # Định tuyến regex, hỗ trợ {id}/{username}
│   │   ├── Session.php            # Quản lý session & flash message
│   │   ├── Validator.php          # Validate dữ liệu đầu vào
│   │   └── View.php               # Render view + layout
│   ├── Middleware/
│   │   ├── AuthMiddleware.php     # Yêu cầu đăng nhập
│   │   ├── GuestMiddleware.php    # Chặn người đã đăng nhập
│   │   └── Middleware.php
│   ├── Models/                    # 13 model tương ứng 13 bảng
│   │   ├── Comment.php  Conversation.php  Follow.php  Friendship.php
│   │   ├── Like.php      Message.php     Notification.php
│   │   ├── PasswordReset.php  Post.php    PostImage.php   Share.php
│   │   └── User.php
│   ├── Controllers/               # 14 controller
│   │   ├── ApiController.php       AuthController.php     ChatController.php
│   │   ├── CommentController.php  FeedController.php     FollowController.php
│   │   ├── FriendController.php   LikeController.php     NotificationController.php
│   │   ├── PasswordController.php PostController.php     ProfileController.php
│   │   ├── SearchController.php   ShareController.php
│   ├── Services/
│   │   ├── MailService.php         # Gửi email qua PHPMailer (SMTP hoặc driver log)
│   │   ├── NotificationService.php # Sinh thông báo theo loại sự kiện
│   │   └── UploadService.php       # Upload ảnh an toàn
│   ├── Helpers/
│   │   └── helpers.php             # e(), url(), absolute_url(), asset(), time_ago()...
│   └── Views/
│       ├── layouts/                # main.php, auth.php + partials (topbar, sidebars, mobile-nav, flash)
│       ├── partials/               # post-card.php, comment-item.php
│       ├── auth/                   # login.php, register.php, forgot-password.php, reset-password.php
│       ├── emails/                 # reset-password.php (template HTML cho email)
│       ├── feed/                   # index.php
│       ├── posts/                  # show.php, edit.php, likes.php
│       ├── profile/                # show.php, edit.php
│       ├── friends/                # index.php, requests.php
│       ├── chat/                   # index.php, show.php
│       ├── notifications/          # index.php
│       ├── search/                 # results.php
│       └── errors/                 # 403.php, 404.php
│
├── config/
│   ├── app.php                     # Tên app, múi giờ, per-page, key mã hóa
│   ├── database.php                # Thông tin kết nối MySQL
│   └── mail.php                    # Cấu hình SMTP (host, port, auth, encryption, from)
│
├── database/
│   ├── install.php                # Script cài đặt gốc: tạo DB + nạp schema & dữ liệu mẫu
│   ├── schema.sql                 # Toàn bộ cấu trúc bảng + FK + index + unique
│   ├── seed.sql                   # Dữ liệu mẫu (5 users, bài viết, bình luận, chat...)
│   └── erd/erd.md                 # Sơ đồ quan hệ thực thể (Mermaid)
│
├── index.php                      # Entry point (chạy tại thư mục gốc)
├── router.php                     # Router script cho `php -S`
├── .htaccess                      # Rewrite về index.php + chặn mã nguồn
├── assets/                        # Tài nguyên tĩnh phục vụ trực tiếp
│   ├── css/app.css                # Design system tự viết (token, layout, component)
│   ├── js/app.js                  # Fetch API: like/comment/share/follow/search/chat polling + lưu nháp form
│   ├── js/realtime.js             # Client WebSocket (chat + feed + thông báo realtime, tự fallback về polling)
│   ├── images/logo.png            # Logo thương hiệu
│   └── vendor/                    # Bootstrap Icons (bản local)
│
├── server/                        # WebSocket server (Node.js) cho chat + feed + thông báo realtime
│   ├── ws-server.js               # Entry point: xác thực token, subscribe, nhận/gửi tin nhắn, endpoint /internal/publish
│   └── lib/
│       ├── env.js                 # Đọc file .env (không cần dotenv)
│       ├── token.js               # Xác thực HMAC token/ticket (thuật toán khớp với PHP)
│       └── database.js            # MySQL connection pool (mysql2) + prepared statements
├── uploads/                       # Ảnh người dùng tải lên (kèm .htaccess chặn thực thi PHP)
│
├── routes/
│   └── web.php                     # Toàn bộ định tuyến (web + API)
│
├── storage/
│   └── logs/                       # Log lỗi
│
├── tests/
│   ├── smoke.php                   # 54 kiểm tra tích hợp end-to-end
│   ├── extended.php                # 40 kiểm tra biên (upload, quyền, riêng tư, mật khẩu, theme, regression)
│   ├── password_reset.php          # 21 kiểm tra luồng quên/đặt lại mật khẩu
│   ├── form_persistence.php        # 16 kiểm tra giữ giá trị form sau khi validate thất bại
│   ├── draft_cleared.php           # 9 kiểm tra cơ chế báo xoá nháp sau khi gửi thành công
│   ├── draft_engine.js             # 14 kiểm tra logic lưu nháp phía trình duyệt (Node)
│   ├── leave_guard.js              # 13 kiểm tra logic cảnh báo rời trang (Node)
│   ├── ws_protocol.js              # 9 kiểm tra token/ticket WebSocket (Node, không cần server)
│   ├── ws_e2e.js                   # 12 kiểm tra end-to-end WebSocket (Node, cần server đang chạy)
│   ├── ws_feed.js                  # 10 kiểm tra end-to-end feed/thông báo WebSocket (Node, cần server đang chạy)
│   ├── ws_send.js                  # Tool: gửi tin nhắn qua WebSocket (Node)
│   ├── ws_typing.js                # Tool: gửi trạng thái đang gõ qua WebSocket (Node)
│   ├── pages.php                   # Render toan bộ route + API, bắt lỗi PHP
│   └── check_classes.php           # Kiểm tra class trong CSS có được định nghĩa
│
├── .env.example                   # Mẫu cấu hình môi trường
├── .gitignore
├── composer.json
├── package.json                   # Bootstrap Icons + WebSocket server (ws, mysql2)
├── README.md
└── PROGRESS.md
```

---

## 4. Yêu cầu hệ thống

| Thành phần | Phiên bản tối thiểu | Ghi chú |
|---|---|---|
| PHP | 8.2 | Cần extension `pdo_mysql`, `mbstring`, `fileinfo`, `gd` (khuyến nghị) |
| MySQL | 8.0 | Hoặc MariaDB 10.4+ |
| Apache | 2.4 | Bật `mod_rewrite`, `AllowOverride All` |
| Composer | 2.x | Chỉ dùng để sinh autoload PSR-4 |
| Node.js | 18+ | **Bắt buộc** cho WebSocket server (chat realtime). Cũng dùng để cập nhật `assets/vendor/bootstrap-icons` |

> WebSocket server là thành phần **tùy chọn**: nếu không chạy nó, ứng dụng vẫn hoạt động bình thường và trang chat tự động dùng AJAX polling 5 giây.

---

## 5. Hướng dẫn cài đặt và chạy

### 5.0. Cài nhanh (môi trường phát triển)

Sau khi đã cài PHP 8.2+, MySQL/MariaDB và Composer, chạy các lệnh sau từ
thư mục muốn lưu dự án:

**Clone bằng Git:**

```bash
git clone https://github.com/nguyen-th-dat/koobecaf.git
```

Hoặc tải bản phát hành mới nhất tại
[GitHub Releases](https://github.com/nguyen-th-dat/koobecaf/releases/latest), giải nén
vào thư mục dự án rồi chạy các lệnh sau:

```bash
cd koobecaf
composer install
copy .env.example .env       # Windows PowerShell
# cp .env.example .env       # Linux/macOS
php database/install.php
composer serve
```

Mở [http://localhost:8000](http://localhost:8000). Trước khi chạy
`database/install.php`, hãy bảo đảm MySQL đang chạy và thông tin `DB_*` trong
`.env` đúng. Lệnh cài database mặc định tạo database `koobecaf`, nạp cấu trúc
bảng và dữ liệu mẫu.

> Nếu dùng XAMPP, có thể bỏ qua `composer serve` và chạy ứng dụng qua Apache
> theo hướng dẫn bên dưới. `npm install` là **không bắt buộc** nếu bạn không
> dùng WebSocket server (Bootstrap Icons đã được vendor sẵn trong `assets/vendor/`).

> Muốn chat realtime? Xem [mục 5.8 — WebSocket](#58-websocket-chat-realtime).

### 5.1. Cài đặt XAMPP

1. Tải XAMPP từ [apachefriends.org](https://www.apachefriends.org/vi/index.html) (bản PHP 8.2+).
2. Cài đặt, mở **XAMPP Control Panel**, bật **Apache** và **MySQL**.
3. Mở `http://localhost/phpmyadmin` để quản lý database.

### 5.2. Đưa mã nguồn vào Document root

Mặc định Document root của XAMPP là `C:\xampp\htdocs`. Có hai cách:

**Cách A — đặt trực tiếp trong `htdocs` (khuyến nghị cho đơn giản):**

```
C:\xampp\htdocs\koobecaf\     ← toàn bộ mã nguồn dự án nằm ở đây
```

Khi đó đường dẫn truy cập là `http://localhost/koobecaf/`.
Bỏ qua bước 5.6 (không cần `APP_BASE_PATH`).

**Cách B — đặt ngoài `htdocs` và trỏ Document root (an toàn hơn):**

1. Copy dự án ra ví dụ `D:\projects\koobecaf`.
2. Trong XAMPP Control Panel → **Apache → Config → httpd-vhosts.conf**, thêm:

```apache
<VirtualHost *:80>
    DocumentRoot "D:/projects/koobecaf"
    ServerName koobecaf.local
    <Directory "D:/projects/koobecaf">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Thêm vào file `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1    koobecaf.local
```

4. Khởi động lại Apache. Truy cập `http://koobecaf.local`.

> Ứng dụng chạy ngay tại thư mục gốc dự án. File `.htaccess` ở thư mục gốc chặn truy cập
> mã nguồn, `.env` và các thư mục hệ thống (`app/`, `config/`, `vendor/`, …) — nhưng
> **vẫn nên đặt dự án ngoài Document root** để không lộ file nhạy cảm.

### 5.3. Cài đặt Composer (autoload)

```bash
cd D:\projects\koobecaf
composer install
```

Composer chỉ dùng để sinh file autoload PSR-4 (`vendor/autoload.php`). Không có dependency ngoài lề thư viện.

> Nếu không dùng Composer, file `app/bootstrap.php` đã có sẵn `spl_autoload_register()` fallback và vẫn chạy bình thường.

`package.json` chỉ cần dùng khi muốn cập nhật lại Bootstrap Icons:

```bash
npm install
```

Sau đó cần chép phiên bản icon mong muốn vào `assets/vendor/bootstrap-icons/`.
Không cần chạy bước này để khởi động ứng dụng.

### 5.4. Tạo database

**Cách A — script cài đặt gốc (khuyến nghị):**

```bash
php database/install.php            # tạo database + schema + dữ liệu mẫu
php database/install.php --fresh    # xóa database cũ rồi tạo lại từ đầu
php database/install.php --schema   # chỉ tạo schema, không nạp dữ liệu mẫu
```

Script đọc cấu hình kết nối từ `.env` (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), gửi nội dung `.sql` thẳng qua PDO nên byte tiếng Việt giữ nguyên — không bị mã hoá lại theo bảng mã console của Windows.

**Cách B — dùng phpMyAdmin:**

1. Mở `http://localhost/phpmyadmin`.
2. Tab **Databases** → tạo database tên `koobecaf`, collation `utf8mb4_unicode_ci`.
3. Chọn database `koobecaf` → tab **Import** → chọn `database/schema.sql` → **Import**.
4. Lặp lại với `database/seed.sql` (nếu muốn có dữ liệu mẫu).

**Cách C — dùng command line:**

```bash
mysql -u root -p -e "CREATE DATABASE koobecaf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p --default-character-set=utf8mb4 koobecaf < database/schema.sql
mysql -u root -p --default-character-set=utf8mb4 koobecaf < database/seed.sql
```

> **Lưu ý về encoding trên Windows:** không dùng `Get-Content file.sql -Raw | mysql ...`. PowerShell đọc file theo bảng mã console (CP437/CP1252) rồi pipe lại, khiến UTF-8 bị giải mã sai và lưu vào DB thành ký tự vỡ (`Ch├áo mß╗½ng` thay vì `Chào mừng`). Nếu buộc phải dùng pipe, hãy thêm `| Out-String` kèm `$OutputEncoding = [System.Text.Encoding]::UTF8`, hoặc dùng `cmd /c "mysql -u root -p koobecaf < database\seed.sql"`.
>
> Cả `schema.sql` và `seed.sql` đều có `SET NAMES utf8mb4;` ở đầu file để client MySQL dùng đúng bảng mã khi import.

### 5.5. Cấu hình `.env`

Copy `.env.example` thành `.env` rồi chỉnh cho phù hợp:

```ini
APP_NAME=Koobecaf
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/koobecaf
APP_BASE_PATH=
APP_TIMEZONE=Asia/Ho_Chi_Minh
APP_PER_PAGE=10

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=koobecaf
DB_USERNAME=root
DB_PASSWORD=

SESSION_NAME=koobecaf_session
SESSION_SECURE=false

MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_EMAIL=no-reply@koobecaf.local
MAIL_FROM_NAME=Koobecaf
MAIL_DEBUG=false
```

| Biến | Ý nghĩa |
|---|---|
| `APP_DEBUG` | `true` = hiện chi tiết lỗi. **Luôn đặt `false` khi triển khai production.** |
| `APP_URL` | URL gốc dùng cho permalink & Open Graph. |
| `APP_BASE_PATH` | Nếu ứng dụng nằm trong thư mục con (Cách A), điền `/koobecaf`. |
| `SESSION_SECURE` | `true` khi chạy qua HTTPS (đặt cookie `Secure`). |
| `MAIL_DRIVER` | `smtp` = gửi mail thật. `log` = ghi nội dung email vào `storage/logs/` (dùng khi dev chưa có SMTP). |
| `MAIL_HOST` / `MAIL_PORT` | Địa chỉ SMTP server và cổng (`587` cho STARTTLS, `465` cho SSL). |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | Tài khoản xác thực SMTP. Với Gmail cần dùng **App Password**, không dùng mật khẩu thường. |
| `MAIL_ENCRYPTION` | `tls` (khuyến nghị), `ssl` hoặc để trống. |
| `MAIL_FROM_EMAIL` | Địa chỉ người gửi — nên dùng địa chỉ đã xác thực với SMTP server, nếu không email sẽ vào spam. |
| `MAIL_DEBUG` | `true` = in log giao tiếp SMTP ra màn hình (chỉ dùng khi debug). |

> **Gmail:** bật [Xác thực 2 bước](https://myaccount.google.com/security), tạo App Password rồi điền vào `MAIL_USERNAME` (email đầy đủ) và `MAIL_PASSWORD` (mã App Password 16 ký tự).

### 5.6. Phân quyền thư mục

Đảm bảo Apache có quyền ghi vào:

- `uploads/` (và các thư mục con `avatars/`, `covers/`, `posts/`)
- `storage/logs/`

Trên Windows, mặc định Apache chạy bằng tài khoản nên đã có quyền. Trên Linux/macOS: `chmod -R 775 storage/logs uploads`.

### 5.7. Chạy dự án

**Cách A — Apache (XAMPP):** mở `http://localhost/koobecaf/` (Cách A) hoặc `http://koobecaf.local` (Cách B).

**Cách B — PHP built-in server:**

```bash
composer serve
# hoặc
php -S localhost:8000 -t . router.php
```

Truy cập `http://localhost:8000`.

### 5.8. WebSocket (chat + feed + thông báo realtime)

Ứng dụng có sẵn một **WebSocket server riêng** (Node.js) để nhận dữ liệu theo
thời gian thực: tin nhắn trong chat, bài viết mới trong feed, số liệu
like/bình luận/chia sẻ và thông báo — thay cho cơ chế AJAX polling 5 giây.

#### Kiến trúc

```
Browser (assets/js/realtime.js) — kết nối ở MỌI trang đã đăng nhập
   │  ① token đăng nhập do layout cấp (data-ws-token, HMAC, hạn 300s)
   │  ② WS connect ws://host:8080/ws
   │  ③ {type:"auth", token}
   │  ④ {type:"subscribe", ticket}   ← chỉ ở trang chat
   ▼
server/ws-server.js (Node.js + ws)
   │  Xác thực HMAC bằng APP_KEY (không cần chia sẻ session PHP)
   │  Ghi tin nhắn vào MySQL bằng prepared statement
   ▼
MySQL (bảng `messages`)

        ▲
        │  POST /internal/publish (body ký HMAC bằng APP_KEY)
app/Core/WsPublisher.php  →  app/Services/RealtimeService.php
        │
        └── sự kiện: post.created / post.updated / post.deleted /
                    post.liked / post.unliked / post.commented /
                    post.uncommented / post.shared /
                    notification.created / social
```

#### Bước 1 — Cài dependency

```bash
npm install
```

#### Bước 2 — Cấu hình `.env`

```ini
APP_KEY=<chuỗi ngẫu nhiên 64 ký tự hex>   # BẮT BUỘC — dùng để ký token

WS_ENABLED=true
WS_HOST=127.0.0.1
WS_PORT=8080
WS_PATH=/ws
WS_TOKEN_TTL=300
```

Sinh `APP_KEY` nhanh:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

> `APP_KEY` **phải giống nhau** giữa PHP và Node — cả hai đọc cùng file `.env`.
> Đổi `APP_KEY` khi triển khai production và giữ kín file `.env`.

#### Bước 3 — Chạy WebSocket server

```bash
npm run ws
# hoặc
node server/ws-server.js
```

Kết quả:

```
[ws] đang lắng nghe tại ws://127.0.0.1:8080/ws
[ws] database: root@127.0.0.1:3306/koobecaf
```

Server cũng có endpoint kiểm tra sức khỏe tại `http://127.0.0.1:8080/health`.

#### Bước 4 — Chạy song song với ứng dụng

```bash
# Terminal 1 — WebSocket server
npm run ws

# Terminal 2 — ứng dụng PHP
composer serve
```

#### Giao thức

Mọi frame là JSON với trường `type`:

| Client → Server | Ý nghĩa |
|---|---|
| `{type:"auth", token}` | Xác thực bằng token lấy từ `/api/ws/session` |
| `{type:"subscribe", ticket}` | Đăng ký theo dõi một cuộc trò chuyện |
| `{type:"message", conversation_id, body}` | Gửi tin nhắn (tối đa 5000 ký tự) |
| `{type:"typing", conversation_id, typing}` | Báo đang gõ / ngừng gõ |
| `{type:"read", conversation_id}` | Đánh dấu đã đọc |
| `{type:"ping"}` | Kiểm tra kết nối |

| Server → Client | Ý nghĩa |
|---|---|
| `{type:"auth:ok", user}` | Xác thực thành công |
| `{type:"subscribe:ok", conversation}` | Đăng ký kênh thành công |
| `{type:"message:new", message}` | Tin nhắn mới trong cuộc trò chuyện |
| `{type:"message:read", ids, reader_id}` | Tin nhắn đã được đọc |
| `{type:"typing", user_id, typing}` | Người kia đang gõ |
| `{type:"notification:new", ...}` | Có thông báo mới (cập nhật badge) |
| `{type:"feed:post", post}` | Bài viết mới — client chèn lên đầu feed |
| `{type:"feed:event", event, post_id, actor, stats, comment}` | Cập nhật like / bình luận / chia sẻ của một bài viết |
| `{type:"feed:social", event, actor, target}` | Theo dõi / kết bạn (follow, unfollow, friend.requested, friend.accepted) |
| `{type:"error", message}` | Lỗi (token hết hạn, không có quyền, …) |

#### Tính năng đã triển khai

**Chat**

- Nhận tin nhắn **realtime** không cần tải lại trang.
- Chỉ báo **đang gõ** của người kia.
- Tự đánh dấu **đã đọc** (✓✓) khi người kia mở cuộc trò chuyện.
- Badge số tin nhắn chưa đọc cập nhật tức thì.
- **Fallback về polling**: nếu WebSocket không khả dụng, trang chat tự chuyển sang AJAX polling 5 giây — người dùng không bị gián đoạn.

**Feed**

- Bài viết mới được **chèn lên đầu feed** kèm hiệu ứng nổi nhẹ.
- Thẻ bài viết được **render sẵn từ server** qua `GET /api/posts/{id}/card` → giao diện luôn giống hệt bài viết render bình thường (đúng trạng thái đã thích / quyền sở hữu của người xem).
- Số liệu like / bình luận / chia sẻ của bài đang xem **cập nhật tức thì** mà không cần tải lại trang.
- Bình luận mới hiện ngay trong khung bình luận đang mở.

**Thông báo**

- Badge chưa đọc trên topbar / sidebar / thanh điều hướng di động **cập nhật tức thì**.
- Dòng thông báo mới được **chèn lên đầu danh sách** khi đang ở trang `/notifications`.
- **Toast** ở góc phải dưới báo cho mọi sự kiện realtime (bài mới, thích, bình luận, theo dõi, kết bạn, thông báo).

**Chung**

- Kết nối WebSocket duy trì ở **mọi trang đã đăng nhập** (token được layout `main.php` cấp tự động).
- Tự **kết nối lại** khi mất mạng (backoff luỹ thừa 1s → 15s).

#### Bảo mật

| Biện pháp | Chi tiết |
|---|---|
| Xác thực | HMAC-SHA256 với `APP_KEY`; `timingSafeEqual` khi so sánh chữ ký |
| Token ngắn hạn | Hết hạn sau `WS_TOKEN_TTL` giây (mặc định 300) |
| Phân tách mục đích | Token đăng nhập và ticket cuộc trò chuyện dùng `purpose` khác nhau → ticket không thể dùng để đăng nhập và ngược lại |
| Phân quyền | Server kiểm tra `conversation_members` trước khi subscribe/gửi; client không tự khai báo quyền |
| SQL injection | 100% prepared statement qua `mysql2` (`connection.execute`) |
| Payload | Giới hạn 16 KB mỗi frame; nội dung tin nhắn tối đa 5000 ký tự |
| Heartbeat | Ping/pong mỗi 30s, đóng kết nối treo |
| Endpoint nội bộ | `/internal/publish` chỉ nhận body có chữ ký HMAC hợp lệ; so sánh bằng `timingSafeEqual` |
| Phân quyền feed | `audience` (tác giả + người theo dõi + bạn bè) do PHP tính sẵn theo mức quyền riêng tư của bài viết |

#### Lưu ý khi triển khai production

- Chạy WebSocket server dưới **systemd / pm2 / Docker** để tự khởi động lại.
- Đặt `WS_HOST` theo hostname thật; khi dùng HTTPS phải chạy **reverse proxy** (Nginx/Caddy)
  hỗ trợ `Upgrade` header và đặt `SESSION_SECURE=true` để client dùng `wss://`.
- `APP_KEY` phải là **secret** — không commit vào Git (file `.env` đã nằm ngoài Git).

---

## 6. Tài khoản mẫu

Dữ liệu mẫu trong `database/seed.sql` gồm 5 tài khoản. **Mật khẩu chung: `Password@123`**

| Username | Email | Tên hiển thị | Ghi chú |
|---|---|---|---|
| `an` | an@koobecaf.test | An Nguyễn | Bạn của `binh`, `chi`, `dung`; có lời mời từ `em` |
| `binh` | binh@koobecaf.test | Bình Trần | Bạn của `an`, `dung` |
| `chi` | chi@koobecaf.test | Chi Lê | Bạn của `an`; giao diện mặc định **dark mode** |
| `dung` | dung@koobecaf.test | Dũng Phạm | Bạn của `an`, `binh`; đã gửi lời mời cho `em` |
| `em` | em@koobecaf.test | Em Võ | Người mới, đã gửi lời mời kết bạn cho `an` (đang chờ) |

Dữ liệu mẫu **chỉ dùng để kiểm thử**. Khi vận hành thật, hãy import `schema.sql` (không import `seed.sql`) và tạo tài khoản qua giao diện đăng ký.

---

## 7. Chạy bộ kiểm thử

Bộ test là script PHP thuần, gọi HTTP tới ứng dụng đang chạy. Khởi động server trước rồi chạy:

```bash
# Terminal 1
php -S localhost:8000 -t . router.php

# Terminal 2
php tests/smoke.php        http://localhost:8000   # 54 kiểm tra end-to-end
php tests/extended.php     http://localhost:8000   # 40 kiểm tra biên
php tests/password_reset.php http://localhost:8000 # 21 kiểm tra quên/đặt lại mật khẩu
php tests/form_persistence.php http://localhost:8000 # 20 kiểm tra giữ giá trị form
php tests/draft_cleared.php http://localhost:8000   # 9 kiểm tra xoá nháp sau khi gửi thành công
node tests/draft_engine.js                          # 14 kiểm tra logic lưu nháp (không cần server)
node tests/leave_guard.js                           # 13 kiểm tra logic cảnh báo rời trang (không cần server)
php tests/pages.php        http://localhost:8000   # render toàn bộ route, bắt lỗi PHP
```

Bộ test WebSocket (cần `npm run ws` đang chạy cho `ws_e2e.js`):

```bash
node tests/ws_protocol.js                          # 9 kiểm tra token/ticket (không cần server)
node tests/ws_e2e.js                               # 12 kiểm tra end-to-end WebSocket
node tests/ws_feed.js                              # 10 kiểm tra end-to-end feed/thông báo
```

Kết quả mong đợi:

```
PASS: 54 | FAIL: 0     (smoke.php)
PASS: 40 | FAIL: 0     (extended.php)
PASS: 21 | FAIL: 0     (password_reset.php — cần MAIL_DRIVER=log)
PASS: 20 | FAIL: 0     (form_persistence.php)
PASS: 9  | FAIL: 0     (draft_cleared.php)
PASS: 14 | FAIL: 0     (draft_engine.js)
PASS: 13 | FAIL: 0     (leave_guard.js)
PASS: 9  | FAIL: 0     (ws_protocol.js)
PASS: 12 | FAIL: 0     (ws_e2e.js — cần npm run ws)
PASS: 10 | FAIL: 0     (ws_feed.js — cần npm run ws)
FAIL: 0                (pages.php — không có lỗi/warning nào)
```

> `password_reset.php` đọc token từ file log của `storage/logs/`, nên cần đặt `MAIL_DRIVER=log` trong `.env` khi chạy test.

Xem `PROGRESS.md` để biết chi tiết từng hạng mục đã kiểm thử.

---

## 8. Kiến trúc & luồng xử lý request

```
Browser
  │
  ▼
index.php
  │  → app/bootstrap.php
  │      • spl_autoload_register (PSR-4: App\ → app/)
  │      • Env::load('.env')
  │      • Config::load('config/*.php')
  │      • Session::start()  (cookie HttpOnly + SameSite=Lax + Secure theo env)
  │      • Error handler → storage/logs/app-YYYY-MM-DD.log
  │
  ▼
Router::dispatch()
  │  • Khớp regex route trong routes/web.php
  │  • Gắn middleware (AuthMiddleware / GuestMiddleware)
  │  • Trích tham số động {id}, {username}
  │
  ▼
Controller::method($params)
  │  • Kiểm tra quyền (quyền sở hữu, quyền xem theo privacy, thành viên hội thoại)
  │  • Validate input (Validator)
  │  • Gọi Model
  │
  ▼
Model → Database (PDO prepared statements)
  │
  ▼
View::render('feed/index', $data)  → layouts/main.php
  │  • htmlspecialchars() cho mọi dữ liệu động
  │  • Flash message, CSRF field, asset()/url()
  │
  ▼
HTML response  ──►  hoặc  JSON response (Response::success / Response::error)
```

### 8.1. Định tuyến (routes/web.php)

| Nhóm | Route |
|---|---|
| Xác thực | `GET/POST /login`, `GET/POST /register`, `POST /logout` |
| Công khai | `GET /`, `GET /post/{id}`, `GET /p/{id}`, `GET /u/{username}`, `GET /search` |
| Bài viết | `GET /posts/create`, `POST /posts`, `GET /posts/{id}/edit`, `POST /posts/{id}/update|delete|like|comment|share` |
| Bình luận | `POST /comments/{id}/update|delete` |
| Hồ sơ | `GET/POST /settings/profile`, `POST /settings/password`, `POST /settings/theme` |
| Bạn bè | `GET /friends`, `GET /friends/requests`, `POST /friends/request/{id}`, `POST /friends/{id}/accept|decline|cancel|remove` |
| Theo dõi | `POST /follow/{id}` |
| Chat | `GET /chat`, `GET /chat/{id}`, `GET /chat/{id}/messages`, `POST /chat/{id}/send`, `GET|POST /chat/start/{id}` |
| Thông báo | `GET /notifications`, `POST /notifications/{id}/read`, `POST /notifications/read-all` |
| API AJAX | `GET /api/feed`, `/api/search/users`, `/api/search/posts`, `/api/posts/{id}/comments`, `/api/notifications/unread`, `/api/messages/unread` |
| API realtime | `GET /api/posts/{id}/card` (render HTML thẻ bài viết), `GET /api/notifications/{id}/item` (render HTML dòng thông báo) |
| WebSocket | `GET /api/ws/session` (cấp token đăng nhập), `GET /api/ws/ticket/{id}` (cấp ticket theo cuộc trò chuyện) |
| WebSocket | `GET /api/ws/session` (cấp token đăng nhập), `GET /api/ws/ticket/{id}` (cấp ticket theo cuộc trò chuyện) |

### 8.2. Cấu trúc database

12 bảng InnoDB, `utf8mb4_unicode_ci`:

| Bảng | Quan hệ chính |
|---|---|
| `users` | 1-n với posts/likes/comments/shares/messages; n-n với friendships/follows |
| `posts` | thuộc `users`; 1-n `post_images`; `shared_post_id` tự tham chiếu |
| `post_images` | thuộc `posts` (ON DELETE CASCADE) |
| `likes` | n-n `posts` × `users`, UNIQUE `(post_id, user_id)` |
| `comments` | thuộc `posts`, tự tham chiếu `parent_id` |
| `shares` | n-n `posts` × `users`, UNIQUE `(post_id, user_id)` |
| `friendships` | n-n `users` tự tham chiếu, UNIQUE `(user_id, friend_id)` |
| `follows` | n-n `users` tự tham chiếu, UNIQUE `(follower_id, following_id)` |
| `conversations` | 1-n `messages`, 1-n `conversation_members` |
| `conversation_members` | n-n `conversations` × `users`, UNIQUE `(conversation_id, user_id)` |
| `messages` | thuộc `conversations`, người gửi thuộc `users` |
| `notifications` | thuộc `users`, người gây ra thuộc `users` |

Chi tiết ERD: [`database/erd/erd.md`](database/erd/erd.md).

---

## 9. Bảo mật đã áp dụng

| Nhóm | Biện pháp |
|---|---|
| SQL Injection | 100% PDO prepared statements; không nối chuỗi dữ liệu người dùng vào SQL (trừ `LIMIT/OFFSET` đã ép kiểu số nguyên) |
| XSS | `e()` = `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` cho mọi dữ liệu động; nội dung người dùng chỉ hiển thị dạng text, link do hệ thống tạo |
| CSRF | Token per-session, kiểm tra trên mọi request đổi dữ liệu; hỗ trợ cả form field, JSON body và header `X-CSRF-TOKEN` |
| Mật khẩu | `password_hash()` (bcrypt) + `password_verify()`; không bao giờ lưu bản rõ; không log mật khẩu |
| Session | Regenerate ID sau đăng nhập/đăng ký; cookie `HttpOnly` + `SameSite=Lax` (+ `Secure` khi bật) |
| Upload | Kiểm tra MIME thực (`finfo`) + extension allowlist (jpg/jpeg/png/gif/webp) + giới hạn 5MB + đổi tên file ngẫu nhiên + `.htaccess` chặn thực thi PHP trong `uploads/` |
| Phân quyền | Kiểm tra ở backend cho mọi thao tác: sở hữu bài viết/bình luận, quyền xem theo `privacy`, thành viên cuộc trò chuyện, quyền xóa bài viết đã bị xóa |
| SQL integrity | FK + UNIQUE + CHECK logic ở tầng ứng dụng: cấm tự follow, cấm tự kết bạn, cấm Like trùng |
| Rò rỉ thông tin | `APP_DEBUG=false` ở production, lỗi ghi vào `storage/logs/`, không hiển thị stack trace cho người dùng |
| Quên mật khẩu | Token random 32 byte, database chỉ lưu bản SHA-256; hết hạn 60 phút; dùng một lần rồi vô hiệu hóa; phản hồi giống nhau dù email có tồn tại hay không |
| Link đặt lại mật khẩu | Host lấy từ `APP_URL` (không dùng header `Host` của request) để chống host header injection |
| Cấu hình | `.env` nằm ngoài Git (`.gitignore`), `.htaccess` chặn autoindex + lộ phiên bản Apache |
| WebSocket | Xác thực HMAC-SHA256 bằng `APP_KEY` (`timingSafeEqual`), token hạn 300s, ticket tách `purpose` khỏi token đăng nhập, kiểm tra `conversation_members` ở server, prepared statement 100%, giới hạn 16 KB/frame |

---

## 10. Ghi chú vận hành

- **Dữ liệu mẫu**: `seed.sql` chỉ dùng cho demo/kiểm thử. Production chỉ import `schema.sql`.
- **`APP_DEBUG`**: đặt `false` khi triển khai thật.
- **HTTPS**: bật `SESSION_SECURE=true` khi dùng HTTPS.
- **Upload**: cần đảm bảo quyền ghi cho `uploads/` và `storage/logs/`.
- **Cron dọn file rác**: nên thêm tác vụ định kỳ xóa file upload mồ côi (không còn tham chiếu trong `post_images`).
- **WebSocket server**: chạy dưới systemd/pm2 để tự khởi động lại; nhiều tiến trình cần Redis pub/sub để đồng bộ tin nhắn, feed và thông báo giữa các node.
- **`APP_KEY`**: bắt buộc cho WebSocket, phải là secret và **không đổi** sau khi token đã được cấp (đổi sẽ vô hiệu hoá các token cũ).

---

## 11. Hướng phát triển tương lai

Gợi ý (chưa triển khai):

1. **Like bình luận** và **đính kèm ảnh cho bình luận** (bảng `comment_likes`, `comment_images`).
3. **Hashtag & mention** — trường `hashtags`, `mentions` trên bài viết/bình luận, trang `/tag/{hashtag}`.
4. **Tin nhắn nhóm** — thay `conversations` 1-1 bằng nhóm nhiều thành viên (protocol `subscribe` đã hỗ trợ sẵn theo `conversation_id`).
5. **Khuyến nghị nội dung** — thuật toán gợi ý dựa trên lịch sử follow/like.
6. **Ẩn danh / báo cáo nội dung** — bảng `reports` và quy trình kiểm duyệt.
7. **Tìm kiếm toàn văn** — chuyển `LIKE` sang MySQL FULLTEXT index khi dữ liệu lớn.
8. **Cache tầng ứng dụng** — Redis cho session, unread count, feed và pub/sub giữa nhiều tiến trình WebSocket.
9. **Chia sẻ ra mạng xã hội khác** — tích hợp Open Graph API để đăng bài lên Facebook/Twitter.
10. **Test tự động hóa** — PHPUnit + browser test (Playwright) cho luồng E2E.
11. **Đồng bộ nhiều thiết bị** — thêm `device_id` vào session để đồng bộ trạng thái đã đọc giữa các thiết bị.

---

