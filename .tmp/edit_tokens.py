import io, re, sys

PATH = 'assets/css/app.css'

def read():
    with io.open(PATH, 'r', encoding='utf-8', newline='') as f:
        return f.read()

def write(s):
    with io.open(PATH, 'w', encoding='utf-8', newline='') as f:
        f.write(s)

def rep(s, old, new, count=1):
    old = old.replace('\n', '\r\n')
    new = new.replace('\n', '\r\n')
    if old not in s:
        raise SystemExit('NOT FOUND:\n' + old[:400])
    return s.replace(old, new, count)

s = read()

# ---------------- 1. Header + tokens ----------------
old_head = """/* =============================================================
   Koobecaf — hệ thống giao diện (Meta design system)
   ------------------------------------------------------------
   Nguồn: .cursor/DESIGN.md
   - Canvas trắng, viền hairline, nâng cấp phẳng (không đổ bóng nặng)
   - Nút pill (100px), thẻ bo góc 32px (xxxl) / 24px (xxl) / 16px (xl)
   - Cobalt (#0064e0) chỉ dùng cho CTA giao dịch; CTA tiếp thị dùng đen
   - Chữ Optimistic VF (fallback Montserrat) cho cả display lẫn body
   ============================================================= */"""

new_head = """/* =============================================================
   Koobecaf — hệ thống giao diện (phong cách Threads)
   ------------------------------------------------------------
   - Trung tính: đen / trắng / xám, không màu nhấn mạnh
   - Nền phẳng, viền hairline 1px, hầu như không đổ bóng
   - Nút bo tròn pill, thẻ bo góc 12–20px
   - Chữ sans hiện đại, tiêu đề gọn, nội dung là trọng tâm
   ============================================================= */"""

s = rep(s, old_head, new_head)

