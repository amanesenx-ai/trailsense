<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; use TrailSense\Core\View;
$t = $trail; $pts = $t['route_points']; $loop = Html::isTrue($t['is_loop']);
$start = $pts[0] ?? [$t['start_lat'], $t['start_lon']]; $end = $pts[count($pts) - 1] ?? $start; ?>
<a class="back" href="<?= e(url('trails')) ?>">← Trails</a>
<header class="trail-head">
    <h1><?= e($t['name']) ?></h1>
    <p class="muted"><?php if ($t['ref'] !== ''): ?><span class="ref"><?= e($t['ref']) ?></span> · <?php endif; ?><?= e($t['region']) ?> · <?= Html::difficulty($t['difficulty']) ?> · <?= $loop ? 'Loop' : 'One way' ?></p>
    <div class="head-actions">
        <form method="post" action="<?= e(url('trail.save', ['id' => $t['id']])) ?>">
            <?= \TrailSense\Core\Csrf::field() ?>
            <button class="btn btn-sm <?= $saved ? 'btn-saved' : 'btn-secondary' ?>" type="submit" aria-pressed="<?= $saved ? 'true' : 'false' ?>"><?= $saved ? '★ Saved' : '☆ Save trail' ?></button>
        </form>
        <button class="btn btn-sm btn-secondary" type="button" data-share data-title="<?= e($t['name']) ?> on TrailSense" data-url="<?= e(url('trail', ['id' => $t['id']])) ?>">Share</button>
        <a class="btn btn-sm btn-primary" href="<?= e(url('plan.new', ['trail_id' => $t['id']])) ?>">Plan this hike</a>
    </div>
    <p class="small muted" data-share-status role="status" aria-live="polite"></p>
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

<?php // Release 6: secondary information in dropdown sections, each with a one-line preview
$wm = \TrailSense\Domain\Waymarks::forTrail((string) $t['ref'], (string) $t['name']);
$fitType = \TrailSense\Domain\RouteType::describe((string) $t['ref'], (string) $t['name'], (float) $t['distance_km'], (int) $t['est_duration_min'], $loop);
$fitClimb = \TrailSense\Domain\Climb::analyse($t['elevation_profile'] ?? [], (int) $t['elevation_gain_m'], (float) $t['distance_km']);
$today = $outlook['days'][0] ?? null; ?>
<div class="drops">
    <details class="drop" open>
        <summary><span class="drop-title">Weather, next 7 days</span>
            <span class="drop-preview"><?= $today ? 'Today ' . e($today['tmax']) . '° · rain ' . e($today['prob']) . '% · gusts ' . e($today['gust']) . ' km/h' : 'Forecast not available right now' ?></span></summary>
        <?= View::partial('partials/trail-weather', ['outlook' => $outlook, 't' => $t]) ?>
    </details>
    <details class="drop">
        <summary><span class="drop-title">Is this route for me?</span>
            <span class="drop-preview"><?= e(explode(' — ', $fitType['name'])[0]) ?> · <?= e($fitClimb['label']) ?> climbs · <?= e(ucfirst($fitType['size'])) ?> walk</span></summary>
        <?= View::partial('partials/route-fit', ['t' => $t]) ?>
    </details>
    <details class="drop">
        <summary><span class="drop-title">How this trail is marked</span>
            <span class="drop-preview"><?= e($wm['name']) ?><?= $wm['system'] ? ' · ' . e(explode('.', $wm['about'])[0]) : '' ?></span></summary>
        <div class="drop-body"><?= View::partial('partials/waymarks', ['waymarks' => $wm]) ?></div>
    </details>

<?php if ($groupHikes): ?>
    <details class="drop">
        <summary><span class="drop-title">Group hikes on this trail</span><span class="drop-preview"><?= count($groupHikes) ?> upcoming — join one</span></summary>
        <div class="drop-body grid-2"><?php foreach ($groupHikes as $h): ?><?= View::partial('partials/group-card', ['h' => $h]) ?><?php endforeach; ?></div>
    </details>
<?php endif; ?>
    <details class="drop"<?= $errors ? ' open' : '' ?>>
        <summary><span class="drop-title">Trail conditions from hikers</span>
            <span class="drop-preview"><?= $reports ? e($conditions[$reports[0]['condition']]) . ' · ' . ((int) $reports[0]['age_days'] === 0 ? 'today' : e($reports[0]['age_days']) . ' days ago') : 'No reports yet' ?></span></summary>
<section class="card reports" aria-labelledby="rep-h">
    <h2 id="rep-h" class="sr-only">Trail conditions from hikers</h2>
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
    </details>
</div>
<a class="btn btn-primary btn-block sticky-action" href="<?= e(url('plan.new', ['trail_id' => $t['id']])) ?>">Plan this hike</a>
