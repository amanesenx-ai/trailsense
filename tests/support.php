<?php
declare(strict_types=1);

use TrailSense\Core\Database;

final class AssertionFailed extends RuntimeException
{
}

function assertSame(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new AssertionFailed(trim($msg . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)));
    }
}

function assertTrue(bool $cond, string $msg = 'expected true'): void
{
    if (!$cond) {
        throw new AssertionFailed($msg);
    }
}

function assertContains(string $needle, string $haystack, string $msg = ''): void
{
    if (!str_contains($haystack, $needle)) {
        throw new AssertionFailed(trim("$msg expected to find '$needle'"));
    }
}

function assertNotContains(string $needle, string $haystack, string $msg = ''): void
{
    if (str_contains($haystack, $needle)) {
        throw new AssertionFailed(trim("$msg did not expect '$needle'"));
    }
}

function assertThrows(callable $fn, string $class = Throwable::class): Throwable
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($e instanceof $class) {
            return $e;
        }
        throw new AssertionFailed('expected ' . $class . ', got ' . get_class($e) . ': ' . $e->getMessage());
    }
    throw new AssertionFailed("expected $class to be thrown");
}

/** Recreate the test database from schema.sql + seed.sql and return a PDO to it. */
function resetTestDatabase(): PDO
{
    $cfg = config('db_test');
    if (empty($cfg)) {
        throw new RuntimeException('config db_test is not set; integration tests need a separate database.');
    }
    $pdo = Database::connect($cfg);
    $pdo->exec((string) file_get_contents(TS_ROOT . '/database/schema.sql'));
    $pdo->exec((string) file_get_contents(TS_ROOT . '/database/seed.sql'));
    Database::use($pdo);
    return $pdo;
}

/** A rule row like those in risk_rule. */
function rule(string $code, string $factor, string $metric, string $cmp, float $threshold, string $level, string $precaution = 'Do X.'): array
{
    return compact('code', 'factor', 'metric', 'threshold', 'level', 'precaution')
        + ['comparator' => $cmp, 'name' => "Rule $code"];
}

function defaultRules(): array
{
    return [
        rule('RAIN_C', 'rain', 'precip_total_mm', '>=', 2, 'caution'),
        rule('RAIN_W', 'rain', 'precip_total_mm', '>=', 10, 'warning'),
        rule('WIND_C', 'wind', 'gust_max_kmh', '>=', 40, 'caution'),
        rule('WIND_W', 'wind', 'gust_max_kmh', '>=', 60, 'warning'),
        rule('HEAT_C', 'heat', 'temp_max_c', '>=', 30, 'caution'),
        rule('COLD_C', 'cold', 'temp_min_c', '<=', 3, 'caution'),
        rule('DAY_C', 'daylight', 'daylight_margin_min', '<', 60, 'caution'),
        rule('DAY_W', 'daylight', 'daylight_margin_min', '<', 30, 'warning', 'Start by {latest_start} or choose a shorter trail.'),
    ];
}
