<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; use TrailSense\Core\Icons; use TrailSense\Core\WeatherChart;
use TrailSense\Domain\RiskEngine; use TrailSense\Domain\WeatherCode;
/** F4.3 visual risk check (AC4.11–AC4.15). @var array $vm @var array $p @var bool $isOwner @var bool $isPast @var ?array $report */
$v = $vm['verdict']; $levelName = ['yellow' => 'Yellow', 'orange' => 'Orange', 'red' => 'Red'];
$statusText = ['ok' => 'OK', 'caution' => 'Caution', 'warning' => 'Warning', 'unknown' => 'No data', 'pending' => 'Not published yet'];
$verdictIcon = ['ok' => 'check', 'caution' => 'alert', 'warning' => 'official', 'pending' => 'clock'][$v['key']];
$hours = $vm['hours']; $links = $vm['links']; ?>
<section class="card assessment risk" aria-labelledby="risk-h">
    <h2 id="risk-h" class="sr-only">Risk check</h2>
    <div class="verdict verdict-<?= e($v['key']) ?>">
        <?= Icons::svg($verdictIcon, 'verdict-icon') ?>
        <div>
            <p class="verdict-kicker">Risk check · <?= e(Html::date($p['planned_date'], 'D j M')) ?>, <?= e($vm['start_time']) ?>–<?= e($vm['end_time']) ?></p>
            <p class="verdict-title"><?= e($v['title']) ?></p>
            <p class="verdict-reason"><?= e($v['reason']) ?></p>
        </div>
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

    <h3 class="risk-h3">Hour by hour</h3>
    <?php if (count($hours) >= 2): ?>
        <div class="wx-wrap"><?= WeatherChart::svg($hours, $vm['sunset']) ?></div>
        <ol class="hours" aria-label="Forecast per hour">
            <?php foreach ($hours as $h): [$label, $icon] = WeatherCode::describe($h['code']); ?>
                <li class="hour<?= $h['in_window'] ? ' hour-in' : '' ?>">
                    <span class="hour-time"><?= e($h['time']) ?></span>
                    <?= Icons::svg($icon, 'hour-icon') ?><span class="sr-only"><?= e($label) ?></span>
                    <span class="hour-temp"><?= $h['temp'] === null ? '–' : e(round((float) $h['temp'])) . '°' ?></span>
                    <span class="hour-rain"><?= e($h['precip'] ?? '–') ?> mm<?= isset($h['prob']) ? ' · ' . e($h['prob']) . '%' : '' ?></span>
                    <span class="hour-gust"><?= isset($h['wind']) ? e($h['wind']) . '/' : '' ?><?= e($h['gust'] ?? '–') ?> km/h</span>
                </li>
            <?php endforeach; ?>
        </ol>
        <p class="small muted">Highlighted: the hours of your hike. Rain: mm · chance. Wind: speed/gusts. Tap or hover the chart for each hour's values.</p>
    <?php else: ?>
        <p class="muted">No hourly forecast stored for this check yet<?= !$isPast && $isOwner ? ' — press “Check again” below to see it hour by hour' : '' ?>.</p>
    <?php endif; ?>

    <h3 class="risk-h3">What to bring and do</h3>
    <ul class="checklist" data-checklist="plan-<?= e($p['id']) ?>">
        <?php foreach ($vm['checklist'] as $i => $c): ?>
            <li class="check-<?= e($c['level']) ?>"><label><input type="checkbox" value="<?= e($i) ?>"> <span><?= e($c['text']) ?></span></label></li>
        <?php endforeach; ?>
    </ul>

    <h3 class="risk-h3">What we checked</h3>
    <ul class="tiles">
        <?php foreach ($vm['factors'] as $f): ?>
            <li class="tile tile-<?= e($f['status']) ?>">
                <details>
                    <summary>
                        <?= Icons::svg($f['factor'], 'tile-icon') ?>
                        <span class="tile-name"><?= e($f['label']) ?></span>
                        <span class="tile-value"><?= e($f['observed_text']) ?></span>
                        <span class="tile-status"><?= e($statusText[$f['status']] ?? $f['status']) ?></span>
                    </summary>
                    <?php if ($f['rule']): ?>
                        <p class="rule">Rule <code><?= e($f['rule']['code']) ?></code>: <?= e($f['rule']['name']) ?> (<?= e($f['condition']) ?>).</p>
                        <p class="precaution">→ <?= e($f['precaution']) ?></p>
                    <?php elseif ($f['status'] === 'unknown'): ?>
                        <p class="rule">Not checked yet: no forecast data for these hours.</p>
                    <?php elseif ($f['status'] === 'pending'): ?>
                        <p class="rule"><?= $f['factor'] === 'fire' ? 'IPMA publishes the fire risk for today and tomorrow.' : 'IPMA publishes warnings about 3 days ahead.' ?> Checked again automatically closer to the date.</p>
                    <?php else: ?>
                        <p class="rule">Within the safe limits of every <?= e(mb_strtolower($f['label'])) ?> rule.</p>
                    <?php endif; ?>
                </details>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($report): ?>
        <div class="community-report">
            <strong>Latest hiker report:</strong> <?= e(\TrailSense\Repositories\TrailReportRepository::CONDITIONS[$report['condition']]) ?>
            <span class="muted small">· <?= e($report['author']) ?>, <?= (int) $report['age_days'] === 0 ? 'today' : e($report['age_days']) . ' days ago' ?></span>
            <?php if ($report['note'] !== ''): ?><p><?= e($report['note']) ?></p><?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="sources">
        <p class="small muted">
            <?php if ($vm['source'] === 'demo'): ?>Demo forecast (offline mode, not real data) ·
            <?php elseif ($vm['source'] === 'open-meteo' && $vm['available']): ?>Forecast: Open-Meteo, checked <?= e(Html::date(substr($vm['fetched_at'], 0, 10), 'j M')) ?> at <?= e(substr($vm['fetched_at'], 11, 5)) ?> ·
            <?php endif; ?>
            Sunset <?= e($vm['sunset']) ?> (calculated)<?php if ($vm['alerts_source'] === 'ipma'): ?> · Warnings and fire risk: IPMA<?php elseif ($vm['alerts_source'] === 'demo'): ?> · IPMA: demo data<?php endif; ?>
        </p>
        <div class="source-links">
            <?php if ($links['api']): ?><a class="chip-link" href="<?= e($links['api']) ?>" target="_blank" rel="noopener"><?= Icons::svg('external', 'icon-xs') ?> Open-Meteo API (raw JSON we used)</a><?php endif; ?>
            <?php if ($links['page']): ?><a class="chip-link" href="<?= e($links['page']) ?>" target="_blank" rel="noopener"><?= Icons::svg('external', 'icon-xs') ?> Open-Meteo for this place</a><?php endif; ?>
            <?php if ($links['ipma']): ?><a class="chip-link" href="<?= e($links['ipma']) ?>" target="_blank" rel="noopener"><?= Icons::svg('external', 'icon-xs') ?> IPMA warnings for the district</a><?php endif; ?>
        </div>
    </div>

    <details class="rules-table">
        <summary>See every rule that was checked</summary>
        <table>
            <thead><tr><th scope="col">Rule</th><th scope="col">Condition</th><th scope="col">Observed</th><th scope="col">Result</th></tr></thead>
            <tbody>
            <?php foreach ($vm['snapshot']['results'] as $r): ?>
                <tr>
                    <td><code><?= e($r['rule']['code']) ?></code></td>
                    <td><?= e(RiskEngine::describeCondition($r['rule'])) ?></td>
                    <td><?= e(RiskEngine::formatObserved($r['observed'], $r['rule']['metric'])) ?></td>
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
