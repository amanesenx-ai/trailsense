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

/** Minimal HTTP client with cookies, used by the HTTP flow tests. */
final class Browser
{
    private array $cookies = [];
    public int $status = 0;
    public string $body = '';
    public string $location = '';

    public function __construct(private readonly string $base)
    {
    }

    public function get(string $query): self
    {
        return $this->request('GET', $query);
    }

    /** POST with the CSRF token taken from the last page (like a real form). */
    public function post(string $query, array $fields, array $files = [], bool $withToken = true): self
    {
        if ($withToken && preg_match('/name="_csrf" value="([^"]+)"/', $this->body, $m)) {
            $fields['_csrf'] = html_entity_decode($m[1]);
        }
        foreach ($files as $k => $path) {
            $fields[$k] = new CURLFile($path);
        }
        return $this->request('POST', $query, $fields, $files !== []);
    }

    public function follow(): self
    {
        $url = parse_url($this->location);
        return $this->get($url['query'] ?? '');
    }

    private function request(string $method, string $query, array $fields = [], bool $multipart = false): self
    {
        $ch = curl_init($this->base . '/index.php?' . $query);
        $cookie = implode('; ', array_map(fn ($k, $v) => "$k=$v", array_keys($this->cookies), $this->cookies));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIE => $cookie, CURLOPT_TIMEOUT => 10]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $fields : http_build_query($fields));
        }
        $raw = (string) curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $headers = substr($raw, 0, $headerSize);
        $this->body = substr($raw, $headerSize);
        $this->location = preg_match('/^Location: (.+)$/mi', $headers, $m) ? trim($m[1]) : '';
        preg_match_all('/^Set-Cookie: ([^=]+)=([^;]*)/mi', $headers, $mm, PREG_SET_ORDER);
        foreach ($mm as $c) {
            $this->cookies[$c[1]] = $c[2];
        }
        return $this;
    }
}

