<?php use TrailSense\Core\Html; $t = $trail; ?>
<a class="back" href="<?= e(url('trails')) ?>">← Trails</a>
<h1><?= e($t['name']) ?></h1>
<p class="muted"><?= e($t['region']) ?> · <?= Html::difficulty($t['difficulty']) ?></p>
<figure class="route-figure">
    <?= Html::route($t['route_points'], 'Route drawing of ' . $t['name'], 'route route-large') ?>
    <figcaption>Simplified route · <a href="https://www.openstreetmap.org/?mlat=<?= e($t['start_lat']) ?>&amp;mlon=<?= e($t['start_lon']) ?>#map=14/<?= e($t['start_lat']) ?>/<?= e($t['start_lon']) ?>" target="_blank" rel="noopener">Open start point in OpenStreetMap</a></figcaption>
</figure>
<dl class="stat-grid">
    <div><dt>Distance</dt><dd><?= e(Html::km($t['distance_km'])) ?></dd></div>
    <div><dt>Est. time</dt><dd><?= e(Html::duration((int) $t['est_duration_min'])) ?></dd></div>
    <div><dt>Elevation gain</dt><dd><?= e($t['elevation_gain_m']) ?> m</dd></div>
    <div><dt>Difficulty</dt><dd><?= e(ucfirst($t['difficulty'])) ?></dd></div>
</dl>
<p class="prose"><?= e($t['description']) ?></p>
<a class="btn btn-primary btn-block sticky-action" href="<?= e(url('plan.new', ['trail_id' => $t['id']])) ?>">Plan this hike</a>
