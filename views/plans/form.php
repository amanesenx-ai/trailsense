<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; ?>
<a class="back" href="<?= e(url('plans')) ?>">← <?= $isGroup ? 'Group hikes' : 'My plans' ?></a>
<h1><?= e($title) ?></h1>
<?php if ($errors): ?><p class="alert" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<form class="card form" method="post" action="<?= e(url('plan.store')) ?>" novalidate>
    <?= Csrf::field() ?>
    <label for="trail_id">Trail</label>
    <select id="trail_id" name="trail_id" required data-duration-source<?= Html::aria($errors, 'trail_id') ?>>
        <option value="">Choose a trail…</option>
        <?php foreach ($trails as $t): ?>
            <option value="<?= e($t['id']) ?>" data-duration="<?= e($t['est_duration_min']) ?>"<?= (string) ($old['trail_id'] ?? '') === (string) $t['id'] ? ' selected' : '' ?>>
                <?= e($t['name']) ?> — <?= e($t['region']) ?>, <?= e(Html::km($t['distance_km'])) ?></option>
        <?php endforeach; ?>
    </select>
    <?= Html::error($errors, 'trail_id') ?>

    <div class="row-3">
        <div>
            <label for="planned_date">Date</label>
            <input id="planned_date" name="planned_date" type="date" required min="<?= e(date('Y-m-d')) ?>" value="<?= e($old['planned_date'] ?? '') ?>"<?= Html::aria($errors, 'planned_date') ?>>
            <?= Html::error($errors, 'planned_date') ?>
        </div>
        <div>
            <label for="start_time">Start</label>
            <input id="start_time" name="start_time" type="time" required value="<?= e($old['start_time'] ?? '') ?>"<?= Html::aria($errors, 'start_time') ?>>
            <?= Html::error($errors, 'start_time') ?>
        </div>
        <div>
            <label for="duration_min">Duration <span class="hint">min</span></label>
            <input id="duration_min" name="duration_min" type="number" inputmode="numeric" min="30" max="720" step="5" required value="<?= e($old['duration_min'] ?? '') ?>"<?= Html::aria($errors, 'duration_min') ?>>
            <?= Html::error($errors, 'duration_min') ?>
        </div>
    </div>

    <?php if ($isGroup): ?>
        <fieldset class="group-fields">
            <legend>Group</legend>
            <div class="row-3">
                <div>
                    <label for="group_size">People</label>
                    <input id="group_size" name="group_size" type="number" inputmode="numeric" min="2" max="40" required value="<?= e($old['group_size'] ?? '') ?>"<?= Html::aria($errors, 'group_size') ?>>
                    <?= Html::error($errors, 'group_size') ?>
                </div>
                <div class="span-2">
                    <label for="meeting_point">Meeting point</label>
                    <input id="meeting_point" name="meeting_point" maxlength="120" required placeholder="e.g. Valongo train station" value="<?= e($old['meeting_point'] ?? '') ?>"<?= Html::aria($errors, 'meeting_point') ?>>
                    <?= Html::error($errors, 'meeting_point') ?>
                </div>
            </div>
            <label for="meeting_time">Meeting time</label>
            <input id="meeting_time" name="meeting_time" type="time" required value="<?= e($old['meeting_time'] ?? '') ?>"<?= Html::aria($errors, 'meeting_time') ?>>
            <?= Html::error($errors, 'meeting_time') ?>
        </fieldset>
    <?php endif; ?>

    <label for="notes">Notes <span class="hint">optional</span></label>
    <textarea id="notes" name="notes" rows="2" maxlength="500"<?= Html::aria($errors, 'notes') ?>><?= e($old['notes'] ?? '') ?></textarea>
    <?= Html::error($errors, 'notes') ?>

    <button class="btn btn-primary btn-block" type="submit">Save plan and check conditions</button>
</form>
