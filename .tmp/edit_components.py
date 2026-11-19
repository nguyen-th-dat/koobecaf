import io

PATH = 'assets/css/app.css'

def read():
    with io.open(PATH, 'r', encoding='utf-8', newline='') as f:
        return f.read()

def write(s):
    with io.open(PATH, 'w', encoding='utf-8', newline='') as f:
        f.write(s)

missing = []

def rep(s, old, new, count=1):
    old = old.replace('\n', '\r\n')
    new = new.replace('\n', '\r\n')
    if old not in s:
        if new in s:
            return s
        missing.append(old[:120])
        return s
    return s.replace(old, new, count)

s = read()

pairs = []

# ---- Buttons: gọn, weight 600, không viền dày ----
pairs.append(("""    .btn {
        font-family: var(--kb-font-body);
        font-weight: 700;
        font-size: 14px;
        line-height: 1.43;
        letter-spacing: -.14px;
        border-radius: var(--kb-radius-pill);
        border: 2px solid transparent;
        padding: 12px 26px;
        min-height: 44px;""",
"""    .btn {
        font-family: var(--kb-font-body);
        font-weight: 600;
        font-size: 14px;
        line-height: 1.43;
        letter-spacing: -.14px;
        border-radius: var(--kb-radius-pill);
        border: 1px solid transparent;
        padding: 10px 20px;
        min-height: 40px;"""))

pairs.append(("""    .btn-sm {
        min-height: 36px;
        padding: 8px 16px;
        font-size: 13px;
        border-width: 2px;
    }""",
"""    .btn-sm {
        min-height: 32px;
        padding: 6px 14px;
        font-size: 13px;
    }"""))

pairs.append(("""    .btn-lg {
        min-height: 48px;
        padding: 14px 32px;
        font-size: 15px;
    }""",
"""    .btn-lg {
        min-height: 44px;
        padding: 12px 26px;
        font-size: 15px;
    }"""))

# btn-outline primary: viền đen
pairs.append(("""    .btn-outline.btn-primary {
        border-color: var(--kb-ink);
        color: var(--kb-ink);
    }""",
"""    .btn-outline.btn-primary {
        border-color: var(--kb-hairline);
        color: var(--kb-ink);
    }"""))

# ---- Form control: bo góc 8px, viền hairline ----
pairs.append(("""        border: 1px solid var(--kb-hairline);
        border-radius: var(--kb-radius-lg);
        padding: 11px 14px;
        min-height: 44px;""",
"""        border: 1px solid var(--kb-hairline);
        border-radius: var(--kb-radius-lg);
        padding: 10px 12px;
        min-height: 42px;"""))

pairs.append(("""    .form-control:focus {
        outline: 0;
        background-color: var(--kb-canvas);
        border-color: var(--kb-fb-blue, #1876f2);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--kb-primary) 14%, transparent);
        color: var(--kb-ink);
    }""",
"""    .form-control:focus {
        outline: 0;
        background-color: var(--kb-canvas);
        border-color: var(--kb-ink);
        box-shadow: none;
        color: var(--kb-ink);
    }"""))

# ---- Card: bo góc nhỏ hơn, không đổ bóng ----
pairs.append(("""    .card,
    .surface-card {
        background-color: var(--kb-surface-card);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xxxl);
        box-shadow: none;
        color: var(--kb-body);
        transition: border-color var(--kb-dur) var(--kb-ease);
    }

    .card-body {
        padding: var(--kb-space-xl);
    }""",
"""    .card,
    .surface-card {
        background-color: var(--kb-surface-card);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xxl);
        box-shadow: none;
        color: var(--kb-body);
        transition: border-color var(--kb-dur) var(--kb-ease);
    }

    .card-body {
        padding: var(--kb-space-lg);
    }"""))

# ---- Menu dropdown: nền canvas, bo góc 16, viền hairline ----
pairs.append(("""        background-color: var(--kb-canvas);
        color: var(--kb-body);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xl);
        box-shadow: 0 12px 32px rgba(20, 22, 26, .18);
        display: none;
        visibility: hidden;
    }""",
"""        background-color: var(--kb-canvas);
        color: var(--kb-body);
        border: 1px solid var(--kb-hairline);
        border-radius: var(--kb-radius-xl);
        box-shadow: var(--kb-shadow-pop);
        display: none;
        visibility: hidden;
    }"""))

# ---- Chip / badge ----
pairs.append(("""    .chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.33;
        letter-spacing: 0;
        text-transform: uppercase;
        color: var(--kb-muted);
        background-color: var(--kb-surface-soft);
        border-radius: var(--kb-radius-pill);
    }""",
"""    .chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.33;
        letter-spacing: 0;
        text-transform: none;
        color: var(--kb-muted);
        background-color: var(--kb-surface-soft);
        border-radius: var(--kb-radius-pill);
    }"""))

