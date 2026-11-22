<?php use TrailSense\Core\Html; use TrailSense\Core\Icons; use TrailSense\Domain\Climb; use TrailSense\Domain\RouteType;
/** AC0.1 public landing page (rev. 22 Nov: lighter, section menu). @var array $trails */
$hero = $trails[0]['thumb_url'] ?? null;
$sections = ['features' => 'Features', 'see' => 'See it', 'why' => 'Why TrailSense', 'clubs' => 'For clubs', 'trails' => 'Trails', 'sources' => 'Sources']; ?>
<section class="hero landing-hero"<?= $hero ? ' style="--hero: url(\'' . e($hero) . '\')"' : '' ?> aria-labelledby="lp-h">
    <div class="hero-inner">
        <p class="hero-kicker">TrailSense · hiking in Portugal</p>
        <h1 id="lp-h">Hike smarter. Explore safer. Share the experience.</h1>
        <p class="hero-meta lp-lead">Pick a trail and a time. TrailSense checks the weather, daylight, IPMA warnings and fire risk for <em>your</em> hike — and tells you in plain words whether to go.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url('register')) ?>">Create a free account</a>
            <a class="btn btn-light" href="<?= e(url('login')) ?>">Sign in</a>
        </div>
    </div>
</section>

<nav class="lp-nav" aria-label="On this page" data-lp-nav>
    <?php foreach ($sections as $id => $label): ?><a href="#<?= e($id) ?>"><?= e($label) ?></a><?php endforeach; ?>
</nav>

<section class="lp-section" id="features" aria-labelledby="pillars-h">
    <h2 id="pillars-h">Everything for a good day on the trail</h2>
    <div class="pillars">
        <article class="pillar">
            <?= Icons::svg('search', 'pillar-icon') ?>
            <h3>Discover and plan</h3>
            <p>Real marked trails with maps, photos and a plain answer to “is this route for me?”.</p>
        </article>
        <article class="pillar">
            <?= Icons::svg('rain', 'pillar-icon') ?>
            <h3>Decide with the weather</h3>
            <p>Hour-by-hour forecast, IPMA warnings, fire risk and sunset — in one verdict.</p>
        </article>
        <article class="pillar">
            <?= Icons::svg('group', 'pillar-icon') ?>
            <h3>Share and hike together</h3>
            <p>Join club hikes, follow the trail with live directions, share your photos.</p>
        </article>
    </div>
</section>

<section class="lp-section" id="see" aria-labelledby="see-h">
    <h2 id="see-h">See it in the app</h2>
    <div class="showcase">
        <figure class="show-main">
            <img src="<?= e(asset('img/shot-risk.jpg')) ?>" alt="Risk check screen: a yellow 'Go prepared' verdict with the reason 'Finishing close to sunset', then hour-by-hour charts of temperature, rain and gusts with the hike window shaded and the sunset marked, and hourly weather cards." loading="lazy" width="1200" height="896">
            <figcaption><strong>The risk check</strong> — a verdict with its reason, the forecast for your hike hours and what to bring.</figcaption>
        </figure>
        <figure>
            <img src="<?= e(asset('img/shot-gps.jpg')) ?>" alt="Hike mode on a phone: green banner 'In 70 m · turn left' with an arrow, progress bar at 5%, next climb +151 m over 1.2 km, and distance done, left, altitude and finish time." loading="lazy" width="780" height="598">
            <figcaption><strong>On the trail</strong> — turn-by-turn directions, progress and the next climb.</figcaption>
        </figure>
    </div>
</section>

<section class="lp-section lp-diff" id="why" aria-labelledby="diff-h">
    <h2 id="diff-h">Why TrailSense and not just another trails app?</h2>
    <div class="diff-grid">
        <div class="diff-col diff-us">
            <h3>TrailSense</h3>
            <ul>
                <li><strong>A clear verdict</strong> with the reason and a checklist.</li>
                <li><strong>IPMA warnings and fire risk</strong> in the same check.</li>
                <li><strong>Made for beginners:</strong> route type, climbs and trail marks explained.</li>
                <li><strong>Made for clubs:</strong> group hikes, sign-ups and a briefing.</li>
            </ul>
        </div>
        <div class="diff-col diff-them">
            <h3>A typical trails app</h3>
            <ul>
                <li>Shows a forecast — you work out what it means.</li>
                <li>Warnings and fire risk live in other apps.</li>
                <li>Codes like “PR2” and paint marks are not explained.</li>
                <li>Clubs organise by chat and spreadsheets.</li>
            </ul>
        </div>
    </div>
</section>

