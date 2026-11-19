<?php
declare(strict_types=1);

use TrailSense\Core\ElevationChart;
use TrailSense\Core\StaticMap;
use TrailSense\Domain\Climb;
use TrailSense\Domain\RouteType;
use TrailSense\Domain\Waymarks;

// 4 km: flat 1 km, climb 200 m over 1 km (20 %), flat, small dip
$profile = [];
foreach (range(0, 40) as $i) {
    $km = $i / 10;
    $m = $km <= 1 ? 100 : ($km <= 2 ? 100 + ($km - 1) * 200 : ($km <= 3 ? 300 : 300 - ($km - 3) * 50));
    $profile[] = [$km, $m];
}

return [
    'climb analysis: highest point, longest climb (where, gain, grade), steepest part (AC1.13)' => function () use ($profile) {
        $c = Climb::analyse($profile, 400, 4.0);
        assertSame(300, $c['highest']);
        assertSame(100, $c['lowest']);
        assertTrue($c['longest']['gain'] >= 180 && $c['longest']['gain'] <= 200, 'gain ~200 m (smoothed)');
        assertTrue($c['longest']['from_km'] >= 0.9 && $c['longest']['to_km'] <= 2.1);
        assertTrue($c["steepest"]["grade"] >= 15 && $c["steepest"]["grade"] <= 20.5, "steepest ≈ 20 %");
        assertSame("very", $c["rating"], "100 m per km");
    },
    'climb rating: flat coastal walk is gentle; no profile still rates by ascent per km' => function () {
        $flat = array_map(fn ($i) => [$i / 10, 20 + ($i % 2)], range(0, 50));
        assertSame('gentle', Climb::analyse($flat, 40, 5.0)['rating']);
        $none = Climb::analyse([], 400, 5.0);
        assertSame(null, $none['highest']);
        assertSame('demanding', $none['rating'], '80 m per km (55–85)');
    },
    'route type in plain words: PR is short and one day, GR long, boardwalk not official (AC1.13)' => function () {
        $pr = RouteType::describe('PR2 VLG', 'Trilho dos Romanos', 4.1, 120, false);
        assertContains('Pequena Rota', $pr['name']);
        assertContains('up to 30 km', $pr['plain']);
        assertContains('one way', $pr['length']);
        assertContains('more than 30 km', RouteType::describe('GR11', 'x', 40, 900, false)['plain']);
        $b = RouteType::describe('', 'Passadiços do Paiva', 8.8, 200, false);
        assertContains('Not an official', $b['name']);
        assertContains('a loop', RouteType::describe('PR1', 'x', 6, 130, true)['length']);
    },
    'FCMP marks as in the manual p.13: GR white/red, PR yellow/red, PL white/green; X for wrong way (AC13.6)' => function () {
        assertSame(['#ffffff', '#d0202e'], [Waymarks::SYSTEMS['GR']['top'], Waymarks::SYSTEMS['GR']['bottom']]);
        assertSame(['#ffffff', '#7ab51d'], [Waymarks::SYSTEMS['PL']['top'], Waymarks::SYSTEMS['PL']['bottom']]);
        $x = Waymarks::svg('wrong', '#ffd400', '#d0202e', 'Wrong way');
        assertSame(2, substr_count($x, 'stroke-width="7"'));
        assertContains('fill="#d0202e"', Waymarks::svg('left', '#ffd400', '#d0202e', 'Turn left'));
        assertContains('h-8V24H12z', Waymarks::svg('left', '#ffd400', '#d0202e', 'Turn left'), 'turn left: leg on the right, corner points left');
        assertContains('h8V24h28z', Waymarks::svg('turn-right', '#ffd400', '#d0202e', 'Turn right'), 'turn right: leg on the left, corner points right');
        assertTrue(count(Waymarks::PLACEMENT) >= 3);
        assertContains('250 m', Waymarks::PLACEMENT[0]);
    },
    'static map: OSM tiles + route + start/end, attribution, fits the route (AC1.15)' => function () {
        $route = [[41.18439, -8.48793], [41.17622, -8.49331], [41.17295, -8.47952]];
        $svg = StaticMap::svg($route, 'Map of Trilho dos Romanos', 400, 220);
        assertContains('https://tile.openstreetmap.org/', $svg);
        assertTrue(substr_count($svg, '<image ') >= 2 && substr_count($svg, '<image ') <= 9, 'only tiles in view');
        assertSame(2, substr_count($svg, '<circle'), 'start and end');
        assertContains('© OpenStreetMap', $svg);
        assertSame(1, substr_count(StaticMap::svg($route, 'loop', 400, 220, true), '<circle'), 'loop: one start/finish dot');
        assertSame('', StaticMap::svg([[41, -8]], 'x'));
        $z = StaticMap::fitZoom(41.17295, 41.18443, -8.49624, -8.47952, 352, 172);
        assertTrue($z >= 13 && $z <= 15);
    },
    'elevation chart: one series, highest point labelled, climb band, a tooltip per sample (AC1.14)' => function () use ($profile) {
        $svg = ElevationChart::svg($profile, ['from_km' => 1.0, 'to_km' => 2.0], 'Profile');
        assertContains('class="elev-line"', $svg);
        assertContains('300 m', $svg);
        assertContains('longest climb', $svg);
        assertSame(count($profile), substr_count($svg, '<title>'));
    },
];
