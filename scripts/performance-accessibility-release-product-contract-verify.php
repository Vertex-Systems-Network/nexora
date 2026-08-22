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
$standardsRunner = $read('scripts/n1-c5-web-standards-certify.php');
$standardsVerifier = $read('scripts/n1-c5-web-standards-evidence-verify.php');
$accessibilityPlan = $read('NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md');
$packageRaw = $read('package.json');
$package = json_decode($packageRaw, true);
$config = is_file($root.'/config/nexora-performance.php') ? require $root.'/config/nexora-performance.php' : [];
$budgets = is_array($config) ? (array) ($config['budgets'] ?? []) : [];
$browserConfig = is_file($root.'/config/nexora-browser-certification.php') ? require $root.'/config/nexora-browser-certification.php' : [];

$require = static function (string $source, string $needle, string $message) use (&$failures): void {
    if (! str_contains($source, $needle)) $failures[] = $message;
};
$forbid = static function (string $source, string $needle, string $message) use (&$failures): void {
    if (str_contains($source, $needle)) $failures[] = $message;
};

// The live C5 scripts are not executed by PR QA because they require a reachable
// target/WAVE credential. They must still parse on the certified PHP runtime.
foreach ([
    'scripts/n1-c5-web-standards-certify.php',
    'scripts/n1-c5-web-standards-evidence-verify.php',
    'scripts/n1-c5-browser-performance-certify.php',
    'scripts/n1-c5-evidence-verify.php',
] as $script) {
    $file = $root.'/'.$script;
    if (! is_file($file)) {
        $failures[] = "C5 executable source missing [{$script}].";
        continue;
    }
    $output = [];
    $exit = 1;
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $output, $exit);
    if ($exit !== 0) {
        $failures[] = "C5 executable source has PHP syntax errors [{$script}]: ".implode(' ', $output);
    }
}

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
// browser/WCAG/WAVE/Web Vitals success from static analysis alone.
foreach (['--base-url=', 'http-performance', 'web-standards', 'web-standards-evidence', 'browser-evidence', 'web-vitals', 'build-assets', 'c5-evidence'] as $needle) {
    $require($c5Runner, $needle, 'C5 target certification runner missing evidence boundary: '.$needle);
}
foreach (['browser-source', 'performance-source', 'security-source'] as $needle) {
    $require($c5Runner, $needle, 'C5 runner must retain ordered source gate: '.$needle);
}
foreach (['browser_evidence_sha256', 'web_vitals_evidence_sha256', 'web_standards_evidence_sha256', 'http_performance_sha256', 'build_assets_sha256'] as $needle) {
    $require($c5Contracts, $needle, 'C5 contract must bind target evidence artifact: '.$needle);
}

// Standards tooling must be real, fail-closed, secret-safe and explicit that WAVE is
// an evaluation aid rather than an accessibility approval.
foreach ([
    'validator.w3.org/nu/',
    'jigsaw.w3.org/css-validator/validator',
    "'output' => 'soap12'",
    "'profile' => 'css3'",
    'wave.webaim.org/api/request',
    'WAVE_API_KEY',
    '--wave-alerts-reviewed',
    '--wave-no-key',
    'Shared wave.webaim.org API always requires an API key',
    "'authentication' => \$waveNoKey ? 'standalone-no-key' : 'environment-key'",
    'web-standards-evidence.json',
    'not_an_accessibility_approval',
] as $needle) {
    $require($standardsRunner, $needle, 'W3C/WAVE target runner missing required marker: '.$needle);
}
foreach ([
    'zero conformance errors',
    'zero validation errors',
    'zero errors',
    'zero contrast errors',
    'alerts must be human-reviewed',
    'authentication must be environment-key or standalone-no-key',
    'Shared wave.webaim.org evidence cannot use standalone-no-key authentication',
    'not_an_accessibility_approval',
] as $needle) {
    $require($standardsVerifier, $needle, 'W3C/WAVE evidence verifier missing fail-closed marker: '.$needle);
}
foreach (['W3C Nu', 'W3C CSS', 'WAVE', 'WCAG 2.2', 'Never weaken a W3C/WAVE/browser gate', '--wave-no-key', 'Never use `--wave-no-key` with the shared WAVE API'] as $needle) {
    $require($accessibilityPlan, $needle, 'Accessibility certification plan missing AI/operator rule: '.$needle);
}
$require($c5Runner, "if (\$waveNoKey) \$standards[] = '--wave-no-key';", 'C5 parent runner must forward explicit WAVE stand-alone no-key mode.');

$standardsConfig = (array) ($browserConfig['standards'] ?? []);
foreach (['/', '/login'] as $route) {
    if (! in_array($route, (array) ($standardsConfig['routes'] ?? []), true)) $failures[] = "W3C/WAVE required route [{$route}] must remain configured.";
}
if ((int) ($standardsConfig['w3c']['max_errors'] ?? -1) !== 0) $failures[] = 'W3C HTML gate must remain zero-error.';
if (($standardsConfig['w3c_css']['profile'] ?? null) !== 'css3' || (int) ($standardsConfig['w3c_css']['max_errors'] ?? -1) !== 0) {
    $failures[] = 'W3C CSS gate must remain CSS3 zero-error.';
}
if ((int) ($standardsConfig['wave']['max_errors'] ?? -1) !== 0 || (int) ($standardsConfig['wave']['max_contrast_errors'] ?? -1) !== 0 || ($standardsConfig['wave']['require_alert_review'] ?? null) !== true) {
    $failures[] = 'WAVE gate must remain zero errors/contrast errors plus human alert review.';
}

if (! is_array($package)) {
    $failures[] = 'package.json must decode to an object.';
} else {
    foreach ([
        'verify:build' => 'php scripts/performance-build-verify.php',
        'certify:n1-c5' => 'php scripts/n1-c5-browser-performance-certify.php',
        'certify:web-standards' => 'php scripts/n1-c5-web-standards-certify.php',
        'verify:web-standards-evidence' => 'php scripts/n1-c5-web-standards-evidence-verify.php',
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
fwrite(STDOUT, " - live C5 PHP runners are syntax-checked without faking target execution\n");
fwrite(STDOUT, " - shared WAVE keeps API-key auth; explicit custom stand-alone endpoints may opt into no-key mode\n");
fwrite(STDOUT, " - development target QA executes Vitest plus production asset-budget verification\n");
fwrite(STDOUT, " - final C5 requires W3C HTML+CSS zero-error validation, WAVE zero-error/contrast review, real browser/AT, HTTP and Web Vitals target evidence\n");