# ---- Focus ring dùng màu focus riêng ----
pairs.append(("""    :focus-visible {
        outline: 2px solid var(--kb-primary);
        outline-offset: 2px;
        border-radius: var(--kb-radius-xs);
    }""",
"""    :focus-visible {
        outline: 2px solid var(--kb-focus);
        outline-offset: 2px;
        border-radius: var(--kb-radius-xs);
    }"""))

# ---- Topbar: nền trắng đặc, viền dưới hairline ----
pairs.append(("""    .topbar {
        background-color: color-mix(in srgb, var(--kb-canvas) 86%, transparent);
        backdrop-filter: saturate(180%) blur(16px);
        -webkit-backdrop-filter: saturate(180%) blur(16px);
        border-bottom: 1px solid var(--kb-hairline-soft);
        z-index: 1030;
        transition: box-shadow var(--kb-dur) var(--kb-ease);
    }

    .topbar.is-scrolled {
        box-shadow: var(--kb-shadow-raised);
    }""",
"""    .topbar {
        background-color: var(--kb-canvas);
        border-bottom: 1px solid var(--kb-hairline-soft);
        z-index: 1030;
        transition: box-shadow var(--kb-dur) var(--kb-ease);
    }

    .topbar.is-scrolled {
        box-shadow: 0 1px 0 var(--kb-hairline-soft);
    }"""))

# ---- Brand: chữ gọn ----
pairs.append(("""    .brand {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        text-decoration: none;
        font-family: var(--kb-font-display);
        font-weight: 500;
        font-size: 1.375rem;
        letter-spacing: -.03em;
        color: var(--kb-ink);
        flex-shrink: 0;
    }""",
"""    .brand {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        text-decoration: none;
        font-family: var(--kb-font-display);
        font-weight: 700;
        font-size: 1.25rem;
        letter-spacing: -.03em;
        color: var(--kb-ink);
        flex-shrink: 0;
    }"""))

# ---- Topbar nav: gọn, chữ thường, active đậm ----
pairs.append(("""    .topbar-nav .nav-link {
        padding: 8px 16px;
        border: 1px solid transparent;
        border-radius: var(--kb-radius-pill);
        font-family: var(--kb-font-body);
        font-weight: 700;
        font-size: 14px;
        letter-spacing: -.14px;
        line-height: 1.43;
        color: var(--kb-ink);
        text-decoration: none;
        white-space: nowrap;
        transition: background-color .18s var(--kb-ease-out), color .18s var(--kb-ease-out), border-color .18s var(--kb-ease-out);
    }

    .topbar-nav .nav-link:hover {
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
        text-decoration: none;
    }

    .topbar-nav .nav-link.active {
        background-color: var(--kb-ink);
        border-color: var(--kb-ink);
        color: var(--kb-canvas);
    }""",
"""    .topbar-nav .nav-link {
        padding: 7px 12px;
        border: 0;
        border-radius: var(--kb-radius-lg);
        font-family: var(--kb-font-body);
        font-weight: 500;
        font-size: 15px;
        letter-spacing: -.14px;
        line-height: 1.43;
        color: var(--kb-body);
        text-decoration: none;
        white-space: nowrap;
        transition: background-color .18s var(--kb-ease-out), color .18s var(--kb-ease-out);
    }

    .topbar-nav .nav-link:hover {
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
        text-decoration: none;
    }

    .topbar-nav .nav-link.active {
        background-color: transparent;
        color: var(--kb-ink);
        font-weight: 700;
    }"""))

# ---- Search box: pill nền xám ----
pairs.append(("""    .search-form input {
        border-radius: var(--kb-radius-pill);
        padding-left: 2.4rem;
        background-color: var(--kb-surface-soft);
        border-color: transparent;
        min-height: 40px;
    }

    .search-form input:focus {
        background-color: var(--kb-canvas);
        border-color: var(--kb-primary);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--kb-primary) 14%, transparent);
    }""",
"""    .search-form input {
        border-radius: var(--kb-radius-pill);
        padding-left: 2.4rem;
        background-color: var(--kb-surface-soft);
        border-color: transparent;
        min-height: 38px;
        font-size: 14px;
    }

    .search-form input:focus {
        background-color: var(--kb-surface-soft);
        border-color: transparent;
        box-shadow: none;
        outline: 2px solid var(--kb-focus);
        outline-offset: -1px;
    }"""))

