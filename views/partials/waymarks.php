<?php /** Waymark legend (AC13.6). @var array $waymarks from Domain\Waymarks::forTrail */
use TrailSense\Domain\Waymarks;
$w = $waymarks; ?>
<div class="waymarks">
    <p class="waymarks-system"><strong><?= e($w['name']) ?></strong> — <?= e($w['about']) ?></p>
    <?php if ($w['system'] !== null): ?>
        <ul class="marks" aria-label="Trail marks">
            <?php foreach (Waymarks::MARKS as $key => [$label, $looks, $todo]): ?>
                <li class="mark">
                    <?= Waymarks::svg($key, $w['top'], $w['bottom'], $label . ' mark, ' . $w['name']) ?>
                    <span><strong><?= e($label) ?></strong><small><?= e($looks) ?> <?= e($todo) ?></small></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <ul class="waymark-tips">
        <?php foreach ($w['tips'] as $tip): ?><li><?= e($tip) ?></li><?php endforeach; ?>
    </ul>
    <p class="small muted">Marks of the Federação de Campismo e Montanhismo de Portugal (FCMP), as in the Rota Vicentina manual “Marcação, Sinalética e Manutenção” (2021).</p>
</div>
