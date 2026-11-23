<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/**
 * AC13.19 practice route: a small square loop that starts where the hiker is, so the GPS can be tried anywhere
 * (no marked trail nearby). It is not a trail: it can cross buildings and is never stored.
 * Pure function — tested in tests/Unit/PracticeRouteTest.php.
 */
final class PracticeRoute
{
    /**
     * @return list<array{0: float, 1: float}> points every ~25 m: north, east, south, west, back to the start
     */
    public static function around(float $lat, float $lon, int $sideM = 150): array
    {
        $dLat = $sideM / 111_320;                                   // metres → degrees of latitude
        $dLon = $sideM / (111_320 * max(0.01, cos(deg2rad($lat))));  // and of longitude at this latitude
        $corners = [[$lat, $lon], [$lat + $dLat, $lon], [$lat + $dLat, $lon + $dLon], [$lat, $lon + $dLon], [$lat, $lon]];
        $steps = max(1, (int) round($sideM / 25));
        $pts = [];
        for ($c = 0; $c < 4; $c++) {
            [$a, $b] = [$corners[$c], $corners[$c + 1]];
            for ($i = 0; $i < $steps; $i++) {
                $f = $i / $steps;
                $pts[] = [round($a[0] + ($b[0] - $a[0]) * $f, 6), round($a[1] + ($b[1] - $a[1]) * $f, 6)];
            }
        }
        $pts[] = [round($lat, 6), round($lon, 6)];
        return $pts;
    }

    public static function validCoordinate(mixed $lat, mixed $lon): bool
    {
        return is_numeric($lat) && is_numeric($lon) && abs((float) $lat) <= 85 && abs((float) $lon) <= 180;
    }
}
