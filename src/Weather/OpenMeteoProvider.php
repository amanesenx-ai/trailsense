<?php
declare(strict_types=1);

namespace TrailSense\Weather;

use DateTimeImmutable;

/** Open-Meteo adapter (free, no API key). https://open-meteo.com/en/docs */
final class OpenMeteoProvider implements WeatherProvider
{
    private const ENDPOINT = 'https://api.open-meteo.com/v1/forecast';

    /** @var callable(string):?string */
    private $fetch;

    /** @param null|callable(string):?string $fetch injectable HTTP GET for tests */
    public function __construct(?callable $fetch = null, private readonly ?DateTimeImmutable $today = null)
    {
        $this->fetch = $fetch ?? [self::class, 'httpGet'];
    }

    public function forecast(float $lat, float $lon, DateTimeImmutable $start, DateTimeImmutable $end): Forecast
    {
        $today = ($this->today ?? new DateTimeImmutable('today'))->setTime(0, 0);
        $day = $start->setTime(0, 0);
        if ($day < $today) {
            return Forecast::unavailable('none', 'This hike date has passed; forecasts are only for upcoming hikes.');
        }
        if ($day > $today->modify('+' . self::HORIZON_DAYS . ' days')) {
            return Forecast::unavailable('none', 'Forecast not available yet — it is published up to 16 days ahead.');
        }

        $url = self::url($lat, $lon, $start, $end);
        $body = ($this->fetch)($url);
        if ($body === null) {
            return Forecast::unavailable('open-meteo', 'The weather service did not answer. Try "Check again" in a moment.');
        }
        return self::parse($body, $start, $end);
    }

    /** The exact request we make — also shown to users as "raw forecast" (AC4.15). */
    public static function url(float $lat, float $lon, DateTimeImmutable $start, DateTimeImmutable $end): string
    {
        return self::ENDPOINT . '?' . http_build_query([
            'latitude' => round($lat, 4),
            'longitude' => round($lon, 4),
            'hourly' => 'temperature_2m,precipitation,wind_gusts_10m,weather_code',
            'wind_speed_unit' => 'kmh',
            'timezone' => 'Europe/Lisbon',
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]);
    }

    /** Open-Meteo's own human-readable page for this location. */
    public static function pageUrl(float $lat, float $lon): string
    {
        return 'https://open-meteo.com/en/docs?latitude=' . round($lat, 4) . '&longitude=' . round($lon, 4)
            . '&hourly=temperature_2m,precipitation,wind_gusts_10m,weather_code&timezone=Europe%2FLisbon';
    }

    public static function parse(string $json, DateTimeImmutable $start, DateTimeImmutable $end): Forecast
    {
        $data = json_decode($json, true);
        $h = is_array($data) ? ($data['hourly'] ?? null) : null;
        if (!is_array($h) || !isset($h['time'], $h['precipitation'], $h['wind_gusts_10m'], $h['temperature_2m'])) {
            return Forecast::unavailable('open-meteo', 'The weather service returned an unexpected answer.');
        }
        return Forecast::fromHourly(
            'open-meteo',
            $h['time'],
            $h['precipitation'],
            $h['wind_gusts_10m'],
            $h['temperature_2m'],
            $start,
            $end,
            $h['weather_code'] ?? []
        );
    }

    public static function httpGet(string $url): ?string
    {
        if (!function_exists('curl_init')) {
            $ctx = stream_context_create(['http' => ['timeout' => 6]]);
            $body = @file_get_contents($url, false, $ctx);
            return $body === false ? null : $body;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,                 // NFR4
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_USERAGENT => 'TrailSense-MVP (student project)',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($body === false || $status !== 200) ? null : (string) $body;
    }
}
