<?php
declare(strict_types=1);

namespace TrailSense\Core;

/** Elevation profile (AC1.14): one series (area + 2 px line), km axis, highest/lowest labelled, longest climb highlighted. */
final class ElevationChart
{
    private const W = 460, H = 180, L = 38, R = 10, T = 22, B = 24;

    /** @param list<array{0: float, 1: float}> $profile @param ?array{from_km: float, to_km: float} $climb */
    public static function svg(array $profile, ?array $climb, string $label): string
    {
        if (count($profile) < 3) {
            return '';
        }
        $kms = array_column($profile, 0);
        $alts = array_column($profile, 1);
        $maxKm = max($kms) ?: 1;
        $lo = floor((min($alts) - 10) / 50) * 50;
        $hi = max($lo + 100, ceil((max($alts) + 10) / 50) * 50);
        $x = fn (float $km) => self::L + $km / $maxKm * (self::W - self::L - self::R);
        $y = fn (float $m) => self::T + (1 - ($m - $lo) / ($hi - $lo)) * (self::H - self::T - self::B);
        $base = self::H - self::B;
        $line = implode(' ', array_map(fn ($p) => self::f($x((float) $p[0])) . ',' . self::f($y((float) $p[1])), $profile));
        $o = '<svg class="elev" viewBox="0 0 ' . self::W . ' ' . self::H . '" role="img" aria-label="' . e($label) . '">';
        foreach ([$lo, ($lo + $hi) / 2, $hi] as $g) {
            $o .= '<line class="elev-grid" x1="' . self::L . '" x2="' . (self::W - self::R) . '" y1="' . self::f($y($g)) . '" y2="' . self::f($y($g)) . '"/>'
                . '<text class="elev-axis" x="' . (self::L - 6) . '" y="' . self::f($y($g) + 4) . '" text-anchor="end">' . (int) $g . '</text>';
        }
        $o .= '<text class="elev-axis" x="2" y="10">m</text>';
        if ($climb !== null) {
            $o .= '<rect class="elev-climb" x="' . self::f($x($climb['from_km'])) . '" y="' . self::T . '" width="' . self::f(max(2, $x($climb['to_km']) - $x($climb['from_km'])))
                . '" height="' . ($base - self::T) . '"/><text class="elev-climb-label" x="' . self::f($x($climb['from_km']) + 4) . '" y="' . (self::T + 11) . '">longest climb</text>';
        }
        $o .= '<polygon class="elev-area" points="' . self::f($x(0)) . ',' . $base . ' ' . $line . ' ' . self::f($x($maxKm)) . ',' . $base . '"/>'
            . '<polyline class="elev-line" points="' . $line . '"/>';
        $hiI = array_search(max($alts), $alts, true);
        $o .= '<circle class="elev-dot" cx="' . self::f($x((float) $kms[$hiI])) . '" cy="' . self::f($y((float) $alts[$hiI])) . '" r="4"/>'
            . '<text class="elev-label" x="' . self::f(min(self::W - self::R - 22, max(self::L + 22, $x((float) $kms[$hiI])))) . '" y="' . self::f($y((float) $alts[$hiI]) - 8) . '" text-anchor="middle">' . (int) round($alts[$hiI]) . ' m</text>';
        $step = $maxKm > 12 ? 2 : 1;
        for ($k = 0; $k <= floor($maxKm); $k += $step) {
            $o .= '<text class="elev-axis" x="' . self::f($x($k)) . '" y="' . (self::H - 6) . '" text-anchor="middle">' . $k . ($k === 0 ? ' km' : '') . '</text>';
        }
        foreach ($profile as $i => [$km, $m]) {
            $next = $profile[$i + 1][0] ?? $km;
            $prev = $profile[$i - 1][0] ?? $km;
            $x0 = $x(((float) $prev + (float) $km) / 2);
            $x1 = $x(((float) $km + (float) $next) / 2);
            $o .= '<rect class="elev-hit" x="' . self::f($x0) . '" y="0" width="' . self::f(max(1, $x1 - $x0)) . '" height="' . $base . '"><title>km '
                . number_format((float) $km, 1) . ' · ' . (int) round((float) $m) . ' m</title></rect>';
        }
        return $o . '</svg>';
    }

    private static function f(float $v): string
    {
        return number_format($v, 1, '.', '');
    }
}
