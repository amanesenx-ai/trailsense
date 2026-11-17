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
        'ipma_warning_level'  => ['IPMA warning level', ''],
        'fire_risk'           => ['IPMA rural fire risk', ''],
        'trail_closed'        => ['Community report: trail closed', ''],
    ];

    public const WARNING_NAMES = [0 => 'no warning', 1 => 'yellow', 2 => 'orange', 3 => 'red'];
    public const FIRE_NAMES = [1 => 'Reduced', 2 => 'Moderate', 3 => 'Elevated', 4 => 'Very high', 5 => 'Maximum'];

    public const FACTOR_LABELS = [
        'rain' => 'Rain', 'wind' => 'Wind', 'heat' => 'Heat', 'cold' => 'Cold', 'daylight' => 'Daylight',
        'official' => 'Official warnings', 'fire' => 'Fire risk', 'trail' => 'Trail condition',
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

            // AC4.4: missing data is never "OK"; advisory rules (required = false) do not block completeness (AC4.9)
            if (!$evaluated && self::isRequired($rule)) {
                $complete = false;
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
                $current['status'] = self::isRequired($r['rule']) ? 'unknown' : 'pending';
            } elseif ($r['triggered'] && !in_array($current['status'], ['unknown', 'pending'], true)
                && self::LEVEL_ORDER[$r['rule']['level']] > self::LEVEL_ORDER[$current['status']]) {
                $current['status'] = $r['rule']['level'];
                $current['rule'] = $r['rule'];
            }
            $factors[$factor] = $current;
        }
        return $factors;
    }

    public static function isRequired(array $rule): bool
    {
        $v = $rule['required'] ?? true;
        return $v === true || $v === 't' || $v === 1 || $v === '1';
    }

    /** Human-readable rule condition, e.g. "Total rain ≥ 2 mm" (AC4.2). */
    public static function describeCondition(array $rule): string
    {
        $t = (int) $rule['threshold'];
        switch ($rule['metric']) {
            case 'ipma_warning_level':
                return 'IPMA warning ' . self::WARNING_NAMES[min(3, max(1, $t))] . ' or higher for this district';
            case 'fire_risk':
                return 'IPMA fire risk ' . (self::FIRE_NAMES[$t] ?? $t) . ' (' . $t . '/5) or higher';
            case 'trail_closed':
                return 'A hiker reported the trail closed in the last 14 days';
        }
        [$label, $unit] = self::METRIC_LABELS[$rule['metric']] ?? [$rule['metric'], ''];
        $symbol = ['>=' => '≥', '<=' => '≤', '<' => '<'][$rule['comparator']] ?? $rule['comparator'];
        return sprintf('%s %s %s %s', $label, $symbol, self::number((float) $rule['threshold']), $unit);
    }

    public static function formatObserved(?float $value, string $metric): string
    {
        if ($value === null) {
            return in_array($metric, ['ipma_warning_level', 'fire_risk'], true) ? 'not published yet' : 'no data';
        }
        if ($metric === 'ipma_warning_level') {
            $n = (int) $value;
            return $n === 0 ? 'no warning' : self::WARNING_NAMES[$n] . ' warning';
        }
        if ($metric === 'fire_risk') {
            return (int) $value . '/5 ' . (self::FIRE_NAMES[(int) $value] ?? '');
        }
        if ($metric === 'trail_closed') {
            return $value >= 1 ? 'reported closed' : 'no closure reported';
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
