import io

PATH = 'assets/css/app.css'

with io.open(PATH, 'r', encoding='utf-8', newline='') as f:
    s = f.read()

pairs = []

# Hàng hành động: gọn, canh trái, không nền — giống Threads
pairs.append(("""    .post-actions .action-btn {
        color: var(--kb-muted);
        font-size: .9rem;
        border-radius: 0;
        min-height: 40px;
        font-weight: 600;
        letter-spacing: -.14px;
        transition: background-color var(--kb-dur) var(--kb-ease-out), color var(--kb-dur) var(--kb-ease-out);
        white-space: nowrap;
    }""",
"""    .post-actions {
        display: flex;
        align-items: center;
        gap: var(--kb-space-md);
        margin-top: var(--kb-space-xxs);
    }

    .post-actions .action-btn {
        flex: 0 0 auto;
        min-height: 32px;
        padding: 0;
        color: var(--kb-muted);
        font-size: .85rem;
        border-radius: var(--kb-radius-pill);
        font-weight: 600;
        letter-spacing: -.14px;
        transition: background-color var(--kb-dur) var(--kb-ease-out), color var(--kb-dur) var(--kb-ease-out);
        white-space: nowrap;
    }"""))

pairs.append(("""    .post-actions .action-btn:hover {
        background-color: transparent;
        color: var(--kb-ink);
    }""",
"""    .post-actions .action-btn:hover {
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
    }"""))

# Nút menu ba chấm trong bài viết
pairs.append(("""    .post-actions .action-btn i {
        font-size: 1rem;
    }""",
"""    .post-actions .action-btn i {
        font-size: 1rem;
    }

    .post-menu-btn {
        width: 32px;
        height: 32px;
        min-height: 32px;
        color: var(--kb-muted);
    }"""))

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

print('post actions ok')
