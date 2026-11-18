<?php
$userId = auth_id();
$suggestions = $userId !== null ? \App\Models\User::suggestions($userId, 5) : [];
$following = $userId !== null ? \App\Models\User::followingSuggestions($userId, 5) : [];
?>
<div class="rail-card">
    <div class="rail-title">Gợi ý kết bạn</div>
    <?php if ($suggestions === []): ?>
        <p class="fg-secondary small mb-0">Không có gợi ý mới.</p>
    <?php else: ?>
        <?php foreach ($suggestions as $person): ?>
            <div class="person-row">
                <img src="<?= e(avatar_url($person['avatar_path'])) ?>" alt="" class="avatar avatar-md">
                <div class="person-meta">
                    <a class="person-name" href="<?= url('/u/' . $person['username']) ?>"><?= e($person['display_name']) ?></a>
                    <span class="person-handle">@<?= e($person['username']) ?></span>
                </div>
                <button class="btn btn-outline btn-primary btn-sm follow-btn" data-user-id="<?= (int) $person['id'] ?>">Theo dõi</button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($following !== []): ?>
    <div class="rail-card">
        <div class="rail-title">Đang theo dõi</div>
        <?php foreach ($following as $person): ?>
            <div class="person-row">
                <img src="<?= e(avatar_url($person['avatar_path'])) ?>" alt="" class="avatar avatar-sm">
                <div class="person-meta">
                    <a class="person-name" href="<?= url('/u/' . $person['username']) ?>"><?= e($person['display_name']) ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
