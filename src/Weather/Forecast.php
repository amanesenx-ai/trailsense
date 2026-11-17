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
        \DateTimeImmutable $end
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
        return new self($source, true, '', round(array_sum($p), 1), max($g), min($t), max($t));
    }
}
