<?php
declare(strict_types=1);

/**
 * Minimal test runner (ADR-007). Usage: php tests/run.php [unit|integration|http]
 * Each test file returns an array: 'test name' => function () { ... }.
 */
putenv('TS_ENV=test');
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/support.php';

$suites = $argv[1] ?? 'all';
$dirs = $suites === 'all' ? ['Unit', 'Integration', 'Http'] : [ucfirst($suites)];

$passed = 0;
$failed = [];
$runStart = microtime(true);

foreach ($dirs as $dir) {
    $files = glob(__DIR__ . "/$dir/*Test.php") ?: [];
    sort($files);
    echo "\n== $dir ==\n";
    foreach ($files as $file) {
        $tests = (static fn (string $f) => require $f)($file);
        $suiteName = basename($file, '.php');
        if (isset($tests['__before'])) {
            ($tests['__before'])();
            unset($tests['__before']);
        }
        $after = $tests['__after'] ?? null;
        unset($tests['__after']);
        foreach ($tests as $name => $fn) {
            try {
                $fn();
                $passed++;
                echo "  ✔ $suiteName › $name\n";
            } catch (Throwable $e) {
                $failed[] = "$suiteName › $name: " . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
                echo "  ✘ $suiteName › $name\n";
            }
        }
        if ($after) {
            $after();
        }
    }
}

printf("\nPASSED: %d  FAILED: %d  (%.2fs)\n", $passed, count($failed), microtime(true) - $runStart);
foreach ($failed as $f) {
    echo "  - $f\n";
}
exit($failed === [] ? 0 : 1);
