<?php
declare(strict_types=1);

use TrailSense\Domain\Daylight;

return [
    'Porto sunset in mid-November is around 17:15' => function () {
        $sunset = Daylight::sunset(new DateTimeImmutable('2026-11-17'), 41.15, -8.61);
        $minutes = (int) $sunset->format('H') * 60 + (int) $sunset->format('i');
        assertTrue($minutes >= 17 * 60 && $minutes <= 17 * 60 + 30, 'got ' . $sunset->format('H:i'));
    },
    'Porto sunset in June is after 21:00 (summer time)' => function () {
        $sunset = Daylight::sunset(new DateTimeImmutable('2026-06-21'), 41.15, -8.61);
        assertTrue((int) $sunset->format('H') === 21, 'got ' . $sunset->format('H:i'));
    },
    'margin is negative when finishing after sunset' => function () {
        $end = new DateTimeImmutable('2026-11-17 18:00');
        $sunset = new DateTimeImmutable('2026-11-17 17:15');
        assertSame(-45, Daylight::marginMinutes($end, $sunset));
    },
    'latest start = sunset − 30 min − duration, rounded down to 5 min (AC5.2)' => function () {
        $sunset = new DateTimeImmutable('2026-11-17 17:17');
        assertSame('13:45', Daylight::latestStart($sunset, 180)->format('H:i'));  // 17:17-210min = 13:47 → 13:45
    },
];
