<?php
/**
 * @var array $friends
 */
?>
<div class="page-head">
    <h1>Bạn bè</h1>
    <a class="btn btn-outline btn-sm" href="<?= url('/friends/requests') ?>">
        <i class="bi bi-inbox me-1"></i>Lời mời
    </a>
</div>

<div class="panel">
    <div class="panel-body">
        <?php if ($friends === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-people"></i></span>
                <h3>Bạn chưa có bạn bè nào</h3>
                <p>Kết nối với những người thú vị qua lời mời hoặc theo dõi họ trên bảng tin.</p>
                <a class="btn btn-solid mt-2" href="<?= url('/search') ?>"><i class="bi bi-search me-1"></i>Tìm người dùng</a>
            </div>
        <?php else: ?>
            <?php foreach ($friends as $person): ?>
                <?php
                $actions = '<a class="btn icon-btn bordered" href="' . url('/chat/start/' . (int) $person['id']) . '" title="Nhắn tin"><i class="bi bi-chat-dots"></i></a>'
                    . '<form action="' . url('/friends/' . (int) $person['id'] . '/remove') . '" method="post" data-confirm="Bạn có chắc muốn hủy kết bạn?" data-confirm-title="Hủy kết bạn" data-confirm-accept="Hủy kết bạn" class="d-inline">'
                    . \App\Core\Csrf::field()
                    . '<button class="btn btn-outline btn-danger btn-sm" type="submit">Hủy bạn</button></form>';
                ?>
                <?= view_partial(dirname(__DIR__) . '/partials/person-row.php', ['person' => $person, 'actionHtml' => $actions]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
