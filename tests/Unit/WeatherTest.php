<?php
declare(strict_types=1);

use TrailSense\Weather\OpenMeteoProvider;

$fixture = (string) file_get_contents(__DIR__ . '/../fixtures/open-meteo-valongo.json');
$today = new DateTimeImmutable('2026-11-17');
$start = new DateTimeImmutable('2026-11-21 09:30');
$end = new DateTimeImmutable('2026-11-21 12:30');

return [
    'parses Open-Meteo hourly data for the hike window only (AC3.1/3.2)' => function () use ($fixture, $start, $end) {
        $f = OpenMeteoProvider::parse($fixture, $start, $end);
        assertTrue($f->available);
        // hours 09,10,11,12 (12:00 < 12:30) → precip 0.4+1.2+0.8+0.3 = 2.7
        assertSame(2.7, $f->precipTotalMm);
        assertSame(47.0, $f->gustMaxKmh);
        assertSame(9.8, $f->tempMinC);
        assertSame(13.1, $f->tempMaxC);
    },
    'beyond the 16-day horizon → unavailable with a clear message (AC3.3)' => function () use ($today) {
        $called = false;
        $p = new OpenMeteoProvider(function () use (&$called) { $called = true; return '{}'; }, $today);
        $s = new DateTimeImmutable('2026-12-20 10:00');
        $f = $p->forecast(41.19, -8.49, $s, $s->modify('+2 hours'));
        assertSame(false, $f->available);
        assertContains('16 days', $f->message);
        assertSame(false, $called, 'no HTTP call when out of horizon');
    },
    'service down → unavailable, nothing invented (ADR-004)' => function () use ($today, $start, $end) {
        $p = new OpenMeteoProvider(fn () => null, $today);
        $f = $p->forecast(41.19, -8.49, $start, $end);
        assertSame(false, $f->available);
        assertSame(null, $f->precipTotalMm);
    },
    'malformed answer → unavailable' => function () use ($start, $end) {
        assertSame(false, OpenMeteoProvider::parse('<html>error</html>', $start, $end)->available);
    },
    'request URL contains coordinates, date and km/h units' => function () use ($today, $start, $end) {
        $url = '';
        $p = new OpenMeteoProvider(function (string $u) use (&$url) { $url = $u; return null; }, $today);
        $p->forecast(41.193, -8.498, $start, $end);
        assertContains('latitude=41.193', $url);
        assertContains('start_date=2026-11-21', $url);
        assertContains('wind_speed_unit=kmh', $url);
        assertContains('timezone=Europe%2FLisbon', $url);
    },
];
