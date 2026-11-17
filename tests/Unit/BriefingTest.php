<?php
declare(strict_types=1);

use TrailSense\Domain\Briefing;
use TrailSense\Domain\RiskEngine;

$plan = ['trail_name' => 'Serra de Santa Justa – PR8', 'region' => 'Valongo', 'planned_date' => '2026-11-21',
    'start_time' => '09:30:00', 'duration_min' => 180, 'distance_km' => '8.0', 'difficulty' => 'moderate'];
$group = ['group_size' => 8, 'meeting_point' => 'Valongo train station', 'meeting_time' => '09:00:00'];

return [
    'briefing lists meeting, timing and each warning with its precaution (AC9.2)' => function () use ($plan, $group) {
        $metrics = ['precip_total_mm' => 3.0, 'gust_max_kmh' => 20.0, 'temp_max_c' => 15.0, 'temp_min_c' => 8.0, 'daylight_margin_min' => 300.0];
        $r = (new RiskEngine())->evaluate(defaultRules(), $metrics);
        $text = Briefing::build($plan, $group, RiskEngine::byFactor($r['results']), $r['complete'], '13:45');
        assertContains('Meet: 09:00 at Valongo train station', $text);
        assertContains('8 people', $text);
        assertContains('Start 09:30 · about 3 h · 8 km · moderate', $text);
        assertContains('CAUTION Rain', $text);
        assertContains('3 mm', $text);
        assertContains('Do X.', $text);
        assertNotContains('Not yet checked', $text);
    },
    'briefing says which factors are not yet checked when incomplete' => function () use ($plan, $group) {
        $metrics = ['precip_total_mm' => null, 'gust_max_kmh' => null, 'temp_max_c' => null, 'temp_min_c' => null, 'daylight_margin_min' => 20.0];
        $r = (new RiskEngine())->evaluate(defaultRules(), $metrics);
        $text = Briefing::build($plan, $group, RiskEngine::byFactor($r['results']), $r['complete'], '13:45');
        assertContains('Not yet checked (forecast unavailable): Rain, Wind, Heat, Cold', $text);
        assertContains('Start by 13:45', $text);
    },
    'duration formatting' => function () {
        assertSame('45 min', Briefing::duration(45));
        assertSame('2 h', Briefing::duration(120));
        assertSame('2 h 50 min', Briefing::duration(170));
    },
];
