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

sub('app/Views/feed/index.php', [
    ('<div class="card surface-card welcome-card mb-3">\n        <div class="card-body">',
     '<div class="panel welcome-card mb-3">\n        <div class="panel-body">'),
    ('<div class="card surface-card">\n        <div class="empty-state">',
     '<div class="panel">\n        <div class="empty-state">'),
])