# ---- Welcome card: tối giản ----
pairs.append(("""    .welcome-card {
        overflow: hidden;
        background: var(--kb-surface-card);
        border-color: var(--kb-hairline-soft);
    }

    .welcome-card .card-body {
        padding: var(--kb-space-xl);
    }""",
"""    .welcome-card {
        overflow: hidden;
        background: var(--kb-surface-card);
        border-color: var(--kb-hairline-soft);
    }

    .welcome-card .card-body {
        padding: var(--kb-space-lg);
    }"""))

pairs.append(("""    .welcome-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        margin-bottom: var(--kb-space-md);
        border-radius: var(--kb-radius-xl);
        background-color: var(--kb-ink);
        color: #fff;
        font-size: 1.35rem;
    }

    .welcome-title {
        margin-bottom: var(--kb-space-xs);
        font-size: 2rem;
        letter-spacing: -.02em;
    }""",
"""    .welcome-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        margin-bottom: var(--kb-space-md);
        border-radius: var(--kb-radius-circle);
        background-color: var(--kb-ink);
        color: var(--kb-canvas);
        font-size: 1.2rem;
    }

    .welcome-title {
        margin-bottom: var(--kb-space-xs);
        font-size: 1.75rem;
        letter-spacing: -.02em;
    }"""))

pairs.append(("""    .welcome-features {
        list-style: none;
        margin: var(--kb-space-lg) 0 0;
        padding: var(--kb-space-md) 0 0;
        border-top: 1px solid var(--kb-hairline-soft);
        display: flex;
        flex-wrap: wrap;
        gap: var(--kb-space-md);
        color: var(--kb-muted);
        font-size: 14px;
    }""",
"""    .welcome-features {
        list-style: none;
        margin: var(--kb-space-lg) 0 0;
        padding: var(--kb-space-md) 0 0;
        border-top: 1px solid var(--kb-hairline-soft);
        display: flex;
        flex-wrap: wrap;
        gap: var(--kb-space-md);
        color: var(--kb-muted);
        font-size: 13.5px;
    }"""))

pairs.append(("""    .welcome-features i {
        color: var(--kb-primary);
    }""",
"""    .welcome-features i {
        color: var(--kb-ink);
    }"""))

# ---- Page head: tiêu đề gọn ----
pairs.append(("""    .page-head h1 {
        margin: 0;
        font-size: 2rem;
        letter-spacing: -.02em;
    }

    .page-head h1 i {
        color: var(--kb-primary);
    }""",
"""    .page-head h1 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -.02em;
    }

    .page-head h1 i {
        color: var(--kb-ink);
    }"""))

# ---- Side nav: icon + nhãn, active đậm nền xám ----
pairs.append(("""    .side-nav .nav-link {
        position: relative;
        display: flex;
        align-items: center;
        gap: .8rem;
        padding: .7rem .9rem;
        border-radius: var(--kb-radius-pill);
        color: var(--kb-body-strong);
        font-family: var(--kb-font-body);
        font-weight: 600;
        font-size: 14px;
        letter-spacing: -.14px;
        text-decoration: none;
        transition: background-color var(--kb-dur) var(--kb-ease-out), color var(--kb-dur) var(--kb-ease-out);
    }

    .side-nav .nav-link:hover {
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
        text-decoration: none;
    }

    .side-nav .nav-link.active {
        background-color: var(--kb-ink);
        color: var(--kb-canvas);
        font-weight: 700;
    }

    .side-nav .nav-link i {
        font-size: 1.15rem;
        color: var(--kb-muted);
    }

    .side-nav .nav-link:hover i {
        color: var(--kb-ink);
    }

    .side-nav .nav-link.active i {
        color: var(--kb-canvas);
    }""",
"""    .side-nav .nav-link {
        position: relative;
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .6rem .7rem;
        border-radius: var(--kb-radius-lg);
        color: var(--kb-body);
        font-family: var(--kb-font-body);
        font-weight: 500;
        font-size: 15px;
        letter-spacing: -.14px;
        text-decoration: none;
        transition: background-color var(--kb-dur) var(--kb-ease-out), color var(--kb-dur) var(--kb-ease-out);
    }

    .side-nav .nav-link:hover {
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
        text-decoration: none;
    }

    .side-nav .nav-link.active {
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
        font-weight: 700;
    }

    .side-nav .nav-link i {
        font-size: 1.2rem;
        color: var(--kb-muted);
    }

    .side-nav .nav-link:hover i {
        color: var(--kb-ink);
    }

    .side-nav .nav-link.active i {
        color: var(--kb-ink);
    }"""))

