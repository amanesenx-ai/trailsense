<?php
declare(strict_types=1);

namespace TrailSense\Services;

use DateTimeImmutable;
use TrailSense\Alerts\DemoAlertsProvider;
use TrailSense\Alerts\IpmaProvider;
use TrailSense\Alerts\OfficialAlerts;
use TrailSense\Alerts\OfficialAlertsProvider;
use TrailSense\Domain\Daylight;
use TrailSense\Domain\RiskEngine;
use TrailSense\Repositories\AssessmentRepository;
use TrailSense\Repositories\RuleRepository;
use TrailSense\Weather\DemoWeatherProvider;
use TrailSense\Weather\Forecast;
use TrailSense\Weather\OpenMeteoProvider;
use TrailSense\Weather\WeatherProvider;

/**
 * Orchestrates F3 → F4 → F5: Open-Meteo forecast + IPMA warnings/fire risk + daylight + community reports
 * → rule engine → stored assessment. `compute()` does the same without storing (public API, ADR-013).
 */
final class AssessmentService
{
    /** @var callable(int):bool trail id → reported closed in the last 14 days */
    private $closedRecently;

    public function __construct(
        private readonly WeatherProvider $weather,
        private readonly RuleRepository $rules,
        private readonly ?AssessmentRepository $assessments = null,
        private readonly ?OfficialAlertsProvider $alerts = null,
        ?callable $closedRecently = null,
        private readonly RiskEngine $engine = new RiskEngine(),
    ) {
        $this->closedRecently = $closedRecently ?? static fn (int $trailId): bool => false;
    }

    public static function providerFromConfig(): WeatherProvider
    {
        return config('weather_provider') === 'demo' ? new DemoWeatherProvider() : new OpenMeteoProvider();
    }

    public static function alertsFromConfig(): OfficialAlertsProvider
    {
        return config('weather_provider') === 'demo' ? new DemoAlertsProvider() : new IpmaProvider();
    }

    /** Wired with the configured providers and repositories (controllers use this). */
    public static function forApp(\PDO $db): self
    {
        $reports = new \TrailSense\Repositories\TrailReportRepository($db);
        return new self(self::providerFromConfig(), new RuleRepository($db), new AssessmentRepository($db),
            self::alertsFromConfig(), fn (int $trailId) => $reports->closedRecently($trailId));
    }

    /**
     * "The same checks every time" (A1): assess upcoming plans that were never checked (max $limit per request,
     * to keep pages fast). Returns true if something was assessed, so the caller can reload its list.
     * @param list<array> $rows rows with id and overall_level
     */
    public static function checkUnchecked(\PDO $db, array $rows, int $limit = 5): bool
    {
        $todo = array_slice(array_filter($rows, static fn (array $r) => $r['overall_level'] === null), 0, $limit);
        if ($todo === []) {
            return false;
        }
        $service = self::forApp($db);
        $plans = new \TrailSense\Repositories\PlanRepository($db);
        foreach ($todo as $r) {
            $plan = $plans->find((int) $r['id']);
            if ($plan !== null) {
                $service->assess($plan);
            }
        }
        return true;
    }

    /** Runs a fresh assessment for a plan row (from PlanRepository::find) and stores it. */
    public function assess(array $plan): int
    {
        if ($this->assessments === null) {
            throw new \LogicException('assess() needs an AssessmentRepository; use compute() for read-only checks.');
        }
        $c = $this->compute($plan);
        return $this->assessments->save((int) $plan['id'], $c['forecast'], $c['sunset'], $c['evaluation'], $c['alerts']);
    }

