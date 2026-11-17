<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; use TrailSense\Core\View;
$max = max(1, ...array_map(fn ($m) => (int) $m['hikes'], $monthly)); ?>
<header class="profile-head">
    <span class="avatar avatar-lg" aria-hidden="true"><?= Html::initial($profile['name']) ?></span>
    <div>
        <h1><?= e($profile['name']) ?></h1>
        <span class="tag"><?= $profile['role'] === 'organizer' ? 'Club organizer' : 'Hiker' ?></span>
        <span class="muted small">member since <?= e((new DateTimeImmutable($profile['created_at']))->format('M Y')) ?></span>
    </div>
</header>
<dl class="stat-grid stat-grid-4">
    <div><dt>Hikes</dt><dd><?= e($stats['hikes']) ?></dd></div>
    <div><dt>Distance</dt><dd><?= e(Html::km($stats['km'])) ?></dd></div>
    <div><dt>Ascent</dt><dd><?= e(number_format($stats['gain_m'], 0, '.', ' ')) ?> m</dd></div>
    <div><dt>Time on trail</dt><dd><?= e(Html::duration($stats['minutes'])) ?></dd></div>
</dl>
<section class="card" aria-labelledby="chart-h">
    <h2 id="chart-h">Hikes in the last 6 months</h2>
    <div class="chart" role="img" aria-label="<?= e(implode(', ', array_map(fn ($m) => $m['label'] . ': ' . $m['hikes'], $monthly))) ?>">
        <?php foreach ($monthly as $m): ?>
            <div class="bar"><b><?= e($m['hikes']) ?></b><span class="fill" style="height: <?= e(round(100 * (int) $m['hikes'] / $max)) ?>%"></span><small><?= e($m['label']) ?></small></div>
        <?php endforeach; ?>
    </div>
</section>
<h2 class="section-title"><?= $isMe ? 'My published hikes' : 'Published hikes' ?></h2>
<?php if ($posts === []): ?>
    <div class="card empty-inline"><p><?= $isMe ? 'You have not published a hike yet.' : 'No published hikes yet.' ?></p>
        <?php if ($isMe): ?><a class="btn btn-primary" href="<?= e(url('publish')) ?>">Publish a hike</a><?php endif; ?></div>
<?php else: ?>
    <div class="feed"><?php foreach ($posts as $post): ?><?= View::partial('partials/post-card', ['post' => $post]) ?><?php endforeach; ?></div>
<?php endif; ?>
<?php if ($isMe): ?>
    <form method="post" action="<?= e(url('logout')) ?>" class="signout">
        <?= Csrf::field() ?>
        <button class="btn btn-secondary btn-block" type="submit">Sign out</button>
    </form>
<?php endif; ?>
