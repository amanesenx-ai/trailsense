<?php
declare(strict_types=1);

// Runs the JavaScript navigation tests (AC13.10) with Node, so `php tests/run.php` stays the single command.
return [
    'hike navigation maths pass their Node tests (AC13.10)' => function () {
        $node = trim((string) shell_exec('command -v node 2>/dev/null'));
        if ($node === '') {
            echo "    (node not installed — JS tests skipped)\n";
            return;
        }
        exec(escapeshellarg($node) . ' ' . escapeshellarg(__DIR__ . '/../js/hike-core.test.js') . ' 2>&1', $out, $code);
        assertSame(0, $code, implode("\n", $out));
        assertTrue(count(array_filter($out, fn ($l) => str_starts_with($l, 'ok '))) >= 6);
    },
];
