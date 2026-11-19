<?php use TrailSense\Core\Html; /** Compact trail tile for dashboard rows. @var array $t */ ?>
<li><a class="tile-link" href="<?= e(url('trail', ['id' => $t['id']])) ?>">
    <?php if (!empty($t['thumb_url'])): ?><img src="<?= e($t['thumb_url']) ?>" alt="" loading="lazy" data-fallback><?php endif; ?>
    <span><strong><?= e($t['name']) ?></strong><span class="small muted"><?= e(Html::km($t['distance_km'])) ?> · <?= e(Html::duration((int) $t['est_duration_min'])) ?></span></span>
</a></li>
