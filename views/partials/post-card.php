<?php use TrailSense\Core\Html; /** @var array $post */ ?>
<article class="post-card">
    <a class="post-media" href="<?= e(url('activity', ['id' => $post['id']])) ?>" tabindex="-1" aria-hidden="true">
        <?php if ($post['photo']): ?>
            <img src="<?= e(upload_url($post['photo'])) ?>" alt="" loading="lazy">
        <?php else: ?>
            <?= Html::route($post['route_points'], 'Route of ' . $post['trail_name']) ?>
        <?php endif; ?>
    </a>
    <div class="post-body">
        <p class="post-meta"><span class="avatar avatar-sm" aria-hidden="true"><?= Html::initial($post['author']) ?></span>
            <span><strong><?= e($post['author']) ?></strong><?= $post['author_role'] === 'organizer' ? ' <span class="tag tag-sm">Organizer</span>' : '' ?>
            · <?= e(Html::date($post['hiked_on'])) ?></span></p>
        <h3><a href="<?= e(url('activity', ['id' => $post['id']])) ?>"><?= e($post['trail_name']) ?></a></h3>
        <p class="stats"><span><?= e(Html::km($post['distance_km'])) ?></span><span><?= e(Html::duration((int) $post['duration_min'])) ?></span><span><?= e($post['region']) ?></span></p>
        <?php if ($post['notes'] !== ''): ?>
            <p class="excerpt"><?= e(mb_strimwidth($post['notes'], 0, 140, '…')) ?></p>
        <?php endif; ?>
    </div>
</article>
