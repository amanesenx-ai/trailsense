<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; use TrailSense\Domain\RiskEngine;
$p = $plan; $isGroup = $p['group_size'] !== null; $left = $isGroup ? max(0, (int) $p['group_size'] - (int) $p['participants']) : 0;
?>
<a class="back" href="<?= e($isOwner ? url('plans') : url('groups')) ?>">← <?= $isOwner ? ($isGroup ? 'My group hikes' : 'My plans') : 'Group hikes' ?></a>
<header class="plan-head">
    <h1><?= e($p['trail_name']) ?></h1>
    <p class="muted"><?= e(Html::date($p['planned_date'], 'l j F Y')) ?> · start <?= e(Html::time($p['start_time'])) ?> · back about <?= e($vm['end_time']) ?></p>
    <p class="muted"><?= e(Html::km($p['distance_km'])) ?> · <?= e(Html::duration((int) $p['duration_min'])) ?> · <?= e(ucfirst($p['difficulty'])) ?><?= $isGroup ? ' · ' . e($p['participants']) . ' of ' . e($p['group_size']) . ' places taken' : '' ?></p>
    <?php if ($isGroup && !$isPast): ?>
        <div class="head-actions"><button class="btn btn-sm btn-secondary" type="button" data-share data-title="Join my group hike: <?= e($p['trail_name']) ?>, <?= e(Html::date($p['planned_date'], 'D j M')) ?>" data-url="<?= e(url('plan', ['id' => $p['id']])) ?>">Share invite link</button></div>
        <p class="small muted" data-share-status role="status" aria-live="polite"></p>
    <?php endif; ?>
    <?php if (!$isOwner): ?><p class="muted">Organised by <?= e($p['organizer_name']) ?> · <a href="<?= e(url('trail', ['id' => $p['trail_id']])) ?>">trail details</a></p><?php endif; ?>
</header>

<?php if (!$isOwner && $isGroup && !$isPast): ?>
    <form method="post" action="<?= e(url($isParticipant ? 'group.leave' : 'group.join', ['id' => $p['id']])) ?>" class="join-bar">
        <?= Csrf::field() ?>
        <?php if ($isParticipant): ?>
            <span class="tag">You're in this group hike</span> <button class="btn btn-ghost btn-sm" type="submit">Leave</button>
        <?php elseif ($left > 0): ?>
            <button class="btn btn-primary btn-block" type="submit">Join this group hike (<?= e($left) ?> place<?= $left === 1 ? '' : 's' ?> left)</button>
        <?php else: ?>
            <p class="notice">This group hike is full.</p>
        <?php endif; ?>
    </form>
<?php endif; ?>

<?= \TrailSense\Core\View::partial('partials/risk', compact('vm', 'p', 'isOwner', 'isPast', 'report')) ?>

<?php if ($isOwner && $isGroup): ?>
<section class="card" aria-labelledby="part-h">
    <h2 id="part-h">Who is coming (<?= count($participants) ?> of <?= e($p['group_size']) ?>)</h2>
    <?php if ($participants === []): ?>
        <p class="muted">Nobody has joined yet. The hike is listed in <a href="<?= e(url('groups')) ?>">Group hikes</a>.</p>
    <?php else: ?>
        <ul class="participants">
            <?php foreach ($participants as $u): ?>
                <li><span class="avatar avatar-sm" aria-hidden="true"><?= Html::initial($u['name']) ?></span><a href="<?= e(url('profile', ['id' => $u['id']])) ?>"><?= e($u['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($briefing !== null && ($isOwner || $isParticipant)): ?>
<section class="card briefing" aria-labelledby="brief-h">
    <div class="section-head">
        <h2 id="brief-h">Group briefing</h2>
        <button class="btn btn-primary btn-sm" type="button" data-copy="#briefing-text">Copy briefing</button>
    </div>
    <p class="muted small">Meet at <?= e(Html::time($p['meeting_time'])) ?>, <?= e($p['meeting_point']) ?>. Paste this into your group chat.</p>
    <label class="sr-only" for="briefing-text">Briefing text</label>
    <textarea id="briefing-text" class="briefing-text" readonly rows="11"><?= e($briefing) ?></textarea>
    <p class="copy-status small" role="status" aria-live="polite"></p>
</section>
<?php endif; ?>

<section class="card map-card">
    <?= Html::map($p['route_points'], Html::isTrue($p['is_loop']), $p['trail_name']) ?>
    <div class="map-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('hike', ['id' => $p['trail_id']] + ($isOwner || $isParticipant ? ['plan_id' => $p['id']] : []))) ?>">▶ Start hike</a>
        <a class="btn btn-secondary btn-sm" href="<?= e(Html::directionsUrl($p['route_points'][0] ?? [$p['start_lat'], $p['start_lon']])) ?>" target="_blank" rel="noopener">Directions to the start</a>
        <a class="btn btn-secondary btn-sm" href="<?= e(url('trail', ['id' => $p['trail_id']])) ?>">Trail details</a>
    </div>
    <?php if ($p['notes'] !== ''): ?><p class="prose"><?= e($p['notes']) ?></p><?php endif; ?>
</section>

<?php if ($isOwner): ?>
<div class="actions">
    <?php if ($isPast): ?>
        <a class="btn btn-primary btn-block" href="<?= e(url('publish', ['plan_id' => $p['id']])) ?>">Publish this hike</a>
    <?php endif; ?>
    <?php if (!$isPast): ?>
        <a class="btn btn-secondary btn-block" href="<?= e(url('plan.edit', ['id' => $p['id']])) ?>"><?= $isGroup ? 'Edit group hike' : 'Edit plan' ?></a>
    <?php endif; ?>
    <form method="post" action="<?= e(url('plan.delete', ['id' => $p['id']])) ?>" data-confirm="<?= $isGroup && !$isPast ? 'Cancel this group hike? ' . (int) $p['participants'] . ' participant(s) will no longer see it.' : 'Delete this plan?' ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="kind" value="<?= $isGroup ? 'group' : 'personal' ?>">
        <button class="btn btn-ghost btn-block" type="submit"><?= $isGroup && !$isPast ? 'Cancel group hike' : 'Delete plan' ?></button>
    </form>
</div>
<?php endif; ?>
