<?php
declare(strict_types=1);

namespace TrailSense\Core;

/**
 * Real map image for cards (AC1.15, ADR-018): OpenStreetMap tiles + the route, as one SVG, no JavaScript.
 * Web-Mercator maths picks the highest zoom where the whole route fits, then only the tiles in view are referenced.
 */
final class StaticMap
{
    private const TILE = 256;

    /** @param list<array{0: float, 1: float}> $points [lat, lon] */
    public static function svg(array $points, string $label, int $w = 400, int $h = 220, bool $loop = false): string
    {
        if (count($points) < 2) {
            return '';
        }
        $lats = array_column($points, 0);
        $lons = array_column($points, 1);
        $zoom = self::fitZoom(min($lats), max($lats), min($lons), max($lons), $w - 40, $h - 40);
        // scale up (≤ 1.6×) between zoom levels so the route fills the card
        [$x1, $y1] = self::project(max($lats), min($lons), $zoom);
        [$x2, $y2] = self::project(min($lats), max($lons), $zoom);
        $k = max(1.0, min(1.6, ($w - 40) / max(1, $x2 - $x1), ($h - 40) / max(1, $y2 - $y1)));
        $tile = self::TILE * $k;
        [$cx, $cy] = self::project((min($lats) + max($lats)) / 2, (min($lons) + max($lons)) / 2, $zoom);
        $cx *= $k;
        $cy *= $k;
        $left = $cx - $w / 2;
        $top = $cy - $h / 2;

        $o = '<svg class="smap" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="xMidYMid slice" role="img" aria-label="' . e($label) . '">'
            . '<rect width="' . $w . '" height="' . $h . '" fill="#cfdccf"/>';
        $max = 2 ** $zoom;
        for ($tx = (int) floor($left / $tile); $tx <= (int) floor(($left + $w) / $tile); $tx++) {
            for ($ty = (int) floor($top / $tile); $ty <= (int) floor(($top + $h) / $tile); $ty++) {
                if ($ty < 0 || $ty >= $max) {
                    continue;
                }
                $o .= '<image href="https://tile.openstreetmap.org/' . $zoom . '/' . (($tx % $max + $max) % $max) . '/' . $ty . '.png" x="'
                    . self::f($tx * $tile - $left) . '" y="' . self::f($ty * $tile - $top) . '" width="' . self::f($tile + 0.5) . '" height="' . self::f($tile + 0.5) . '"/>';
            }
        }
        $pts = [];
        foreach ($points as [$lat, $lon]) {
            [$x, $y] = self::project((float) $lat, (float) $lon, $zoom);
            $pts[] = self::f($x * $k - $left) . ',' . self::f($y * $k - $top);
        }
        $line = implode(' ', $pts);
        [$sx, $sy] = explode(',', $pts[0]);
        [$ex, $ey] = explode(',', $pts[count($pts) - 1]);
        $o .= '<polyline points="' . $line . '" fill="none" stroke="#0b2219" stroke-opacity=".55" stroke-width="7" stroke-linejoin="round" stroke-linecap="round"/>'
            . '<polyline points="' . $line . '" fill="none" stroke="#ff6a00" stroke-width="4" stroke-linejoin="round" stroke-linecap="round"/>';
        if (!$loop) {
            $o .= '<circle cx="' . $ex . '" cy="' . $ey . '" r="6" fill="#d62828" stroke="#fff" stroke-width="2.5"/>';
        }
        $o .= '<circle cx="' . $sx . '" cy="' . $sy . '" r="6" fill="#1f9d55" stroke="#fff" stroke-width="2.5"/>'
            . '<rect x="' . ($w - 112) . '" y="' . ($h - 15) . '" width="112" height="15" fill="#fff" fill-opacity=".75"/>'
            . '<text x="' . ($w - 4) . '" y="' . ($h - 4) . '" text-anchor="end" font-size="10" fill="#333">© OpenStreetMap</text></svg>';
        return $o;
    }

    /** Highest zoom (≤ 16) at which the bounding box fits in the given pixel area. */
    public static function fitZoom(float $minLat, float $maxLat, float $minLon, float $maxLon, int $w, int $h): int
    {
        for ($z = 16; $z > 2; $z--) {
            [$x1, $y1] = self::project($maxLat, $minLon, $z);
            [$x2, $y2] = self::project($minLat, $maxLon, $z);
            if ($x2 - $x1 <= $w && $y2 - $y1 <= $h) {
                return $z;
            }
        }
        return 2;
    }

    /** @return array{0: float, 1: float} world pixel coordinates at zoom $z */
    public static function project(float $lat, float $lon, int $z): array
    {
        $n = self::TILE * 2 ** $z;
        $rad = deg2rad($lat);
        return [($lon + 180) / 360 * $n, (1 - log(tan($rad) + 1 / cos($rad)) / M_PI) / 2 * $n];
    }

    private static function f(float $v): string
    {
        return number_format($v, 1, '.', '');
    }
}
