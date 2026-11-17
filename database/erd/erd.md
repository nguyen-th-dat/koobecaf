# ERD — Koobecaf Social Network

Sơ đồ quan hệ thực thể của toàn hệ thống. Engine InnoDB, charset `utf8mb4_unicode_ci`.

```mermaid
erDiagram
    users ||--o{ posts : "viết"
    users ||--o{ post_images : ""
    users ||--o{ likes : "thích"
    users ||--o{ comments : "bình luận"
    users ||--o{ shares : "chia sẻ"
    users ||--o{ follows : "theo dõi"
    users ||--o{ friendships : "bạn bè"
    users ||--o{ notifications : "nhận"
    users ||--o{ notifications : "gây ra"
    users ||--o{ conversation_members : "tham gia"
    users ||--o{ messages : "gửi"

    posts ||--o{ post_images : "có"
    posts ||--o{ likes : "được thích"
    posts ||--o{ comments : "có"
    posts ||--o{ shares : "được chia sẻ"
    posts ||--o| posts : "chia sẻ nội bộ (shared_post_id)"

    comments ||--o{ comments : "trả lời (parent_id)"

    conversations ||--o{ conversation_members : "gồm"
    conversations ||--o{ messages : "chứa"

    users {
        bigint id PK
        varchar username UK
        varchar email UK
        varchar password_hash
        varchar display_name
        varchar bio
        varchar avatar_path
        varchar cover_path
        enum theme
        datetime created_at
        datetime updated_at
    }

    posts {
        bigint id PK
        bigint user_id FK
        text content
        enum privacy
        bigint shared_post_id FK
        varchar share_content
        varchar shared_url
        datetime created_at
        datetime updated_at
    }

    post_images {
        bigint id PK
        bigint post_id FK
        varchar path
        tinyint position
        datetime created_at
    }

    likes {
        bigint id PK
        bigint post_id FK
        bigint user_id FK
        datetime created_at
    }

    comments {
        bigint id PK
        bigint post_id FK
        bigint user_id FK
        bigint parent_id FK
        varchar content
        datetime created_at
        datetime updated_at
    }

    shares {
        bigint id PK
        bigint post_id FK
        bigint user_id FK
        varchar content
        datetime created_at
    }

    follows {
        bigint id PK
        bigint follower_id FK
        bigint following_id FK
        datetime created_at
    }

    friendships {
        bigint id PK
        bigint user_id FK
        bigint friend_id FK
        enum status
        datetime created_at
        datetime updated_at
    }

    conversations {
        bigint id PK
        datetime created_at
        datetime updated_at
    }

    conversation_members {
        bigint id PK
        bigint conversation_id FK
        bigint user_id FK
        datetime last_read_at
        datetime joined_at
    }

    messages {
        bigint id PK
        bigint conversation_id FK
        bigint sender_id FK
        text body
        datetime read_at
        datetime created_at
    }

    notifications {
        bigint id PK
        bigint user_id FK
        bigint actor_id FK
        varchar type
        bigint entity_id
        varchar data
        tinyint is_read
        datetime created_at
    }
```

## Quan hệ và ràng buộc chính

| Quan hệ | Loại | Ghi chú |
|---|---|---|
| `users` – `posts` | 1-n | Xóa user → xóa toàn bộ bài viết (`ON DELETE CASCADE`) |
| `posts` – `post_images` | 1-n | Xóa bài viết → xóa ảnh |
| `posts` – `likes` | 1-n | UNIQUE `(post_id, user_id)` chống like trùng |
| `posts` – `comments` | 1-n | `parent_id` tự tham chiếu cho reply |
| `posts` – `shares` | 1-n | UNIQUE `(post_id, user_id)` chống chia sẻ trùng |
| `posts` – `posts` | 0-1 | `shared_post_id` trỏ tới bài gốc |
| `users` – `follows` | n-n | UNIQUE `(follower_id, following_id)` + CHECK không tự follow |
| `users` – `friendships` | n-n | UNIQUE `(user_id, friend_id)` + CHECK không tự kết bạn |
| `conversations` – `conversation_members` | 1-n | UNIQUE `(conversation_id, user_id)` |
| `conversations` – `messages` | 1-n | Chỉ thành viên mới đọc được tin nhắn |
| `users` – `notifications` | 1-n | UNIQUE `(user_id, actor_id, type, entity_id)` chống thông báo lặp |

## Quyền riêng tư bài viết

| `privacy` | Ai xem được |
|---|---|
| `public` | Mọi người (kể cả khách chưa đăng nhập) |
| `followers` | Người theo dõi + chủ bài viết + bạn bè đã chấp nhận |
| `private` | Chỉ chủ bài viết |
