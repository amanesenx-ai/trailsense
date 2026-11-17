<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/**
 * Generic rule engine (F4/F5, ADR-003). Rules come from the risk_rule table;
 * metrics come from the forecast and the daylight calculation. No I/O here.
 */
final class RiskEngine
{
    public const LEVEL_ORDER = ['ok' => 0, 'caution' => 1, 'warning' => 2];

    public const METRIC_LABELS = [
        'precip_total_mm'     => ['Total rain', 'mm'],
        'gust_max_kmh'        => ['Max wind gust', 'km/h'],
        'temp_max_c'          => ['Max temperature', '°C'],
        'temp_min_c'          => ['Min temperature', '°C'],
        'daylight_margin_min' => ['Time between finish and sunset', 'min'],
    ];

    public const FACTOR_LABELS = [
        'rain' => 'Rain', 'wind' => 'Wind', 'heat' => 'Heat', 'cold' => 'Cold', 'daylight' => 'Daylight',
    ];

    /**
     * @param list<array<string,mixed>> $rules   rows of risk_rule (active only)
     * @param array<string,float|null>  $metrics metric name => value, null = missing data
     * @return array{overall:string, complete:bool, results:list<array<string,mixed>>}
     */
    public function evaluate(array $rules, array $metrics): array
    {
        $results = [];
        $overall = 'ok';
        $complete = true;

        foreach ($rules as $rule) {
            $observed = $metrics[$rule['metric']] ?? null;
            $evaluated = $observed !== null;
            $triggered = $evaluated
                && self::compare((float) $observed, (string) $rule['comparator'], (float) $rule['threshold']);

            if (!$evaluated) {
                $complete = false;                     // AC4.4: missing data is never "OK"
            }
            if ($triggered && self::LEVEL_ORDER[$rule['level']] > self::LEVEL_ORDER[$overall]) {
                $overall = $rule['level'];             // AC4.3: worst level wins
            }
            $results[] = [
                'rule'      => $rule,
                'observed'  => $evaluated ? (float) $observed : null,
                'evaluated' => $evaluated,
                'triggered' => $triggered,
            ];
        }

        return ['overall' => $overall, 'complete' => $complete, 'results' => $results];
    }

    public static function compare(float $value, string $comparator, float $threshold): bool
    {
        return match ($comparator) {
            '>=' => $value >= $threshold,
            '<=' => $value <= $threshold,
            '<'  => $value < $threshold,
            default => throw new \InvalidArgumentException("Unknown comparator: $comparator"),
        };
    }

    /**
     * Collapse rule results into one line per factor (what the user reads):
     * status = worst triggered level, 'ok' if evaluated and nothing triggered, 'unknown' if not evaluated.
     *
     * @return array<string, array{factor:string,label:string,status:string,rule:?array,observed:?float,metric:string}>
     */
    public static function byFactor(array $results): array
    {
        $factors = [];
        foreach ($results as $r) {
            $factor = $r['rule']['factor'];
            $current = $factors[$factor] ?? [
                'factor' => $factor,
                'label' => self::FACTOR_LABELS[$factor] ?? ucfirst($factor),
                'status' => 'ok',
                'rule' => null,
                'observed' => $r['observed'],
                'metric' => $r['rule']['metric'],
            ];
            if (!$r['evaluated']) {
                $current['status'] = 'unknown';
            } elseif ($r['triggered'] && $current['status'] !== 'unknown'
                && self::LEVEL_ORDER[$r['rule']['level']] > self::LEVEL_ORDER[$current['status']]) {
                $current['status'] = $r['rule']['level'];
                $current['rule'] = $r['rule'];
            }
            $factors[$factor] = $current;
        }
        return $factors;
    }

    /** Human-readable rule condition, e.g. "Total rain ≥ 2 mm" (AC4.2). */
    public static function describeCondition(array $rule): string
    {
        [$label, $unit] = self::METRIC_LABELS[$rule['metric']] ?? [$rule['metric'], ''];
        $symbol = ['>=' => '≥', '<=' => '≤', '<' => '<'][$rule['comparator']] ?? $rule['comparator'];
        return sprintf('%s %s %s %s', $label, $symbol, self::number((float) $rule['threshold']), $unit);
    }

    public static function formatObserved(?float $value, string $metric): string
    {
        if ($value === null) {
            return 'no data';
        }
        if ($metric === 'daylight_margin_min') {
            $m = (int) round(abs($value));
            return $value >= 0 ? "ends {$m} min before sunset" : "ends {$m} min after sunset";
        }
        $unit = self::METRIC_LABELS[$metric][1] ?? '';
        return self::number($value) . ' ' . $unit;
    }

    /** Fill placeholders in a precaution, e.g. {latest_start} (AC5.2). */
    public static function precaution(array $rule, array $vars): string
    {
        $text = (string) $rule['precaution'];
        foreach ($vars as $k => $v) {
            $text = str_replace('{' . $k . '}', (string) $v, $text);
        }
        return $text;
    }

    private static function number(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }
}