# ---- Rail card: bỏ viền, dùng nền nhạt ----
pairs.append(("""    .rail-card {
        background-color: var(--kb-surface-card);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xxl);
        padding: var(--kb-space-lg);
        margin-top: var(--kb-space-lg);
    }

    .rail-title {
        font-family: var(--kb-font-body);
        font-weight: 700;
        font-size: 12px;
        letter-spacing: 0;
        text-transform: none;
        color: var(--kb-ink);
        margin-bottom: var(--kb-space-sm);
    }""",
"""    .rail-card {
        background-color: var(--kb-surface-card);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xl);
        padding: var(--kb-space-md);
        margin-top: var(--kb-space-lg);
    }

    .rail-title {
        font-family: var(--kb-font-body);
        font-weight: 700;
        font-size: 13px;
        letter-spacing: 0;
        text-transform: none;
        color: var(--kb-ink);
        margin-bottom: var(--kb-space-xs);
    }"""))

# ---- Person row ----
pairs.append(("""    .person-row {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .55rem 0;
        border-radius: var(--kb-radius-sm);
        transition: background-color var(--kb-dur-fast) var(--kb-ease);
    }""",
"""    .person-row {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .5rem 0;
        border-radius: var(--kb-radius-sm);
        transition: background-color var(--kb-dur-fast) var(--kb-ease);
    }"""))

# ---- Avatar: bo tròn, không viền ----
pairs.append(("""    .avatar {
        border-radius: var(--kb-radius-pill);
        object-fit: cover;
        background-color: var(--kb-surface-cream-strong);
        flex-shrink: 0;
    }""",
"""    .avatar {
        border-radius: var(--kb-radius-circle);
        object-fit: cover;
        background-color: var(--kb-surface-cream-strong);
        flex-shrink: 0;
    }"""))

# ---- Post card: bỏ viền dày, dùng đường kẻ mảnh ----
pairs.append(("""    .post-card {
        overflow: visible;
        transition: border-color .18s var(--kb-ease-out);
    }

    .post-card:hover {
        border-color: var(--kb-hairline);
    }""",
"""    .post-card {
        overflow: visible;
        border-radius: var(--kb-radius-xl);
        transition: border-color .18s var(--kb-ease-out);
    }

    .post-card:hover {
        border-color: var(--kb-hairline);
    }"""))

pairs.append(("""    .post-author {
        font-family: var(--kb-font-body);
        font-weight: 700;
        font-size: 15px;
        letter-spacing: -.14px;
        color: var(--kb-ink);
        text-decoration: none;
    }""",
"""    .post-author {
        font-family: var(--kb-font-body);
        font-weight: 700;
        font-size: 14.5px;
        letter-spacing: -.14px;
        color: var(--kb-ink);
        text-decoration: none;
    }"""))

pairs.append(("""    .post-meta {
        font-size: 13px;
        color: var(--kb-muted);
    }""",
"""    .post-meta {
        font-size: 12.5px;
        color: var(--kb-muted);
    }"""))

pairs.append(("""    .post-text {
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.50;
        color: var(--kb-body);
        font-size: 16px;
        letter-spacing: -.16px;
    }""",
"""    .post-text {
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.50;
        color: var(--kb-body);
        font-size: 15px;
        letter-spacing: -.1px;
    }"""))

pairs.append(("""    .post-images {
        display: grid;
        gap: .35rem;
        border-radius: var(--kb-radius-xl);
        overflow: hidden;
        margin-top: var(--kb-space-xs);
    }""",
"""    .post-images {
        display: grid;
        gap: 2px;
        border-radius: var(--kb-radius-lg);
        overflow: hidden;
        margin-top: var(--kb-space-xs);
    }"""))

pairs.append(("""    .post-actions .action-btn {
        color: var(--kb-muted);
        font-size: .9rem;
        border-radius: 0;
        min-height: 48px;
        font-weight: 700;
        letter-spacing: -.14px;
        transition: background-color var(--kb-dur) var(--kb-ease-out), color var(--kb-dur) var(--kb-ease-out);
        white-space: nowrap;
    }

    .post-actions .action-btn:hover {
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
    }

    .post-actions .action-btn.active {
        color: var(--kb-primary);
    }

    .post-actions .action-btn i {
        font-size: 1.05rem;
    }""",
"""    .post-actions .action-btn {
        color: var(--kb-muted);
        font-size: .9rem;
        border-radius: 0;
        min-height: 40px;
        font-weight: 600;
        letter-spacing: -.14px;
        transition: background-color var(--kb-dur) var(--kb-ease-out), color var(--kb-dur) var(--kb-ease-out);
        white-space: nowrap;
    }

    .post-actions .action-btn:hover {
        background-color: transparent;
        color: var(--kb-ink);
    }

    .post-actions .action-btn.active {
        color: var(--kb-ink);
    }

    .post-actions .action-btn i {
        font-size: 1rem;
    }"""))

