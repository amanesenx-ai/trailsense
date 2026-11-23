<?php
declare(strict_types=1);

use TrailSense\Domain\PracticeRoute;

/* AC13.19 practice route: starts and ends at the hiker, four turns, about 600 m. */
$dist = function (array $a, array $b): float {
    $r = 6371000; $dLat = deg2rad($b[0] - $a[0]); $dLon = deg2rad($b[1] - $a[1]);
    $h = sin($dLat / 2) ** 2 + cos(deg2rad($a[0])) * cos(deg2rad($b[0])) * sin($dLon / 2) ** 2;
    return 2 * $r * asin(sqrt($h));
};
return [
    'practice square starts and ends at the given position (FEUP) and is about 600 m' => function () use ($dist) {
        $pts = PracticeRoute::around(41.1780, -8.5980);
        assertSame([41.178, -8.598], $pts[0]);
        assertSame($pts[0], $pts[count($pts) - 1]);
        $len = 0.0;
        for ($i = 1; $i < count($pts); $i++) { $len += $dist($pts[$i - 1], $pts[$i]); }
        assertTrue(abs($len - 600) < 15, 'length ' . round($len));
        assertTrue(count($pts) >= 20, 'points every ~25 m');
    },
    'only real coordinates are accepted' => function () {
        assertTrue(PracticeRoute::validCoordinate('41.178', '-8.598'));
        assertTrue(!PracticeRoute::validCoordinate('abc', '-8.5'));
        assertTrue(!PracticeRoute::validCoordinate('91', '0'));
        assertTrue(!PracticeRoute::validCoordinate(null, null));
    },
];
