<?php use TrailSense\Core\View; ?>
<div class="section-head">
    <h1>Group hikes</h1>
    <?php if (TrailSense\Core\Auth::isOrganizer()): ?><a class="btn btn-primary btn-sm" href="<?= e(url('plan.new')) ?>">New group hike</a><?php endif; ?>
</div>
<p class="muted">Club hikes organised by TrailSense organizers. Join one to get the briefing and the weather check.</p>
<?php if ($hikes === []): ?>
    <div class="card empty-inline"><p>No group hikes planned right now. Check again soon.</p></div>
<?php else: ?>
    <div class="grid-2"><?php foreach ($hikes as $h): ?><?= View::partial('partials/group-card', ['h' => $h]) ?><?php endforeach; ?></div>
<?php endif; ?>
