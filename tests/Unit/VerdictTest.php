<?php
declare(strict_types=1);

use TrailSense\Core\WeatherChart;
use TrailSense\Domain\Verdict;

$factor = fn (string $factor, string $status, ?string $name = null, string $precaution = '') => [
    'factor' => $factor, 'label' => ucfirst($factor), 'status' => $status,
    'rule' => $name === null ? null : ['name' => $name], 'precaution' => $precaution ?: null,
];

return [
    'verdict: good to go when everything is OK and complete (AC4.11)' => function () use ($factor) {
        $v = Verdict::from('ok', true, [$factor('rain', 'ok'), $factor('wind', 'ok')]);
        assertSame(['ok', 'Good to go'], [$v['key'], $v['title']]);
    },
    'verdict: caution names the reasons; warning wins over caution' => function () use ($factor) {
        $f = [$factor('rain', 'caution', 'Rain expected during the hike'), $factor('wind', 'caution', 'Strong wind gusts')];
        $v = Verdict::from('caution', true, $f);
        assertSame('Go prepared', $v['title']);
        assertSame('Rain expected during the hike and strong wind gusts.', $v['reason']);
        $three = Verdict::from('caution', true, [...$f, $factor('daylight', 'caution', 'Finishing close to sunset')]);
        assertSame('Rain expected during the hike, strong wind gusts and finishing close to sunset.', $three['reason']);
        $f[] = $factor('daylight', 'warning', 'Finishing after or near sunset');
        assertSame('Rethink this plan', Verdict::from('warning', true, $f)['title']);
    },
    'verdict: incomplete OK says what is not published yet' => function () use ($factor) {
        $v = Verdict::from('ok', false, [$factor('rain', 'ok'), $factor('fire', 'pending')]);
        assertSame('pending', $v['key']);
        assertContains('fire', $v['reason']);
    },
    'checklist lists triggered precautions first (warning, then caution), then basics (AC4.12)' => function () use ($factor) {
        $c = Verdict::checklist([$factor('rain', 'caution', 'R', 'Pack a waterproof'), $factor('wind', 'warning', 'W', 'Avoid ridges'), $factor('heat', 'ok')]);
        assertSame(['Avoid ridges', 'Pack a waterproof'], [$c[0]['text'], $c[1]['text']]);
        assertSame('basic', $c[2]['level']);
    },
    'weather chart: three panels on one hour axis, window band, sunset marker, tooltips (AC4.13)' => function () {
        $hours = [];
        foreach (range(13, 19) as $i => $h) {
            $hours[] = ['time' => sprintf('%02d:00', $h), 'temp' => 12.0 - $i, 'precip' => $i === 3 ? 1.6 : 0.0, 'gust' => 20.0 + $i, 'code' => 3, 'in_window' => $i >= 2 && $i <= 4];
        }
        $svg = WeatherChart::svg($hours, '17:06');
        assertContains('class="wx-window"', $svg);
        assertContains('sunset 17:06', $svg);
        assertContains('1.6 mm', $svg);
        assertSame(7, substr_count($svg, '<title>'));
        assertSame(2, substr_count($svg, '<polyline'));
        assertContains('aria-label="Hour by hour from 13:00 to 19:00', $svg);
        assertSame('', WeatherChart::svg([], null));
    },
];
