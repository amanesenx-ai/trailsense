<?php use TrailSense\Core\Html; use TrailSense\Core\View; $u = TrailSense\Core\Auth::user(); ?>
<header class="hello">
    <h1>Hi, <?= e(explode(' ', $u['name'])[0]) ?>!</h1><span class="tag">Hiker</span>
</header>

<section aria-labelledby="next-h">
    <h2 id="next-h" class="section-title">Next hike</h2>
    <?php if ($next): ?>
        <?= View::partial('partials/plan-card', ['p' => $next]) ?>
    <?php else: ?>
        <div class="card empty-inline">
            <p>No hike planned yet. Pick a trail and we will check the weather for you.</p>
        </div>
    <?php endif; ?>
    <a class="btn btn-primary btn-block" href="<?= e(url('plan.new')) ?>">Plan a hike</a>
</section>

<?php if ($more): ?>
<section aria-labelledby="later-h">
    <h2 id="later-h" class="section-title">Later</h2>
    <div class="grid-2"><?php foreach ($more as $p): ?><?= View::partial('partials/plan-card', ['p' => $p]) ?><?php endforeach; ?></div>
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
    <ul class="mini-feed">
        <?php foreach ($feed as $post): ?>
            <li><a href="<?= e(url('activity', ['id' => $post['id']])) ?>">
                <strong><?= e($post['trail_name']) ?></strong>
                <span class="muted"><?= e(explode(' ', $post['author'])[0]) ?> · <?= e(Html::km($post['distance_km'])) ?> · <?= e(Html::date($post['hiked_on'])) ?></span>
            </a></li>
        <?php endforeach; ?>
    </ul>
</section>
