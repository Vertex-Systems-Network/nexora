<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/browser-ux-contracts.php';
require_once $root.'/scripts/lib/performance-contracts.php';

$failures = [];

$browser = nexoraAnalyzeBrowserUxContracts($root);
foreach ((array) ($browser['errors'] ?? []) as $error) {
    $failures[] = 'Browser/accessibility: '.$error;
}

$performance = nexoraAnalyzePerformanceContracts($root);
foreach ((array) ($performance['errors'] ?? []) as $error) {
    $failures[] = 'Performance/packaging: '.$error;
}

$read = static fn (string $relative): string => is_file($root.'/'.$relative)
    ? (string) file_get_contents($root.'/'.$relative)
    : '';

$appEntry = $read('resources/js/app.tsx');
$buildVerifier = $read('scripts/performance-build-verify.php');
$accessibilityTest = $read('resources/js/admin/ui/untitled/accessibility.test.tsx');
$readiness = $read('scripts/development-readiness.php');
$c5Runner = $read('scripts/n1-c5-browser-performance-certify.php');
$c5Contracts = $read('scripts/lib/n1-c5-contracts.php');
$packageRaw = $read('package.json');
$package = json_decode($packageRaw, true);
$config = is_file($root.'/config/nexora-performance.php') ? require $root.'/config/nexora-performance.php' : [];
$budgets = is_array($config) ? (array) ($config['budgets'] ?? []) : [];

$require = static function (string $source, string $needle, string $message) use (&$failures): void {
    if (! str_contains($source, $needle)) $failures[] = $message;
};
$forbid = static function (string $source, string $needle, string $message) use (&$failures): void {
    if (str_contains($source, $needle)) $failures[] = $message;
};

// First-load performance must remain route-split and bounded.
$require($appEntry, 'path: "./admin/pages"', 'Inertia Admin page root must remain explicit.');
$require($appEntry, 'lazy: true', 'Inertia Admin pages must remain lazily resolved.');
$forbid($appEntry, 'lazy: false', 'Inertia Admin pages must not be switched to eager resolution.');
$forbid($appEntry, 'eager: true', 'Admin page imports must not be eagerly globbed.');
if ((int) ($budgets['initial_javascript_gzip_bytes'] ?? 0) <= 0) {
    $failures[] = 'Initial JavaScript gzip budget must be configured and positive.';
}
foreach (['initial_js_gzip', 'initial_javascript_gzip_bytes', 'initial_javascript_assets', "['imports']"] as $needle) {
    $require($buildVerifier, $needle, 'Production build verifier must enforce static first-load JS graph marker: '.$needle);
}
$forbid($buildVerifier, "['dynamicImports']", 'First-load JS graph must not count dynamic route imports as eagerly loaded assets.');

// Shared modal semantics must be backed by an executable focus-containment regression.
$require($accessibilityTest, 'traps tab focus inside an open modal', 'Modal focus containment regression test is missing.');
$require($accessibilityTest, 'expect(first).toHaveFocus()', 'Modal focus containment regression must assert Tab wraps inside the dialog.');

// Development target QA must actually execute the frontend regression suite and
// validate the production artifact that was just built.
foreach ([
    "'vitest', 'Frontend Vitest suite'",
    "['npm', 'run', 'test']",
    "'build_assets', 'Production asset budgets and provenance'",
    "[PHP_BINARY, 'scripts/performance-build-verify.php']",
] as $needle) {
    $require($readiness, $needle, 'Development readiness missing executable N1.26 QA marker: '.$needle);
}

// N1.26 source closure must retain the real C5 target boundary rather than claiming
// Lighthouse/Web Vitals/accessibility performance from static analysis alone.
foreach (['--base-url=', 'http-performance', 'browser-evidence', 'web-vitals', 'build-assets', 'c5-evidence'] as $needle) {
    $require($c5Runner, $needle, 'C5 target certification runner missing evidence boundary: '.$needle);
}
foreach (['browser-source', 'performance-source', 'security-source'] as $needle) {
    $require($c5Runner, $needle, 'C5 runner must retain ordered source gate: '.$needle);
}
foreach (['browser_evidence_sha256', 'web_vitals_evidence_sha256', 'http_performance_sha256', 'build_assets_sha256'] as $needle) {
    $require($c5Contracts, $needle, 'C5 contract must bind target evidence artifact: '.$needle);
}

if (! is_array($package)) {
    $failures[] = 'package.json must decode to an object.';
} else {
    foreach ([
        'verify:build' => 'php scripts/performance-build-verify.php',
        'certify:n1-c5' => 'php scripts/n1-c5-browser-performance-certify.php',
    ] as $script => $expected) {
        if (($package['scripts'][$script] ?? null) !== $expected) {
            $failures[] = "package.json script [{$script}] must remain [{$expected}].";
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Nexora Performance + Accessibility + Release Product Contract: FAIL\n");
    foreach (array_values(array_unique($failures)) as $failure) {
        fwrite(STDERR, ' - '.$failure."\n");
    }
    exit(1);
}

fwrite(STDOUT, "Nexora Performance + Accessibility + Release Product Contract: PASS\n");
fwrite(STDOUT, " - Admin pages remain lazy-route split and first-load JS is separately budgeted\n");
fwrite(STDOUT, " - shared dialog focus containment is source-guarded and regression-tested\n");
fwrite(STDOUT, " - development target QA executes Vitest plus production asset-budget verification\n");
fwrite(STDOUT, " - real browser, assistive-technology, HTTP and Web Vitals evidence remains a target C5 requirement\n");
