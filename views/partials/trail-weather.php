<?php use TrailSense\Core\Html; use TrailSense\Core\Icons; use TrailSense\Domain\WeatherCode;
/** AC1.16 next 7 days at the trail start. @var array $outlook @var array $t */ ?>
<section class="card outlook" aria-labelledby="wx-h">
    <div class="section-head"><h2 id="wx-h">Weather at the start, next 7 days</h2></div>
    <?php if ($outlook['days'] === []): ?>
        <p class="muted">The forecast is not available right now. Plan your hike anyway — the risk check fetches it again for your date.</p>
    <?php else: ?>
        <ol class="days">
            <?php foreach ($outlook['days'] as $i => $d): [$label, $icon] = WeatherCode::describe($d['code']);
                $risky = ($d['prob'] ?? 0) >= 60 || ($d['gust'] ?? 0) >= 50; ?>
                <li class="day<?= $risky ? ' day-risky' : '' ?>">
                    <span class="day-name"><?= $i === 0 ? 'Today' : e(Html::date($d['date'], 'D j')) ?></span>
                    <?= Icons::svg($icon, 'day-icon') ?><span class="sr-only"><?= e($label) ?></span>
                    <span class="day-temp"><strong><?= e($d['tmax'] ?? '–') ?>°</strong> / <?= e($d['tmin'] ?? '–') ?>°</span>
                    <span class="day-rain" title="Chance of rain"><?= Icons::svg('rain', 'icon-xs') ?> <?= e($d['prob'] ?? '–') ?>%</span>
                    <span class="day-wind" title="Wind / gusts"><?= Icons::svg('wind', 'icon-xs') ?> <?= e($d['wind'] ?? '–') ?>/<?= e($d['gust'] ?? '–') ?></span>
                    <a class="day-plan" href="<?= e(url('plan.new', ['trail_id' => $t['id'], 'date' => $d['date']])) ?>">Plan this day</a>
                </li>
            <?php endforeach; ?>
        </ol>
        <p class="small muted">Wind and gusts in km/h. Days with a high chance of rain or strong gusts are highlighted. <?= $outlook['source'] === 'demo' ? 'Demo forecast (offline mode)' : 'Source: Open-Meteo' ?>, updated <?= e(substr((string) $outlook['fetched_at'], 8, 2) . '/' . substr((string) $outlook['fetched_at'], 5, 2) . ' at ' . substr((string) $outlook['fetched_at'], 11, 5)) ?>.
            <?php if ($outlook['url']): ?><a href="<?= e($outlook['url']) ?>" target="_blank" rel="noopener">Raw data</a>.<?php endif; ?>
            Plan a hike to get the full risk check with IPMA warnings, fire risk and daylight.</p>
    <?php endif; ?>
</section>
