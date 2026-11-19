import io

PATH = 'assets/css/app.css'

with io.open(PATH, 'r', encoding='utf-8', newline='') as f:
    s = f.read()

pairs = []

# ---- Post card: phẳng, dùng đường kẻ mảnh, không viền thẻ ----
pairs.append(("""    .post-card {
        overflow: visible;
        border-radius: var(--kb-radius-xl);
        transition: border-color .18s var(--kb-ease-out);
    }

    .post-card:hover {
        border-color: var(--kb-hairline);
    }""",
"""    /* Bài viết hiển thị như một dòng trong luồng: không viền thẻ,
       chỉ dùng đường kẻ hairline để tách các bài. */
    .post-card {
        overflow: visible;
        background-color: transparent;
        border: 0;
        border-bottom: 1px solid var(--kb-hairline-soft);
        border-radius: 0;
        padding: var(--kb-space-md) 0;
        transition: background-color .18s var(--kb-ease-out);
    }

    .post-card:hover {
        background-color: color-mix(in srgb, var(--kb-surface-soft) 45%, transparent);
    }

    .post-card:last-child {
        border-bottom: 0;
    }"""))

# ---- Post body: bỏ padding nội tại (do .post-card đảm nhiệm) ----
pairs.append(("""    .post-text {
        white-space: pre-wrap;""",
"""    .post-body {
        padding: 0;
        gap: var(--kb-space-sm);
    }

    .post-text {
        white-space: pre-wrap;"""))

# ---- Post stats: dòng số liệu tối giản ----
pairs.append(("""    /* Số liệu tương tác dùng chữ số đều nhau */
    .post-stats {
        font-variant-numeric: tabular-nums;
    }""",
"""    /* Số liệu tương tác dùng chữ số đều nhau */
    .post-stats {
        font-variant-numeric: tabular-nums;
        font-size: 12.5px;
        color: var(--kb-muted);
    }

    .post-stats i {
        font-size: .9rem;
    }"""))

# ---- Shared header / original: gọn hơn ----
pairs.append(("""    .shared-original {
        background-color: var(--kb-surface-soft);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xl);
    }""",
"""    .shared-original {
        background-color: var(--kb-surface-soft);
        border: 0;
        border-radius: var(--kb-radius-lg);
        padding: var(--kb-space-sm);
    }"""))

# ---- Rail card dùng chung: bỏ viền, dùng nền nhạt (đã đổi ở nhóm trước) ----
pairs.append(("""    .app-rail-inline .rail-card {
        margin-top: var(--kb-space-md);
    }""",
"""    .app-rail-inline .rail-card {
        margin-top: var(--kb-space-md);
    }

    /* Khung nội dung chính: dùng đường kẻ hairline bao quanh như Threads */
    .app-main > .feed-stream {
        border-top: 1px solid var(--kb-hairline-soft);
    }"""))

for old, new in pairs:
    o = old.replace('\n', '\r\n')
    n = new.replace('\n', '\r\n')
    if o in s:
        s = s.replace(o, n, 1)
    elif n in s:
        pass
    else:
        raise SystemExit('NOT FOUND: ' + old[:120])

with io.open(PATH, 'w', encoding='utf-8', newline='') as f:
    f.write(s)

print('post-card css ok')
