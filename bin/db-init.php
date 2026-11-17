<?php
declare(strict_types=1);
// Loads schema + seed ONLY if the database is empty (never wipes data). Usage: php bin/db-init.php
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
@unlink(TrailSense\Core\Installer::markerFile());
echo TrailSense\Core\Installer::ensureInstalled($pdo) ? "db-init: schema and seed loaded.\n" : "db-init: tables already exist, nothing to do.\n";
