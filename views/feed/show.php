<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; $p = $post; $me = TrailSense\Core\Auth::user(); ?>
<a class="back" href="<?= e(url('feed')) ?>">← Community</a>
<p class="post-meta"><span class="avatar" aria-hidden="true"><?= Html::initial($p['author']) ?></span>
    <span><a class="author" href="<?= e(url('profile', ['id' => $p['user_id']])) ?>"><strong><?= e($p['author']) ?></strong></a><?= $p['author_role'] === 'organizer' ? ' <span class="tag tag-sm">Organizer</span>' : '' ?><br>
    <span class="muted">hiked on <?= e(Html::date($p['hiked_on'], 'l j F Y')) ?></span></span></p>
<h1><?= e($p['trail_name']) ?></h1>
<p class="muted"><?= e($p['region']) ?> · <?= Html::difficulty($p['difficulty']) ?></p>
<?php if ($p['photo']): ?>
    <figure class="photo"><img src="<?= e(photo_url($p['photo'])) ?>" alt="<?= e($p['photo_alt'] ?: 'Photo from the hike on ' . $p['trail_name']) ?>"></figure>
<?php endif; ?>
<div class="card share-route">
    <?= Html::route($p['route_points'], 'Route of ' . $p['trail_name'], 'route route-large') ?>
    <dl class="stat-grid">
        <div><dt>Distance</dt><dd><?= e(Html::km($p['distance_km'])) ?></dd></div>
        <div><dt>Moving time</dt><dd><?= e(Html::duration((int) $p['duration_min'])) ?></dd></div>
        <div><dt>Avg pace</dt><dd><?php $pace = (int) round($p['duration_min'] / max((float) $p['distance_km'], 0.1)); echo e(intdiv($pace, 60) > 0 ? sprintf('%d:%02d h', intdiv($pace, 60), $pace % 60) : sprintf('%d min', $pace)); ?> /km</dd></div>
        <div><dt>Ascent</dt><dd><?= e($p['elevation_gain_m']) ?> m</dd></div>
    </dl>
</div>
<?php if ($p['notes'] !== ''): ?><p class="prose"><?= nl2br(e($p['notes'])) ?></p><?php endif; ?>

<div class="post-actions">
    <form method="post" action="<?= e(url('activity.like', ['id' => $p['id']])) ?>">
        <?= Csrf::field() ?>
        <button class="like-btn" type="submit" aria-pressed="<?= $liked ? 'true' : 'false' ?>"><span aria-hidden="true"><?= $liked ? '♥' : '♡' ?></span> <?= $liked ? 'Liked' : 'Like' ?></button>
    </form>
    <span class="counts"><?= e($p['likes']) ?> like<?= (int) $p['likes'] === 1 ? '' : 's' ?></span>
    <a class="btn btn-secondary btn-sm" href="<?= e(url('trail', ['id' => $p['trail_id']])) ?>">See this trail</a>
</div>

<section class="card" id="comments" aria-labelledby="com-h">
    <h2 id="com-h">Comments (<?= count($comments) ?>)</h2>
    <?php if ($comments === []): ?><p class="muted">No comments yet.</p><?php endif; ?>
    <ul class="comments">
        <?php foreach ($comments as $c): ?>
            <li class="comment">
                <div class="comment-head"><span class="avatar avatar-sm" aria-hidden="true"><?= Html::initial($c['author']) ?></span>
                    <a class="author" href="<?= e(url('profile', ['id' => $c['user_id']])) ?>"><strong><?= e($c['author']) ?></strong></a>
                    <span class="muted small"><?= e((new DateTimeImmutable($c['created_at']))->format('j M, H:i')) ?></span>
                    <?php if ((int) $c['user_id'] === (int) $me['id']): ?>
                        <form method="post" action="<?= e(url('comment.delete', ['id' => $c['id']])) ?>" data-confirm="Delete your comment?">
                            <?= Csrf::field() ?><button class="link-btn" type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
                <p><?= e($c['body']) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
    <form class="form" method="post" action="<?= e(url('comment.store', ['id' => $p['id']])) ?>" novalidate>
        <?= Csrf::field() ?>
        <label for="body">Add a comment</label>
        <textarea id="body" name="body" rows="2" maxlength="500" required<?= Html::aria($errors, 'body') ?>><?= e($draft) ?></textarea>
        <?= Html::error($errors, 'body') ?>
        <button class="btn btn-primary" type="submit">Comment</button>
    </form>
</section>
