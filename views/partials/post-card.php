<?php use TrailSense\Core\Html; /** @var array $post */ ?>
<article class="post-card">
    <a class="post-media" href="<?= e(url('activity', ['id' => $post['id']])) ?>" tabindex="-1" aria-hidden="true">
        <?php if ($post['photo']): ?>
            <?= Html::cardMedia($post['route_points'], $post['is_loop'] ?? false, $post['trail_name'], photo_url($post['photo']), (string) ($post['photo_alt'] ?? ''), 'photo') ?>
        <?php else: ?>
            <?= Html::cardMedia($post['route_points'], $post['is_loop'] ?? false, $post['trail_name'], $post['trail_thumb'] ?? null) ?>
        <?php endif; ?>
    </a>
    <div class="post-body">
        <p class="post-meta"><span class="avatar avatar-sm" aria-hidden="true"><?= Html::initial($post['author']) ?></span>
            <span><a class="author" href="<?= e(url('profile', ['id' => $post['user_id']])) ?>"><strong><?= e($post['author']) ?></strong></a><?= $post['author_role'] === 'organizer' ? ' <span class="tag tag-sm">Organizer</span>' : '' ?>
            · <?= e(Html::date($post['hiked_on'])) ?></span></p>
        <h3><a href="<?= e(url('activity', ['id' => $post['id']])) ?>"><?= e($post['trail_name']) ?></a></h3>
        <p class="stats"><span><?= e(Html::km($post['distance_km'])) ?></span><span><?= e(Html::duration((int) $post['duration_min'])) ?></span><span><?= e($post['region']) ?></span></p>
        <?php if ($post['notes'] !== ''): ?>
            <p class="excerpt"><?= e(mb_strimwidth($post['notes'], 0, 140, '…')) ?></p>
        <?php endif; ?>
        <p class="counts"><span aria-label="<?= e($post['likes']) ?> likes">♥ <?= e($post['likes']) ?></span> · <a href="<?= e(url('activity', ['id' => $post['id']])) ?>#comments"><?= e($post['comments']) ?> comment<?= (int) $post['comments'] === 1 ? '' : 's' ?></a></p>
    </div>
</article>
