<?php
declare(strict_types=1);

use TrailSense\Repositories\ActivityRepository;
use TrailSense\Repositories\AssessmentRepository;
use TrailSense\Repositories\PlanRepository;
use TrailSense\Repositories\RuleRepository;
use TrailSense\Repositories\TrailRepository;
use TrailSense\Repositories\UserRepository;
use TrailSense\Services\AssessmentService;
use TrailSense\Weather\Forecast;
use TrailSense\Weather\WeatherProvider;

$db = null;
$fixedWeather = new class implements WeatherProvider {
    public ?Forecast $next = null;
    public function forecast(float $lat, float $lon, DateTimeImmutable $start, DateTimeImmutable $end): Forecast
    {
        return $this->next ?? new Forecast('open-meteo', true, '', 12.0, 20.0, 8.0, 14.0);
    }
};
$future = (new DateTimeImmutable('+5 days'))->format('Y-m-d');

return [
    '__before' => function () use (&$db) { $db = resetTestDatabase(); },

    'schema has the 16 tables of the data model' => function () use (&$db) {
        $n = (int) $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = current_schema()")->fetchColumn();
        assertSame(16, $n);
    },
    'seed: 11 real OSM trails with photos, 13 rules, 4 users' => function () use (&$db) {
        assertSame(11, (int) $db->query('SELECT COUNT(*) FROM trail WHERE osm_relation_id IS NOT NULL')->fetchColumn());
        assertSame(0, (int) $db->query('SELECT COUNT(*) FROM trail t WHERE NOT EXISTS (SELECT 1 FROM trail_photo p WHERE p.trail_id = t.id)')->fetchColumn(), 'every trail has a photo');
        assertSame(13, count((new RuleRepository($db))->active()));
        assertSame(4, (int) $db->query('SELECT COUNT(*) FROM app_user')->fetchColumn());
    },
    'trail search combines filters (AC1.2)' => function () use (&$db) {
        $repo = new TrailRepository($db);
        assertSame(2, count($repo->search('valongo')));
        assertSame(3, count($repo->search('AROUCA')), 'case-insensitive');
        assertSame('Trilho dos Romanos', $repo->search('PR2 VLG')[0]['name'], 'search by official code');
        foreach ($repo->search('', 'easy', 10.0) as $t) {
            assertTrue($t['difficulty'] === 'easy' && (float) $t['distance_km'] <= 10);
        }
        assertSame([], $repo->search('Mount Everest'));
    },
    'search text with % and _ is treated literally (no wildcard injection)' => function () use (&$db) {
        assertSame([], (new TrailRepository($db))->search('%'));
    },
    'registering normalises e-mail and the unique constraint holds (AC8.2)' => function () use (&$db) {
        $users = new UserRepository($db);
        $id = $users->create('Ana Test', 'Ana@Test.PT', 'password123', 'hiker');
        assertTrue($id > 0);
        assertTrue($users->emailExists('ana@test.pt'));
        assertThrows(fn () => $users->create('Dup', 'ana@test.pt', 'password123', 'hiker'), PDOException::class);
        assertTrue(password_verify('password123', $users->findByEmail('ana@test.pt')['password_hash']));
    },
    'role outside hiker/organizer is rejected by the database' => function () use (&$db) {
        assertThrows(fn () => (new UserRepository($db))->create('X', 'x@test.pt', 'password123', 'admin'), PDOException::class);
    },
    'group plan is saved atomically with its group details (ADR-008)' => function () use (&$db, $future) {
        $plans = new PlanRepository($db);
        $id = $plans->create(2, ['trail_id' => 4, 'planned_date' => $future, 'start_time' => '09:00', 'duration_min' => 240, 'notes' => '',
            'group' => ['group_size' => 10, 'meeting_point' => 'Station', 'meeting_time' => '08:30']]);
        $p = $plans->find($id);
        assertSame(10, (int) $p['group_size']);
        assertSame('Station', $p['meeting_point']);
    },
    'failed group insert rolls back the plan' => function () use (&$db, $future) {
        $before = (int) $db->query('SELECT COUNT(*) FROM hike_plan')->fetchColumn();
        assertThrows(fn () => (new PlanRepository($db))->create(2, ['trail_id' => 4, 'planned_date' => $future, 'start_time' => '09:00',
            'duration_min' => 240, 'group' => ['group_size' => 99, 'meeting_point' => 'Station', 'meeting_time' => '08:30']]), PDOException::class);
        assertSame($before, (int) $db->query('SELECT COUNT(*) FROM hike_plan')->fetchColumn());
    },
    'duration CHECK constraint protects the table even if validation is bypassed' => function () use (&$db, $future) {
        assertThrows(fn () => (new PlanRepository($db))->create(1, ['trail_id' => 1, 'planned_date' => $future, 'start_time' => '09:00', 'duration_min' => 5]), PDOException::class);
    },
    'a user cannot delete someone else\'s plan (AC2.4)' => function () use (&$db) {
        $plans = new PlanRepository($db);
        assertSame(false, $plans->deleteOwned(3, 1));   // plan 3 belongs to Bruno (2)
        assertTrue($plans->find(3) !== null);
    },
    'assessment stores snapshot + one result per rule and is read back (F3/F4, ADR-005)' => function () use (&$db, $fixedWeather) {
        $plans = new PlanRepository($db);
        $service = new AssessmentService($fixedWeather, new RuleRepository($db), new AssessmentRepository($db));
        $service->assess($plans->find(1));
        $a = (new AssessmentRepository($db))->latestForPlan(1);
        assertSame('warning', $a['overall_level']);      // 12 mm rain → RAIN_W
        assertSame(true, $a['is_complete']);
        assertSame(13, count($a['results']));
        assertSame('12.0', (string) $a['precip_total_mm']);
    },
    'unavailable forecast → incomplete assessment, daylight still evaluated (AC4.4/4.5)' => function () use (&$db, $fixedWeather) {
        $fixedWeather->next = Forecast::unavailable('none', 'Forecast not available yet');
        $service = new AssessmentService($fixedWeather, new RuleRepository($db), new AssessmentRepository($db));
        $service->assess((new PlanRepository($db))->find(2));
        $a = (new AssessmentRepository($db))->latestForPlan(2);
        assertSame(false, $a['is_complete']);
        $evaluated = array_column(array_filter($a['results'], fn ($r) => $r['evaluated']), 'rule');
        assertSame(['daylight', 'daylight', 'trail'], array_column($evaluated, 'factor'), 'daylight + community report still evaluated');
        $fixedWeather->next = null;
    },
    'editing a threshold in risk_rule changes the next assessment (AC4.6)' => function () use (&$db, $fixedWeather) {
        $fixedWeather->next = new Forecast('open-meteo', true, '', 0.0, 20.0, 8.0, 14.0);   // calm
        $service = new AssessmentService($fixedWeather, new RuleRepository($db), new AssessmentRepository($db));
        $plan = (new PlanRepository($db))->find(3);
        $service->assess($plan);
        $before = (new AssessmentRepository($db))->latestForPlan(3)['overall_level'];
        $db->exec("UPDATE risk_rule SET threshold = 15 WHERE code = 'WIND_C'");
        $service->assess($plan);
        $after = (new AssessmentRepository($db))->latestForPlan(3)['overall_level'];
        $db->exec("UPDATE risk_rule SET threshold = 40 WHERE code = 'WIND_C'");
        assertSame('ok', $before);
        assertSame('caution', $after);
        $fixedWeather->next = null;
    },
    'K1 completeness counts only upcoming plans with a complete latest check' => function () use (&$db) {
        $k = (new PlanRepository($db))->completeness(2);
        assertTrue($k['total'] >= 3);
        assertTrue($k['checked'] >= 1 && $k['checked'] <= $k['total']);
    },
    'publishing a hike puts it at the top of the feed (AC7.4)' => function () use (&$db) {
        $acts = new ActivityRepository($db);
        $png = (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $id = $acts->create(1, ['trail_id' => 6, 'hiked_on' => date('Y-m-d'), 'distance_km' => 5.6, 'duration_min' => 150, 'notes' => 'Great'], null,
            ['name' => str_repeat('a', 32) . '.png', 'mime' => 'image/png', 'bytes' => $png], 'Waterfall');
        $feed = $acts->feed();
        assertSame($id, (int) $feed[0]['id']);
        assertSame(str_repeat('a', 32) . '.png', $feed[0]['photo']);
        $stored = $acts->photo(str_repeat('a', 32) . '.png');
        assertSame($png, $stored['bytes'], 'binary photo round-trips through BYTEA');
    },
    'deleting a plan keeps the published hike but unlinks it (ON DELETE SET NULL)' => function () use (&$db) {
        $activityId = (int) $db->query('SELECT id FROM activity WHERE plan_id = 5')->fetchColumn();
        assertTrue($activityId > 0, 'seed: a published hike is linked to plan 5');
        assertTrue((new PlanRepository($db))->deleteOwned(5, 1));
        $row = $db->query('SELECT plan_id FROM activity WHERE id = ' . $activityId)->fetch();
        assertTrue($row !== false, 'the published hike still exists');
        assertSame(null, $row['plan_id']);
    },
];
