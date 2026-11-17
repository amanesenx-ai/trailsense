<?php use TrailSense\Core\View; $isOrg = TrailSense\Core\Auth::isOrganizer(); ?>
<div class="section-head">
    <h1><?= e($title) ?></h1>
    <a class="btn btn-primary btn-sm" href="<?= e(url('plan.new')) ?>"><?= $isOrg ? 'New group hike' : 'Plan a hike' ?></a>
</div>
<h2 class="section-title">Upcoming</h2>
<?php if ($plans['upcoming'] === []): ?>
    <div class="card empty-inline"><p>Nothing planned. <a href="<?= e(url('trails')) ?>">Browse trails</a> to start.</p></div>
<?php else: ?>
    <div class="grid-2"><?php foreach ($plans['upcoming'] as $p): ?><?= View::partial('partials/plan-card', ['p' => $p]) ?><?php endforeach; ?></div>
<?php endif; ?>
<?php if ($plans['past'] !== []): ?>
    <h2 class="section-title">Past</h2>
    <div class="grid-2 past"><?php foreach ($plans['past'] as $p): ?><?= View::partial('partials/plan-card', ['p' => $p]) ?><?php endforeach; ?></div>
<?php endif; ?>
