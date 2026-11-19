<?php
declare(strict_types=1);

namespace TrailSense\Services;

use TrailSense\Weather\OpenMeteoProvider;
use TrailSense\Weather\WeatherProvider;

/** AC1.16 7-day outlook for a trail start, cached on disk for an hour so trail pages stay fast and Open-Meteo is not hammered. */
final class WeatherOutlook
{
    private const TTL = 3600;

    /** @return array{days: list<array>, fetched_at: ?string, source: string, url: ?string} */
    public static function forPlace(float $lat, float $lon, ?WeatherProvider $provider = null): array
    {
        $provider ??= AssessmentService::providerFromConfig();
        $source = $provider instanceof OpenMeteoProvider ? 'open-meteo' : 'demo';
        $file = TS_ROOT . '/var/cache/outlook-' . $source . '-' . round($lat, 3) . '_' . round($lon, 3) . '.json';
        $cached = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        if (is_array($cached) && ($cached['saved'] ?? 0) > time() - self::TTL && ($cached['days'] ?? []) !== []) {
            return $cached['data'] + ['days' => $cached['days']];
        }
        $days = $provider->outlook($lat, $lon);
        $data = ['fetched_at' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Lisbon')))->format('Y-m-d H:i'),
            'source' => $source, 'url' => $source === 'open-meteo' ? OpenMeteoProvider::outlookUrl($lat, $lon) : null];
        if ($days !== []) {
            @mkdir(dirname($file), 0775, true);
            @file_put_contents($file, json_encode(['saved' => time(), 'days' => $days, 'data' => $data]), LOCK_EX);
        }
        return $data + ['days' => $days];
    }
}
