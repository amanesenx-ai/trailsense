<?php
declare(strict_types=1);

// Autoload: namespace TrailSense\X\Y -> src/X/Y.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'TrailSense\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

const TS_ROOT = __DIR__ . '/..';

function config(?string $key = null): mixed
{
    static $config = null;
    if ($config === null) {
        $file = TS_ROOT . '/config/config.php';
        if (is_file($file)) {
            $config = require $file;
        } elseif (getenv('DATABASE_URL')) {
            $config = config_from_env();          // cloud hosting: secrets come from environment variables
        } else {
            http_response_code(500);
            exit('Missing config: copy config/config.example.php to config/config.php or set DATABASE_URL.');
        }
        // Test runs point the app at the test database (never the real one).
        if (getenv('TS_ENV') === 'test' && !empty($config['db_test'])) {
            $config['db'] = $config['db_test'];
        }
    }
    return $key === null ? $config : ($config[$key] ?? null);
}

/**
 * Build config from environment variables (ADR-009). DATABASE_URL format:
 * postgresql://user:password@host[:port]/dbname?sslmode=require
 */
function config_from_env(): array
{
    $url = parse_url((string) getenv('DATABASE_URL'));
    parse_str($url['query'] ?? '', $query);
    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
        $url['host'] ?? 'localhost',
        $url['port'] ?? 5432,
        ltrim($url['path'] ?? '', '/'),
        $query['sslmode'] ?? 'require'
    );
    return [
        'db' => [
            'dsn' => $dsn,
            'user' => rawurldecode($url['user'] ?? ''),
            'password' => rawurldecode($url['pass'] ?? ''),
            'schema' => 'public',
        ],
        'db_test' => null,
        'base_url' => getenv('BASE_URL') ?: '/',
        'weather_provider' => getenv('WEATHER_PROVIDER') ?: 'open-meteo',
        'debug' => getenv('APP_DEBUG') === '1',
        'timezone' => 'Europe/Lisbon',
    ];
}

/** Escape any value for HTML output (NFR3). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an internal URL: url('plan', ['id' => 3]) -> index.php?r=plan&id=3 */
function url(string $route = 'home', array $params = []): string
{
    $query = http_build_query(['r' => $route] + $params);
    return rtrim((string) config('base_url'), '/') . '/index.php?' . $query;
}

function asset(string $path): string
{
    $file = TS_ROOT . '/public/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 0;
    return rtrim((string) config('base_url'), '/') . '/assets/' . $path . '?v=' . $v;
}

/** Photos are stored in the database (ADR-009) and served by the `photo` route. */
function photo_url(string $fileName): string
{
    return url('photo', ['f' => $fileName]);
}

date_default_timezone_set((string) (config('timezone') ?? 'Europe/Lisbon'));
