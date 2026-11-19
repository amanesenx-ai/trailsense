<?php
declare(strict_types=1);

namespace TrailSense\Weather;

use DateTimeImmutable;

/** Port for forecast sources (ARCHITECTURE: Weather adapter). */
interface WeatherProvider
{
    public const HORIZON_DAYS = 15;   // Open-Meteo: today + 15 days = 16 forecast days

    public function forecast(float $lat, float $lon, DateTimeImmutable $start, DateTimeImmutable $end): Forecast;

    /** AC1.16 daily outlook (7 days) for a place; [] when unavailable. */
    public function outlook(float $lat, float $lon): array;
}