# ---- Thanh hành động bài viết: bỏ nền xám ----
pairs.append(("""    .post-actions {
        background-color: color-mix(in srgb, var(--kb-surface-soft) 45%, transparent);
    }""",
"""    .post-actions {
        background-color: transparent;
    }"""))

# ---- Composer ----
pairs.append(("""    .composer-input {
        border-color: transparent;
        background-color: var(--kb-canvas);
        resize: vertical;
        min-height: 96px;
        line-height: 1.50;
        font-size: 17px;
    }

    .composer-input:focus {
        border-color: var(--kb-primary);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--kb-primary) 12%, transparent);
    }""",
"""    .composer-input {
        border-color: transparent;
        background-color: var(--kb-canvas);
        resize: vertical;
        min-height: 96px;
        line-height: 1.50;
        font-size: 16px;
    }

    .composer-input:focus {
        border-color: var(--kb-hairline);
        box-shadow: none;
    }"""))

pairs.append(("""    .composer-card .card-body {
        padding: var(--kb-space-xl);
    }""",
"""    .composer-card .card-body {
        padding: var(--kb-space-lg);
    }"""))

# ---- Empty state ----
pairs.append(("""    .empty-state .empty-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        margin-bottom: var(--kb-space-xs);
        border-radius: var(--kb-radius-xl);
        background-color: var(--kb-surface-soft);
        color: var(--kb-muted-soft);
        font-size: 1.75rem;
    }""",
"""    .empty-state .empty-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        margin-bottom: var(--kb-space-xs);
        border-radius: var(--kb-radius-circle);
        background-color: var(--kb-surface-soft);
        color: var(--kb-muted-soft);
        font-size: 1.5rem;
    }"""))

# ---- Chat: bo góc lớn hơn, màu trung tính, bỏ đổ bóng ----
pairs.append(("""    .chat-bubble {
        position: relative;
        background-color: var(--kb-surface-soft);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xl);
        border-bottom-left-radius: var(--kb-radius-xs);
        padding: .6rem .9rem;
        max-width: min(72%, 560px);
        color: var(--kb-body);
        box-shadow: var(--kb-shadow-hover);
        animation: kb-bubble-in var(--kb-dur) var(--kb-ease-out);
    }""",
"""    .chat-bubble {
        position: relative;
        background-color: var(--kb-surface-soft);
        border: 1px solid transparent;
        border-radius: var(--kb-radius-xl);
        border-bottom-left-radius: var(--kb-radius-xs);
        padding: .55rem .85rem;
        max-width: min(72%, 560px);
        color: var(--kb-body);
        box-shadow: none;
        animation: kb-bubble-in var(--kb-dur) var(--kb-ease-out);
    }"""))

pairs.append(("""    .chat-bubble-row.mine .chat-bubble {
        background-color: var(--kb-primary);
        border-color: var(--kb-primary);
        color: var(--kb-primary-contrast);
        border-bottom-left-radius: var(--kb-radius-lg);
        border-bottom-right-radius: var(--kb-radius-xs);
        box-shadow: 0 1px 2px color-mix(in srgb, var(--kb-primary) 40%, transparent);
    }""",
"""    .chat-bubble-row.mine .chat-bubble {
        background-color: var(--kb-ink);
        border-color: var(--kb-ink);
        color: var(--kb-canvas);
        border-bottom-left-radius: var(--kb-radius-lg);
        border-bottom-right-radius: var(--kb-radius-xs);
        box-shadow: none;
    }"""))

# ---- Notification: bỏ nền xanh ----
pairs.append(("""    .notification-item.unread {
        background-color: var(--kb-primary-soft);
    }""",
"""    .notification-item.unread {
        background-color: transparent;
        font-weight: 600;
    }"""))

pairs.append(("""    .notification-item.unread .notification-icon {
        background-color: var(--kb-primary);
        color: var(--kb-primary-contrast);
    }""",
"""    .notification-item.unread .notification-icon {
        background-color: var(--kb-ink);
        color: var(--kb-canvas);
    }"""))

pairs.append(("""    .unread-dot {
        width: 8px;
        height: 8px;
        flex-shrink: 0;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-primary);
    }""",
"""    .unread-dot {
        width: 8px;
        height: 8px;
        flex-shrink: 0;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-ink);
    }"""))

# ---- Badge dot ----
pairs.append(("""    .badge-dot {
        position: absolute;
        top: .1rem;
        right: .1rem;
        min-width: 1.05rem;
        height: 1.05rem;
        padding: 0 .25rem;
        font-size: .65rem;
        line-height: 1.05rem;
        font-weight: 700;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-error);
        color: #fff;
        text-align: center;
    }""",
"""    .badge-dot {
        position: absolute;
        top: .1rem;
        right: .1rem;
        min-width: 1.05rem;
        height: 1.05rem;
        padding: 0 .25rem;
        font-size: .65rem;
        line-height: 1.05rem;
        font-weight: 700;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-ink);
        color: var(--kb-canvas);
        text-align: center;
    }"""))

