import io

PATH = 'assets/css/app.css'

with io.open(PATH, 'r', encoding='utf-8', newline='') as f:
    s = f.read()

pairs = []

# Panel phẳng dùng cho danh sách (thông báo, bạn bè, kết quả tìm kiếm…)
pairs.append(("""    /* ---------------- Danh sách ---------------- */
    .list-unified {""",
"""    /* ---------------- Panel phẳng (danh sách) ----------------
       Thay cho thẻ có viền: khối nội dung nằm trên nền trang, được tách
       bằng đường kẻ hairline — đúng cách Threads trình bày danh sách. */
    .panel {
        background-color: var(--kb-surface-card);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xl);
        overflow: hidden;
    }

    .panel-body {
        padding: var(--kb-space-md) var(--kb-space-lg);
    }

    .panel-head {
        padding: var(--kb-space-md) var(--kb-space-lg);
        border-bottom: 1px solid var(--kb-hairline-soft);
    }

    .panel-head .rail-title {
        margin-bottom: 0;
    }

    /* ---------------- Danh sách ---------------- */
    .list-unified {"""))

# Trang chủ: khung luồng bài viết
pairs.append(("""    /* Khung nội dung chính: dùng đường kẻ hairline bao quanh như Threads */
    .app-main > .feed-stream {
        border-top: 1px solid var(--kb-hairline-soft);
    }""",
"""    /* Khung luồng bài viết: một khối duy nhất, tách bằng đường kẻ hairline */
    .feed-stream {
        background-color: var(--kb-surface-card);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xl);
        overflow: hidden;
    }

    .feed-stream > .post-card:first-child {
        border-top: 0;
    }

    .feed-stream > .post-card:last-child {
        border-bottom: 0;
    }"""))

# Panel phẳng cho trang bài viết chi tiết: bo góc nhỏ
pairs.append(("""    /* ---- Thẻ bài viết ---- */
    .post-detail {
        overflow: hidden;
        padding: 0;
    }""",
"""    /* ---- Thẻ bài viết ---- */
    .post-detail {
        overflow: hidden;
        padding: 0;
        border-radius: var(--kb-radius-xl);
    }"""))

# Bình luận trong chi tiết: dùng panel phẳng
pairs.append(("""    .post-detail-comments {
        margin-top: var(--kb-space-md);
        overflow: hidden;
    }""",
"""    .post-detail-comments {
        margin-top: var(--kb-space-md);
        overflow: hidden;
        border-radius: var(--kb-radius-xl);
    }"""))

# Trang cá nhân: khối nội dung dùng panel
pairs.append(("""    .post-detail-crumb {
        display: flex;""",
"""    .post-detail-crumb {
        display: flex;"""))

for old, new in pairs:
    o = old.replace('\n', '\r\n')
    n = new.replace('\n', '\r\n')
    if o in s:
        s = s.replace(o, n, 1)
    elif n in s:
        pass
    else:
        raise SystemExit('NOT FOUND: ' + old[:150])

with io.open(PATH, 'w', encoding='utf-8', newline='') as f:
    f.write(s)

print('panels ok')
