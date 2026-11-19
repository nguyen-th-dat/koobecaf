import io

PATH = 'assets/css/app.css'

with io.open(PATH, 'r', encoding='utf-8', newline='') as f:
    s = f.read()

pairs = []

pairs.append(("""    .auth-title {
        margin-bottom: var(--kb-space-lg);
    }""",
"""    /* Biểu mẫu đăng nhập/đăng ký: không viền, không bóng — chỉ khoảng trắng */
    .auth-card {
        background-color: var(--kb-surface-card);
        border: 1px solid var(--kb-hairline-soft);
        border-radius: var(--kb-radius-xxl);
        padding: var(--kb-space-xl);
    }

    .auth-title {
        margin-bottom: var(--kb-space-lg);
    }"""))

pairs.append(("""    /* ---------------- Điều hướng di động ---------------- */""",
"""    /* ---------------- Trang lỗi ---------------- */
    .error-page {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: var(--kb-space-xs);
        padding: var(--kb-space-xxxl) var(--kb-space-lg);
    }

    .error-code {
        font-family: var(--kb-font-display);
        font-size: 4rem;
        font-weight: 700;
        line-height: 1;
        letter-spacing: -.04em;
        color: var(--kb-ink);
    }

    .error-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }

    /* ---------------- Điều hướng di động ---------------- */"""))

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

print('auth + error css ok')
