<?php
declare(strict_types=1);

use TrailSense\Core\Database;
use TrailSense\Core\Installer;

$pdo = null;
return [
    '__before' => function () use (&$pdo) {
        $pdo = Database::connect(config('db_test'));
        $pdo->exec('DROP TABLE IF EXISTS activity_photo, activity, assessment_result, risk_assessment, weather_snapshot, risk_rule, group_plan, hike_plan, trail, app_user CASCADE');
        @unlink(Installer::markerFile());
    },
    'empty database → tables and demo data are created (first run)' => function () use (&$pdo) {
        assertSame(true, Installer::ensureInstalled($pdo));
        assertSame(10, (int) $pdo->query('SELECT COUNT(*) FROM trail')->fetchColumn());
        assertTrue(is_file(Installer::markerFile()));
    },
    'existing data is never touched (second run, even without the marker)' => function () use (&$pdo) {
        $pdo->exec("INSERT INTO app_user (name, email, password_hash, role) VALUES ('Keep Me', 'keep@me.pt', 'x', 'hiker')");
        @unlink(Installer::markerFile());
        assertSame(false, Installer::ensureInstalled($pdo));
        assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM app_user WHERE email = 'keep@me.pt'")->fetchColumn());
    },
    '__after' => function () { @unlink(Installer::markerFile()); },
];
