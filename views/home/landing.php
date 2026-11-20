<?php use TrailSense\Core\Html; use TrailSense\Core\Icons; use TrailSense\Domain\Climb; use TrailSense\Domain\RouteType;
/** AC0.1 public landing page: value proposition, pillars, difference, the two profiles. @var array $trails */
$hero = $trails[0]['thumb_url'] ?? null; ?>
<section class="hero landing-hero"<?= $hero ? ' style="--hero: url(\'' . e($hero) . '\')"' : '' ?> aria-labelledby="lp-h">
    <div class="hero-inner">
        <p class="hero-kicker">TrailSense · hiking in Portugal</p>
        <h1 id="lp-h">Hike smarter. Explore safer. Share the experience.</h1>
        <p class="hero-meta lp-lead">Pick a trail and a time. TrailSense checks the forecast, daylight and the official IPMA warnings and fire risk for <em>your</em> hike, and tells you in plain words whether to go — and what to bring.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url('register')) ?>">Create a free account</a>
            <a class="btn btn-light" href="<?= e(url('login')) ?>">Sign in</a>
        </div>
    </div>
</section>

<section class="lp-section" aria-labelledby="pillars-h">
    <h2 id="pillars-h">Everything for a good day on the trail</h2>
    <div class="pillars">
        <article class="pillar">
            <?= Icons::svg('trail', 'pillar-icon') ?>
            <h3>Discover and plan</h3>
            <p>Real marked trails (PR, GR) with maps, photos, elevation profiles and a plain-words answer to “is this route for me?”. Plan a date and time in seconds.</p>
        </article>
        <article class="pillar">
            <?= Icons::svg('rain', 'pillar-icon') ?>
            <h3>Decide with the weather</h3>
            <p>Hour-by-hour forecast for your hike, IPMA warnings and fire risk, sunset. Every warning shows the rule that triggered it and what to do about it.</p>
        </article>
        <article class="pillar">
            <?= Icons::svg('check', 'pillar-icon') ?>
            <h3>Share and hike together</h3>
            <p>Publish your hikes with photos, like and comment, join club group hikes, and follow the trail with a live map, compass and turn-by-turn directions.</p>
        </article>
    </div>
</section>

<section class="lp-section" aria-labelledby="see-h">
    <h2 id="see-h">See it in the app</h2>
    <div class="showcase">
        <figure class="show-main">
            <img src="<?= e(asset('img/shot-risk.jpg')) ?>" alt="Risk check screen: a yellow 'Go prepared' verdict with the reason 'Finishing close to sunset', then hour-by-hour charts of temperature, rain and gusts with the hike window shaded and the sunset marked, and hourly weather cards." loading="lazy" width="1200" height="896">
            <figcaption><strong>The risk check</strong> — a verdict with its reason, the hour-by-hour forecast for your hike window, sunset marked, then a “what to bring” list and the rule behind every warning.</figcaption>
        </figure>
        <figure>
            <img src="<?= e(asset('img/shot-gps.jpg')) ?>" alt="Hike mode on a phone: green banner 'In 70 m · turn left' with an arrow, progress bar at 5%, next climb +151 m over 1.2 km, and distance done, left, altitude and finish time." loading="lazy" width="780" height="598">
            <figcaption><strong>On the trail</strong> — turn-by-turn banner, progress, altitude and the next climb.</figcaption>
        </figure>
    </div>
    <p class="small muted">Screenshots from the running app with demo data.</p>
</section>

<section class="lp-section lp-diff" aria-labelledby="diff-h">
    <h2 id="diff-h">Why TrailSense and not just another trails app?</h2>
    <div class="diff-grid">
        <div class="diff-col diff-them">
            <h3>A typical trails app</h3>
            <ul>
                <li>Shows a forecast — you work out what it means.</li>
                <li>Official warnings and fire risk live in other apps.</li>
                <li>Codes like “PR2” and paint marks are not explained.</li>
                <li>Clubs organise by chat and spreadsheets.</li>
            </ul>
        </div>
        <div class="diff-col diff-us">
            <h3>TrailSense</h3>
            <ul>
                <li><strong>A clear verdict</strong> — Good to go / Go prepared / Rethink — with the reason and a checklist.</li>
                <li><strong>IPMA warnings and rural fire risk</strong> for your district and day, in the same check.</li>
                <li><strong>Made for beginners</strong>: route type, climbs and the FCMP trail marks explained.</li>
                <li><strong>Made for clubs</strong>: group hikes with places, sign-ups and a briefing to share.</li>
            </ul>
        </div>
    </div>
