<?php use TrailSense\Core\Html; use TrailSense\Core\View; use TrailSense\Domain\Climb;
/** AC8.3 richer home. @var ?array $next @var array $more @var array $groupHikes @var array $feed @var array $stats @var array $discover */
$u = TrailSense\Core\Auth::user(); $first = explode(' ', $u['name'])[0];
$hero = $next['trail_thumb'] ?? ($discover[0]['thumb_url'] ?? null);
$verdict = ['ok' => 'Good to go', 'caution' => 'Go prepared', 'warning' => 'Rethink this plan'];
$hour = (int) date('G'); $greet = $hour < 12 ? 'Good morning' : ($hour < 20 ? 'Good afternoon' : 'Good evening'); ?>
<section class="hero<?= $hero ? '' : ' hero-plain' ?>"<?= $hero ? ' style="--hero: url(\'' . e($hero) . '\')"' : '' ?> aria-labelledby="hero-h">
    <div class="hero-inner">
        <p class="hero-kicker"><?= e($greet) ?>, <?= e($first) ?> <span class="tag">Hiker</span></p>
        <?php if ($next): $days = (int) (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($next['planned_date']))->format('%r%a'); ?>
            <h1 id="hero-h">Your next hike: <?= e($next['trail_name']) ?></h1>
            <p class="hero-meta"><?= e(Html::date($next['planned_date'], 'l j F')) ?> · <?= e(Html::time($next['start_time'])) ?> · <?= e(Html::km($next['distance_km'])) ?>
                · <strong><?= $days === 0 ? 'today' : ($days === 1 ? 'tomorrow' : 'in ' . $days . ' days') ?></strong></p>
            <p class="hero-verdict"><?= Html::badge($next['overall_level'], $next['is_complete'] === false) ?> <?= e($verdict[$next['overall_level'] ?? ''] ?? 'Not checked yet') ?></p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= e(url('plan', ['id' => $next['id']])) ?>">See the risk check</a>
                <a class="btn btn-light" href="<?= e(url('hike', ['id' => $next['trail_id'], 'plan_id' => $next['id']])) ?>">▶ Start hike</a>
            </div>
        <?php else: ?>
            <h1 id="hero-h">Where are you walking next?</h1>
            <p class="hero-meta">Pick a trail and TrailSense checks the weather, daylight and IPMA warnings for you.</p>
            <div class="hero-actions"><a class="btn btn-primary" href="<?= e(url('plan.new')) ?>">Plan a hike</a><a class="btn btn-light" href="<?= e(url('trails')) ?>">Browse trails</a></div>
        <?php endif; ?>
    </div>
</section>

<dl class="stat-grid stat-grid-4 home-stats" aria-label="Your hiking">
    <div><dt>Hikes done</dt><dd><?= e($stats['hikes']) ?></dd></div>
    <div><dt>km walked</dt><dd><?= e(rtrim(rtrim(number_format($stats['km'], 1), '0'), '.')) ?></dd></div>
    <div><dt>m climbed</dt><dd><?= e(number_format($stats['gain_m'], 0, '.', ' ')) ?></dd></div>
    <div><dt>Trails done</dt><dd><?= e($stats['trails']) ?></dd></div>
</dl>

<div class="dash-grid">
    <section class="card dash-box" aria-labelledby="saved-h">
        <div class="section-head"><h2 id="saved-h" class="section-title">★ Saved trails</h2><a href="<?= e(url('trails', ['saved' => '1'])) ?>">All</a></div>
        <?php if ($saved): ?><ul class="tile-list"><?php foreach (array_slice($saved, 0, 4) as $t): ?><?= View::partial('partials/trail-tile', ['t' => $t]) ?><?php endforeach; ?></ul>
        <?php else: ?><p class="muted small">Nothing saved yet. Open a trail and press “☆ Save trail” to keep it here.</p><?php endif; ?>
    </section>
    <section class="card dash-box" aria-labelledby="recent-h">
        <div class="section-head"><h2 id="recent-h" class="section-title">Recently viewed</h2><a href="<?= e(url('trails')) ?>">Browse</a></div>
        <?php if ($recent): ?><ul class="tile-list"><?php foreach (array_slice($recent, 0, 4) as $t): ?><?= View::partial('partials/trail-tile', ['t' => $t]) ?><?php endforeach; ?></ul>
        <?php else: ?><p class="muted small">Trails you open appear here so you can get back to them.</p><?php endif; ?>
    </section>
    <section class="card dash-box" aria-labelledby="mine-h">
        <div class="section-head"><h2 id="mine-h" class="section-title">Your recent hikes</h2><a href="<?= e(url('publish')) ?>">Publish</a></div>
        <?php if ($myHikes): ?><ul class="tile-list"><?php foreach ($myHikes as $a): ?>
            <li><a class="tile-link" href="<?= e(url('activity', ['id' => $a['id']])) ?>"><span><strong><?= e($a['trail_name']) ?></strong><span class="small muted"><?= e(Html::date($a['hiked_on'], 'j M')) ?> · <?= e(Html::km($a['distance_km'])) ?> · ♥ <?= e($a['likes'] ?? 0) ?></span></span></a></li>
        <?php endforeach; ?></ul>
        <?php else: ?><p class="muted small">No hikes published yet. After a walk, press Finish in hike mode or “Publish”.</p><?php endif; ?>
    </section>
</div>

<?php if ($more): ?>
<section aria-labelledby="later-h">
    <div class="section-head"><h2 id="later-h" class="section-title">Also planned</h2><a href="<?= e(url('plans')) ?>">All plans</a></div>
    <div class="grid-2"><?php foreach ($more as $p): ?><?= View::partial('partials/plan-card', ['p' => $p]) ?><?php endforeach; ?></div>
</section>
<?php endif; ?>

<?php if ($discover): ?>
<section aria-labelledby="disc-h">
    <div class="section-head"><h2 id="disc-h" class="section-title">Recommended trails</h2><a href="<?= e(url('trails')) ?>">See all</a></div>
    <ul class="discover">
        <?php foreach ($discover as $t): $c = Climb::analyse([], (int) $t['elevation_gain_m'], (float) $t['distance_km']); ?>
            <li><a class="disc-card" href="<?= e(url('trail', ['id' => $t['id']])) ?>">
                <img src="<?= e($t['thumb_url']) ?>" alt="<?= e($t['thumb_alt']) ?>" loading="lazy" data-fallback>
                <span class="disc-text"><strong><?= e($t['name']) ?></strong>
                    <span><?= $t['ref'] !== '' ? e($t['ref']) . ' · ' : '' ?><?= e(Html::km($t['distance_km'])) ?> · <?= e($c['label']) ?> climbs</span></span>
            </a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if ($groupHikes): ?>
<section aria-labelledby="gh-h">
    <div class="section-head"><h2 id="gh-h" class="section-title">Group hikes you can join</h2><a href="<?= e(url('groups')) ?>">See all</a></div>
    <div class="grid-2"><?php foreach ($groupHikes as $h): ?><?= View::partial('partials/group-card', ['h' => $h]) ?><?php endforeach; ?></div>
</section>
<?php endif; ?>

<section aria-labelledby="feed-h">
    <div class="section-head"><h2 id="feed-h" class="section-title">Latest from the community</h2><a href="<?= e(url('feed')) ?>">See all</a></div>
    <div class="feed-grid"><?php foreach ($feed as $post): ?><?= View::partial('partials/post-card', ['post' => $post]) ?><?php endforeach; ?></div>
</section>
