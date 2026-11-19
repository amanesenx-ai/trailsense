<?php use TrailSense\Core\Html; use TrailSense\Core\View; $u = TrailSense\Core\Auth::user();
/** AC9.8 organizer dashboard: events, participants, registration status, readiness, quick create. */
$pct = $kpi['total'] > 0 ? (int) round(100 * $kpi['checked'] / $kpi['total']) : 0;
$group = array_values(array_filter($plans, fn ($p) => $p['group_size'] !== null));
$people = array_sum(array_map(fn ($p) => (int) $p['participants'], $group));
$places = array_sum(array_map(fn ($p) => max(0, (int) $p['group_size'] - (int) $p['participants']), $group));
$next = $group[0] ?? null;
$verdict = ['ok' => 'Good to go', 'caution' => 'Go prepared', 'warning' => 'Rethink this plan']; ?>
<section class="hero hero-org<?= $hero ? '' : ' hero-plain' ?>"<?= $hero ? ' style="--hero: url(\'' . e($hero) . '\')"' : '' ?> aria-labelledby="org-h">
    <div class="hero-inner">
        <p class="hero-kicker">Hi, <?= e(explode(' ', $u['name'])[0]) ?> <span class="tag tag-org">Organizer</span></p>
        <?php if ($next): ?>
            <h1 id="org-h">Next event: <?= e($next['trail_name']) ?></h1>
            <p class="hero-meta"><?= e(Html::date($next['planned_date'], 'l j F')) ?> · meet <?= e(Html::time($next['meeting_time'])) ?> at <?= e($next['meeting_point']) ?> · <strong><?= e($next['participants']) ?>/<?= e($next['group_size']) ?> signed up</strong></p>
            <p class="hero-verdict"><?= Html::badge($next['overall_level'], $next['is_complete'] === false) ?> <?= e($verdict[$next['overall_level'] ?? ''] ?? 'Not checked yet') ?></p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= e(url('plan', ['id' => $next['id']])) ?>">Risk check and briefing</a>
                <a class="btn btn-light" href="<?= e(url('plan.new')) ?>">+ New group hike</a>
            </div>
        <?php else: ?>
            <h1 id="org-h">Run safer group hikes</h1>
            <p class="hero-meta">Create an event, share the invite link, and TrailSense checks the weather, IPMA warnings and daylight for the whole group.</p>
            <div class="hero-actions"><a class="btn btn-primary" href="<?= e(url('plan.new')) ?>">+ New group hike</a></div>
        <?php endif; ?>
    </div>
</section>

<dl class="stat-grid stat-grid-4 home-stats" aria-label="Your events">
    <div><dt>Upcoming events</dt><dd><?= count($group) ?></dd></div>
    <div><dt>People signed up</dt><dd><?= e($people) ?></dd></div>
    <div><dt>Places left</dt><dd><?= e($places) ?></dd></div>
    <div><dt>Events held</dt><dd><?= e($pastCount) ?></dd></div>
</dl>

<section class="kpi card" aria-labelledby="kpi-h">
    <div>
        <h2 id="kpi-h" class="kpi-label">Upcoming hikes fully checked</h2>
        <p class="kpi-value"><?= e($kpi['checked']) ?> <span>of <?= e($kpi['total']) ?></span></p>
        <p class="muted small">Route, forecast, IPMA warnings and daylight all checked. Forecasts appear 16 days ahead.</p>
    </div>
    <div class="meter" role="img" aria-label="<?= e($pct) ?>% checked"><span style="width: <?= e($pct) ?>%"></span></div>
</section>

<section aria-labelledby="events-h">
    <div class="section-head"><h2 id="events-h" class="section-title">My events</h2><a href="<?= e(url('plans')) ?>">All, incl. past</a></div>
    <?php if ($group === []): ?>
        <div class="card empty-inline"><p>No group hike yet. Create one, share the invite link and people can join with one tap.</p>
            <a class="btn btn-primary" href="<?= e(url('plan.new')) ?>">+ New group hike</a></div>
    <?php else: ?>
        <div class="events">
            <div class="events-head" aria-hidden="true"><span>Date</span><span>Event</span><span>Sign-ups</span><span>Weather</span><span></span></div>
            <?php foreach ($group as $p): $taken = (int) $p['participants']; $size = (int) $p['group_size'];
                $status = $taken >= $size ? ['Full', 'full'] : ($taken === 0 ? ['Open — no sign-ups yet', 'empty'] : ['Open', 'open']); ?>
                <div class="event-row">
                    <div class="ag-date" aria-hidden="true"><span class="ag-dow"><?= e(Html::date($p['planned_date'], 'D')) ?></span><span class="ag-day"><?= e(Html::date($p['planned_date'], 'j')) ?></span><span class="ag-mon"><?= e(Html::date($p['planned_date'], 'M')) ?></span></div>
                    <div class="ev-main"><a class="title" href="<?= e(url('plan', ['id' => $p['id']])) ?>"><?= e($p['trail_name']) ?></a>
                        <p class="muted small"><span class="sr-only"><?= e(Html::date($p['planned_date'], 'l j F')) ?> · </span>meet <?= e(Html::time($p['meeting_time'])) ?>, <?= e($p['meeting_point']) ?> · <?= e(Html::km($p['distance_km'])) ?></p></div>
                    <div class="ev-signups">
                        <span class="ev-count"><strong><?= $taken ?></strong>/<?= $size ?></span>
                        <span class="ev-bar" aria-hidden="true"><span style="width: <?= $size ? (int) round(100 * $taken / $size) : 0 ?>%"></span></span>
                        <span class="ev-status ev-<?= $status[1] ?>"><?= e($status[0]) ?></span>
                    </div>
                    <div class="ev-risk"><?= Html::badge($p['overall_level'], $p['is_complete'] === false) ?></div>
                    <div class="ev-actions"><a class="btn btn-sm btn-secondary" href="<?= e(url('plan.edit', ['id' => $p['id']])) ?>">Edit</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section aria-labelledby="feed-h">
    <div class="section-head"><h2 id="feed-h" class="section-title">Latest from the community</h2><a href="<?= e(url('feed')) ?>">See all</a></div>
    <div class="feed-grid"><?php foreach ($feed as $post): ?><?= View::partial('partials/post-card', ['post' => $post]) ?><?php endforeach; ?></div>
</section>
