<?php use TrailSense\Core\Html; $f = $filters; ?>
<h1>Find a trail</h1>
<form class="filters card" method="get" action="<?= e(url('trails')) ?>" role="search">
    <input type="hidden" name="r" value="trails">
    <div class="field-grow">
        <label for="q">Name or region</label>
        <input id="q" name="q" type="search" placeholder="e.g. Gerês or PR2" value="<?= e($f['q']) ?>">
    </div>
    <div>
        <label for="difficulty">Difficulty</label>
        <select id="difficulty" name="difficulty">
            <option value="">Any</option>
            <?php foreach (['easy', 'moderate', 'hard'] as $d): ?>
                <option value="<?= e($d) ?>"<?= $f['difficulty'] === $d ? ' selected' : '' ?>><?= e(ucfirst($d)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="max_km">Max distance</label>
        <select id="max_km" name="max_km">
            <option value="">Any</option>
            <?php foreach ([5, 8, 10, 15] as $km): ?>
                <option value="<?= $km ?>"<?= $f['max_km'] == $km ? ' selected' : '' ?>>≤ <?= $km ?> km</option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-primary" type="submit">Search</button>
</form>

<p class="muted" aria-live="polite"><?= count($trails) ?> trail<?= count($trails) === 1 ? '' : 's' ?></p>
<?php if ($trails === []): ?>
    <div class="card empty-inline">
        <p>No trails match these filters.</p>
        <a class="btn btn-secondary" href="<?= e(url('trails')) ?>">Clear filters</a>
    </div>
<?php else: ?>
    <ul class="trail-list">
        <?php foreach ($trails as $t): ?>
            <li><a class="trail-card" href="<?= e(url('trail', ['id' => $t['id']])) ?>">
                <?php if (!empty($t['thumb_url'])): ?>
                    <img class="trail-photo" src="<?= e($t['thumb_url']) ?>" alt="<?= e($t['thumb_alt']) ?>" loading="lazy">
                <?php else: ?>
                    <?= Html::route($t['route_points'], 'Route of ' . $t['name'], 'route route-thumb') ?>
                <?php endif; ?>
                <div>
                    <p class="title"><?= e($t['name']) ?><?php if ($t['ref'] !== ''): ?> <span class="ref small"><?= e($t['ref']) ?></span><?php endif; ?></p>
                    <p class="muted"><?= e($t['region']) ?></p>
                    <p class="stats"><span><?= e(Html::km($t['distance_km'])) ?></span><span><?= e(Html::duration((int) $t['est_duration_min'])) ?></span><span>↑ <?= e($t['elevation_gain_m']) ?> m</span><span><?= Html::isTrue($t['is_loop']) ? 'Loop' : 'One way' ?></span></p>
                    <?= Html::difficulty($t['difficulty']) ?>
                </div>
            </a></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
