<?php use TrailSense\Core\Html; /** AC2.5 agenda row. @var array $p @var bool $past */
$date = new DateTimeImmutable($p['planned_date']); $days = (int) (new DateTimeImmutable('today'))->diff($date)->format('%r%a');
$end = (new DateTimeImmutable($p['planned_date'] . ' ' . $p['start_time']))->modify('+' . (int) $p['duration_min'] . ' minutes');
$isGroup = ($p['group_size'] ?? null) !== null;
$verdict = ['ok' => 'Good to go', 'caution' => 'Go prepared', 'warning' => 'Rethink this plan'][$p['overall_level'] ?? ''] ?? 'Not checked yet'; ?>
<a class="agenda-row<?= !empty($past) ? ' agenda-past' : '' ?>" href="<?= e(url('plan', ['id' => $p['id']])) ?>">
    <div class="ag-date" aria-hidden="true">
        <span class="ag-dow"><?= e($date->format('D')) ?></span><span class="ag-day"><?= e($date->format('j')) ?></span><span class="ag-mon"><?= e($date->format('M')) ?></span>
    </div>
    <div class="ag-map"><?= Html::cardMedia($p['route_points'], $p['is_loop'] ?? false, $p['trail_name'], $p['trail_thumb'] ?? null) ?></div>
    <div class="ag-main">
        <p class="title"><?= e($p['trail_name']) ?></p>
        <p class="muted"><span class="sr-only"><?= e(Html::date($p['planned_date'], 'l j F')) ?>, </span><?= e(Html::time($p['start_time'])) ?>–<?= e($end->format('H:i')) ?> · <?= e(Html::km($p['distance_km'])) ?><?= isset($p['elevation_gain_m']) ? ' · ↑ ' . e($p['elevation_gain_m']) . ' m' : '' ?></p>
        <p class="ag-kind small"><?php if ($isGroup): ?><span class="tag tag-sm">Group</span> <?= isset($p['participants']) ? e($p['participants']) . '/' : '' ?><?= e($p['group_size']) ?> people · meet <?= e(Html::time($p['meeting_time'])) ?>, <?= e($p['meeting_point']) ?><?php if (!empty($p['organizer_name']) && empty($own)): ?> · by <?= e($p['organizer_name']) ?><?php endif; ?><?php else: ?><span class="tag tag-sm tag-quiet">Personal</span> only you can see it<?php endif; ?></p>
    </div>
    <div class="ag-risk">
        <?= Html::badge($p['overall_level'] ?? null, ($p['is_complete'] ?? null) === false) ?>
        <span class="ag-verdict"><?= e($verdict) ?></span>
        <span class="small muted"><?= empty($past) ? ($days === 0 ? 'Today' : ($days === 1 ? 'Tomorrow' : 'In ' . $days . ' days')) : e(abs($days)) . ' days ago' ?></span>
    </div>
</a>