<section class="lp-section" id="clubs" aria-labelledby="org-h">
    <h2 id="org-h">For club organizers: from invite to briefing</h2>
    <p class="muted lp-sub">Run safer group hikes in four steps — hikers simply join with one tap.</p>
    <ol class="org-steps">
        <li><span class="step-n">1</span><strong>Create the event</strong><span>Trail, date, meeting point, places.</span></li>
        <li><span class="step-n">2</span><strong>Share the invite</strong><span>Members join; places are counted.</span></li>
        <li><span class="step-n">3</span><strong>Follow sign-ups and weather</strong><span>Status and risk check per event.</span></li>
        <li><span class="step-n">4</span><strong>Brief the group</strong><span>Copy a ready-made briefing.</span></li>
    </ol>
    <figure class="show-wide">
        <img src="<?= e(asset('img/shot-organizer.jpg')) ?>" alt="Organizer dashboard: three events with date, trail, meeting point, sign-ups such as 2 of 8 with a progress bar and status 'Open', a weather badge and an Edit button." loading="lazy" width="1200" height="447">
        <figcaption><strong>Organizer dashboard</strong> — sign-ups, places left and weather for every event.</figcaption>
    </figure>
</section>

<?php if ($trails): ?>
<section class="lp-section" id="trails" aria-labelledby="lp-trails-h">
    <h2 id="lp-trails-h">Trails waiting for you</h2>
    <ul class="discover">
        <?php foreach ($trails as $t):
            $c = Climb::analyse([], (int) $t['elevation_gain_m'], (float) $t['distance_km']);
            $type = RouteType::describe((string) $t['ref'], (string) $t['name'], (float) $t['distance_km'], (int) $t['est_duration_min'], Html::isTrue($t['is_loop'])); ?>
            <li><a class="disc-card lp-trail" href="<?= e(url('register')) ?>" aria-label="<?= e($t['name']) ?> — create an account to plan it">
                <img src="<?= e($t['thumb_url']) ?>" alt="" loading="lazy" data-fallback>
                <?php if ($t['ref'] !== ''): ?><span class="lp-ref"><?= e($t['ref']) ?></span><?php endif; ?>
                <span class="disc-text">
                    <strong><?= e($t['name']) ?></strong>
                    <span><?= e($t['region']) ?> · <?= $type['code'] !== '' ? e($type['code']) . ' · ' : '' ?><?= Html::isTrue($t['is_loop']) ? 'Loop' : 'One way' ?></span>
                    <span class="lp-facts"><span><?= e(Html::km($t['distance_km'])) ?></span><span><?= e(Html::duration((int) $t['est_duration_min'])) ?></span><span>↑ <?= e($t['elevation_gain_m']) ?> m</span></span>
                    <span class="lp-diff-row"><?= Html::difficulty($t['difficulty']) ?></span>
                    <span class="lp-climb"><?= e($c['label']) ?> climbs</span>
                </span>
            </a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="lp-section" id="sources" aria-labelledby="src-h">
    <h2 id="src-h">Where the information comes from — and its limits</h2>
    <ul class="src-strip">
        <li><strong>Forecast</strong> <a href="https://open-meteo.com" target="_blank" rel="noopener">Open-Meteo</a></li>
        <li><strong>Warnings and fire risk</strong> <a href="https://www.ipma.pt" target="_blank" rel="noopener">IPMA</a></li>
        <li><strong>Trails and maps</strong> OpenStreetMap</li>
        <li><strong>Sunset</strong> calculated for each trail</li>
    </ul>
    <details class="drop">
        <summary class="drop-sum"><?= Icons::svg('alert', 'drop-icon') ?><span class="drop-title">What TrailSense cannot do</span>
            <span class="drop-preview">Guidance, not a guarantee — and how each source works</span></summary>
        <div class="drop-body">
            <ul>
                <li>Forecasts can be wrong and conditions change. Check again before you leave and use your own judgement.</li>
                <li>It does not replace official alerts — follow IPMA and Proteção Civil instructions and any closures on site.</li>
                <li>IPMA publishes warnings about 3 days ahead and fire risk for today and tomorrow; earlier, the check says “not published yet” instead of guessing.</li>
                <li>Directions follow the shape of the route: always confirm with the painted trail marks.</li>
                <li>Elevation comes from a 90 m terrain model, so small bumps are smoothed out.</li>
                <li>Download a trail before you leave to use the map and directions without mobile data.</li>
            </ul>
        </div>
    </details>
</section>

<section class="lp-cta card">
    <h2>Ready for your next walk?</h2>
    <p class="muted">Free. Your plans stay private; group hikes are visible to members so they can join.</p>
    <a class="btn btn-primary" href="<?= e(url('register')) ?>">Create a free account</a>
</section>
