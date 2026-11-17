<?php
declare(strict_types=1);
// Recreates the database from database/schema.sql + database/seed.sql. Usage: php bin/db-reset.php
require __DIR__ . '/../src/bootstrap.php';
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
$pdo = TrailSense\Core\Database::connect(config('db'));
$pdo->exec((string) file_get_contents(TS_ROOT . '/database/schema.sql'));
$pdo->exec((string) file_get_contents(TS_ROOT . '/database/seed.sql'));
echo "Database reset: schema + seed loaded.\n";