# ---- Mobile nav: nền đặc, không blur ----
pairs.append(("""    .mobile-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: color-mix(in srgb, var(--kb-canvas) 90%, transparent);
        backdrop-filter: saturate(180%) blur(16px);
        -webkit-backdrop-filter: saturate(180%) blur(16px);
        border-top: 1px solid var(--kb-hairline-soft);""",
"""    .mobile-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: var(--kb-canvas);
        border-top: 1px solid var(--kb-hairline-soft);"""))

pairs.append(("""    .mobile-nav a i {
        font-size: 1.2rem;
    }""",
"""    .mobile-nav a i {
        font-size: 1.25rem;
    }"""))

pairs.append(("""    .mobile-nav a.active::before {
        content: "";
        position: absolute;
        top: -.4rem;
        left: 50%;
        transform: translateX(-50%);
        width: 18px;
        height: 3px;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-primary);
    }""",
"""    .mobile-nav a.active::before {
        content: "";
        position: absolute;
        top: -.4rem;
        left: 50%;
        transform: translateX(-50%);
        width: 16px;
        height: 2px;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-ink);
    }"""))

# ---- Footer: nền trung tính ----
pairs.append(("""    .site-footer {
        margin-top: var(--kb-space-section);
        padding: var(--kb-space-xxl) 0 var(--kb-space-xl);
        background-color: var(--kb-surface-dark);
        color: var(--kb-on-dark-soft);
        font-size: 14px;
    }""",
"""    .site-footer {
        margin-top: var(--kb-space-section);
        padding: var(--kb-space-xl) 0 var(--kb-space-lg);
        background-color: var(--kb-surface-soft);
        color: var(--kb-muted);
        font-size: 13.5px;
        border-top: 1px solid var(--kb-hairline-soft);
    }"""))

pairs.append(("""    .site-footer .brand {
        color: var(--kb-on-dark);
    }

    .site-footer h4 {
        color: var(--kb-on-dark);
        font-family: var(--kb-font-body);
        font-weight: 700;
        font-size: 13px;
        letter-spacing: 0;
        text-transform: none;
        margin-bottom: var(--kb-space-sm);
    }

    .site-footer a {
        color: var(--kb-on-dark-soft);
        text-decoration: none;
    }

    .site-footer a:hover {
        color: var(--kb-on-dark);
        text-decoration: underline;
    }""",
"""    .site-footer .brand {
        color: var(--kb-ink);
    }

    .site-footer h4 {
        color: var(--kb-ink);
        font-family: var(--kb-font-body);
        font-weight: 700;
        font-size: 13px;
        letter-spacing: 0;
        text-transform: none;
        margin-bottom: var(--kb-space-xs);
    }

    .site-footer a {
        color: var(--kb-muted);
        text-decoration: none;
    }

    .site-footer a:hover {
        color: var(--kb-ink);
        text-decoration: underline;
    }"""))

pairs.append(("""    .site-footer hr {
        border-color: rgba(255, 255, 255, .14);
        margin: var(--kb-space-xl) 0 var(--kb-space-lg);
    }""",
"""    .site-footer hr {
        border-color: var(--kb-hairline);
        margin: var(--kb-space-lg) 0;
    }"""))

# ---- Auth: tiêu đề gọn hơn ----
pairs.append(("""    .auth-title h1 {
        font-size: 2.25rem;
        margin-bottom: var(--kb-space-xs);
    }""",
"""    .auth-title h1 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: var(--kb-space-xs);
    }"""))

pairs.append(("""    .auth-panel-title {
        margin: 0;
        font-family: var(--kb-font-display);
        font-weight: 500;
        font-size: 2.25rem;
        line-height: 1.17;
        letter-spacing: -.02em;
        color: var(--kb-on-dark);
    }""",
"""    .auth-panel-title {
        margin: 0;
        font-family: var(--kb-font-display);
        font-weight: 700;
        font-size: 2rem;
        line-height: 1.2;
        letter-spacing: -.02em;
        color: var(--kb-on-dark);
    }"""))

pairs.append(("""    .auth-panel-list i {
        color: var(--kb-primary-soft);
        font-size: 1.05rem;
    }""",
"""    .auth-panel-list i {
        color: var(--kb-on-dark);
        font-size: 1.05rem;
    }"""))

