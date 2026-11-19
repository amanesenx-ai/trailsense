<?php use TrailSense\Core\Html; use TrailSense\Core\Icons;
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

<?php if ($trails): ?>
<section class="lp-section" aria-labelledby="lp-trails-h">
    <h2 id="lp-trails-h">Trails waiting for you</h2>
    <ul class="discover">
        <?php foreach ($trails as $t): ?>
            <li><div class="disc-card">
                <img src="<?= e($t['thumb_url']) ?>" alt="<?= e($t['thumb_alt']) ?>" loading="lazy" data-fallback>
                <span class="disc-text"><strong><?= e($t['name']) ?></strong><span><?= e($t['region']) ?> · <?= e(Html::km($t['distance_km'])) ?></span></span>
            </div></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="lp-cta card">
    <h2>Ready for your next walk?</h2>
    <p class="muted">Free. Your plans stay private; group hikes are visible to members so they can join.</p>
    <a class="btn btn-primary" href="<?= e(url('register')) ?>">Create a free account</a>
</section>
