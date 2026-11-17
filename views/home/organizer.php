<?php use TrailSense\Core\Html; use TrailSense\Core\View; $u = TrailSense\Core\Auth::user();
$pct = $kpi['total'] > 0 ? (int) round(100 * $kpi['checked'] / $kpi['total']) : 0; ?>
<header class="hello">
    <h1>Hi, <?= e(explode(' ', $u['name'])[0]) ?>!</h1><span class="tag">Organizer</span>
</header>

<section class="kpi card" aria-labelledby="kpi-h">
    <div>
        <h2 id="kpi-h" class="kpi-label">Upcoming hikes fully checked</h2>
        <p class="kpi-value"><?= e($kpi['checked']) ?> <span>of <?= e($kpi['total']) ?></span></p>
        <p class="muted small">Route, forecast and daylight all checked. Forecasts appear 16 days ahead.</p>
    </div>
    <div class="meter" role="img" aria-label="<?= e($pct) ?>% checked"><span style="width: <?= e($pct) ?>%"></span></div>
</section>

<section aria-labelledby="groups-h">
    <h2 id="groups-h" class="section-title">My group hikes</h2>
    <?php if ($plans === []): ?>
        <div class="card empty-inline"><p>No group hike yet. Create one and share the briefing with your group.</p></div>
    <?php else: ?>
        <div class="grid-2"><?php foreach ($plans as $p): ?><?= View::partial('partials/plan-card', ['p' => $p]) ?><?php endforeach; ?></div>
    <?php endif; ?>
    <a class="btn btn-primary btn-block" href="<?= e(url('plan.new')) ?>">New group hike</a>
</section>

<section aria-labelledby="feed-h">
    <div class="section-head"><h2 id="feed-h" class="section-title">Latest from the community</h2><a href="<?= e(url('feed')) ?>">See all</a></div>
    <ul class="mini-feed">
        <?php foreach ($feed as $post): ?>
            <li><a href="<?= e(url('activity', ['id' => $post['id']])) ?>">
                <strong><?= e($post['trail_name']) ?></strong>
                <span class="muted"><?= e(explode(' ', $post['author'])[0]) ?> · <?= e(Html::km($post['distance_km'])) ?> · <?= e(Html::date($post['hiked_on'])) ?></span>
            </a></li>
        <?php endforeach; ?>
    </ul>
</section>
