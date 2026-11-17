<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; use TrailSense\Domain\RiskEngine;
$p = $plan; $isGroup = $p['group_size'] !== null; $left = $isGroup ? max(0, (int) $p['group_size'] - (int) $p['participants']) : 0;
$levelName = ['yellow' => 'Yellow', 'orange' => 'Orange', 'red' => 'Red']; ?>
<a class="back" href="<?= e($isOwner ? url('plans') : url('groups')) ?>">← <?= $isOwner ? ($isGroup ? 'My group hikes' : 'My plans') : 'Group hikes' ?></a>
<header class="plan-head">
    <h1><?= e($p['trail_name']) ?></h1>
    <p class="muted"><?= e(Html::date($p['planned_date'], 'l j F Y')) ?> · start <?= e(Html::time($p['start_time'])) ?> · back about <?= e($vm['end_time']) ?></p>
    <p class="muted"><?= e(Html::km($p['distance_km'])) ?> · <?= e(Html::duration((int) $p['duration_min'])) ?> · <?= e(ucfirst($p['difficulty'])) ?><?= $isGroup ? ' · ' . e($p['participants']) . ' of ' . e($p['group_size']) . ' places taken' : '' ?></p>
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

    <?php if ($vm['warnings']): ?>
        <div class="notice"><strong>IPMA warnings during your hike:</strong>
            <ul class="official-list">
                <?php foreach ($vm['warnings'] as $w): ?>
                    <li><span class="badge badge-<?= e($w['level']) ?>"><?= e($levelName[$w['level']] ?? $w['level']) ?></span> <?= e($w['type']) ?> · <?= e($w['start']) ?>–<?= e($w['end']) ?><?= $w['text'] !== '' ? ' · ' . e($w['text']) : '' ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
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
                <?php elseif ($f['status'] === 'pending'): ?>
                    <p class="rule"><?= $f['factor'] === 'fire' ? 'IPMA publishes the fire risk for today and tomorrow.' : 'IPMA publishes warnings about 3 days ahead.' ?> Checked again automatically when you open the plan closer to the date.</p>
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
        Sunset <?= e($vm['sunset']) ?> (calculated)<?php if ($vm['alerts_source'] === 'ipma'): ?> · Warnings and fire risk: IPMA<?php elseif ($vm['alerts_source'] === 'demo'): ?> · IPMA: demo data<?php endif; ?>
    </p>

    <?php if ($report): ?>
        <div class="community-report">
            <strong>Latest hiker report:</strong> <?= e(\TrailSense\Repositories\TrailReportRepository::CONDITIONS[$report['condition']]) ?>
            <span class="muted small">· <?= e($report['author']) ?>, <?= (int) $report['age_days'] === 0 ? 'today' : e($report['age_days']) . ' days ago' ?></span>
            <?php if ($report['note'] !== ''): ?><p><?= e($report['note']) ?></p><?php endif; ?>
        </div>
    <?php endif; ?>

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

    <?php if (!$isPast && $isOwner): ?>
        <form method="post" action="<?= e(url('plan.check', ['id' => $p['id']])) ?>">
            <?= Csrf::field() ?>
            <button class="btn btn-secondary btn-block" type="submit">Check again with the latest forecast</button>
        </form>
    <?php endif; ?>
</section>

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
    <form method="post" action="<?= e(url('plan.delete', ['id' => $p['id']])) ?>" data-confirm="Delete this plan?">
        <?= Csrf::field() ?>
        <button class="btn btn-ghost btn-block" type="submit">Delete plan</button>
    </form>
</div>
<?php endif; ?>