    /**
     * @param array $plan needs planned_date, start_time, duration_min, start_lat, start_lon, trail_id, ipma_area, coastal
     * @return array{forecast: Forecast, alerts: OfficialAlerts, sunset: string, metrics: array, evaluation: array}
     */
    public function compute(array $plan): array
    {
        [$start, $end] = self::window($plan);
        $lat = (float) $plan['start_lat'];
        $lon = (float) $plan['start_lon'];

        $sunset = Daylight::sunset($start, $lat, $lon);
        $forecast = $this->weather->forecast($lat, $lon, $start, $end);
        $alerts = $this->alerts !== null
            ? $this->alerts->alerts((string) ($plan['ipma_area'] ?? 'PTO'), self::bool($plan['coastal'] ?? false), $lat, $lon, $start, $end)
            : OfficialAlerts::unavailable('');

        $metrics = [
            'precip_total_mm' => $forecast->precipTotalMm,
            'gust_max_kmh' => $forecast->gustMaxKmh,
            'temp_max_c' => $forecast->tempMaxC,
            'temp_min_c' => $forecast->tempMinC,
            'daylight_margin_min' => (float) Daylight::marginMinutes($end, $sunset),   // always available (AC4.5)
            'ipma_warning_level' => $alerts->warningLevel === null ? null : (float) $alerts->warningLevel,
            'fire_risk' => $alerts->fireRisk === null ? null : (float) $alerts->fireRisk,
            'trail_closed' => ($this->closedRecently)((int) $plan['trail_id']) ? 1.0 : 0.0,
        ];
        $evaluation = $this->engine->evaluate($this->rules->active(), $metrics);

        return ['forecast' => $forecast, 'alerts' => $alerts, 'sunset' => $sunset->format('H:i'),
            'metrics' => $metrics, 'evaluation' => $evaluation];
    }

    /** View model shared by the plan page, the briefing and the API. */
    public static function present(array $plan, array $assessment): array
    {
        [$start, $end] = self::window($plan);
        $sunset = $assessment['sunset'] ? substr((string) $assessment['sunset'], 0, 5) : null;
        $latestStart = $sunset !== null
            ? Daylight::latestStart($start->modify($sunset), (int) $plan['duration_min'])->format('H:i')
            : '';

        $factors = RiskEngine::byFactor($assessment['results']);
        foreach ($factors as &$f) {
            $f['condition'] = $f['rule'] ? RiskEngine::describeCondition($f['rule']) : null;
            $f['observed_text'] = RiskEngine::formatObserved($f['observed'], $f['metric']);
            $f['precaution'] = $f['rule'] ? RiskEngine::precaution($f['rule'], ['latest_start' => $latestStart]) : null;
        }
        unset($f);

        $warnings = $assessment['warnings'] ?? [];
        if (is_string($warnings)) {
            $warnings = json_decode($warnings, true) ?: [];
        }

        return [
            'factors' => $factors,
            'overall' => $assessment['overall_level'],
            'complete' => self::bool($assessment['is_complete']),
            'sunset' => $sunset,
            'latest_start' => $latestStart,
            'end_time' => $end->format('H:i'),
            'source' => $assessment['source'],
            'available' => self::bool($assessment['available']),
            'message' => $assessment['message'],
            'fetched_at' => $assessment['fetched_at'] ?? date('Y-m-d H:i:s'),
            'alerts_source' => $assessment['alerts_source'] ?? 'none',
            'alerts_message' => $assessment['alerts_message'] ?? '',
            'warnings' => $warnings,
            'snapshot' => $assessment,
        ];
    }

    /** Shape a compute() result like a stored assessment so present() can render it (API). */
    public static function asAssessment(array $c): array
    {
        $f = $c['forecast'];
        $a = $c['alerts'];
        return [
            'overall_level' => $c['evaluation']['overall'], 'is_complete' => $c['evaluation']['complete'],
            'results' => $c['evaluation']['results'], 'sunset' => $c['sunset'], 'source' => $f->source,
            'available' => $f->available, 'message' => $f->message, 'precip_total_mm' => $f->precipTotalMm,
            'gust_max_kmh' => $f->gustMaxKmh, 'temp_min_c' => $f->tempMinC, 'temp_max_c' => $f->tempMaxC,
            'alerts_source' => $a->source, 'alerts_message' => $a->message, 'warnings' => $a->warnings,
            'warning_level' => $a->warningLevel, 'fire_risk' => $a->fireRisk,
        ];
    }

    /** @return array{0: DateTimeImmutable, 1: DateTimeImmutable} */
    public static function window(array $plan): array
    {
        $start = new DateTimeImmutable($plan['planned_date'] . ' ' . substr((string) $plan['start_time'], 0, 5));
        return [$start, $start->modify('+' . (int) $plan['duration_min'] . ' minutes')];
    }

    private static function bool(mixed $v): bool
    {
        return $v === true || $v === 't' || $v === 1 || $v === '1';
    }
}
