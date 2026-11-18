<?php
/**
 * @var array $friends
 */
?>
<div class="page-head">
    <h1><i class="bi bi-people"></i>Bạn bè</h1>
    <a class="btn btn-outline btn-primary btn-sm" href="<?= url('/friends/requests') ?>">
        <i class="bi bi-inbox me-1"></i>Lời mời
    </a>
</div>

<div class="card surface-card">
    <div class="card-body">
        <?php if ($friends === []): ?>
            <div class="text-center fg-secondary py-4">
                <i class="bi bi-people d-block mb-2 fs-2xl"></i>
                Bạn chưa có bạn bè nào. Hãy kết nối với những người thú vị!
            </div>
        <?php else: ?>
            <?php foreach ($friends as $person): ?>
                <?php
                $actions = '<a class="btn icon-btn bordered" href="' . url('/chat/start/' . (int) $person['id']) . '" title="Nhắn tin"><i class="bi bi-chat-dots"></i></a>'
                    . '<form action="' . url('/friends/' . (int) $person['id'] . '/remove') . '" method="post" data-confirm="Bạn có chắc muốn hủy kết bạn?" class="d-inline">'
                    . \App\Core\Csrf::field()
                    . '<button class="btn btn-outline btn-danger btn-sm" type="submit">Hủy bạn</button></form>';
                ?>
                <?= view_partial(dirname(__DIR__) . '/partials/person-row.php', ['person' => $person, 'actionHtml' => $actions]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
