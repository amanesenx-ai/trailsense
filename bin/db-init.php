<?php
declare(strict_types=1);
// Runs at container start (docker/entrypoint.sh): loads schema + seed ONLY if the database is empty.
// Never wipes existing data. Usage: php bin/db-init.php
require __DIR__ . '/../src/bootstrap.php';
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
for ($i = 1; ; $i++) {
    try {
        $pdo = TrailSense\Core\Database::connect(config('db'));
        break;
    } catch (PDOException $e) {
        if ($i >= 5) { fwrite(STDERR, "db-init: cannot connect: {$e->getMessage()}\n"); exit(1); }
        sleep(3);   // serverless Postgres may need a few seconds to wake up
    }
}
$exists = $pdo->query("SELECT to_regclass('app_user') IS NOT NULL")->fetchColumn();
if ($exists) {
    echo "db-init: tables already exist, nothing to do.\n";
    exit(0);
}
$pdo->exec((string) file_get_contents(TS_ROOT . '/database/schema.sql'));
$pdo->exec((string) file_get_contents(TS_ROOT . '/database/seed.sql'));
echo "db-init: schema and seed loaded.\n";
