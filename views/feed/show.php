<?php use TrailSense\Core\Html; $p = $post; ?>
<a class="back" href="<?= e(url('feed')) ?>">← Community</a>
<p class="post-meta"><span class="avatar" aria-hidden="true"><?= Html::initial($p['author']) ?></span>
    <span><strong><?= e($p['author']) ?></strong><?= $p['author_role'] === 'organizer' ? ' <span class="tag tag-sm">Organizer</span>' : '' ?><br>
    <span class="muted">hiked on <?= e(Html::date($p['hiked_on'], 'l j F Y')) ?></span></span></p>
<h1><?= e($p['trail_name']) ?></h1>
<p class="muted"><?= e($p['region']) ?> · <?= Html::difficulty($p['difficulty']) ?></p>
<?php if ($p['photo']): ?>
    <figure class="photo"><img src="<?= e(upload_url($p['photo'])) ?>" alt="<?= e($p['photo_alt'] ?: 'Photo from the hike on ' . $p['trail_name']) ?>"></figure>
<?php endif; ?>
<div class="card share-route">
    <?= Html::route($p['route_points'], 'Route of ' . $p['trail_name'], 'route route-large') ?>
    <dl class="stat-grid">
        <div><dt>Distance</dt><dd><?= e(Html::km($p['distance_km'])) ?></dd></div>
        <div><dt>Moving time</dt><dd><?= e(Html::duration((int) $p['duration_min'])) ?></dd></div>
        <div><dt>Avg pace</dt><dd><?php $pace = (int) round($p['duration_min'] / max((float) $p['distance_km'], 0.1)); echo e(intdiv($pace, 60) > 0 ? sprintf('%d:%02d h', intdiv($pace, 60), $pace % 60) : sprintf('%d min', $pace)); ?> /km</dd></div>
        <div><dt>Date</dt><dd><?= e(Html::date($p['hiked_on'], 'j M')) ?></dd></div>
    </dl>
</div>
<?php if ($p['notes'] !== ''): ?><p class="prose"><?= nl2br(e($p['notes'])) ?></p><?php endif; ?>
<a class="btn btn-secondary btn-block" href="<?= e(url('trail', ['id' => $p['trail_id']])) ?>">See this trail</a>
