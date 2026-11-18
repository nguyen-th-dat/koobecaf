# Koobecaf — Nền tảng mạng xã hội (PHP thuần + MySQL)

Koobecaf là một nền tảng mạng xã hội hoàn chỉnh ở mức cơ bản, được xây dựng bằng **PHP 8.2 thuần** (không dùng Laravel), **MySQL/MariaDB (InnoDB)** và **Bootstrap 5.3.x**. Toàn bộ Backend, Frontend và Database đều là mã nguồn thật, có thể triển khai và chạy trực tiếp trên XAMPP.

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
| Frontend | HTML5, CSS3, JavaScript ES6+ (Fetch API), Bootstrap 5.3.3, Bootstrap Icons 1.11.x |
| Database | MySQL 8.0+ / MariaDB 10.4+, InnoDB, `utf8mb4_unicode_ci` |
| Môi trường | XAMPP (Apache + PHP + MySQL/MariaDB) |
| Công cụ | Composer, `php -S` (dev server), bộ test tích hợp tự viết |

> Bootstrap 5.3.x và Bootstrap Icons được **vendor sẵn** trong `assets/vendor/` nên không cần tải qua CDN — ứng dụng chạy được cả khi môi trường không có Internet.

---

## 2. Danh sách chức năng

### 2.1. Xác thực (Authentication)
- Đăng ký, đăng nhập, đăng xuất.
- Kiểm tra email trùng lặp, validate dữ liệu đầu vào (tên, username, email, mật khẩu tối thiểu 8 ký tự).
- Mã hóa mật khẩu bằng `password_hash()` (bcrypt), xác thực bằng `password_verify()`.
- Quản lý phiên bằng PHP Session, **regenerate Session ID** sau khi đăng nhập/đăng ký.
- Cookie phiên an toàn (`HttpOnly`, `SameSite=Lax`, `Secure` khi bật `SESSION_SECURE`).
- Đổi mật khẩu (yêu cầu xác nhận mật khẩu hiện tại).

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

### 2.4. Bài viết (Posts)
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

### 2.10. Thông báo (Notifications)
8 loại thông báo: `like`, `comment`, `reply`, `share`, `friend_request`, `friend_accepted`, `message`, `follow`.
- Danh sách, đánh dấu đã đọc, đánh dấu tất cả đã đọc (AJAX), badge số chưa đọc.
- Click vào thông báo sẽ điều hướng tới đúng nội dung liên quan.

### 2.11. Tìm kiếm (Search)
- Tìm người dùng theo tên / username — gợi ý tức thì bằng AJAX.
- Tìm bài viết công khai theo từ khóa — có phân trang.
- Không hiển thị nội dung ngoài quyền truy cập của người xem.

### 2.12. Giao diện
- Light Mode / Dark Mode, lưu theo tài khoản (`users.theme`) và trong session.
- Responsive: sidebar → offcanvas + bottom navigation trên mobile.

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
│   ├── Models/                    # 12 model tương ứng 12 bảng
│   │   ├── Comment.php  Conversation.php  Follow.php  Friendship.php
│   │   ├── Like.php      Message.php     Notification.php
│   │   ├── Post.php      PostImage.php   Share.php     User.php
│   ├── Controllers/               # 13 controller
│   │   ├── ApiController.php       AuthController.php     ChatController.php
│   │   ├── CommentController.php  FeedController.php     FollowController.php
│   │   ├── FriendController.php   LikeController.php     NotificationController.php
│   │   ├── PostController.php     ProfileController.php   SearchController.php
│   │   └── ShareController.php
│   ├── Services/
│   │   ├── NotificationService.php # Sinh thông báo theo loại sự kiện
│   │   └── UploadService.php       # Upload ảnh an toàn
│   ├── Helpers/
│   │   └── helpers.php             # e(), url(), asset(), time_ago(), auth_user()...
│   └── Views/
│       ├── layouts/                # main.php, auth.php + partials (topbar, sidebars, mobile-nav, flash)
│       ├── partials/               # post-card.php, comment-item.php
│       ├── auth/                   # login.php, register.php
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
│   └── database.php                # Thông tin kết nối MySQL
│
├── database/
│   ├── schema.sql                  # Toàn bộ cấu trúc bảng + FK + index + unique
│   ├── seed.sql                    # Dữ liệu mẫu (5 users, bài viết, bình luận, chat...)
│   └── erd/erd.md                  # Sơ đồ quan hệ thực thể (Mermaid)
│
├── index.php                      # Entry point (chạy tại thư mục gốc)
├── router.php                     # Router script cho `php -S`
├── .htaccess                      # Rewrite về index.php + chặn mã nguồn
├── assets/                        # Tài nguyên tĩnh phục vụ trực tiếp
│   ├── css/app.css                # CSS riêng (bám theo biến Bootstrap 5.3)
│   ├── js/app.js                  # Fetch API: like/comment/share/follow/search/chat polling
│   ├── images/                    # favicon.svg, default.svg
│   └── vendor/                    # Bootstrap 5.3.3 + Bootstrap Icons (bản local)
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
│   └── pages.php                   # Render toàn bộ route + API, bắt lỗi PHP
│
├── .env.example                   # Mẫu cấu hình môi trường
├── .gitignore
├── composer.json
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
| Bootstrap | 5.3.x | Đã vendor sẵn, không cần tải thêm |

