<?php /** Waymark legend (AC13.6). @var array $waymarks from Domain\Waymarks::forTrail */
use TrailSense\Domain\Waymarks;
$w = $waymarks;
$stripe = fn (string $d, string $c) => '<path d="' . $d . '" stroke="' . e($c) . '" stroke-width="6.5" fill="none" stroke-linecap="butt" stroke-linejoin="miter"/>';
$shapes = [
    'right' => ['M8 14H52', 'M8 24H52'],
    'left' => ['M52 11H24L13 27', 'M52 20H29L21 33'],
    'turn-right' => ['M8 11H36L47 27', 'M8 20H31L39 33'],
    'wrong' => ['M15 7L45 33', 'M45 7L15 33'],
];
?>
<div class="waymarks">
    <p class="waymarks-system"><strong><?= e($w['name']) ?></strong> — <?= e($w['about']) ?></p>
    <?php if ($w['system'] !== null): ?>
        <ul class="marks" aria-label="Trail marks">
            <?php foreach (Waymarks::MARKS as $key => [$label, $hint]): ?>
                <li class="mark">
                    <svg viewBox="0 0 60 40" role="img" aria-label="<?= e($label) ?> mark: <?= e($w['name']) ?>">
                        <rect x="1" y="1" width="58" height="38" rx="6" fill="#8b928e"/>
                        <?= $stripe($shapes[$key][0], $w['top']) ?><?= $stripe($shapes[$key][1], $w['bottom']) ?>
                    </svg>
                    <span><strong><?= e($label) ?></strong><small><?= e($hint) ?></small></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <ul class="waymark-tips">
        <?php foreach ($w['tips'] as $tip): ?><li><?= e($tip) ?></li><?php endforeach; ?>
    </ul>
    <p class="small muted">Marking standard of the Federação de Campismo e Montanhismo de Portugal (FCMP).</p>
</div>
