<?php
declare(strict_types=1);

use TrailSense\Core\Database;
use TrailSense\Core\Installer;

$pdo = null;
return [
    '__before' => function () use (&$pdo) {
        $pdo = Database::connect(config('db_test'));
        $pdo->exec('DROP TABLE IF EXISTS trail_favourite, schema_migration, activity_comment, activity_like, trail_report, hike_participant, trail_photo, activity_photo, activity, assessment_result, risk_assessment, weather_snapshot, risk_rule, group_plan, hike_plan, trail, app_user CASCADE');
        @unlink(Installer::markerFile());
    },
    'empty database → tables and demo data are created (first run)' => function () use (&$pdo) {
        assertSame('installed', Installer::ensureInstalled($pdo));
        assertSame(11, (int) $pdo->query('SELECT COUNT(*) FROM trail')->fetchColumn());
        assertSame(Installer::latestVersion(), (int) $pdo->query('SELECT MAX(version) FROM schema_migration')->fetchColumn());
        assertTrue(is_file(Installer::markerFile()));
    },
    'existing data is never touched (second run, even without the marker)' => function () use (&$pdo) {
        $pdo->exec("INSERT INTO app_user (name, email, password_hash, role) VALUES ('Keep Me', 'keep@me.pt', 'x', 'hiker')");
        @unlink(Installer::markerFile());
        assertSame('up-to-date', Installer::ensureInstalled($pdo));
        assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM app_user WHERE email = 'keep@me.pt'")->fetchColumn());
    },
    'a release-1 database is upgraded by migration 002 and keeps its data (ADR-014)' => function () use (&$pdo) {
        $pdo->exec('DROP TABLE IF EXISTS trail_favourite, schema_migration, activity_comment, activity_like, trail_report, hike_participant, trail_photo, activity_photo, activity, assessment_result, risk_assessment, weather_snapshot, risk_rule, group_plan, hike_plan, trail, app_user CASCADE');
        $pdo->exec((string) file_get_contents(__DIR__ . '/../fixtures/release1-schema.sql'));
        $pdo->exec((string) file_get_contents(__DIR__ . '/../fixtures/release1-seed.sql'));
        $pdo->exec("INSERT INTO hike_plan (user_id, trail_id, planned_date, start_time, duration_min) VALUES (1, 6, CURRENT_DATE + 3, '10:00', 90)");
        // release 1 left its own marker file (an ISO date, not a version): it must not block the upgrade
        @mkdir(dirname(Installer::LEGACY_MARKER), 0775, true);
        file_put_contents(Installer::LEGACY_MARKER, "2026-11-17T23:05:54+00:00\n");
        @unlink(Installer::markerFile());
        assertSame('migrated:2,3,4,5', Installer::ensureInstalled($pdo));
        assertSame(11, (int) $pdo->query('SELECT COUNT(*) FROM trail')->fetchColumn());
        assertSame(30, (int) $pdo->query('SELECT COUNT(*) FROM trail_photo')->fetchColumn());
        assertSame(6, (int) $pdo->query('SELECT COUNT(*) FROM hike_plan')->fetchColumn(), 'user data kept');
        assertSame('Passadiços do Paiva', $pdo->query('SELECT name FROM trail WHERE id = 4')->fetchColumn());
        assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM trail WHERE name LIKE '%[r1-%'")->fetchColumn());
        @unlink(Installer::markerFile());
        assertSame('up-to-date', Installer::ensureInstalled($pdo), 'migration never runs twice');
    },
    '__after' => function () { @unlink(Installer::markerFile()); resetTestDatabase(); },
];
