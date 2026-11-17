<?php
declare(strict_types=1);

namespace TrailSense\Core;

use PDO;

/**
 * First-run installation (ADR-009): creates the tables and demo data ONLY when the database is empty.
 * Never drops or changes existing data. Used by bin/db-init.php and, once, by public/index.php
 * on hosts where we cannot run a command (alwaysdata). A marker file avoids checking on every request.
 */
final class Installer
{
    public static function markerFile(): string
    {
        return TS_ROOT . '/var/installed';
    }

    /** @return bool true if tables were created now */
    public static function ensureInstalled(PDO $pdo): bool
    {
        if (is_file(self::markerFile())) {
            return false;
        }
        $exists = (bool) $pdo->query("SELECT to_regclass('app_user') IS NOT NULL")->fetchColumn();
        if (!$exists) {
            $pdo->exec((string) file_get_contents(TS_ROOT . '/database/schema.sql'));
            $pdo->exec((string) file_get_contents(TS_ROOT . '/database/seed.sql'));
        }
        @mkdir(dirname(self::markerFile()), 0775, true);
        @file_put_contents(self::markerFile(), date('c') . "\n");
        return !$exists;
    }
}
