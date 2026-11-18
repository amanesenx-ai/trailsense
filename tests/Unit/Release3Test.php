<?php
declare(strict_types=1);

use TrailSense\Domain\Waymarks;
use TrailSense\Domain\WeatherCode;
use TrailSense\Weather\OpenMeteoProvider;

$fixture = (string) file_get_contents(__DIR__ . '/../fixtures/open-meteo-valongo.json');

return [
    'hourly rows cover the hike window ± 2 h and flag the window (AC4.13)' => function () use ($fixture) {
        $f = OpenMeteoProvider::parse($fixture, new DateTimeImmutable('2026-11-21 09:30'), new DateTimeImmutable('2026-11-21 12:30'));
        assertSame(['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00'], array_column($f->hours, 'time'));
        assertSame([false, false, true, true, true, true, false, false], array_column($f->hours, 'in_window'));
        assertSame(63, $f->hours[3]['code']);
        assertSame(47.0, (float) $f->hours[3]['gust']);
    },
    'the request asks for weather codes and is the same URL shown to users (AC4.15)' => function () {
        $u = OpenMeteoProvider::url(41.19, -8.5, new DateTimeImmutable('2026-11-21 09:00'), new DateTimeImmutable('2026-11-21 11:00'));
        assertContains('weather_code', $u);
        assertContains('start_date=2026-11-21', $u);
    },
    'WMO weather codes map to label and icon' => function () {
        assertSame(['Clear sky', 'sun'], WeatherCode::describe(0));
        assertSame(['Rain', 'rain'], WeatherCode::describe(63));
        assertSame(['Thunderstorm', 'storm'], WeatherCode::describe(95));
        assertSame(['Fog', 'fog'], WeatherCode::describe(45));
        assertSame('cloud', WeatherCode::describe(null)[1]);
    },
    'waymark legend follows the trail code (FCMP): PR yellow/red, GR white/red, PL green/white (AC13.6)' => function () {
        $pr = Waymarks::forTrail('PR2 VLG');
        assertSame('PR', $pr['system']);
        assertSame(['#ffd400', '#d0202e'], [$pr['top'], $pr['bottom']]);
        assertSame(['#ffffff', '#7ab51d'], [Waymarks::forTrail('PL 1')['top'], Waymarks::forTrail('PL 1')['bottom']], 'PL is white over green (manual p. 13)');
        assertSame('GR', Waymarks::forTrail('GR28')['system']);
        assertSame('PL', Waymarks::forTrail('PL 1 FAR')['system']);
        assertSame('PR', Waymarks::forTrail('PR10TBR')['system']);
    },
    'routes without a PR/GR/PL code get their own guidance (boardwalk)' => function () {
        $w = Waymarks::forTrail('', 'Passadiços do Paiva');
        assertSame(null, $w['system']);
        assertSame('Managed walkway', $w['name']);
        assertTrue(count($w['tips']) >= 2);
    },
];
