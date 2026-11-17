<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html;
$p = $plan; $isGroup = $p['group_size'] !== null; ?>
<a class="back" href="<?= e(url('plans')) ?>">← <?= $isGroup ? 'Group hikes' : 'My plans' ?></a>
<header class="plan-head">
    <h1><?= e($p['trail_name']) ?></h1>
    <p class="muted"><?= e(Html::date($p['planned_date'], 'l j F Y')) ?> · start <?= e(Html::time($p['start_time'])) ?> · back about <?= e($vm['end_time']) ?></p>
    <p class="muted"><?= e(Html::km($p['distance_km'])) ?> · <?= e(Html::duration((int) $p['duration_min'])) ?> · <?= e(ucfirst($p['difficulty'])) ?><?= $isGroup ? ' · ' . e($p['group_size']) . ' people' : '' ?></p>
</header>

<section class="card assessment" aria-labelledby="risk-h">
    <div class="section-head">
        <h2 id="risk-h">Risk assessment</h2>
        <span class="overall"><?= Html::badge($vm['overall'], !$vm['complete']) ?></span>
    </div>

    <?php if (!$vm['available']): ?>
        <p class="notice"><?= e($vm['message']) ?> Daylight was still checked.</p>
    <?php endif; ?>

    <?php if (isset($vm['factors']['daylight']) && $vm['factors']['daylight']['status'] === 'warning'): ?>
        <p class="callout">Start by <strong><?= e($vm['latest_start']) ?></strong> or choose a shorter trail.</p>
    <?php endif; ?>

    <ul class="factors">
        <?php foreach ($vm['factors'] as $f): ?>
            <li class="factor factor-<?= e($f['status']) ?>">
                <div class="factor-head">
                    <span class="factor-name"><?= e($f['label']) ?></span>
                    <span class="factor-value"><?= e($f['observed_text']) ?></span>
                    <?= Html::badge($f['status']) ?>
                </div>
                <?php if ($f['rule']): ?>
                    <p class="rule">Rule <code><?= e($f['rule']['code']) ?></code> triggered: <?= e($f['rule']['name']) ?> (<?= e($f['condition']) ?>).</p>
                    <p class="precaution"><span aria-hidden="true">→</span> <?= e($f['precaution']) ?></p>
                <?php elseif ($f['status'] === 'unknown'): ?>
                    <p class="rule">Not checked yet: no forecast data for these hours.</p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="source small muted">
        <?php if ($vm['source'] === 'demo'): ?>
            Demo forecast (offline mode, not real data) ·
        <?php elseif ($vm['source'] === 'open-meteo' && $vm['available']): ?>
            Forecast: Open-Meteo, checked <?= e(Html::date(substr($vm['fetched_at'], 0, 10), 'j M')) ?> at <?= e(substr($vm['fetched_at'], 11, 5)) ?> ·
        <?php endif; ?>
        Sunset <?= e($vm['sunset']) ?> (calculated)
    </p>

    <details class="rules-table">
        <summary>See every rule that was checked</summary>
        <table>
            <thead><tr><th scope="col">Rule</th><th scope="col">Condition</th><th scope="col">Observed</th><th scope="col">Result</th></tr></thead>
            <tbody>
            <?php foreach ($vm['snapshot']['results'] as $r): ?>
                <tr>
                    <td><code><?= e($r['rule']['code']) ?></code></td>
                    <td><?= e(\TrailSense\Domain\RiskEngine::describeCondition($r['rule'])) ?></td>
                    <td><?= e(\TrailSense\Domain\RiskEngine::formatObserved($r['observed'], $r['rule']['metric'])) ?></td>
                    <td><?= !$r['evaluated'] ? Html::badge('unknown') : ($r['triggered'] ? Html::badge($r['rule']['level']) : '<span class="muted">not triggered</span>') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </details>

    <?php if (!$isPast): ?>
        <form method="post" action="<?= e(url('plan.check', ['id' => $p['id']])) ?>">
            <?= Csrf::field() ?>
            <button class="btn btn-secondary btn-block" type="submit">Check again with the latest forecast</button>
        </form>
    <?php endif; ?>
</section>

<?php if ($briefing !== null): ?>
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

<section class="card route-card">
    <?= Html::route($p['route_points'], 'Route of ' . $p['trail_name'], 'route route-large') ?>
    <?php if ($p['notes'] !== ''): ?><p class="prose"><?= e($p['notes']) ?></p><?php endif; ?>
</section>

<div class="actions">
    <?php if ($isPast): ?>
        <a class="btn btn-primary btn-block" href="<?= e(url('publish', ['plan_id' => $p['id']])) ?>">Publish this hike</a>
    <?php endif; ?>
    <form method="post" action="<?= e(url('plan.delete', ['id' => $p['id']])) ?>" data-confirm="Delete this plan?">
        <?= Csrf::field() ?>
        <button class="btn btn-ghost btn-block" type="submit">Delete plan</button>
    </form>
</div>
