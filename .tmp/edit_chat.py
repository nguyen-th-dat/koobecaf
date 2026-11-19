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

sub('app/Views/chat/index.php', [
    ('<aside class="chat-sidebar card surface-card">', '<aside class="chat-sidebar panel">'),
    ('<section class="chat-main card surface-card">\n        <div class="card-body d-flex flex-column justify-content-center h-100">',
     '<section class="chat-main panel">\n        <div class="panel-body d-flex flex-column justify-content-center h-100">'),
    ('<div class="search-results card surface-card d-none" id="chatSearchResults" role="listbox"></div>',
     '<div class="search-results panel d-none" id="chatSearchResults" role="listbox"></div>'),
    ('<span class="badge rounded-pill bg-primary d-none" data-chat-unread-total>',
     '<span class="badge rounded-pill d-none" data-chat-unread-total>'),
])

sub('app/Views/chat/show.php', [
    ('<aside class="chat-sidebar card surface-card">', '<aside class="chat-sidebar panel">'),
    ('<section class="chat-main card surface-card">', '<section class="chat-main panel">'),
])