---

## 5. Hướng dẫn cài đặt

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

### 5.4. Tạo database

**Cách A — dùng phpMyAdmin:**

1. Mở `http://localhost/phpmyadmin`.
2. Tab **Databases** → tạo database tên `koobecaf`, collation `utf8mb4_unicode_ci`.
3. Chọn database `koobecaf` → tab **Import** → chọn `database/schema.sql` → **Import**.
4. Lặp lại với `database/seed.sql` (nếu muốn có dữ liệu mẫu).

**Cách B — dùng command line:**

```bash
mysql -u root -p -e "CREATE DATABASE koobecaf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p koobecaf < database/schema.sql
mysql -u root -p koobecaf < database/seed.sql
```

> Trên PowerShell, `mysql < file.sql` không hoạt động do stream redirection. Dùng:
> `cmd /c "mysql -u root -p koobecaf < database\schema.sql"` hoặc `Get-Content database\schema.sql -Raw | mysql -u root -p koobecaf`.

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
```

| Biến | Ý nghĩa |
|---|---|
| `APP_DEBUG` | `true` = hiện chi tiết lỗi. **Luôn đặt `false` khi triển khai production.** |
| `APP_URL` | URL gốc dùng cho permalink & Open Graph. |
| `APP_BASE_PATH` | Nếu ứng dụng nằm trong thư mục con (Cách A), điền `/koobecaf`. |
| `SESSION_SECURE` | `true` khi chạy qua HTTPS (đặt cookie `Secure`). |

### 5.6. Phân quyền thư mục

Đảm bảo Apache có quyền ghi vào:

- `public/uploads/` (và các thư mục con `avatars/`, `covers/`, `posts/`)
- `storage/logs/`

Trên Windows, mặc định Apache chạy bằng tài khoản nên đã có quyền. Trên Linux/macOS: `chmod -R 775 storage/logs public/uploads`.

### 5.7. Chạy dự án

**Cách A — Apache (XAMPP):** mở `http://localhost/koobecaf/public/` (Cách A) hoặc `http://koobecaf.local` (Cách B).

**Cách B — PHP built-in server:**

```bash
composer serve
# hoặc
php -S localhost:8000 -t public public/router.php
```

Truy cập `http://localhost:8000`.

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
php -S localhost:8000 -t public public/router.php

# Terminal 2
php tests/smoke.php     http://localhost:8000   # 54 kiểm tra end-to-end
php tests/extended.php  http://localhost:8000   # 40 kiểm tra biên
php tests/pages.php     http://localhost:8000   # render toàn bộ route, bắt lỗi PHP
```

Kết quả mong đợi:

```
PASS: 54 | FAIL: 0     (smoke.php)
PASS: 40 | FAIL: 0     (extended.php)
FAIL: 0                (pages.php — không có lỗi/warning nào)
```

Xem `PROGRESS.md` để biết chi tiết từng hạng mục đã kiểm thử.

---

## 8. Kiến trúc & luồng xử lý request

```
Browser
  │
  ▼
