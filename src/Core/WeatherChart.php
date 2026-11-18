<?php
declare(strict_types=1);

namespace TrailSense\Core;

/**
 * Hour-by-hour weather for the hike window (AC4.13, ADR-017): three small panels sharing one hour axis —
 * temperature (line), rain (bars), gusts (line). One scale per panel, never a dual axis. Server-side SVG, no JS.
 * Every hour column has a hit area with a native tooltip; the hour cards next to it are the table view.
 */
final class WeatherChart
{
    private const W = 640, LEFT = 44, RIGHT = 12, PANEL = 62, GAP = 22, TOP = 8;

    /** @param list<array{time:string,temp:?float,precip:?float,gust:?float,code:?int,in_window:bool}> $hours */
    public static function svg(array $hours, ?string $sunset): string
    {
        $n = count($hours);
        if ($n < 2) {
            return '';
        }
        $plotW = self::W - self::LEFT - self::RIGHT;
        $col = $plotW / $n;
        $cx = fn (int $i) => self::LEFT + $col * ($i + 0.5);
        $height = self::TOP + 3 * self::PANEL + 2 * self::GAP + 26;
        $o = '<svg class="wx-chart" viewBox="0 0 ' . self::W . ' ' . $height . '" role="img" aria-label="'
            . e(self::summary($hours)) . '">';

        // hike window band (behind everything)
        $in = array_keys(array_filter($hours, fn ($h) => $h['in_window']));
        if ($in !== []) {
            $x0 = self::LEFT + $col * min($in);
            $o .= '<rect class="wx-window" x="' . self::f($x0) . '" y="0" width="' . self::f($col * count($in)) . '" height="' . ($height - 22) . '" rx="6"/>'
                . '<text class="wx-window-label" x="' . self::f($x0 + 6) . '" y="' . ($height - 26) . '">your hike</text>';
        }

        $panels = [
            ['temp', 'Temperature', '°C', 'line'],
            ['precip', 'Rain', 'mm', 'bars'],
            ['gust', 'Gusts', 'km/h', 'line'],
        ];
        foreach ($panels as $p => [$key, $title, $unit, $kind]) {
            $y0 = self::TOP + $p * (self::PANEL + self::GAP);
            $vals = array_map(fn ($h) => $h[$key], $hours);
            $nums = array_values(array_filter($vals, fn ($v) => $v !== null));
            if ($nums === []) {
                continue;
            }
            $min = $kind === 'bars' ? 0.0 : floor(min($nums) - 1);
            $max = max($kind === 'bars' ? 2.0 : $min + 4, ceil(max($nums) + ($kind === 'bars' ? 0 : 1)));
            $y = fn (float $v) => $y0 + self::PANEL - ($v - $min) / ($max - $min) * self::PANEL;
            $o .= '<text class="wx-title" x="0" y="' . ($y0 + 10) . '">' . e($title) . '</text>'
                . '<text class="wx-unit" x="0" y="' . ($y0 + 24) . '">' . e($unit) . '</text>'
                . '<line class="wx-base" x1="' . self::LEFT . '" x2="' . (self::W - self::RIGHT) . '" y1="' . self::f($y0 + self::PANEL) . '" y2="' . self::f($y0 + self::PANEL) . '"/>';
            $peak = array_search(max($nums), $vals, true);
            if ($kind === 'bars') {
                foreach ($vals as $i => $v) {
                    if ($v === null || $v <= 0) {
                        continue;
                    }
                    $top = $y((float) $v);
                    $bw = max(4, $col - 6);
                    $o .= '<path class="wx-rain" d="' . self::bar($cx($i) - $bw / 2, $top, $bw, $y0 + self::PANEL) . '"/>';
                }
                $o .= max($nums) > 0
                    ? '<text class="wx-label" x="' . self::f($cx((int) $peak)) . '" y="' . self::f($y((float) max($nums)) - 5) . '" text-anchor="middle">' . e(self::num(max($nums))) . ' mm</text>'
                    : '<text class="wx-label" x="' . self::f(self::LEFT + 6) . '" y="' . self::f($y0 + self::PANEL - 8) . '">no rain</text>';
            } else {
                $pts = [];
                foreach ($vals as $i => $v) {
                    if ($v !== null) {
                        $pts[] = self::f($cx($i)) . ',' . self::f($y((float) $v));
                    }
                }
                $o .= '<polyline class="wx-' . $key . '" points="' . implode(' ', $pts) . '"/>';
                $low = array_search(min($nums), $vals, true);
                foreach (array_unique([(int) $peak, (int) $low]) as $i) {
                    $o .= '<circle class="wx-' . $key . '-dot" cx="' . self::f($cx($i)) . '" cy="' . self::f($y((float) $vals[$i])) . '" r="4"/>'
                        . '<text class="wx-label" x="' . self::f($cx($i)) . '" y="' . self::f($y((float) $vals[$i]) - 8) . '" text-anchor="middle">'
                        . e(self::num((float) $vals[$i])) . '</text>';
                }
            }
        }

        // sunset marker
        if ($sunset !== null) {
            $first = self::minutes($hours[0]['time']);
            $pos = (self::minutes($sunset) - $first) / 60;
            if ($pos >= 0 && $pos <= $n) {
                $x = self::LEFT + $col * $pos;
                $o .= '<line class="wx-sunset" x1="' . self::f($x) . '" x2="' . self::f($x) . '" y1="0" y2="' . ($height - 22) . '"/>'
                    . '<text class="wx-sunset-label" x="' . self::f($x - 4) . '" y="' . ($height - 26) . '" text-anchor="end">sunset ' . e($sunset) . '</text>';
            }
        }

        // hour axis + hover hit areas with a tooltip per hour
        $every = $n > 12 ? 2 : 1;
        foreach ($hours as $i => $h) {
            if ($i % $every === 0) {
                $o .= '<text class="wx-axis" x="' . self::f($cx($i)) . '" y="' . ($height - 6) . '" text-anchor="middle">' . e($h['time']) . '</text>';
            }
            $o .= '<rect class="wx-hit" x="' . self::f(self::LEFT + $col * $i) . '" y="0" width="' . self::f($col) . '" height="' . ($height - 20) . '"><title>'
                . e($h['time'] . ' · ' . self::num($h['temp']) . ' °C · rain ' . self::num($h['precip']) . ' mm · gusts ' . self::num($h['gust']) . ' km/h') . '</title></rect>';
        }
        return $o . '</svg>';
    }

