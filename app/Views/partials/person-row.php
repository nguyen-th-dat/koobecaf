<?php
/**
 * Hàng thông tin người dùng — dùng chung cho gợi ý, bạn bè, tìm kiếm, người theo dõi.
 *
 * @var array $person
 * @var string|null $actionHtml HTML hành động tuỳ biến (nút hành động ở cuối hàng)
 * @var string $avatarSize
 */
$avatarSize = $avatarSize ?? 'avatar-md';
$actionHtml = $actionHtml ?? '';
?>
<div class="person-row">
    <img src="<?= e(avatar_url($person['avatar_path'])) ?>" alt="" class="avatar <?= e($avatarSize) ?>">
    <div class="person-meta">
        <a class="person-name" href="<?= url('/u/' . $person['username']) ?>"><?= e($person['display_name']) ?></a>
        <span class="person-handle">@<?= e($person['username']) ?></span>
    </div>
    <?= $actionHtml ?>
</div>