# ---- Share modal: bo góc 16, bỏ đổ bóng nặng ----
pairs.append(("""        background-color: var(--kb-canvas);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xxxl);
        box-shadow: 0 24px 60px rgba(20, 22, 26, .3);
        animation: share-modal-in var(--kb-dur) var(--kb-ease-out);
    }""",
"""        background-color: var(--kb-canvas);
        border: 1px solid var(--kb-hairline);
        border-radius: var(--kb-radius-xl);
        box-shadow: var(--kb-shadow-pop);
        animation: share-modal-in var(--kb-dur) var(--kb-ease-out);
    }"""))

# ---- Confirm modal ----
pairs.append(("""    background-color: var(--kb-canvas);
    border: 1px solid var(--kb-hairline-soft);
    border-radius: var(--kb-radius-xxxl);
    box-shadow: 0 24px 60px rgba(20, 22, 26, .3);
    animation: kb-confirm-in var(--kb-dur) var(--kb-ease-out);
}""",
"""    background-color: var(--kb-canvas);
    border: 1px solid var(--kb-hairline);
    border-radius: var(--kb-radius-xl);
    box-shadow: var(--kb-shadow-pop);
    animation: kb-confirm-in var(--kb-dur) var(--kb-ease-out);
}"""))

# ---- Leave modal ----
pairs.append(("""        background-color: var(--kb-canvas);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xxxl);
        box-shadow: 0 24px 60px rgba(20, 22, 26, .28);
        animation: leave-modal-in .22s var(--kb-ease-out);
    }""",
"""        background-color: var(--kb-canvas);
        border: 1px solid var(--kb-hairline);
        border-radius: var(--kb-radius-xl);
        box-shadow: var(--kb-shadow-pop);
        animation: leave-modal-in .22s var(--kb-ease-out);
    }"""))

# ---- Post detail: bo góc nhỏ hơn, action bar gọn ----
pairs.append(("""    .post-detail-head {
        display: flex;
        align-items: center;
        gap: var(--kb-space-md);
        padding: var(--kb-space-lg);
        border-bottom: 1px solid var(--kb-hairline-soft);
        background: var(--kb-surface-soft);
    }""",
"""    .post-detail-head {
        display: flex;
        align-items: center;
        gap: var(--kb-space-sm);
        padding: var(--kb-space-md) var(--kb-space-lg);
        border-bottom: 1px solid var(--kb-hairline-soft);
        background: var(--kb-surface-card);
    }"""))

pairs.append(("""    .post-detail-text {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 17px;
        line-height: 1.62;
        color: var(--kb-body-strong);
        letter-spacing: -.16px;
    }""",
"""    .post-detail-text {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 16px;
        line-height: 1.6;
        color: var(--kb-body);
        letter-spacing: -.1px;
    }"""))

pairs.append(("""    .post-detail-actions {
        display: flex;
        border-top: 1px solid var(--kb-hairline-soft);
        background-color: color-mix(in srgb, var(--kb-surface-soft) 45%, transparent);
    }""",
"""    .post-detail-actions {
        display: flex;
        border-top: 1px solid var(--kb-hairline-soft);
        background-color: transparent;
    }"""))

pairs.append(("""    .post-detail-action {
        flex: 1 1 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        min-height: 52px;
        padding: 0 var(--kb-space-xs);
        border: 0;
        background: none;
        font-family: var(--kb-font-body);
        font-size: 14px;
        font-weight: 700;
        letter-spacing: -.14px;
        color: var(--kb-muted);
        cursor: pointer;
        white-space: nowrap;
        transition: background-color var(--kb-dur) var(--kb-ease-out), color var(--kb-dur) var(--kb-ease-out);
    }

    .post-detail-action + .post-detail-action {
        border-left: 1px solid var(--kb-hairline-soft);
    }

    .post-detail-action:hover {
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
    }

    .post-detail-action.active {
        color: var(--kb-primary);
    }""",
"""    .post-detail-action {
        flex: 1 1 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        min-height: 44px;
        padding: 0 var(--kb-space-xs);
        border: 0;
        background: none;
        font-family: var(--kb-font-body);
        font-size: 13.5px;
        font-weight: 600;
        letter-spacing: -.14px;
        color: var(--kb-muted);
        cursor: pointer;
        white-space: nowrap;
        transition: background-color var(--kb-dur) var(--kb-ease-out), color var(--kb-dur) var(--kb-ease-out);
    }

    .post-detail-action + .post-detail-action {
        border-left: 0;
    }

    .post-detail-action:hover {
        background-color: transparent;
        color: var(--kb-ink);
    }

    .post-detail-action.active {
        color: var(--kb-ink);
    }"""))

