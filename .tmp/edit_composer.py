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

sub('app/Views/posts/create.php', [
    ('<h1><i class="bi bi-pencil-square"></i>Đăng bài viết</h1>', '<h1>Đăng bài viết</h1>'),
    ('<div class="card surface-card composer-card" id="composer">\n    <div class="card-body">',
     '<div class="panel composer-card" id="composer">\n    <div class="panel-body">'),
    ('<article class="card surface-card composer-preview-card">', '<article class="composer-preview-card">'),
    ('''<div class="card surface-card composer-tips mt-3">
    <div class="card-body">
        <h2 class="h5 mb-2"><i class="bi bi-lightbulb me-1"></i>Gợi ý khi viết bài</h2>''',
     '''<div class="panel composer-tips mt-3">
    <div class="panel-body">
        <h2 class="h5 mb-2">Gợi ý khi viết bài</h2>'''),
])

sub('app/Views/posts/edit.php', [
    ('<div class="card surface-card composer-card">\n    <div class="card-body">',
     '<div class="panel composer-card">\n    <div class="panel-body">'),
    ('<article class="card surface-card composer-preview-card">', '<article class="composer-preview-card">'),
])
