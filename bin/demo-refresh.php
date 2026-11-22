<?php
declare(strict_types=1);
// Moves past, never-hiked plans of the four demo accounts forward by whole weeks (same SQL as migration 006).
// Usage before a demo: php bin/demo-refresh.php   (real users' data is never touched)
require __DIR__ . '/../src/bootstrap.php';
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
$pdo = TrailSense\Core\Database::connect(config('db'));
$pdo->beginTransaction();
$pdo->exec((string) file_get_contents(TS_ROOT . '/database/migrations/006_refresh_demo_dates.sql'));
$pdo->commit();
echo "Demo plans moved to upcoming dates.\n";
