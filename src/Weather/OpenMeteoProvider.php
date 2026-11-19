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
            'hourly' => 'temperature_2m,precipitation,wind_gusts_10m,weather_code,precipitation_probability,wind_speed_10m',
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
            $h['weather_code'] ?? [],
            ['prob' => $h['precipitation_probability'] ?? [], 'wind' => $h['wind_speed_10m'] ?? []]
        );
    }

    /** AC1.16 7-day outlook request for a trail start. */
    public static function outlookUrl(float $lat, float $lon): string
    {
        return self::ENDPOINT . '?' . http_build_query([
            'latitude' => round($lat, 4), 'longitude' => round($lon, 4),
            'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,precipitation_sum,wind_speed_10m_max,wind_gusts_10m_max',
            'wind_speed_unit' => 'kmh', 'timezone' => 'Europe/Lisbon', 'forecast_days' => 7,
        ]);
    }

    public function outlook(float $lat, float $lon): array
    {
        $body = self::httpGet(self::outlookUrl($lat, $lon));
        return $body === null ? [] : self::parseOutlook($body);
    }

    /** @return list<array{date:string,code:?int,tmax:?float,tmin:?float,prob:?int,rain:?float,wind:?int,gust:?int}> */
    public static function parseOutlook(string $json): array
    {
        $d = json_decode($json, true)['daily'] ?? null;
        if (!is_array($d) || !isset($d['time'])) {
            return [];
        }
        $rows = [];
        foreach ($d['time'] as $i => $date) {
            $rows[] = ['date' => (string) $date, 'code' => isset($d['weather_code'][$i]) ? (int) $d['weather_code'][$i] : null,
                'tmax' => isset($d['temperature_2m_max'][$i]) ? round((float) $d['temperature_2m_max'][$i]) : null,
                'tmin' => isset($d['temperature_2m_min'][$i]) ? round((float) $d['temperature_2m_min'][$i]) : null,
                'prob' => isset($d['precipitation_probability_max'][$i]) ? (int) $d['precipitation_probability_max'][$i] : null,
                'rain' => isset($d['precipitation_sum'][$i]) ? round((float) $d['precipitation_sum'][$i], 1) : null,
                'wind' => isset($d['wind_speed_10m_max'][$i]) ? (int) round((float) $d['wind_speed_10m_max'][$i]) : null,
                'gust' => isset($d['wind_gusts_10m_max'][$i]) ? (int) round((float) $d['wind_gusts_10m_max'][$i]) : null];
        }
        return $rows;
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
