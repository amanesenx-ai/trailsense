<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; use TrailSense\Core\View;
$t = $trail; $pts = $t['route_points']; $loop = Html::isTrue($t['is_loop']);
$start = $pts[0] ?? [$t['start_lat'], $t['start_lon']]; $end = $pts[count($pts) - 1] ?? $start; ?>
<a class="back" href="<?= e(url('trails')) ?>">← Trails</a>
<header class="trail-head">
    <h1><?= e($t['name']) ?></h1>
    <p class="muted"><?php if ($t['ref'] !== ''): ?><span class="ref"><?= e($t['ref']) ?></span> · <?php endif; ?><?= e($t['region']) ?> · <?= Html::difficulty($t['difficulty']) ?> · <?= $loop ? 'Loop' : 'One way' ?></p>
</header>

<?php if ($photos): ?>
<section class="gallery" aria-label="Photos of the trail">
    <?php foreach ($photos as $i => $ph): ?>
        <figure class="gallery-item<?= $i === 0 ? ' gallery-main' : '' ?>">
            <img src="<?= e($ph['thumb_url']) ?>" alt="<?= e($ph['alt_text']) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>" data-fallback>
            <figcaption><a href="<?= e($ph['source_url']) ?>" target="_blank" rel="noopener"><?= e($ph['author']) ?></a>, <?= e($ph['license']) ?></figcaption>
        </figure>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="card map-card" aria-labelledby="map-h">
    <h2 id="map-h" class="sr-only">Map</h2>
    <?= Html::map($pts, $loop, $t['name']) ?>
    <ul class="map-legend">
        <?php if ($loop): ?>
            <li><span class="dot dot-start" aria-hidden="true"></span> Start and finish</li>
        <?php else: ?>
            <li><span class="dot dot-start" aria-hidden="true"></span> Start</li>
            <li><span class="dot dot-end" aria-hidden="true"></span> End</li>
        <?php endif; ?>
        <li class="muted small">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors</li>
    </ul>
    <div class="map-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('hike', ['id' => $t['id']])) ?>">▶ Start hike (live map &amp; compass)</a>
        <a class="btn btn-secondary btn-sm" href="<?= e(Html::directionsUrl($start)) ?>" target="_blank" rel="noopener">Directions to the start</a>
        <?php if (!empty($t['osm_relation_id'])): ?>
            <a class="btn btn-secondary btn-sm" href="https://www.openstreetmap.org/relation/<?= e($t['osm_relation_id']) ?>" target="_blank" rel="noopener">View route on OpenStreetMap</a>
        <?php endif; ?>
    </div>
</section>

<dl class="stat-grid stat-grid-4">
    <div><dt>Distance</dt><dd><?= e(Html::km($t['distance_km'])) ?></dd></div>
    <div><dt>Est. time</dt><dd><?= e(Html::duration((int) $t['est_duration_min'])) ?></dd></div>
    <div><dt>Ascent</dt><dd><?= e($t['elevation_gain_m']) ?> m</dd></div>
    <div><dt>Type</dt><dd><?= $loop ? 'Loop' : 'One way' ?></dd></div>
</dl>
<p class="prose"><?= e($t['description']) ?></p>

<section class="card" aria-labelledby="marks-h">
    <h2 id="marks-h">How this trail is marked</h2>
    <?= View::partial('partials/waymarks', ['waymarks' => \TrailSense\Domain\Waymarks::forTrail((string) $t['ref'], (string) $t['name'])]) ?>
</section>
<a class="btn btn-primary btn-block sticky-action" href="<?= e(url('plan.new', ['trail_id' => $t['id']])) ?>">Plan this hike</a>

<?php if ($groupHikes): ?>
<section aria-labelledby="gh-h">
    <h2 id="gh-h" class="section-title">Group hikes on this trail</h2>
    <div class="grid-2"><?php foreach ($groupHikes as $h): ?><?= View::partial('partials/group-card', ['h' => $h]) ?><?php endforeach; ?></div>
</section>
<?php endif; ?>

<section class="card reports" aria-labelledby="rep-h">
    <h2 id="rep-h">Trail conditions from hikers</h2>
    <?php if ($reports === []): ?>
        <p class="muted">No reports yet. Hiked it recently? Tell others how it was.</p>
    <?php else: ?>
        <ul class="report-list">
            <?php foreach ($reports as $r): ?>
                <li class="report report-<?= e($r['condition']) ?><?= (int) $r['age_days'] > 30 ? ' report-old' : '' ?>">
                    <span class="report-cond"><?= e($conditions[$r['condition']]) ?></span>
                    <span class="muted small"><a href="<?= e(url('profile', ['id' => $r['user_id']])) ?>"><?= e($r['author']) ?></a> ·
                        <?= (int) $r['age_days'] === 0 ? 'today' : e($r['age_days']) . ' days ago' ?><?= (int) $r['age_days'] > 30 ? ' (old)' : '' ?></span>
                    <?php if ($r['note'] !== ''): ?><p><?= e($r['note']) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <details class="report-form"<?= $errors ? ' open' : '' ?>>
        <summary>Report the current condition</summary>
        <form method="post" action="<?= e(url('trail.report', ['id' => $t['id']])) ?>" class="form" novalidate>
            <?= Csrf::field() ?>
            <fieldset<?= Html::aria($errors, 'condition') ?>>
                <legend>Condition</legend>
                <div class="chips-select">
                <?php foreach ($conditions as $key => $label): ?>
                    <label class="chip-option"><input type="radio" name="condition" value="<?= e($key) ?>"<?= ($old['condition'] ?? '') === $key ? ' checked' : '' ?>> <span><?= e($label) ?></span></label>
                <?php endforeach; ?>
                </div>
            </fieldset>
            <?= Html::error($errors, 'condition') ?>
            <label for="note">Note <span class="hint">optional, max 300</span></label>
            <textarea id="note" name="note" rows="2" maxlength="300" placeholder="e.g. fallen tree after the bridge"<?= Html::aria($errors, 'note') ?>><?= e($old['note'] ?? '') ?></textarea>
            <?= Html::error($errors, 'note') ?>
            <button class="btn btn-primary btn-block" type="submit">Send report</button>
        </form>
    </details>
</section>
