<?php use TrailSense\Core\Html;
/** F13 hike mode. Everything live happens in hike.js; this page works as a map + legend without it. */
$t = $trail; $pts = $t['route_points']; $loop = Html::isTrue($t['is_loop']);
$publish = ['from' => 'hike', 'trail_id' => $t['id']] + ($planId ? ['plan_id' => $planId] : []);
$data = ['route' => $pts, 'loop' => $loop, 'name' => $t['name'], 'pace' => $paceKmh, 'trailId' => (int) $t['id'],
    'profile' => $t['elevation_profile'] ?? [],
    'directions' => Html::directionsUrl($pts[0]),
    'publish' => url('publish', $publish)]; ?>
<div class="hike" data-hike="<?= e((string) json_encode($data, JSON_UNESCAPED_UNICODE)) ?>">
    <header class="hike-bar">
        <a class="back" href="<?= e(url('trail', ['id' => $t['id']])) ?>" aria-label="Leave hike mode">←</a>
        <div class="hike-title">
            <strong><?= e($t['name']) ?></strong>
            <span class="small muted"><?php if ($t['ref'] !== ''): ?><?= e($t['ref']) ?> · <?php endif; ?><?= e(Html::km($t['distance_km'])) ?> · <?= $loop ? 'loop' : 'one way' ?></span>
        </div>
        <span class="offline-badge" data-el="offline-badge" hidden>Offline</span>
        <span class="hike-clock" data-el="clock" aria-label="Elapsed time">0:00</span>
    </header>

    <div class="nav-banner" data-el="nav" aria-live="polite" hidden>
        <svg class="nav-arrow" viewBox="0 0 48 48" aria-hidden="true" data-el="nav-arrow"><path d="M24 4 40 26H30v18H18V26H8z"/></svg>
        <div class="nav-text">
            <p class="nav-main" data-el="nav-main"></p>
            <p class="nav-then" data-el="nav-then"></p>
        </div>
    </div>

    <div class="hike-map-wrap">
        <div class="hike-map" data-el="map" role="region" aria-label="Live map of <?= e($t['name']) ?>">
            <?= Html::route($pts, 'Route drawing of ' . $t['name'], 'route route-large') ?>
        </div>
        <button class="compass" type="button" data-el="compass" aria-label="Compass. Tap to turn on the phone compass">
            <svg viewBox="0 0 64 64" aria-hidden="true" data-el="rose">
                <circle cx="32" cy="32" r="30" class="rose-ring"/>
                <path d="M32 17 38 32H26z" class="rose-n"/><path d="M32 52 26 32h12z" class="rose-s"/>
                <text x="32" y="14" text-anchor="middle">N</text>
            </svg>
            <span class="compass-read" data-el="heading">tap</span>
        </button>
        <button class="btn btn-sm follow" type="button" data-el="follow" aria-pressed="true">Follow me</button>
    </div>

    <div class="off-route" data-el="offroute" role="alert" hidden></div>

    <section class="hike-panel" aria-label="Hike status">
        <p class="hike-status" data-el="status" role="status" aria-live="polite">Press <strong>Start hike</strong> when you are at the start. Your position stays on this phone.</p>
        <div class="progress-row"><div class="progress" aria-hidden="true"><span data-el="bar"></span></div><span class="small" data-el="pct">0%</span></div>
        <p class="hike-climb" data-el="climb"></p>
        <dl class="hike-stats">
            <div><dt>Done</dt><dd data-el="done">—</dd></div>
            <div><dt>Left</dt><dd data-el="left"><?= e(Html::km($t['distance_km'])) ?></dd></div>
            <div><dt>Altitude</dt><dd data-el="alt">—</dd></div>
            <div><dt>Finish ≈</dt><dd data-el="eta">—</dd></div>
        </dl>
        <div class="hike-actions">
            <button class="btn btn-primary" type="button" data-el="start">Start hike</button>
            <button class="btn btn-secondary" type="button" data-el="finish" hidden>Finish</button>
            <button class="btn btn-secondary" type="button" data-el="share">Share my position</button>
            <button class="btn btn-secondary" type="button" data-el="voice" aria-pressed="false">Voice: off</button>
        </div>
        <p class="small muted" data-el="share-status" role="status" aria-live="polite"></p>

        <section class="offline-box" aria-labelledby="off-h">
            <h2 id="off-h" class="offline-title">Use it without signal</h2>
            <p class="small muted" data-el="offline-status">Download this trail before you leave: the map around the route, directions, compass and progress then work with no signal.</p>
            <div class="progress" data-el="offline-progress" hidden aria-hidden="true"><span data-el="offline-bar"></span></div>
            <button class="btn btn-secondary btn-block" type="button" data-el="offline">Download for offline</button>
        </section>

        <details class="hike-legend" open>
            <summary>Trail marks for this route</summary>
            <?= \TrailSense\Core\View::partial('partials/waymarks', ['waymarks' => $waymarks]) ?>
        </details>
        <p class="small muted">Directions follow the shape of the route, not a map of junctions — always confirm with the trail marks. The live map needs mobile data, location permission and the screen on (no offline maps yet).</p>
        <noscript><p class="notice">Live position needs JavaScript. You can still use the route drawing and the trail marks.</p></noscript>
    </section>
</div>
