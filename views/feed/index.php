<?php use TrailSense\Core\View; ?>
<div class="section-head">
    <h1>Community</h1>
    <a class="btn btn-primary btn-sm" href="<?= e(url('publish')) ?>">Publish a hike</a>
</div>
<?php if ($posts === []): ?>
    <div class="card empty-inline">
        <p>No hikes shared yet. Be the first to publish one!</p>
        <a class="btn btn-primary" href="<?= e(url('publish')) ?>">Publish a hike</a>
    </div>
<?php else: ?>
    <div class="feed"><?php foreach ($posts as $post): ?><?= View::partial('partials/post-card', ['post' => $post]) ?><?php endforeach; ?></div>
<?php endif; ?>
