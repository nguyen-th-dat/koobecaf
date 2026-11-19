<?php
/**
 * @var array $requests
 */
?>
<div class="page-head">
    <h1>Lời mời kết bạn</h1>
    <a class="btn btn-outline btn-sm" href="<?= url('/friends') ?>"><i class="bi bi-arrow-left"></i> Bạn bè</a>
</div>

<div class="panel">
    <div class="panel-body">
        <?php if ($requests === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-inbox"></i></span>
                <h3>Không có lời mời kết bạn nào</h3>
                <p>Khi có người gửi lời mời, bạn sẽ nhận được thông báo ở đây.</p>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <?php
                $actions = '<form action="' . url('/friends/' . (int) $request['id'] . '/accept') . '" method="post" class="d-inline">'
                    . \App\Core\Csrf::field()
                    . '<button class="btn btn-solid btn-sm" type="submit">Chấp nhận</button></form>'
                    . '<form action="' . url('/friends/' . (int) $request['id'] . '/decline') . '" method="post" class="d-inline">'
                    . \App\Core\Csrf::field()
                    . '<button class="btn btn-outline btn-sm" type="submit">Từ chối</button></form>';
                ?>
                <?= view_partial(dirname(__DIR__) . '/partials/person-row.php', ['person' => $request, 'actionHtml' => $actions]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
