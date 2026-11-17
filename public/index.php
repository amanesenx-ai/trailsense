<?php
declare(strict_types=1);

// Front controller: the only PHP entry point (ARCHITECTURE.md).
require __DIR__ . '/../src/bootstrap.php';

use TrailSense\Core\HttpException;
use TrailSense\Core\View;

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off'),
]);
session_name('trailsense');
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$routes = require __DIR__ . '/../src/routes.php';
$name = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'home';

try {
    if (!isset($routes[$name])) {
        throw HttpException::notFound();
    }
    [$method, $controller, $action] = $routes[$name];
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        throw new HttpException('Method not allowed', 405);
    }
    (new $controller())->$action();
} catch (HttpException $e) {
    View::render('errors/error', ['title' => 'Error', 'code' => $e->getCode(), 'message' => $e->getMessage()], $e->getCode());
} catch (Throwable $e) {
    error_log('[TrailSense] ' . $e);
    View::render('errors/error', [
        'title' => 'Something went wrong',
        'code' => 500,
        'message' => config('debug') ? $e->getMessage() : 'Something went wrong on our side. Please try again.',
    ], 500);
}