pairs.append(("""    .post-detail-composer {
        display: flex;
        gap: var(--kb-space-sm);
        padding: var(--kb-space-md) var(--kb-space-lg);
        border-bottom: 1px solid var(--kb-hairline-soft);
        background-color: color-mix(in srgb, var(--kb-surface-soft) 45%, transparent);
    }""",
"""    .post-detail-composer {
        display: flex;
        gap: var(--kb-space-sm);
        padding: var(--kb-space-md) var(--kb-space-lg);
        border-bottom: 1px solid var(--kb-hairline-soft);
        background-color: transparent;
    }"""))

pairs.append(("""    .post-detail-image {
        position: relative;
        display: block;
        padding: 0;
        border: 0;
        background: none;
        cursor: zoom-in;
        overflow: hidden;
        border-radius: var(--kb-radius-xl);
        line-height: 0;
        min-height: 120px;
    }""",
"""    .post-detail-image {
        position: relative;
        display: block;
        padding: 0;
        border: 0;
        background: none;
        cursor: zoom-in;
        overflow: hidden;
        border-radius: var(--kb-radius-lg);
        line-height: 0;
        min-height: 120px;
    }"""))

# ---- Privacy picker: chọn bằng nền đen ----
pairs.append(("""    .privacy-option.is-active .privacy-option-icon {
        background-color: var(--kb-primary);
        color: var(--kb-primary-contrast);
    }""",
"""    .privacy-option.is-active .privacy-option-icon {
        background-color: var(--kb-ink);
        color: var(--kb-canvas);
    }"""))

# ---- Share privacy option active ----
pairs.append(("""    .share-privacy-option.is-active {
        border: 2px solid var(--kb-primary);
        padding: calc(var(--kb-space-sm) - 1px) calc(var(--kb-space-md) - 1px);
        background-color: var(--kb-primary-soft);
        color: var(--kb-primary);
    }

    .share-privacy-option.is-active i {
        background-color: var(--kb-primary);
        color: var(--kb-primary-contrast);
    }""",
"""    .share-privacy-option.is-active {
        border: 1px solid var(--kb-ink);
        padding: var(--kb-space-sm) var(--kb-space-md);
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
    }

    .share-privacy-option.is-active i {
        background-color: var(--kb-ink);
        color: var(--kb-canvas);
    }"""))

pairs.append(("""    .share-privacy-option.is-active .share-privacy-desc {
        color: var(--kb-primary);
    }""",
"""    .share-privacy-option.is-active .share-privacy-desc {
        color: var(--kb-muted);
    }"""))

pairs.append(("""    .share-modal-preview-icon {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: var(--kb-radius-lg);
        background-color: var(--kb-primary-soft);
        color: var(--kb-primary);
        font-size: 1rem;
    }""",
"""    .share-modal-preview-icon {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: var(--kb-radius-lg);
        background-color: var(--kb-surface-soft);
        color: var(--kb-muted);
        font-size: 1rem;
    }"""))

# ---- Settings: sạch hơn ----
pairs.append(("""    .settings-title {""", """    .settings-title {"""))

# ---- Chat filter tabs: bo góc, không đổ bóng ----
pairs.append(("""    .chat-filter-tabs button.active {
        background-color: var(--kb-surface-card);
        color: var(--kb-ink);
        box-shadow: var(--kb-shadow-hover);
    }""",
"""    .chat-filter-tabs button.active {
        background-color: var(--kb-surface-card);
        color: var(--kb-ink);
        box-shadow: none;
    }"""))

# ---- Chat conversation active: gạch đen ----
pairs.append(("""    .chat-conversation.active {
        background-color: var(--kb-primary-soft);
        color: var(--kb-ink);
    }""",
"""    .chat-conversation.active {
        background-color: var(--kb-surface-soft);
        color: var(--kb-ink);
    }"""))

pairs.append(("""        width: 3px;
        height: 24px;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-primary);
    }""",
"""        width: 2px;
        height: 24px;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-ink);
    }"""))

pairs.append(("""    .chat-conversation.unread .chat-conversation-time {
        color: var(--kb-primary);
        font-weight: 600;
    }""",
"""    .chat-conversation.unread .chat-conversation-time {
        color: var(--kb-ink);
        font-weight: 600;
    }"""))

pairs.append(("""    .chat-unread {
        flex-shrink: 0;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-primary);
        color: var(--kb-primary-contrast);""",
"""    .chat-unread {
        flex-shrink: 0;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: var(--kb-radius-pill);
        background-color: var(--kb-ink);
        color: var(--kb-canvas);"""))

for old, new in pairs:
    s = rep(s, old, new)

write(s)
print('components done; missing %d' % len(missing))
for m in missing:
    print('---')
    print(m)
