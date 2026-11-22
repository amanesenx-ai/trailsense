<?php
declare(strict_types=1);

use TrailSense\Core\Database;

/* Migration 006: demo plans that slid into the past move forward by whole weeks; nothing else changes. */
$pdo = null;
$sql = (string) file_get_contents(__DIR__ . '/../../database/migrations/006_refresh_demo_dates.sql');
return [
    '__before' => function () use (&$pdo) {
        $pdo = Database::connect(config('db_test'));
        $pdo->exec((string) file_get_contents(__DIR__ . '/../../database/schema.sql'));
        $pdo->exec((string) file_get_contents(__DIR__ . '/../../database/seed.sql'));
    },
    'past demo plans move to the future on the same weekday; hiked, upcoming and real-user plans stay' => function () use (&$pdo, $sql) {
        // simulate the live site 10 days after it was filled: every plan 10 days earlier
        $pdo->exec("UPDATE hike_plan SET planned_date = planned_date - 10");
        $pdo->exec("INSERT INTO app_user (name, email, password_hash, role) VALUES ('Real', 'real@user.pt', 'x', 'hiker')");
        $pdo->exec("INSERT INTO hike_plan (user_id, trail_id, planned_date, start_time, duration_min) SELECT id, 1, CURRENT_DATE - 3, '10:00', 60 FROM app_user WHERE email = 'real@user.pt'");
        $before = $pdo->query('SELECT id, planned_date FROM hike_plan ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR);
        $pdo->beginTransaction(); $pdo->exec($sql); $pdo->commit();
        $after = $pdo->query('SELECT id, planned_date FROM hike_plan ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR);
        $today = new DateTimeImmutable('today');
        $hiked = array_map('intval', $pdo->query('SELECT plan_id FROM activity WHERE plan_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN));
        $realId = (int) $pdo->query("SELECT p.id FROM hike_plan p JOIN app_user u ON u.id = p.user_id WHERE u.email = 'real@user.pt'")->fetchColumn();
        foreach ($before as $id => $d) {
            $old = new DateTimeImmutable($d); $new = new DateTimeImmutable($after[$id]);
            if ($id === $realId || in_array($id, $hiked, true) || $old >= $today) {
                assertSame($d, $after[$id], "plan $id unchanged");
            } else {
                assertTrue($new >= $today, "plan $id now upcoming");
                assertSame($old->format('N'), $new->format('N'), "plan $id same weekday");
            }
        }
        assertTrue(count(array_diff_assoc($after, $before)) >= 3, 'some demo plans moved');
        assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM hike_plan WHERE planned_date >= CURRENT_DATE AND id IN (SELECT plan_id FROM group_plan)") >= 1 ? 1 : 0, 'group hikes upcoming again');
    },
];
