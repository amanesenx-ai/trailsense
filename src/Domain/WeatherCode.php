<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/** WMO weather interpretation codes used by Open-Meteo → label + icon key (AC4.13). */
final class WeatherCode
{
    /** @return array{0:string,1:string} [label, icon] icon ∈ sun, partly, cloud, fog, drizzle, rain, heavy, snow, storm */
    public static function describe(?int $code): array
    {
        return match (true) {
            $code === null => ['No data', 'cloud'],
            $code === 0 => ['Clear sky', 'sun'],
            $code === 1, $code === 2 => [$code === 1 ? 'Mainly clear' : 'Partly cloudy', 'partly'],
            $code === 3 => ['Overcast', 'cloud'],
            $code === 45, $code === 48 => ['Fog', 'fog'],
            $code >= 51 && $code <= 57 => ['Drizzle', 'drizzle'],
            $code === 61, $code === 80 => ['Light rain', 'rain'],
            $code === 63, $code === 81 => ['Rain', 'rain'],
            $code === 65, $code === 82, $code === 66, $code === 67 => ['Heavy rain', 'heavy'],
            ($code >= 71 && $code <= 77) || $code === 85 || $code === 86 => ['Snow', 'snow'],
            $code >= 95 => ['Thunderstorm', 'storm'],
            default => ['Cloudy', 'cloud'],
        };
    }
}
