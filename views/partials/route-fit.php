<?php use TrailSense\Core\ElevationChart; use TrailSense\Domain\Climb; use TrailSense\Domain\RouteType; use TrailSense\Core\Html;
/** AC1.13–1.14 "Is this route for me?" @var array $t trail row (with elevation_profile) */
$type = RouteType::describe((string) $t['ref'], (string) $t['name'], (float) $t['distance_km'], (int) $t['est_duration_min'], Html::isTrue($t['is_loop']));
$c = Climb::analyse($t['elevation_profile'] ?? [], (int) $t['elevation_gain_m'], (float) $t['distance_km']); ?>
<section class="card fit" aria-labelledby="fit-h">
    <h2 id="fit-h">Is this route for me?</h2>
    <div class="fit-grid">
        <div class="fit-item">
            <p class="fit-k">Type of route</p>
            <p class="fit-v"><?= e($type['name']) ?></p>
            <p class="small muted"><?= e($type['plain']) ?></p>
        </div>
        <div class="fit-item">
            <p class="fit-k">Distance and time</p>
            <p class="fit-v"><?= e(Html::km($t['distance_km'])) ?> · <?= e(Html::duration((int) $t['est_duration_min'])) ?></p>
            <p class="small muted"><?= e($type['length']) ?> <?= e($type['time']) ?></p>
        </div>
        <div class="fit-item fit-climb fit-<?= e($c['rating']) ?>">
            <p class="fit-k">Climbing</p>
            <p class="fit-v"><?= e($c['label']) ?> · ↑ <?= e($c['ascent']) ?> m</p>
            <p class="small muted"><?= e($c['advice']) ?></p>
        </div>
    </div>
    <?php if ($t['elevation_profile']): ?>
        <h3 class="risk-h3">Elevation profile</h3>
        <div class="elev-wrap"><?= ElevationChart::svg($t['elevation_profile'], $c['longest'], 'Elevation profile of ' . $t['name'] . ' from ' . $c['start'] . ' m to ' . $c['end'] . ' m, highest point ' . $c['highest'] . ' m') ?></div>
        <ul class="climb-notes">
            <li><strong>About <?= e($c['ascent']) ?> m of climbing</strong> in total (≈ <?= e($c['per_km']) ?> m per km). Starts at <?= e($c['start']) ?> m, highest point <?= e($c['highest']) ?> m, lowest <?= e($c['lowest']) ?> m.</li>
            <?php if ($c['longest']): ?>
                <li><strong>Longest climb:</strong> km <?= e($c['longest']['from_km']) ?> → <?= e($c['longest']['to_km']) ?>, +<?= e($c['longest']['gain']) ?> m (average <?= e($c['longest']['grade']) ?> %).</li>
            <?php endif; ?>
            <?php if ($c['steepest']): ?>
                <li><strong>Steepest part:</strong> around km <?= e($c['steepest']['from_km']) ?>–<?= e($c['steepest']['to_km']) ?>, about <?= e($c['steepest']['grade']) ?> % uphill<?= $c['steepest']['grade'] >= 12 ? ' — slow down and use your hands on rocks if needed' : '' ?>.</li>
            <?php endif; ?>
            <li class="muted small">What the numbers mean: under 5 % feels almost flat, 5–10 % is a steady climb, over 10 % is steep, over 15 % very steep. Profile from the Open-Meteo Elevation API (90 m terrain model) — small bumps are smoothed out.</li>
        </ul>
    <?php endif; ?>
</section>
