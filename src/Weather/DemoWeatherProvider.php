<?php
declare(strict_types=1);

namespace TrailSense\Weather;

use DateTimeImmutable;

/**
 * Offline provider for local development and rehearsals without internet (ADR-004).
 * Deterministic values per date + place; always labelled "Demo forecast" in the UI.
 * Never configured in production.
 */
final class DemoWeatherProvider implements WeatherProvider
{
    public function __construct(private readonly ?DateTimeImmutable $today = null)
    {
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

        $seed = crc32($start->format('Y-m-d') . sprintf('%.2f%.2f', $lat, $lon));
        $month = (int) $start->format('n');
        $winter = in_array($month, [11, 12, 1, 2, 3], true);
        $hours = max(1, (int) ceil(($end->getTimestamp() - $start->getTimestamp()) / 3600));

        $rainPerHour = [0.0, 0.0, 0.2, 0.6, 1.5, 3.0][$seed % 6];
        $gust = 18 + ($seed >> 3) % 40;                       // 18–57 km/h
        $base = $winter ? 9 + ($seed >> 7) % 8 : 17 + ($seed >> 7) % 15;
        $minT = $base - 2 - ($seed >> 11) % 4;

        // hourly rows for the visual check, consistent with the aggregates above
        $times = $precip = $gusts = $temps = $codes = [];
        $day = $start->setTime(0, 0);
        for ($h = 0; $h < 48; $h++) {
            $t = $day->modify("+$h hours");
            $inside = $t >= $start->setTime((int) $start->format('H'), 0) && $t < $end;
            $times[] = $t->format('Y-m-d\TH:i');
            $precip[] = $inside ? $rainPerHour : max(0, $rainPerHour - 0.2);
            $gusts[] = $gust - 6 + (($seed >> $h % 16) % 7);
            $temps[] = $minT + ($base - $minT) * max(0, sin(M_PI * (((int) $t->format('H')) - 7) / 12));
            $codes[] = $rainPerHour >= 1.5 ? 63 : ($rainPerHour > 0 ? 61 : [0, 1, 2, 3][$seed % 4]);
        }
        $hourly = Forecast::hourRows($times, $precip, $gusts, $temps, $codes, $start, $end);
        return new Forecast('demo', true, '', round($rainPerHour * $hours, 1), (float) $gust, (float) $minT, (float) $base, $hourly);
    }
}
