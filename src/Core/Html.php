<?php
declare(strict_types=1);

namespace TrailSense\Core;

/** Small presentation helpers used by views. Every value is escaped here. */
final class Html
{
    public const LEVEL_TEXT = ['ok' => 'OK', 'caution' => 'Caution', 'warning' => 'Warning', 'unknown' => 'Not checked',
        'pending' => 'Not yet published'];

    /** Level badge: always text + colour, never colour alone (NFR2). */
    public static function badge(?string $level, bool $incomplete = false): string
    {
        if ($level === null) {
            return '<span class="badge badge-unknown">Not checked</span>';
        }
        $html = '<span class="badge badge-' . e($level) . '">' . e(self::LEVEL_TEXT[$level] ?? $level) . '</span>';
        if ($incomplete && $level !== 'unknown') {
            $html .= ' <span class="badge badge-unknown" title="Some conditions could not be checked yet">Incomplete</span>';
        }
        return $html;
    }

    public static function difficulty(string $d): string
    {
        return '<span class="chip chip-' . e($d) . '">' . e(ucfirst($d)) . '</span>';
    }

    public static function duration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h > 0 ? ($m > 0 ? "{$h} h {$m} min" : "{$h} h") : "{$m} min";
    }

    public static function km(mixed $km): string
    {
        return rtrim(rtrim(number_format((float) $km, 1, '.', ''), '0'), '.') . ' km';
    }

    public static function date(string $ymd, string $format = 'D j M'): string
    {
        return (new \DateTimeImmutable($ymd))->format($format);
    }

    public static function time(?string $t): string
    {
        return $t === null ? '' : substr($t, 0, 5);
    }

    public static function error(array $errors, string $field): string
    {
        return isset($errors[$field])
            ? '<p class="field-error" id="err-' . e($field) . '">' . e($errors[$field]) . '</p>'
            : '';
    }

    /** aria attributes linking an input to its error message. */
    public static function aria(array $errors, string $field): string
    {
        return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="err-' . e($field) . '"' : '';
    }

    /** Route drawing as inline SVG (ADR-006). */
    public static function route(array $points, string $label, string $class = 'route'): string
    {
        if (count($points) < 2) {
            return '';
        }
        $lats = array_column($points, 0);
        $lons = array_column($points, 1);
        $k = cos(deg2rad(array_sum($lats) / count($lats)));
        $minX = min($lons) * $k; $maxX = max($lons) * $k;
        $minY = min($lats); $maxY = max($lats);
        $w = 320; $h = 180; $pad = 22;
        $scale = min(($w - 2 * $pad) / max($maxX - $minX, 1e-6), ($h - 2 * $pad) / max($maxY - $minY, 1e-6));
        $offX = ($w - ($maxX - $minX) * $scale) / 2;
        $offY = ($h - ($maxY - $minY) * $scale) / 2;
        $xy = [];
        foreach ($points as [$lat, $lon]) {
            $xy[] = sprintf('%.1f,%.1f', $offX + ($lon * $k - $minX) * $scale, $h - ($offY + ($lat - $minY) * $scale));
        }
        [$sx, $sy] = explode(',', $xy[0]);
        [$ex, $ey] = explode(',', end($xy));
        $contours = '';
        for ($i = 1; $i <= 4; $i++) {
            $contours .= sprintf('<ellipse cx="%d" cy="%d" rx="%d" ry="%d"/>', 60 + $i * 30, 70 + $i * 8, 40 + $i * 34, 22 + $i * 16);
        }
        return '<svg class="' . e($class) . '" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . e($label) . '">'
            . '<g class="route-contours">' . $contours . '</g>'
            . '<polyline class="route-line-bg" points="' . implode(' ', $xy) . '"/>'
            . '<polyline class="route-line" points="' . implode(' ', $xy) . '"/>'
            . '<circle class="route-end" cx="' . $ex . '" cy="' . $ey . '" r="5"/>'
            . '<circle class="route-start" cx="' . $sx . '" cy="' . $sy . '" r="6"/>'
            . '</svg>';
    }

    /**
     * Interactive map (F1.2, ADR-010): Leaflet enhances this element; the SVG drawing inside is the
     * no-JS / no-tiles fallback (AC1.8).
     */
    public static function map(array $points, bool $isLoop, string $name, string $class = 'map'): string
    {
        if (count($points) < 2) {
            return '';
        }
        $data = json_encode(['route' => $points, 'loop' => $isLoop, 'name' => $name], JSON_UNESCAPED_UNICODE);
        return '<div class="' . e($class) . '" data-map="' . e((string) $data) . '" role="region" aria-label="Map of ' . e($name) . '">'
            . self::route($points, 'Route drawing of ' . $name, 'route route-large')
            . '</div>';
    }

    public static function isTrue(mixed $v): bool
    {
        return $v === true || $v === 't' || $v === 1 || $v === '1';
    }

    /** OSM directions to the start point (the user's own position is chosen on OSM). */
    public static function directionsUrl(array $point): string
    {
        return 'https://www.openstreetmap.org/directions?route=%3B' . rawurlencode($point[0] . ',' . $point[1])
            . '#map=15/' . $point[0] . '/' . $point[1];
    }

    public static function initial(string $name): string
    {
        return e(mb_strtoupper(mb_substr(trim($name), 0, 1)));
    }
}
