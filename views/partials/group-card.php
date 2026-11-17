<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; /** @var array $h */
$left = max(0, (int) $h['group_size'] - (int) $h['participants']); $joined = Html::isTrue($h['joined']);
$mine = (int) $h['organizer_id'] === (int) (TrailSense\Core\Auth::user()['id'] ?? 0); ?>
<article class="group-card">
    <a class="group-card-link" href="<?= e(url('plan', ['id' => $h['id']])) ?>">
        <?= Html::route(is_array($h['route_points']) ? $h['route_points'] : (json_decode((string) $h['route_points'], true) ?: []), 'Route of ' . $h['trail_name'], 'route route-thumb') ?>
        <div>
            <p class="title"><?= e($h['trail_name']) ?></p>
            <p class="muted"><?= e(Html::date($h['planned_date'])) ?> · start <?= e(Html::time($h['start_time'])) ?> · <?= e(Html::km($h['distance_km'])) ?></p>
            <p class="muted small">by <?= e($h['organizer_name']) ?> · meet <?= e(Html::time($h['meeting_time'])) ?>, <?= e($h['meeting_point']) ?></p>
            <p><?= Html::badge($h['overall_level'], $h['is_complete'] === false) ?>
                <span class="places<?= $left === 0 ? ' places-full' : '' ?>"><?= $left === 0 ? 'Full' : e($left) . ' place' . ($left === 1 ? '' : 's') . ' left' ?></span></p>
        </div>
    </a>
    <?php if (!$mine): ?>
    <form method="post" action="<?= e(url($joined ? 'group.leave' : 'group.join', ['id' => $h['id']])) ?>">
        <?= Csrf::field() ?>
        <?php if ($joined): ?>
            <button class="btn btn-ghost btn-sm" type="submit">Leave</button> <span class="tag tag-sm">You're in</span>
        <?php elseif ($left > 0): ?>
            <button class="btn btn-primary btn-sm" type="submit">Join this hike</button>
        <?php endif; ?>
    </form>
    <?php else: ?>
        <p class="muted small">You organise this hike · <?= e($h['participants']) ?> joined</p>
    <?php endif; ?>
</article>
