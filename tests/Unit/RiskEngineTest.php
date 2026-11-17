<?php
declare(strict_types=1);

use TrailSense\Domain\RiskEngine;

$calm = ['precip_total_mm' => 0.0, 'gust_max_kmh' => 15.0, 'temp_max_c' => 18.0, 'temp_min_c' => 10.0, 'daylight_margin_min' => 180.0];

return [
    'calm day gives OK and complete (AC4.1)' => function () use ($calm) {
        $r = (new RiskEngine())->evaluate(defaultRules(), $calm);
        assertSame('ok', $r['overall']);
        assertTrue($r['complete']);
        assertSame(8, count($r['results']));
    },
    'rain at threshold triggers CAUTION (boundary ≥ 2 mm)' => function () use ($calm) {
        $r = (new RiskEngine())->evaluate(defaultRules(), ['precip_total_mm' => 2.0] + $calm);
        assertSame('caution', $r['overall']);
    },
    'rain just below threshold stays OK' => function () use ($calm) {
        $r = (new RiskEngine())->evaluate(defaultRules(), ['precip_total_mm' => 1.9] + $calm);
        assertSame('ok', $r['overall']);
    },
    'worst level wins: caution + warning = WARNING (AC4.3)' => function () use ($calm) {
        $r = (new RiskEngine())->evaluate(defaultRules(), ['precip_total_mm' => 3.0, 'gust_max_kmh' => 65.0] + $calm);
        assertSame('warning', $r['overall']);
    },
    'missing forecast marks incomplete, never OK for that factor (AC4.4)' => function () use ($calm) {
        $metrics = ['precip_total_mm' => null, 'gust_max_kmh' => null, 'temp_max_c' => null, 'temp_min_c' => null] + $calm;
        $r = (new RiskEngine())->evaluate(defaultRules(), $metrics);
        assertSame(false, $r['complete']);
        $f = RiskEngine::byFactor($r['results']);
        assertSame('unknown', $f['rain']['status']);
        assertSame('ok', $f['daylight']['status'], 'daylight still evaluated (AC4.5)');
    },
    'byFactor reports the strongest triggered rule per factor' => function () use ($calm) {
        $r = (new RiskEngine())->evaluate(defaultRules(), ['precip_total_mm' => 12.0, 'daylight_margin_min' => 45.0] + $calm);
        $f = RiskEngine::byFactor($r['results']);
        assertSame('warning', $f['rain']['status']);
        assertSame('RAIN_W', $f['rain']['rule']['code']);
        assertSame('caution', $f['daylight']['status']);
        assertSame('ok', $f['wind']['status']);
        assertSame(null, $f['wind']['rule']);
    },
    'thresholds are data: changing a rule changes the outcome (AC4.6)' => function () use ($calm) {
        $rules = defaultRules();
        $rules[2]['threshold'] = 10;  // WIND_C now at 10 km/h
        $r = (new RiskEngine())->evaluate($rules, $calm);
        assertSame('caution', $r['overall']);
    },
    'condition text explains the rule (AC4.2)' => function () {
        assertSame('Total rain ≥ 2 mm', RiskEngine::describeCondition(defaultRules()[0]));
        assertSame('Time between finish and sunset < 30 min', RiskEngine::describeCondition(defaultRules()[7]));
    },
    'precaution placeholder is filled (AC5.2)' => function () {
        assertSame('Start by 13:30 or choose a shorter trail.', RiskEngine::precaution(defaultRules()[7], ['latest_start' => '13:30']));
    },
    'daylight value reads as before/after sunset' => function () {
        assertSame('ends 62 min before sunset', RiskEngine::formatObserved(62.0, 'daylight_margin_min'));
        assertSame('ends 15 min after sunset', RiskEngine::formatObserved(-15.0, 'daylight_margin_min'));
        assertSame('no data', RiskEngine::formatObserved(null, 'gust_max_kmh'));
    },
    'advisory rules without data do not make the check incomplete (AC4.9)' => function () use ($calm) {
        $rules = array_merge(defaultRules(), [
            rule('IPMA_Y', 'official', 'ipma_warning_level', '>=', 1, 'caution') + ['required' => false],
            rule('FIRE_C', 'fire', 'fire_risk', '>=', 3, 'caution') + ['required' => false],
        ]);
        $r = (new RiskEngine())->evaluate($rules, $calm + ['ipma_warning_level' => null, 'fire_risk' => null]);
        assertSame(true, $r['complete']);
        $f = RiskEngine::byFactor($r['results']);
        assertSame('pending', $f['official']['status']);
        assertSame('not published yet', RiskEngine::formatObserved(null, 'fire_risk'));
    },
    'orange IPMA warning and fire risk 4 trigger as data says (AC4.7/4.8)' => function () use ($calm) {
        $rules = array_merge(defaultRules(), [
            rule('IPMA_Y', 'official', 'ipma_warning_level', '>=', 1, 'caution') + ['required' => false],
            rule('IPMA_O', 'official', 'ipma_warning_level', '>=', 2, 'warning') + ['required' => false],
            rule('FIRE_C', 'fire', 'fire_risk', '>=', 3, 'caution') + ['required' => false],
            rule('FIRE_W', 'fire', 'fire_risk', '>=', 4, 'warning') + ['required' => false],
        ]);
        $r = (new RiskEngine())->evaluate($rules, $calm + ['ipma_warning_level' => 2.0, 'fire_risk' => 3.0]);
        $f = RiskEngine::byFactor($r['results']);
        assertSame('warning', $f['official']['status']);
        assertSame('IPMA_O', $f['official']['rule']['code']);
        assertSame('caution', $f['fire']['status']);
        assertSame('3/5 Elevated', RiskEngine::formatObserved(3.0, 'fire_risk'));
        assertSame('orange warning', RiskEngine::formatObserved(2.0, 'ipma_warning_level'));
        assertSame('IPMA warning orange or higher for this district', RiskEngine::describeCondition($rules[9]));
    },
    'a closed-trail report is a WARNING (AC10.3)' => function () use ($calm) {
        $rules = array_merge(defaultRules(), [rule('TRAIL_CL', 'trail', 'trail_closed', '>=', 1, 'warning')]);
        $r = (new RiskEngine())->evaluate($rules, $calm + ['trail_closed' => 1.0]);
        assertSame('warning', $r['overall']);
    },
    'unknown comparator is rejected' => function () {
        assertThrows(fn () => RiskEngine::compare(1, '!=', 2), InvalidArgumentException::class);
    },
    'every default rule has a precaution (AC5.1)' => function () {
        foreach (defaultRules() as $r) {
            assertTrue(trim($r['precaution']) !== '', $r['code'] . ' has no precaution');
        }
    },
];
