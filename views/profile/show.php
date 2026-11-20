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
<?php if ($isMe): ?>
<section class="card dash-box" aria-labelledby="psaved-h">
    <div class="section-head"><h2 id="psaved-h" class="section-title">★ My saved trails <span class="small muted">(only you see these)</span></h2></div>
    <?php if ($saved): ?><ul class="tile-list tile-list-2"><?php foreach ($saved as $t): ?><?= \TrailSense\Core\View::partial('partials/trail-tile', ['t' => $t]) ?><?php endforeach; ?></ul>
    <?php else: ?><p class="muted small">No saved trails yet. <a href="<?= e(url('trails')) ?>">Find a trail</a> and press “☆ Save trail”.</p><?php endif; ?>
</section>
<?php endif; ?>

<h2 class="section-title"><?= $isMe ? 'My published hikes' : 'Published hikes' ?></h2>
<?php if ($posts === []): ?>
    <div class="card empty-inline"><p><?= $isMe ? 'You have not published a hike yet.' : 'No published hikes yet.' ?></p>
        <?php if ($isMe): ?><a class="btn btn-primary" href="<?= e(url('publish')) ?>">Publish a hike</a><?php endif; ?></div>
<?php else: ?>
    <div class="feed"><?php foreach ($posts as $post): ?><?= View::partial('partials/post-card', ['post' => $post]) ?><?php endforeach; ?></div>
<?php endif; ?>
<?php if ($isMe): ?>
    <details class="card password-box" id="password">
        <summary>Change password</summary>
        <form class="form" method="post" action="<?= e(url('password.update')) ?>" novalidate>
            <?= \TrailSense\Core\Csrf::field() ?>
            <label for="current_password">Current password</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
            <label for="new_password">New password <span class="muted small">(at least 8 characters)</span></label>
            <input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" required>
            <label for="repeat_password">Repeat new password</label>
            <input id="repeat_password" name="repeat_password" type="password" autocomplete="new-password" minlength="8" required>
            <button class="btn btn-primary" type="submit">Change password</button>
        </form>
    </details>
    <form method="post" action="<?= e(url('logout')) ?>" class="signout">
        <?= Csrf::field() ?>
        <button class="btn btn-secondary btn-block" type="submit">Sign out</button>
    </form>
<?php endif; ?>
