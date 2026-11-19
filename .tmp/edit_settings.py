import io

def read(p):
    with io.open(p, 'r', encoding='utf-8', newline='') as f:
        return f.read()

def write(p, s):
    with io.open(p, 'w', encoding='utf-8', newline='') as f:
        f.write(s)

def sub(p, pairs):
    s = read(p)
    for old, new in pairs:
        o = old.replace('\n', '\r\n')
        n = new.replace('\n', '\r\n')
        if o in s:
            s = s.replace(o, n, 1)
        elif n in s:
            continue
        else:
            raise SystemExit('NOT FOUND in %s: %r' % (p, old[:120]))
    write(p, s)
    print('ok', p)

sub('app/Views/profile/edit.php', [
    ('<div class="card surface-card settings-card mb-3">\n    <div class="card-body">',
     '<div class="panel settings-card mb-3">\n    <div class="panel-body">'),
    ('<h2 class="settings-card-title"><i class="bi bi-person-badge me-2"></i>Thông tin cá nhân</h2>',
     '<h2 class="settings-card-title">Thông tin cá nhân</h2>'),
    ('<h2 class="settings-card-title"><i class="bi bi-key me-2"></i>Đổi mật khẩu</h2>',
     '<h2 class="settings-card-title">Đổi mật khẩu</h2>'),
    ('<h2 class="settings-card-title"><i class="bi bi-palette me-2"></i>Giao diện</h2>',
     '<h2 class="settings-card-title">Giao diện</h2>'),
    ('<button class="btn btn-outline btn-primary" type="submit"><i class="bi bi-shield-lock me-1"></i>Đổi mật khẩu</button>',
     '<button class="btn btn-solid" type="submit">Đổi mật khẩu</button>'),
    ('<button class="btn btn-solid" type="submit"><i class="bi bi-check-lg me-1"></i>Lưu thay đổi</button>',
     '<button class="btn btn-solid" type="submit">Lưu thay đổi</button>'),
])
