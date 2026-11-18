<?php use TrailSense\Core\View; $isOrg = TrailSense\Core\Auth::isOrganizer();
/** AC2.5 Plans as an agenda with tabs. @var array $plans @var array $joined @var string $tab */
$lists = ['upcoming' => $plans['upcoming'], 'joined' => $joined, 'past' => $plans['past']];
$labels = ['upcoming' => $isOrg ? 'Upcoming' : 'My upcoming', 'joined' => 'Joined', 'past' => 'Past'];
$km = array_sum(array_map(fn ($p) => (float) $p['distance_km'], $plans['upcoming']));
$groups = count(array_filter($plans['upcoming'], fn ($p) => $p['group_size'] !== null)); ?>
<div class="section-head">
    <h1><?= e($title) ?></h1>
    <a class="btn btn-primary btn-sm" href="<?= e(url('plan.new')) ?>"><?= $isOrg ? 'New group hike' : 'Plan a hike' ?></a>
</div>
<dl class="stat-grid stat-grid-4 plan-stats">
    <div><dt>Upcoming</dt><dd><?= count($plans['upcoming']) ?></dd></div>
    <div><dt>km planned</dt><dd><?= e(rtrim(rtrim(number_format($km, 1), '0'), '.')) ?></dd></div>
    <div><dt>Group hikes joined</dt><dd><?= count($joined) ?></dd></div>
    <div><dt>Hikes done</dt><dd><?= count($plans['past']) ?></dd></div>
</dl>
<nav class="tabs" aria-label="Plans">
    <?php foreach ($labels as $key => $label): ?>
        <a href="<?= e(url('plans', ['tab' => $key])) ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="tab-count"><?= count($lists[$key]) ?></span></a>
    <?php endforeach; ?>
</nav>
<?php $list = $lists[$tab]; ?>
<?php if ($list === []): ?>
    <div class="card empty-inline">
        <?php if ($tab === 'joined'): ?><p>You have not joined a group hike yet. <a href="<?= e(url('groups')) ?>">See group hikes you can join</a>.</p>
        <?php elseif ($tab === 'past'): ?><p>No past hikes yet.</p>
        <?php else: ?><p>Nothing planned. <a href="<?= e(url('trails')) ?>">Browse trails</a> to start.</p><?php endif; ?>
    </div>
<?php else: ?>
    <div class="agenda-head" aria-hidden="true"><span>Date</span><span>Map</span><span>Trail and time</span><span>Risk check</span></div>
    <div class="agenda"><?php foreach ($list as $p): ?><?= View::partial('partials/plan-row', ['p' => $p, 'past' => $tab === 'past', 'own' => $tab !== 'joined']) ?><?php endforeach; ?></div>
<?php endif; ?>
<p class="muted small">Personal plans are private. Group hikes are visible to all members so they can join. <a href="<?= e(url('groups')) ?>">See group hikes</a></p>
