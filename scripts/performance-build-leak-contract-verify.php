<?php

declare(strict_types=1);

require_once __DIR__.'/lib/performance-build-leaks.php';

$cases = [
    [
        'name' => 'admin users route remains valid application source',
        'source' => 'const module = "./admin/pages/Admin/Users/Form.tsx";',
        'expected' => [],
    ],
    [
        'name' => 'nested users route is not a macOS home path',
        'source' => 'import("../features/Users/Profile.tsx")',
        'expected' => [],
    ],
    [
        'name' => 'absolute macOS developer home path is rejected',
        'source' => 'const source = "/Users/alice/projects/nexora/app.ts";',
        'expected' => ['/Users/<user>/'],
    ],
    [
        'name' => 'file URL macOS developer home path is rejected',
        'source' => 'const source = "file:///Users/alice/projects/nexora/app.ts";',
        'expected' => ['/Users/<user>/'],
    ],
    [
        'name' => 'vite localhost development endpoint is rejected',
        'source' => 'const source = "http://localhost:5173/resources/js/app.tsx";',
        'expected' => ['localhost:5173'],
    ],
    [
        'name' => 'vite loopback development endpoint is rejected',
        'source' => 'const source = "http://127.0.0.1:5173/resources/js/app.tsx";',
        'expected' => ['127.0.0.1:5173'],
    ],
    [
        'name' => 'laragon development path is rejected',
        'source' => 'const source = "D:\\laragon\\www\\nexora\\resources\\js\\app.tsx";',
        'expected' => ['D:\\laragon\\'],
    ],
];

$failures = [];
foreach ($cases as $case) {
    $actual = nexoraPerformanceBuildLocalLeaks($case['source']);
    if ($actual !== $case['expected']) {
        $failures[] = sprintf(
            '%s: expected %s, got %s',
            $case['name'],
            json_encode($case['expected'], JSON_UNESCAPED_SLASHES),
            json_encode($actual, JSON_UNESCAPED_SLASHES),
        );
    }
}

if ($failures !== []) {
    fwrite(STDERR, "[Nexora Build Leak Contract] FAILED\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Build Leak Contract] PASS — route Users segments remain valid while real local development paths are rejected.\n");
