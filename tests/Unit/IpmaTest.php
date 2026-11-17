<?php
declare(strict_types=1);

use TrailSense\Alerts\IpmaProvider;

$warnings = (string) file_get_contents(__DIR__ . '/../fixtures/ipma-warnings.json');
$rcm = (string) file_get_contents(__DIR__ . '/../fixtures/ipma-rcm.json');
$today = new DateTimeImmutable('2026-11-18');
$fetch = function (string $url) use ($warnings, $rcm): ?string {
    return str_contains($url, 'warnings') ? $warnings : (str_contains($url, 'rcm-d1') ? $rcm : null);
};

return [
    'only warnings of the trail district that overlap the hike window count (AC4.7)' => function () use ($warnings) {
        [$level, $list] = IpmaProvider::matchWarnings(json_decode($warnings, true), 'PTO', false,
            new DateTimeImmutable('2026-11-19 09:00'), new DateTimeImmutable('2026-11-19 11:30'));
        assertSame(1, $level, 'yellow rain only; orange wind starts at 12:00; BRG red ignored');
        assertSame(['Precipitação'], array_column($list, 'type'));
    },
    'orange beats yellow when both overlap' => function () use ($warnings) {
        [$level] = IpmaProvider::matchWarnings(json_decode($warnings, true), 'PTO', false,
            new DateTimeImmutable('2026-11-19 10:00'), new DateTimeImmutable('2026-11-19 14:00'));
        assertSame(2, $level);
    },
    'coastal swell warnings only apply to coastal trails' => function () use ($warnings) {
        $s = new DateTimeImmutable('2026-11-19 16:00'); $e = new DateTimeImmutable('2026-11-19 18:00');
        [, $inland] = IpmaProvider::matchWarnings(json_decode($warnings, true), 'PTO', false, $s, $e);
        [, $coast] = IpmaProvider::matchWarnings(json_decode($warnings, true), 'PTO', true, $s, $e);
        assertSame(['Vento'], array_column($inland, 'type'));
        assertSame(['Vento', 'Agitação Marítima'], array_column($coast, 'type'));
    },
    'green warnings are ignored' => function () use ($warnings) {
        [$level] = IpmaProvider::matchWarnings(json_decode($warnings, true), 'PTO', false,
            new DateTimeImmutable('2026-11-19 00:30'), new DateTimeImmutable('2026-11-19 02:00'));
        assertSame(0, $level);
    },
    'fire risk comes from the nearest municipality (AC4.8)' => function () use ($rcm) {
        assertSame(2, IpmaProvider::nearestFireRisk(json_decode($rcm, true), 41.178, -8.488));   // Valongo
        assertSame(4, IpmaProvider::nearestFireRisk(json_decode($rcm, true), 40.97, -8.19));     // Arouca
        assertSame(null, IpmaProvider::nearestFireRisk([], 41.0, -8.0));
    },
    'tomorrow: warnings and fire risk (rcm-d1) are both evaluated' => function () use ($fetch, $today) {
        $a = (new IpmaProvider($fetch, $today))->alerts('PTO', false, 41.19, -8.5,
            new DateTimeImmutable('2026-11-19 09:00'), new DateTimeImmutable('2026-11-19 11:00'));
        assertSame('ipma', $a->source);
        assertSame(1, $a->warningLevel);
        assertSame(2, $a->fireRisk);
    },
    'beyond the horizon: not published yet (null), with an explanation (AC4.9)' => function () use ($fetch, $today) {
        $a = (new IpmaProvider($fetch, $today))->alerts('PTO', false, 41.19, -8.5,
            new DateTimeImmutable('2026-11-25 09:00'), new DateTimeImmutable('2026-11-25 11:00'));
        assertSame(null, $a->warningLevel);
        assertSame(null, $a->fireRisk);
        assertContains('3 days ahead', $a->message);
    },
    'IPMA down: nothing invented, page keeps working (AC4.10)' => function () use ($today) {
        $a = (new IpmaProvider(fn () => null, $today))->alerts('PTO', false, 41.19, -8.5,
            new DateTimeImmutable('2026-11-18 15:00'), new DateTimeImmutable('2026-11-18 17:00'));
        assertSame(null, $a->warningLevel);
        assertSame(null, $a->fireRisk);
        assertContains('could not be loaded', $a->message);
    },
    'each IPMA file is downloaded once per request (cache)' => function () use ($warnings, $today) {
        $calls = 0;
        $p = new IpmaProvider(function () use (&$calls, $warnings) { $calls++; return $warnings; }, $today);
        $s = new DateTimeImmutable('2026-11-18 15:00');
        $p->alerts('PTO', false, 41.19, -8.5, $s, $s->modify('+1 hour'));
        $p->alerts('AVR', false, 40.9, -8.2, $s, $s->modify('+1 hour'));
        assertSame(2, $calls, 'warnings once + rcm-d0 once');
    },
];
