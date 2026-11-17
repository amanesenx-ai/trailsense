<?php
declare(strict_types=1);

namespace TrailSense\Services;

use DateTimeImmutable;
use TrailSense\Domain\Daylight;
use TrailSense\Domain\RiskEngine;
use TrailSense\Repositories\AssessmentRepository;
use TrailSense\Repositories\RuleRepository;
use TrailSense\Weather\DemoWeatherProvider;
use TrailSense\Weather\OpenMeteoProvider;
use TrailSense\Weather\WeatherProvider;

/** Orchestrates F3 → F4 → F5: forecast + daylight → rule engine → stored assessment. */
final class AssessmentService
{
    public function __construct(
        private readonly WeatherProvider $weather,
        private readonly RuleRepository $rules,
        private readonly AssessmentRepository $assessments,
        private readonly RiskEngine $engine = new RiskEngine(),
    ) {
    }

    public static function providerFromConfig(): WeatherProvider
    {
        return config('weather_provider') === 'demo' ? new DemoWeatherProvider() : new OpenMeteoProvider();
    }

    /** Runs a fresh assessment for a plan row (from PlanRepository::find). Returns the assessment id. */
    public function assess(array $plan): int
    {
        [$start, $end] = self::window($plan);
        $lat = (float) $plan['start_lat'];
        $lon = (float) $plan['start_lon'];

        $sunset = Daylight::sunset($start, $lat, $lon);
        $forecast = $this->weather->forecast($lat, $lon, $start, $end);

        $metrics = [
            'precip_total_mm' => $forecast->precipTotalMm,
            'gust_max_kmh' => $forecast->gustMaxKmh,
            'temp_max_c' => $forecast->tempMaxC,
            'temp_min_c' => $forecast->tempMinC,
            'daylight_margin_min' => (float) Daylight::marginMinutes($end, $sunset),   // always available (AC4.5)
        ];
        $evaluation = $this->engine->evaluate($this->rules->active(), $metrics);

        return $this->assessments->save((int) $plan['id'], $forecast, $sunset->format('H:i'), $evaluation);
    }

    /** View model shared by the plan page and the briefing. */
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

        return [
            'factors' => $factors,
            'overall' => $assessment['overall_level'],
            'complete' => (bool) $assessment['is_complete'],
            'sunset' => $sunset,
            'latest_start' => $latestStart,
            'end_time' => $end->format('H:i'),
            'source' => $assessment['source'],
            'available' => (bool) $assessment['available'],
            'message' => $assessment['message'],
            'fetched_at' => $assessment['fetched_at'],
            'snapshot' => $assessment,
        ];
    }

    /** @return array{0: DateTimeImmutable, 1: DateTimeImmutable} */
    public static function window(array $plan): array
    {
        $start = new DateTimeImmutable($plan['planned_date'] . ' ' . substr((string) $plan['start_time'], 0, 5));
        return [$start, $start->modify('+' . (int) $plan['duration_min'] . ' minutes')];
    }
}
