<?php
declare(strict_types=1);

namespace TrailSense\Core;

use PDO;

/**
 * Installs and upgrades the database (ADR-009, ADR-014).
 * - Empty database: runs database/schema.sql + database/seed.sql (already at the latest version).
 * - Existing database: applies database/migrations/NNN_*.sql that are not yet recorded in schema_migration,
 *   each one in its own transaction. Never drops existing data.
 * A marker file holding the latest version avoids any check on normal requests.
 */
final class Installer
{
    /** Release 1 wrote an ISO date here; it is ignored (never read as a version). */
    public const LEGACY_MARKER = TS_ROOT . '/var/installed';

    public static function markerFile(): string
    {
        return TS_ROOT . '/var/schema-version';
    }

    /** @return array<int,string> version => file, sorted */
    public static function migrations(): array
    {
        $out = [];
        foreach (glob(TS_ROOT . '/database/migrations/*.sql') ?: [] as $file) {
            if (preg_match('/^(\d{3})_/', basename($file), $m)) {
                $out[(int) $m[1]] = $file;
            }
        }
        ksort($out);
        return $out;
    }

    public static function latestVersion(): int
    {
        $versions = array_keys(self::migrations());
        return $versions === [] ? 1 : max(1, max($versions));
    }

    /** @return string what happened: 'installed', 'migrated:N,M', or 'up-to-date' */
    public static function ensureInstalled(PDO $pdo): string
    {
        $latest = self::latestVersion();
        $marker = is_file(self::markerFile()) ? trim((string) file_get_contents(self::markerFile())) : '';
        if (ctype_digit($marker) && (int) $marker >= $latest) {
            return 'up-to-date';                                  // fast path: no database query
        }

        $result = 'up-to-date';
        $pdo->query('SELECT pg_advisory_lock(7342101)');      // one installer at a time (concurrent first requests)
        try {
            $hasTables = (bool) $pdo->query("SELECT to_regclass('app_user') IS NOT NULL")->fetchColumn();
            if (!$hasTables) {
                $pdo->exec((string) file_get_contents(TS_ROOT . '/database/schema.sql'));
                $pdo->exec((string) file_get_contents(TS_ROOT . '/database/seed.sql'));
                $result = 'installed';
            } else {
                $applied = self::migrate($pdo);
                if ($applied !== []) {
                    $result = 'migrated:' . implode(',', $applied);
                }
            }
        } finally {
            $pdo->query('SELECT pg_advisory_unlock(7342101)');
        }

        @mkdir(dirname(self::markerFile()), 0775, true);
        @file_put_contents(self::markerFile(), $latest . "\n");
        return $result;
    }

    /** @return list<int> versions applied now */
    public static function migrate(PDO $pdo): array
    {
        $hadTable = (bool) $pdo->query("SELECT to_regclass('schema_migration') IS NOT NULL")->fetchColumn();
        if (!$hadTable) {
            // database created by release 1 (before migrations existed) = version 1
            $pdo->exec('CREATE TABLE schema_migration (version INTEGER PRIMARY KEY, applied_at TIMESTAMPTZ NOT NULL DEFAULT now())');
            $pdo->exec('INSERT INTO schema_migration (version) VALUES (1)');
        }
        $done = array_map('intval', $pdo->query('SELECT version FROM schema_migration')->fetchAll(PDO::FETCH_COLUMN));

        $applied = [];
        foreach (self::migrations() as $version => $file) {
            if (in_array($version, $done, true)) {
                continue;
            }
            $pdo->beginTransaction();
            try {
                $pdo->exec((string) file_get_contents($file));
                $pdo->prepare('INSERT INTO schema_migration (version) VALUES (?)')->execute([$version]);
                $pdo->commit();
                $applied[] = $version;
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw new \RuntimeException("Migration $version failed: " . $e->getMessage(), 0, $e);
            }
        }
        return $applied;
    }
}
