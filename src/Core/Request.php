<?php
declare(strict_types=1);

namespace TrailSense\Core;

/** Small helpers to read input safely (always strings, trimmed). */
final class Request
{
    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function post(string $key, string $default = ''): string
    {
        $v = $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    public static function get(string $key, string $default = ''): string
    {
        $v = $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    /** Positive integer id from GET/POST or null. */
    public static function id(string $key = 'id'): ?int
    {
        $v = $_GET[$key] ?? $_POST[$key] ?? null;
        if (!is_string($v) || !ctype_digit($v) || (int) $v < 1) {
            return null;
        }
        return (int) $v;
    }
}
