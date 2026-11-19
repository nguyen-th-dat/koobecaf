import io, re

def read(p):
    with io.open(p, 'r', encoding='utf-8', newline='') as f:
        return f.read()

def write(p, s):
    with io.open(p, 'w', encoding='utf-8', newline='') as f:
        f.write(s)

def sub(p, pairs, count=1):
    s = read(p)
    for old, new in pairs:
        o = old.replace('\n', '\r\n')
        n = new.replace('\n', '\r\n')
        if o in s:
            s = s.replace(o, n, count)
        elif n in s:
            continue
        else:
            raise SystemExit('NOT FOUND in %s: %r' % (p, old[:120]))
    write(p, s)
    print('ok', p)

# ---- friends/index.php ----
sub('app/Views/friends/index.php', [
    ('<h1><i class="bi bi-people"></i>Bạn bè</h1>', '<h1>Bạn bè</h1>'),
    ('<a class="btn btn-outline btn-primary btn-sm" href="<?= url(\'/friends/requests\') ?>">\n        <i class="bi bi-inbox me-1"></i>Lời mời\n    </a>',
     '<a class="btn btn-outline btn-sm" href="<?= url(\'/friends/requests\') ?>">\n        <i class="bi bi-inbox me-1"></i>Lời mời\n    </a>'),
    ('<div class="card surface-card">\n    <div class="card-body">', '<div class="panel">\n    <div class="panel-body">'),
    ('<a class="btn btn-solid mt-2" href="<?= url(\'/search\') ?>"><i class="bi bi-search me-1"></i>Tìm người dùng</a>',
     '<a class="btn btn-solid mt-2" href="<?= url(\'/search\') ?>"><i class="bi bi-search me-1"></i>Tìm người dùng</a>'),
])

# ---- friends/requests.php ----
sub('app/Views/friends/requests.php', [
    ('<h1><i class="bi bi-inbox"></i>Lời mời kết bạn</h1>', '<h1>Lời mời kết bạn</h1>'),
    ('<div class="card surface-card">\n    <div class="card-body">', '<div class="panel">\n    <div class="panel-body">'),
])

# ---- posts/likes.php ----
sub('app/Views/posts/likes.php', [
    ('<div class="card surface-card">\n    <div class="card-body">', '<div class="panel">\n    <div class="panel-body">'),
])

# ---- search/results.php ----
sub('app/Views/search/results.php', [
    ('''    <h1>
        <i class="bi bi-search"></i>
        <?= $keyword === '' ? 'Tìm kiếm' : 'Kết quả cho "' . e($keyword) . '"' ?>
    </h1>''',
     '''    <h1><?= $keyword === '' ? 'Tìm kiếm' : 'Kết quả cho "' . e($keyword) . '"' ?></h1>'''),
    ('''    <div class="card surface-card">
        <div class="empty-state">
            <span class="empty-icon"><i class="bi bi-search"></i></span>
            <h3>Tìm kiếm trên Koobecaf</h3>
            <p>Nhập tên, username hoặc từ khoá để tìm người dùng và bài viết.</p>
        </div>
    </div>''',
     '''    <div class="panel">
        <div class="empty-state">
            <span class="empty-icon"><i class="bi bi-search"></i></span>
            <h3>Tìm kiếm trên Koobecaf</h3>
            <p>Nhập tên, username hoặc từ khoá để tìm người dùng và bài viết.</p>
        </div>
    </div>'''),
    ('''    <div class="card surface-card mb-3">
        <div class="card-body">
            <div class="rail-title">Người dùng</div>''',
     '''    <div class="panel mb-3">
        <div class="panel-body">
            <div class="rail-title">Người dùng</div>'''),
    ('''    <div class="rail-title mb-2">Bài viết (<?= (int) $totalPosts ?>)</div>

    <?php if ($posts === []): ?>
        <div class="card surface-card">
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-search"></i></span>
                <h3>Không tìm thấy bài viết nào</h3>
                <p>Thử từ khoá khác hoặc tìm theo tên người dùng.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <?php $data = post_view_data($post, $viewerId); ?>
            <?= view_partial(dirname(__DIR__) . '/partials/post-card.php', ['post' => $post, 'data' => $data]) ?>
        <?php endforeach; ?>
''',
     '''    <div class="rail-title mb-2">Bài viết (<?= (int) $totalPosts ?>)</div>

    <?php if ($posts === []): ?>
        <div class="panel">
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-search"></i></span>
                <h3>Không tìm thấy bài viết nào</h3>
                <p>Thử từ khoá khác hoặc tìm theo tên người dùng.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="feed-stream">
            <?php foreach ($posts as $post): ?>
                <?php $data = post_view_data($post, $viewerId); ?>
                <?= view_partial(dirname(__DIR__) . '/partials/post-card.php', ['post' => $post, 'data' => $data]) ?>
            <?php endforeach; ?>
        </div>
'''),
])
