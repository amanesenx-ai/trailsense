<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; ?>
<a class="back" href="<?= e(url('feed')) ?>">← Community</a>
<h1>Publish a hike</h1>
<?php if ($errors): ?><p class="alert" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<form class="card form" method="post" action="<?= e(url('publish.store')) ?>" enctype="multipart/form-data" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="plan_id" value="<?= e($old['plan_id'] ?? '') ?>">
    <label for="trail_id">Trail</label>
    <select id="trail_id" name="trail_id" required<?= Html::aria($errors, 'trail_id') ?>>
        <option value="">Choose the trail you hiked…</option>
        <?php foreach ($trails as $t): ?>
            <option value="<?= e($t['id']) ?>"<?= (string) ($old['trail_id'] ?? '') === (string) $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?> — <?= e($t['region']) ?></option>
        <?php endforeach; ?>
    </select>
    <?= Html::error($errors, 'trail_id') ?>
    <div class="row-3">
        <div>
            <label for="hiked_on">Date</label>
            <input id="hiked_on" name="hiked_on" type="date" required max="<?= e(date('Y-m-d')) ?>" value="<?= e($old['hiked_on'] ?? '') ?>"<?= Html::aria($errors, 'hiked_on') ?>>
            <?= Html::error($errors, 'hiked_on') ?>
        </div>
        <div>
            <label for="duration_min">Duration <span class="hint">min</span></label>
            <input id="duration_min" name="duration_min" type="number" inputmode="numeric" min="10" max="1440" required value="<?= e($old['duration_min'] ?? '') ?>"<?= Html::aria($errors, 'duration_min') ?>>
            <?= Html::error($errors, 'duration_min') ?>
        </div>
        <div>
            <label for="distance_km">Distance <span class="hint">km, optional</span></label>
            <input id="distance_km" name="distance_km" inputmode="decimal" placeholder="trail distance" value="<?= e($old['distance_km'] ?? '') ?>"<?= Html::aria($errors, 'distance_km') ?>>
            <?= Html::error($errors, 'distance_km') ?>
        </div>
    </div>
    <label for="notes">Notes for other hikers <span class="hint">max 1000</span></label>
    <textarea id="notes" name="notes" rows="4" maxlength="1000" placeholder="Trail conditions, tips, what you would do differently…"<?= Html::aria($errors, 'notes') ?>><?= e($old['notes'] ?? '') ?></textarea>
    <?= Html::error($errors, 'notes') ?>
    <label for="photo">Photo <span class="hint">JPEG, PNG or WebP · phone photos are resized automatically</span></label>
    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" data-shrink<?= Html::aria($errors, 'photo') ?>>
    <p class="hint" id="photo-status" role="status" aria-live="polite"></p>
    <?= Html::error($errors, 'photo') ?>
    <label for="photo_alt">Describe the photo <span class="hint">for screen readers, optional</span></label>
    <input id="photo_alt" name="photo_alt" maxlength="150" value="<?= e($old['photo_alt'] ?? '') ?>">
    <button class="btn btn-primary btn-block" type="submit">Publish</button>
</form>
