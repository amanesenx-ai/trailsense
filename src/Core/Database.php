<?php
declare(strict_types=1);

namespace TrailSense\Core;

use PDO;

/** Single shared PDO connection. Repositories receive it through their constructor. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect(config('db'));
        }
        return self::$pdo;
    }

    public static function connect(array $db): PDO
    {
        $pdo = new PDO($db['dsn'], $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $schema = $db['schema'] ?? 'public';
        if (preg_match('/^[a-z_][a-z0-9_]*$/i', $schema)) {
            $pdo->exec('SET search_path TO ' . $schema);
        }
        $pdo->exec("SET TIME ZONE 'Europe/Lisbon'");
        return $pdo;
    }

    /** Used by tests to inject the test connection. */
    public static function use(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