</section>

<section class="lp-section" aria-labelledby="roles-h">
    <h2 id="roles-h">Two profiles, two ways to use it</h2>
    <div class="roles">
        <article class="role-card">
            <p class="tag">Hiker</p>
            <h3>Go out with confidence</h3>
            <ul>
                <li>Find trails by region, distance, difficulty and time.</li>
                <li>Save favourites and plan a hike in three fields.</li>
                <li>Get the risk check and a “what to bring” list.</li>
                <li>Join club hikes and share your own.</li>
            </ul>
        </article>
        <article class="role-card">
            <p class="tag tag-org">Organizer</p>
            <h3>Run safer group hikes</h3>
            <ul>
                <li>Create a group hike with meeting point and places.</li>
                <li>See who signed up and how many places are left.</li>
                <li>Check the weather for the event and copy a briefing for the group.</li>
                <li>Edit or cancel the event — participants see it straight away.</li>
            </ul>
        </article>
    </div>
</section>

<section class="lp-section" aria-labelledby="org-h">
    <h2 id="org-h">For club organizers: from invite to briefing</h2>
    <ol class="org-steps">
        <li><span class="step-n">1</span><strong>Create the event</strong><span>Trail, date, meeting point and number of places.</span></li>
        <li><span class="step-n">2</span><strong>Share the invite link</strong><span>Members join with one tap; places are counted.</span></li>
        <li><span class="step-n">3</span><strong>Follow sign-ups and weather</strong><span>Every event shows sign-ups, status and its risk check.</span></li>
        <li><span class="step-n">4</span><strong>Brief the group</strong><span>Copy a ready-made briefing with times and precautions.</span></li>
    </ol>
    <figure class="show-wide">
        <img src="<?= e(asset('img/shot-organizer.jpg')) ?>" alt="Organizer dashboard: three events with date, trail, meeting point, sign-ups such as 2 of 8 with a progress bar and status 'Open', a weather badge and an Edit button." loading="lazy" width="1200" height="447">
        <figcaption><strong>Organizer dashboard</strong> — sign-ups, places left, status and weather for every event (demo data).</figcaption>
    </figure>
</section>

<?php if ($trails): ?>
<section class="lp-section" aria-labelledby="lp-trails-h">
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
                    <span class="lp-diff-row"><?= Html::difficulty($t['difficulty']) ?> <span class="lp-climb"><?= e($c['label']) ?> climbs</span></span>
                </span>
            </a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="lp-section" aria-labelledby="src-h">
    <h2 id="src-h">Where the information comes from — and its limits</h2>
    <div class="sources-grid">
        <div class="src"><h3>Forecast</h3><p><a href="https://open-meteo.com" target="_blank" rel="noopener">Open-Meteo</a> weather models for the trail start, hour by hour, up to 16 days ahead. The check links to the exact data it used and when it was fetched.</p></div>
        <div class="src"><h3>Official warnings and fire risk</h3><p><a href="https://www.ipma.pt" target="_blank" rel="noopener">IPMA</a> open data. Warnings are published about 3 days ahead and fire risk for today and tomorrow — earlier than that, the check says “not published yet” instead of guessing.</p></div>
        <div class="src"><h3>Daylight, trails and elevation</h3><p>Sunset is calculated for the trail’s position and date. Routes come from OpenStreetMap; elevation from a 90 m terrain model, so small bumps are smoothed out.</p></div>
    </div>
    <div class="limits card">
        <h3>What TrailSense cannot do</h3>
        <ul>
            <li>It is guidance, not a guarantee: forecasts can be wrong and conditions on the ground change. Check again before you leave and use your own judgement.</li>
            <li>It does not replace official alerts — follow IPMA and Proteção Civil instructions and any trail closures on site.</li>
            <li>Directions follow the shape of the route, not a database of junctions: always confirm with the painted trail marks.</li>
            <li>The live map runs in your browser: it needs mobile data for the map, location permission, and the screen on. There are no offline maps yet.</li>
        </ul>
    </div>
</section>

<section class="lp-cta card">
    <h2>Ready for your next walk?</h2>
    <p class="muted">Free. Your plans stay private; group hikes are visible to members so they can join.</p>
    <a class="btn btn-primary" href="<?= e(url('register')) ?>">Create a free account</a>
</section>