    private static function summary(array $hours): string
    {
        $t = array_filter(array_column($hours, 'temp'), fn ($v) => $v !== null);
        $p = array_sum(array_map(fn ($h) => $h['in_window'] ? (float) $h['precip'] : 0, $hours));
        $g = array_filter(array_column($hours, 'gust'), fn ($v) => $v !== null);
        return 'Hour by hour from ' . $hours[0]['time'] . ' to ' . $hours[count($hours) - 1]['time']
            . ($t ? ': temperature ' . self::num(min($t)) . ' to ' . self::num(max($t)) . ' °C' : '')
            . ', ' . self::num($p) . ' mm of rain during the hike'
            . ($g ? ', gusts up to ' . self::num(max($g)) . ' km/h' : '') . '.';
    }

    /** Bar with 4 px rounded top, square on the baseline. */
    private static function bar(float $x, float $top, float $w, float $base): string
    {
        $r = min(4, $w / 2, max(0, $base - $top));
        return 'M' . self::f($x) . ' ' . self::f($base) . 'V' . self::f($top + $r) . 'Q' . self::f($x) . ' ' . self::f($top) . ' ' . self::f($x + $r) . ' ' . self::f($top)
            . 'H' . self::f($x + $w - $r) . 'Q' . self::f($x + $w) . ' ' . self::f($top) . ' ' . self::f($x + $w) . ' ' . self::f($top + $r) . 'V' . self::f($base) . 'Z';
    }

    private static function minutes(string $hhmm): int
    {
        return (int) substr($hhmm, 0, 2) * 60 + (int) substr($hhmm, 3, 2);
    }

    private static function num(?float $v): string
    {
        return $v === null ? '–' : rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }

    private static function f(float $v): string
    {
        return number_format($v, 1, '.', '');
    }
}
