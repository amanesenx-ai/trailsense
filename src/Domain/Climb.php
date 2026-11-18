<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/**
 * "Is this route for me?" — the climbs (AC1.13). Pure: works on the stored elevation profile [[km, m], ...] (ADR-019).
 * Total ascent is the trail's published figure; the profile gives the shape: highest point, longest and steepest climbs.
 */
final class Climb
{
    public const RATINGS = [
        'gentle' => ['Gentle', 'Mostly flat or gentle slopes — fine for a first hike.'],
        'moderate' => ['Moderate', 'Some steady climbs. Take it slow uphill and you will be fine with normal fitness.'],
        'demanding' => ['Demanding', 'Long or steep climbs. Good walking shoes, regular breaks and some hiking experience help.'],
        'very' => ['Very demanding', 'Big, steep climbs. For fit, experienced hikers — start early and carry extra water.'],
    ];

    /**
     * @param list<array{0: float, 1: float}> $profile
     * @return array{ascent:int, per_km:int, highest:?int, lowest:?int, start:?int, end:?int, longest:?array, steepest:?array, rating:string, label:string, advice:string}
     */
    public static function analyse(array $profile, int $ascentM, float $distanceKm): array
    {
        $perKm = $distanceKm > 0 ? (int) round($ascentM / $distanceKm) : 0;
        $out = ['ascent' => $ascentM, 'per_km' => $perKm, 'highest' => null, 'lowest' => null, 'start' => null, 'end' => null,
            'longest' => null, 'steepest' => null];
        if (count($profile) >= 3) {
            $s = self::smooth($profile);
            $alts = array_column($s, 1);
            $out['highest'] = (int) round(max(array_column($profile, 1)));
            $out['lowest'] = (int) round(min(array_column($profile, 1)));
            $out['start'] = (int) round($profile[0][1]);
            $out['end'] = (int) round($profile[count($profile) - 1][1]);
            $out['longest'] = self::longestClimb($s);
            $out['steepest'] = self::steepest(self::smooth($profile, 2), 0.5);
        }
        $grade = $out['steepest']['grade'] ?? 0;
        $levels = array_keys(self::RATINGS);
        $level = match (true) { $perKm < 25 => 0, $perKm < 55 => 1, $perKm < 85 => 2, default => 3 };
        if ($grade >= 20) {
            $level = min(3, $level + 1);          // a very steep pitch makes the whole route harder
        }
        $rating = $levels[$level];
        [$label, $advice] = self::RATINGS[$rating];
        return $out + ['rating' => $rating, 'label' => $label, 'advice' => $advice];
    }

    /** Biggest continuous climb, ignoring dips under 15 m. @return ?array{from_km:float,to_km:float,gain:int,grade:float} */
    public static function longestClimb(array $s): ?array
    {
        $best = null;
        $startIdx = 0;
        $lowIdx = 0;
        $highIdx = 0;
        for ($i = 1, $n = count($s); $i < $n; $i++) {
            if ($s[$i][1] > $s[$highIdx][1]) {
                $highIdx = $i;
            }
            $dropped = $s[$highIdx][1] - $s[$i][1] >= 15;
            if ($dropped || $i === $n - 1) {
                $gain = $s[$highIdx][1] - $s[$lowIdx][1];
                if ($highIdx > $lowIdx && ($best === null || $gain > $best['gain'])) {
                    $dist = max(0.01, $s[$highIdx][0] - $s[$lowIdx][0]);
                    $best = ['from_km' => round($s[$lowIdx][0], 1), 'to_km' => round($s[$highIdx][0], 1),
                        'gain' => (int) round($gain), 'grade' => round($gain / ($dist * 1000) * 100, 1)];
                }
                if ($dropped) {
                    $lowIdx = $i;
                    $highIdx = $i;
                }
            }
            if ($s[$i][1] <= $s[$lowIdx][1] + 0.5) {   // flat or lower ground before the climb moves its start forward
                $lowIdx = $i;
                $highIdx = $i;
            }
        }
        return $best !== null && $best['gain'] >= 10 ? $best : null;
    }

    /** Steepest uphill window of about $windowKm. @return ?array{from_km:float,to_km:float,grade:float} */
    public static function steepest(array $s, float $windowKm): ?array
    {
        $best = null;
        $n = count($s);
        $spacing = $n > 1 ? max(0.001, ($s[$n - 1][0] - $s[0][0]) / ($n - 1)) : 1;
        $k = max(1, (int) round($windowKm / $spacing));        // samples spanning ≈ the window
        for ($i = 0; $i + $k < $n; $i++) {
            $j = $i + $k;
            $dist = max(0.001, $s[$j][0] - $s[$i][0]);
            {
                $grade = ($s[$j][1] - $s[$i][1]) / ($dist * 1000) * 100;
                if ($grade > 0 && ($best === null || $grade > $best['grade'])) {
                    $best = ['from_km' => round($s[$i][0], 1), 'to_km' => round($s[$j][0], 1), 'grade' => round($grade, 1)];
                }
            }
        }
        return $best;
    }

    /** Moving average over ±$half samples: the 90 m terrain model has step noise. */
    public static function smooth(array $profile, int $half = 1): array
    {
        $out = [];
        foreach ($profile as $i => [$km, $m]) {
            $w = array_slice($profile, max(0, $i - $half), $half * 2 + 1 - max(0, $half - $i));
            $out[] = [(float) $km, array_sum(array_column($w, 1)) / count($w)];
        }
        return $out;
    }
}