public/index.php
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
| Bài viết | `POST /posts`, `GET /posts/{id}/edit`, `POST /posts/{id}/update|delete|like|comment|share` |
| Bình luận | `POST /comments/{id}/update|delete` |
| Hồ sơ | `GET/POST /settings/profile`, `POST /settings/password`, `POST /settings/theme` |
| Bạn bè | `GET /friends`, `GET /friends/requests`, `POST /friends/request/{id}`, `POST /friends/{id}/accept|decline|cancel|remove` |
| Theo dõi | `POST /follow/{id}` |
| Chat | `GET /chat`, `GET /chat/{id}`, `GET /chat/{id}/messages`, `POST /chat/{id}/send`, `GET|POST /chat/start/{id}` |
| Thông báo | `GET /notifications`, `POST /notifications/{id}/read`, `POST /notifications/read-all` |
| API AJAX | `GET /api/feed`, `/api/search/users`, `/api/search/posts`, `/api/posts/{id}/comments`, `/api/notifications/unread`, `/api/messages/unread` |

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
| Upload | Kiểm tra MIME thực (`finfo`) + extension allowlist (jpg/jpeg/png/gif/webp) + giới hạn 5MB + đổi tên file ngẫu nhiên + `.htaccess` chặn thực thi PHP trong `public/uploads/` |
| Phân quyền | Kiểm tra ở backend cho mọi thao tác: sở hữu bài viết/bình luận, quyền xem theo `privacy`, thành viên cuộc trò chuyện, quyền xóa bài viết đã bị xóa |
| SQL integrity | FK + UNIQUE + CHECK logic ở tầng ứng dụng: cấm tự follow, cấm tự kết bạn, cấm Like trùng |
| Rò rỉ thông tin | `APP_DEBUG=false` ở production, lỗi ghi vào `storage/logs/`, không hiển thị stack trace cho người dùng |
| Cấu hình | `.env` nằm ngoài Git (`.gitignore`), `public/.htaccess` chặn autoindex + lộ phiên bản Apache |

---

## 10. Ghi chú vận hành

- **Dữ liệu mẫu**: `seed.sql` chỉ dùng cho demo/kiểm thử. Production chỉ import `schema.sql`.
- **`APP_DEBUG`**: đặt `false` khi triển khai thật.
- **HTTPS**: bật `SESSION_SECURE=true` khi dùng HTTPS.
- **Upload**: cần đảm bảo quyền ghi cho `public/uploads/` và `storage/logs/`.
- **Cron dọn file rác**: nên thêm tác vụ định kỳ xóa file upload mồ côi (không còn tham chiếu trong `post_images`).

---

## 11. Hướng phát triển tương lai

Gợi ý (chưa triển khai):

1. **WebSocket** cho chat — thay thế AJAX polling, nâng cấp dần từ `app/Controllers/ChatController.php`.
2. **Like bình luận** và **đính kèm ảnh cho bình luận** (bảng `comment_likes`, `comment_images`).
3. **Hashtag & mention** — trường `hashtags`, `mentions` trên bài viết/bình luận, trang `/tag/{hashtag}`.
4. **Tin nhắn nhóm** — thay `conversations` 1-1 bằng nhóm nhiều thành viên.
5. **Khuyến nghị nội dung** — thuật toán gợi ý dựa trên lịch sử follow/like.
6. **Ẩn danh / báo cáo nội dung** — bảng `reports` và quy trình kiểm duyệt.
7. **Tìm kiếm toàn văn** — chuyển `LIKE` sang MySQL FULLTEXT index khi dữ liệu lớn.
8. **Cache tầng ứng dụng** — Redis cho session, unread count và feed.
9. **Chia sẻ ra mạng xã hội khác** — tích hợp Open Graph API để đăng bài lên Facebook/Twitter.
10. **Test tự động hóa** — PHPUnit + browser test (Playwright) cho luồng E2E.

---

## 12. Giấy phép

MIT — xem `composer.json`.