# Token block: replace individual declarations
pairs = [
    # light primary
    ("""        /* ---- Màu thương hiệu: cobalt ---- */
        --kb-primary: #0064e0;
        --kb-primary-active: #0457cb;
        --kb-primary-soft: #e7f0fd;
        --kb-primary-mint: #0091ff;
        --kb-primary-contrast: #ffffff;""",
     """        /* ---- Màu nhấn: đen (CTA chủ đạo) ---- */
        --kb-primary: #000000;
        --kb-primary-active: #3a3a3a;
        --kb-primary-soft: #f2f2f2;
        --kb-primary-mint: #000000;
        --kb-primary-contrast: #ffffff;"""),
    # light surfaces
    ("""        --kb-canvas: #ffffff;
        --kb-surface-soft: #f1f4f7;
        --kb-surface-card: #ffffff;
        --kb-surface-cream-strong: #e6ebf1;
        --kb-surface-dark: #0a1317;
        --kb-surface-dark-elevated: #1c1e21;
        --kb-surface-dark-soft: #141a1e;""",
     """        --kb-canvas: #ffffff;
        --kb-surface-soft: #f5f5f5;
        --kb-surface-card: #ffffff;
        --kb-surface-cream-strong: #ebebeb;
        --kb-surface-dark: #171717;
        --kb-surface-dark-elevated: #262626;
        --kb-surface-dark-soft: #1c1c1c;"""),
    # light text
    ("""        --kb-ink: #0a1317;
        --kb-body-strong: #1c1e21;
        --kb-body: #1c1e21;
        --kb-muted: #5d6c7b;
        --kb-muted-soft: #8595a4;
        --kb-on-dark: #ffffff;
        --kb-on-dark-soft: #b0b3b8;""",
     """        --kb-ink: #000000;
        --kb-body-strong: #1f1f1f;
        --kb-body: #262626;
        --kb-muted: #737373;
        --kb-muted-soft: #8e8e8e;
        --kb-on-dark: #ffffff;
        --kb-on-dark-soft: #a8a8a8;"""),
    # light hairline
    ("""        --kb-hairline: #ced0d4;
        --kb-hairline-soft: #dee3e9;""",
     """        --kb-hairline: #dbdbdb;
        --kb-hairline-soft: #efefef;"""),
    # light semantic
    ("""        --kb-success: #31a24c;
        --kb-success-soft: #e7f5ec;
        --kb-warning: #a16207;
        --kb-warning-soft: #fff6e0;
        --kb-error: #d81e37;
        --kb-error-soft: #fdecef;""",
     """        --kb-success: #2f9e44;
        --kb-success-soft: #e8f5ec;
        --kb-warning: #b45309;
        --kb-warning-soft: #fdf3e3;
        --kb-error: #e02424;
        --kb-error-soft: #fdecec;

        /* ---- Focus ring ---- */
        --kb-focus: #1876f2;"""),
    # typography
    ("""        --kb-font-display: "Optimistic VF", "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        --kb-font-body: "Optimistic VF", "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;""",
     """        --kb-font-display: "Optimistic VF", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        --kb-font-body: "Optimistic VF", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;"""),
    # radius
    ("""        --kb-radius-xl: 16px;
        --kb-radius-xxl: 24px;
        --kb-radius-xxxl: 32px;
        --kb-radius-pill: 100px;""",
     """        --kb-radius-xl: 12px;
        --kb-radius-xxl: 16px;
        --kb-radius-xxxl: 20px;
        --kb-radius-pill: 100px;"""),
    # shadows
    ("""        --kb-shadow-hover: 0 1px 3px rgba(20, 22, 26, .16);
        --kb-shadow-pop: rgba(20, 22, 26, .3) 0px 1px 4px 0px;
        --kb-shadow-raised: rgba(20, 22, 26, .2) 1px 1px 0px 0px;
        --kb-shadow-card: rgba(20, 22, 26, .3) 0px 1px 4px 0px;""",
     """        --kb-shadow-hover: 0 1px 2px rgba(0, 0, 0, .08);
        --kb-shadow-pop: 0 8px 24px rgba(0, 0, 0, .12);
        --kb-shadow-raised: 0 1px 2px rgba(0, 0, 0, .08);
        --kb-shadow-card: 0 1px 2px rgba(0, 0, 0, .06);"""),
    # layout metrics
    ("""        --kb-topbar-h-sm: 56px;""", """        --kb-topbar-h-sm: 52px;"""),
    ("""        --kb-shell-max: 1280px;
        --kb-rail-left: 244px;
        --kb-rail-right: 320px;
        --kb-topbar-h: 64px;""",
     """        --kb-shell-max: 1200px;
        --kb-rail-left: 220px;
        --kb-rail-right: 300px;
        --kb-topbar-h: 60px;"""),
    # dark primary
    ("""        --kb-primary: #4a9eff;
        --kb-primary-active: #1a7fe8;
        --kb-primary-soft: #16273a;
        --kb-primary-mint: #6bc8ff;
        --kb-primary-contrast: #06121f;""",
     """        --kb-primary: #ffffff;
        --kb-primary-active: #d4d4d4;
        --kb-primary-soft: #262626;
        --kb-primary-mint: #ffffff;
        --kb-primary-contrast: #000000;"""),
    # dark surfaces
    ("""        --kb-canvas: #181a1b;
        --kb-surface-soft: #242527;
        --kb-surface-card: #242527;
        --kb-surface-cream-strong: #33363a;
        --kb-surface-dark: #0e0f10;
        --kb-surface-dark-elevated: #242527;
        --kb-surface-dark-soft: #1c1d1f;""",
     """        --kb-canvas: #0a0a0a;
        --kb-surface-soft: #1f1f1f;
        --kb-surface-card: #141414;
        --kb-surface-cream-strong: #2f2f2f;
        --kb-surface-dark: #000000;
        --kb-surface-dark-elevated: #1f1f1f;
        --kb-surface-dark-soft: #161616;"""),
    # dark text
    ("""        --kb-ink: #f2f4f7;
        --kb-body-strong: #e4e6eb;
        --kb-body: #d0d4da;
        --kb-muted: #9aa2ab;
        --kb-muted-soft: #79818a;
        --kb-on-dark: #f2f4f7;
        --kb-on-dark-soft: #9aa2ab;""",
     """        --kb-ink: #f5f5f5;
        --kb-body-strong: #ededed;
        --kb-body: #dcdcdc;
        --kb-muted: #a1a1a1;
        --kb-muted-soft: #8b8b8b;
        --kb-on-dark: #f5f5f5;
        --kb-on-dark-soft: #a1a1a1;"""),
    # dark hairline
    ("""        --kb-hairline: #3a3c3e;
        --kb-hairline-soft: #303234;""",
     """        --kb-hairline: #333333;
        --kb-hairline-soft: #262626;"""),
    # dark semantic
    ("""        --kb-success: #4caf50;
        --kb-success-soft: #16301f;
        --kb-warning: #f7b928;
        --kb-warning-soft: #33280f;
        --kb-error: #f0284a;
        --kb-error-soft: #351b1a;""",
     """        --kb-success: #4ade80;
        --kb-success-soft: #14281c;
        --kb-warning: #fbbf24;
        --kb-warning-soft: #2b2213;
        --kb-error: #f87171;
        --kb-error-soft: #2c1616;

        --kb-focus: #4a9eff;"""),
    # dark shadows
    ("""        --kb-shadow-hover: 0 1px 3px rgba(0, 0, 0, .4);
        --kb-shadow-pop: rgba(0, 0, 0, .55) 0px 8px 24px 0px;
        --kb-shadow-raised: rgba(0, 0, 0, .4) 1px 1px 0px 0px;
        --kb-shadow-card: rgba(0, 0, 0, .4) 0px 1px 4px 0px;""",
     """        --kb-shadow-hover: 0 1px 2px rgba(0, 0, 0, .5);
        --kb-shadow-pop: 0 8px 24px rgba(0, 0, 0, .6);
        --kb-shadow-raised: 0 1px 2px rgba(0, 0, 0, .5);
        --kb-shadow-card: 0 1px 2px rgba(0, 0, 0, .4);"""),
]

for old, new in pairs:
    s = rep(s, old, new)

write(s)
print('tokens ok')
