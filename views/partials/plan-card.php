<?php use TrailSense\Core\Html; /** @var array $p */ ?>
<a class="plan-card" href="<?= e(url('plan', ['id' => $p['id']])) ?>">
    <?= Html::cardMedia($p['route_points'], $p['is_loop'] ?? false, $p['trail_name'], $p['trail_thumb'] ?? null) ?>
    <div class="plan-card-body">
        <p class="title"><?= e($p['trail_name']) ?></p>
        <p class="muted"><?= e(Html::date($p['planned_date'])) ?> · <?= e(Html::time($p['start_time'])) ?> · <?= e(Html::km($p['distance_km'])) ?></p>
        <?php if ($p['group_size'] !== null): ?>
            <p class="muted small"><?= isset($p['participants']) ? e($p['participants']) . '/' : '' ?><?= e($p['group_size']) ?> people · meet <?= e(Html::time($p['meeting_time'])) ?>, <?= e($p['meeting_point']) ?><?= array_key_exists('notes', $p) && $p['notes'] === null ? ' · by ' . e($p['organizer_name']) : '' ?></p>
        <?php endif; ?>
        <p><?= Html::badge($p['overall_level'], $p['is_complete'] === false) ?></p>
    </div>
</a>
