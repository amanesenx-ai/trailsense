<?php
declare(strict_types=1);

use TrailSense\Domain\UserValidator;
use TrailSense\Weather\DemoWeatherProvider;
use TrailSense\Weather\Forecast;
use TrailSense\Weather\OpenMeteoProvider;

return [
    'outlook parser reads 7 days with rain chance and wind (AC1.16)' => function () {
        $json = json_encode(['daily' => ['time' => ['2026-11-20', '2026-11-21'], 'weather_code' => [3, 63], 'temperature_2m_max' => [15.4, 12.1],
            'temperature_2m_min' => [8.2, 7.9], 'precipitation_probability_max' => [10, 85], 'precipitation_sum' => [0, 6.3],
            'wind_speed_10m_max' => [12.4, 30.2], 'wind_gusts_10m_max' => [25.1, 61.8]]]);
        $d = OpenMeteoProvider::parseOutlook((string) $json);
        assertSame(2, count($d));
        assertSame(['2026-11-21', 63, 12.0, 85, 30, 62], [$d[1]['date'], $d[1]['code'], $d[1]['tmax'], $d[1]['prob'], $d[1]['wind'], $d[1]['gust']]);
        assertSame([], OpenMeteoProvider::parseOutlook('{"error":true}'));
        assertContains('precipitation_probability_max', OpenMeteoProvider::outlookUrl(41.1, -8.5));
        assertSame(7, count((new DemoWeatherProvider())->outlook(41.1, -8.5)));
    },
    'hourly rows carry rain chance and wind speed (items 11)' => function () {
        $t = ['2026-11-21T09:00', '2026-11-21T10:00'];
        $rows = Forecast::hourRows($t, [0, 1], [20, 30], [10, 11], [3, 61], new DateTimeImmutable('2026-11-21 09:00'), new DateTimeImmutable('2026-11-21 10:00'),
            ['prob' => [15, 70], 'wind' => [9.6, 14.2]]);
        assertSame([15, 70], array_column($rows, 'prob'));
        assertSame([10, 14], array_column($rows, 'wind'));
        assertContains('precipitation_probability', OpenMeteoProvider::url(41, -8, new DateTimeImmutable('2026-11-21 09:00'), new DateTimeImmutable('2026-11-21 10:00')));
    },
    'new password rules: current must match, ≥ 8 chars, typed twice, different (AC8.5)' => function () {
        $ok = fn ($p) => $p === 'old-secret';
        assertSame([], UserValidator::newPassword('old-secret', 'brand-new-1', 'brand-new-1', $ok));
        assertSame(1, count(UserValidator::newPassword('wrong', 'brand-new-1', 'brand-new-1', $ok)));
        assertContains('8 characters', UserValidator::newPassword('old-secret', 'short', 'short', $ok)[0]);
        assertContains('do not match', UserValidator::newPassword('old-secret', 'brand-new-1', 'brand-new-2', $ok)[0]);
        assertContains('different', UserValidator::newPassword('old-secret', 'old-secret', 'old-secret', $ok)[0]);
    },
];
