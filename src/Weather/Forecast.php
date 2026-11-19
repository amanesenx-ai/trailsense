<?php
declare(strict_types=1);

namespace TrailSense\Weather;

/** Aggregated forecast for the hike window (F3). Immutable value object. */
final class Forecast
{
    public function __construct(
        public readonly string $source,          // 'open-meteo' | 'demo' | 'none'
        public readonly bool $available,
        public readonly string $message = '',
        public readonly ?float $precipTotalMm = null,
        public readonly ?float $gustMaxKmh = null,
        public readonly ?float $tempMinC = null,
        public readonly ?float $tempMaxC = null,
        /** @var list<array{time:string,temp:?float,precip:?float,gust:?float,code:?int,in_window:bool}> hike window ± 2 h (ADR-017) */
        public readonly array $hours = [],
    ) {
    }

    public static function unavailable(string $source, string $message): self
    {
        return new self($source, false, $message);
    }

    /**
     * Aggregate hourly values whose hour falls inside [start, end).
     * The hour that contains the start time is included.
     *
     * @param list<string> $times ISO local times "YYYY-MM-DDTHH:MM"
     */
    public static function fromHourly(
        string $source,
        array $times,
        array $precip,
        array $gusts,
        array $temps,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        array $codes = [],
        array $extra = []          // ['prob' => precipitation_probability[], 'wind' => wind_speed_10m[]] (AC1.16)
    ): self {
        $from = $start->format('Y-m-d\TH:00');
        $to = $end->format('Y-m-d\TH:i');
        $p = $g = $t = [];
        foreach ($times as $i => $time) {
            if ($time < $from || $time >= $to) {
                continue;
            }
            if (isset($precip[$i])) { $p[] = (float) $precip[$i]; }
            if (isset($gusts[$i]))  { $g[] = (float) $gusts[$i]; }
            if (isset($temps[$i]))  { $t[] = (float) $temps[$i]; }
        }
        if ($p === [] || $g === [] || $t === []) {
            return self::unavailable($source, 'The forecast does not cover the planned hours yet.');
        }
        return new self($source, true, '', round(array_sum($p), 1), max($g), min($t), max($t),
            self::hourRows($times, $precip, $gusts, $temps, $codes, $start, $end, $extra));
    }

    /** Hourly rows from 2 h before the start to 2 h after the end, flagged when inside the hike window. */
    public static function hourRows(array $times, array $precip, array $gusts, array $temps, array $codes,
        \DateTimeImmutable $start, \DateTimeImmutable $end, array $extra = []): array
    {
        $from = $start->modify('-2 hours')->format('Y-m-d\TH:00');
        $to = $end->modify('+2 hours')->format('Y-m-d\TH:i');
        $winFrom = $start->format('Y-m-d\TH:00');
        $winTo = $end->format('Y-m-d\TH:i');
        $rows = [];
        foreach ($times as $i => $time) {
            if ($time < $from || $time >= $to) {
                continue;
            }
            $rows[] = [
                'time' => substr((string) $time, 11, 5),
                'temp' => isset($temps[$i]) ? round((float) $temps[$i], 1) : null,
                'precip' => isset($precip[$i]) ? round((float) $precip[$i], 1) : null,
                'gust' => isset($gusts[$i]) ? round((float) $gusts[$i]) : null,
                'code' => isset($codes[$i]) ? (int) $codes[$i] : null,
                'prob' => isset($extra['prob'][$i]) ? (int) $extra['prob'][$i] : null,
                'wind' => isset($extra['wind'][$i]) ? (int) round((float) $extra['wind'][$i]) : null,
                'in_window' => $time >= $winFrom && $time < $winTo,
            ];
        }
        return $rows;
    }
}
