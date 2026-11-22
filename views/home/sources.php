<?php use TrailSense\Domain\Sources;
/** Public page: where TrailSense's data, rules and evidence come from. */ ?>
<section class="sources-page">
    <h1>Sources and credits</h1>
    <p class="muted lp-lead">Where the information in TrailSense comes from. Forecasts can be wrong and conditions change — TrailSense is guidance, not a guarantee.</p>
    <?php foreach (Sources::all() as $key => $group): ?>
        <section class="src-group" aria-labelledby="src-<?= e($key) ?>">
            <h2 id="src-<?= e($key) ?>"><?= e($group['title']) ?></h2>
            <p class="muted small"><?= e($group['intro']) ?></p>
            <ul class="src-list">
                <?php foreach ($group['items'] as $s): ?>
                    <li><a href="<?= e($s['url']) ?>" target="_blank" rel="noopener"><?= e($s['name']) ?></a><span><?= e($s['what']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
    <p class="small muted">User research: 7 interviews with hikers and club organizers (November 2026). Built for the Information Systems Architecture course, FEUP. An AI assistant (Claude) helped write the code; the app itself uses no AI.</p>
</section>
