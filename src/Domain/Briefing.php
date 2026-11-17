<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/** F9: plain-text group briefing built from plan + assessment (AC9.2). Pure function. */
final class Briefing
{
    /**
     * @param array $plan    hike_plan row joined with trail fields (trail_name, region, distance_km, difficulty)
     * @param array $group   group_plan row
     * @param array $factors output of RiskEngine::byFactor()
     */
    public static function build(array $plan, array $group, array $factors, bool $complete, string $latestStart): string
    {
        $date = (new \DateTimeImmutable($plan['planned_date']))->format('D j M Y');
        $lines = [];
        $lines[] = 'TrailSense briefing: ' . $plan['trail_name'] . ' (' . $plan['region'] . ')';
        $lines[] = $date . ' · ' . $group['group_size'] . ' people';
        $lines[] = 'Meet: ' . substr($group['meeting_time'], 0, 5) . ' at ' . $group['meeting_point'];
        $lines[] = sprintf(
            'Start %s · about %s · %s km · %s',
            substr($plan['start_time'], 0, 5),
            self::duration((int) $plan['duration_min']),
            rtrim(rtrim((string) $plan['distance_km'], '0'), '.'),
            $plan['difficulty']
        );
        $lines[] = '';

        $warnings = array_filter($factors, static fn ($f) => in_array($f['status'], ['caution', 'warning'], true));
        if ($warnings === []) {
            $lines[] = 'Conditions: no rule triggered.';
        } else {
            $lines[] = 'Conditions to prepare for:';
            foreach ($warnings as $f) {
                $lines[] = sprintf(
                    '- %s %s: %s (%s). %s',
                    strtoupper($f['status']),
                    $f['label'],
                    $f['rule']['name'],
                    RiskEngine::formatObserved($f['observed'], $f['metric']),
                    RiskEngine::precaution($f['rule'], ['latest_start' => $latestStart])
                );
            }
        }
        if (!$complete) {
            $unknown = array_map(static fn ($f) => $f['label'], array_filter($factors, static fn ($f) => $f['status'] === 'unknown'));
            $lines[] = 'Not yet checked (forecast unavailable): ' . implode(', ', $unknown) . '. Check again closer to the date.';
        }
        $lines[] = '';
        $lines[] = 'Bring: water, snack, charged phone, layers and suitable footwear.';
        return implode("\n", $lines);
    }

    public static function duration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h > 0 ? ($m > 0 ? "{$h} h {$m} min" : "{$h} h") : "{$m} min";
    }
}
