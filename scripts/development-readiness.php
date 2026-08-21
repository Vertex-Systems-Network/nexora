<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/target-composer.php';

$json = in_array('--json', $argv, true);
$full = in_array('--full', $argv, true);
$environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);

$checks = [];
$run = static function (string $id, string $label, array $command, bool $required = true) use (&$checks, $root, $environment): array {
    $result = nexoraRunTargetCommand($command, $root, $environment);
    $checks[$id] = [
        'label' => $label,
        'status' => $result['exit_code'] === 0 ? 'pass' : ($required ? 'fail' : 'warning'),
        'exit_code' => $result['exit_code'],
        'detail' => trim($result['stdout'] !== '' ? $result['stdout'] : $result['stderr']),
    ];
    return $result;
};

$checks['php'] = [
    'label' => 'PHP runtime',
    'status' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 'pass' : 'fail',
    'exit_code' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1,
    'detail' => PHP_VERSION,
];

$composer = nexoraLocateTargetComposer($root);
$checks['composer'] = [
    'label' => 'Composer',
    'status' => ($composer['available'] ?? false) ? 'pass' : 'fail',
    'exit_code' => ($composer['available'] ?? false) ? 0 : 1,
    'detail' => trim(((string) ($composer['version'] ?? 'unavailable')).' '.((string) ($composer['source'] ?? ''))),
];
$run('node', 'Node.js', ['node', '--version']);
$run('npm', 'npm', ['npm', '--version']);
$run('post_install_runtime_contract', 'Post-install runtime convergence contract', [PHP_BINARY, 'scripts/post-install-runtime-convergence-contract-verify.php']);
$run('dev4_core_contract', 'DEV-4 core functional source contract', [PHP_BINARY, 'scripts/dev4-core-functional-contract-verify.php']);
$run('theme_product_contract', 'Theme product source contract', [PHP_BINARY, 'scripts/theme-product-contract-verify.php']);
$run('extension_product_contract', 'Extension product source contract', [PHP_BINARY, 'scripts/extension-product-contract-verify.php']);
$run('studio_product_contract', 'Studio product source contract', [PHP_BINARY, 'scripts/studio-product-contract-verify.php']);
$run('document_product_contract', 'Document product source contract', [PHP_BINARY, 'scripts/document-product-contract-verify.php']);
$run('collection_product_contract', 'Content collection product source contract', [PHP_BINARY, 'scripts/collection-product-contract-verify.php']);
$run('publishing_seo_product_contract', 'Publishing + SEO product source contract', [PHP_BINARY, 'scripts/publishing-seo-product-contract-verify.php']);
$run('admin_ux_product_contract', 'Admin UX product source contract', [PHP_BINARY, 'scripts/admin-ux-product-contract-verify.php']);
$run('forms_workflow_product_contract', 'Forms + Data + Workflows product source contract', [PHP_BINARY, 'scripts/forms-workflow-product-contract-verify.php']);

$vendorReady = is_file($root.'/vendor/autoload.php');
$nodeReady = is_dir($root.'/node_modules') && (is_file($root.'/node_modules/typescript/bin/tsc') || is_file($root.'/node_modules/.bin/tsc'));
$checks['vendor'] = [
    'label' => 'Composer dependencies',
    'status' => $vendorReady ? 'pass' : 'warning',
    'exit_code' => $vendorReady ? 0 : 2,
    'detail' => $vendorReady ? 'vendor/autoload.php present' : 'Run composer install before backend execution checks.',
];
$checks['node_modules'] = [
    'label' => 'Frontend dependencies',
    'status' => $nodeReady ? 'pass' : 'warning',
    'exit_code' => $nodeReady ? 0 : 2,
    'detail' => $nodeReady ? 'node_modules TypeScript toolchain present' : 'Run npm install before TypeScript/Vite checks.',
];

if ($full && $vendorReady) {
    $run('artisan_about', 'Laravel bootstrap', [PHP_BINARY, 'artisan', 'about', '--only=environment']);
    $run('routes', 'Route registration', [PHP_BINARY, 'artisan', 'route:list', '--json']);
}
if ($full && $nodeReady) {
    $run('typescript', 'TypeScript noEmit', ['npm', 'exec', '--', 'tsc', '--noEmit']);
    if (($checks['typescript']['status'] ?? 'fail') === 'pass') {
        $run('vite', 'Production frontend build', ['npm', 'run', 'build:raw']);
    }
}

$requiredFailures = array_filter($checks, static fn (array $check): bool => $check['status'] === 'fail');
$warnings = array_filter($checks, static fn (array $check): bool => $check['status'] === 'warning');
$status = $requiredFailures === [] ? ($warnings === [] ? 'ready' : 'dependencies-needed') : 'blocked';

$payload = [
    'schema' => 1,
    'mode' => 'development-readiness',
    'platform_version' => (string) ((require $root.'/config/nexora.php')['version'] ?? 'unknown'),
    'status' => $status,
    'audit_required_for_this_check' => false,
    'checks' => $checks,
];

if ($json) {
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    exit($requiredFailures === [] ? 0 : 1);
}

fwrite(STDOUT, "Nexora Development Readiness - {$status}\n");
foreach ($checks as $check) {
    $mark = $check['status'] === 'pass' ? 'PASS' : strtoupper($check['status']);
    fwrite(STDOUT, sprintf("%-8s %-28s %s\n", $mark, $check['label'], str_replace(["\r", "\n"], ' ', (string) $check['detail'])));
}
fwrite(STDOUT, "\nThis command does not promote dependency locks or grant release certification.\n");
exit($requiredFailures === [] ? 0 : 1);
