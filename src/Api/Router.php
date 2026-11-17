<?php
declare(strict_types=1);

namespace TrailSense\Api;

/**
 * Public read-only JSON API /api/v1 (F12, ADR-013). No session, no cookies, CORS open, no personal data.
 */
final class Router
{
    /** Path after /api/v1, e.g. "/trails/3/conditions". Accepts /api/v1/... or ?r=api&path=... */
    public static function pathFrom(string $uri, string $basePath, ?string $queryPath): ?string
    {
        if ($queryPath !== null) {
            return '/' . trim($queryPath, '/');
        }
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $base = rtrim($basePath, '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = preg_replace('#^/index\.php#', '', $path);
        return preg_match('#^/api/v1(/.*)?$#', (string) $path, $m) ? ($m[1] ?? '/') : null;
    }

    public static function handle(string $path, string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Cache-Control: no-store');
        if ($method === 'OPTIONS') {
            http_response_code(204);
            return;
        }
        try {
            if ($method !== 'GET') {
                throw new ApiError(405, 'Only GET is supported: this API is read-only.');
            }
            $api = new Endpoints();
            $path = rtrim($path, '/') ?: '/';
            $data = match (true) {
                $path === '/' => $api->index(),
                $path === '/trails' => $api->trails($_GET),
                (bool) preg_match('#^/trails/(\d+)$#', $path, $m) => $api->trail((int) $m[1]),
                (bool) preg_match('#^/trails/(\d+)/conditions$#', $path, $m) => $api->conditions((int) $m[1], $_GET),
                $path === '/group-hikes' => $api->groupHikes(),
                $path === '/feed' => $api->feed($_GET),
                default => throw new ApiError(404, 'Unknown endpoint. See /api/v1 for the list.'),
            };
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } catch (ApiError $e) {
            http_response_code($e->status);
            echo json_encode(['error' => ['status' => $e->status, 'message' => $e->getMessage()]], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('[TrailSense API] ' . $e);
            http_response_code(500);
            echo json_encode(['error' => ['status' => 500, 'message' => 'Internal error.']]);
        }
    }
}
