<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/target-composer.php';
require_once $root.'/scripts/lib/source-attestation.php';

$json = in_array('--json', $argv, true);
$full = in_array('--full', $argv, true);
$tests = in_array('--tests', $argv, true);
$evidence = in_array('--evidence', $argv, true);
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
$run('data_connection_product_contract', 'Data Connections product source contract', [PHP_BINARY, 'scripts/data-connection-product-contract-verify.php']);
$run('primary_sql_portability_contract', 'Primary SQL portability source contract', [PHP_BINARY, 'scripts/primary-sql-portability-contract-verify.php']);
$run('installer_database_ux_contract', 'Installer database UX source contract', [PHP_BINARY, 'scripts/installer-database-ux-contract-verify.php']);
$run('development_target_qa_contract', 'Development target QA source contract', [PHP_BINARY, 'scripts/development-target-qa-contract-verify.php']);
$run('marketplace_product_contract', 'Marketplace product source contract', [PHP_BINARY, 'scripts/marketplace-product-contract-verify.php']);
$run('commerce_product_contract', 'Commerce product source contract', [PHP_BINARY, 'scripts/commerce-product-contract-verify.php']);
$run('customer_portal_product_contract', 'Customer Portal product source contract', [PHP_BINARY, 'scripts/customer-portal-product-contract-verify.php']);
$run('crm_membership_product_contract', 'CRM + Membership product source contract', [PHP_BINARY, 'scripts/crm-membership-product-contract-verify.php']);
$run('search_product_contract', 'Search 2.0 product source contract', [PHP_BINARY, 'scripts/search-product-contract-verify.php']);
$run('collaboration_product_contract', 'Collaboration product source contract', [PHP_BINARY, 'scripts/collaboration-product-contract-verify.php']);
$run('automation_product_contract', 'Automation product source contract', [PHP_BINARY, 'scripts/automation-product-contract-verify.php']);
$run('ai_platform_product_contract', 'AI Platform product source contract', [PHP_BINARY, 'scripts/ai-platform-product-contract-verify.php']);
$run('multisite_organizations_product_contract', 'Multisite / Organizations product source contract', [PHP_BINARY, 'scripts/multisite-organizations-product-contract-verify.php']);
$run('enterprise_governance_product_contract', 'SSO / Enterprise Governance product source contract', [PHP_BINARY, 'scripts/enterprise-governance-product-contract-verify.php']);
$run('public_api_sdk_product_contract', 'Public API / SDK product source contract', [PHP_BINARY, 'scripts/public-api-sdk-product-contract-verify.php']);
$run('content_migration_product_contract', 'Content Migration product source contract', [PHP_BINARY, 'scripts/content-migration-product-contract-verify.php']);

$vendorReady = is_file($root.'/vendor/autoload.php');
$nodeReady = is_dir($root.'/node_modules') && (is_file($root.'/node_modules/typescript/bin/tsc') || is_file($root.'/node_modules/.bin/tsc'));
$checks['vendor'] = [
    'label' => 'Composer dependencies',
    'status' => $vendorReady ? 'pass' : ($tests ? 'fail' : 'warning'),
    'exit_code' => $vendorReady ? 0 : ($tests ? 1 : 2),
    'detail' => $vendorReady ? 'vendor/autoload.php present' : 'Run composer install before backend execution checks.',
];
$checks['node_modules'] = [
    'label' => 'Frontend dependencies',
    'status' => $nodeReady ? 'pass' : ($full ? 'fail' : 'warning'),
    'exit_code' => $nodeReady ? 0 : ($full ? 1 : 2),
    'detail' => $nodeReady ? 'node_modules TypeScript toolchain present' : 'Run npm install before TypeScript/Vite checks.',
];

if ($full && $vendorReady) {
    $run('artisan_about', 'Laravel bootstrap', [PHP_BINARY, 'artisan', 'about', '--only=environment']);
    $run('routes', 'Route registration', [PHP_BINARY, 'artisan', 'route:list', '--json']);
}
if ($tests && $vendorReady) {
    $run('phpunit', 'Full Laravel/PHPUnit suite', [PHP_BINARY, 'artisan', 'test', '--colors=never']);
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
$platform = require $root.'/config/nexora.php';

$payload = [
    'schema' => 2,
    'mode' => 'development-readiness',
    'platform_version' => (string) ($platform['version'] ?? 'unknown'),
    'status' => $status,
    'full_requested' => $full,
    'tests_requested' => $tests,
    'audit_required_for_this_check' => false,
    'checks' => $checks,
];

if ($evidence) {
    $attestation = nexoraComputeSourceAttestation($root);
    $evidenceDir = $root.'/storage/app/nexora/qa';
    if (! is_dir($evidenceDir) && ! mkdir($evidenceDir, 0775, true) && ! is_dir($evidenceDir)) {
        fwrite(STDERR, "[Nexora Development Readiness] Unable to create QA evidence directory.\n");
        exit(1);
    }

    $evidenceChecks = [];
    foreach ($checks as $id => $check) {
        $evidenceChecks[$id] = [
            'label' => $check['label'],
            'status' => $check['status'],
            'exit_code' => $check['exit_code'],
        ];
    }

    $evidencePayload = [
        'schema' => 1,
        'scope' => 'development-target-functional-qa',
        'status' => $status,
        'platform_version' => (string) ($platform['version'] ?? 'unknown'),
        'source_tree_sha256' => $attestation['tree_sha256'] ?? null,
        'source_file_count' => $attestation['file_count'] ?? null,
        'php_version' => PHP_VERSION,
        'os_family' => PHP_OS_FAMILY,
        'generated_at' => gmdate(DATE_ATOM),
        'full_requested' => $full,
        'tests_requested' => $tests,
        'checks' => $evidenceChecks,
        'note' => 'This development evidence does not promote dependency locks or grant final C1-C6 release certification.',
    ];

    $evidencePath = $evidenceDir.'/development-readiness.json';
    $encoded = json_encode($evidencePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    if (file_put_contents($evidencePath, $encoded, LOCK_EX) === false) {
        fwrite(STDERR, "[Nexora Development Readiness] Unable to write QA evidence directory.\n");
        exit(1);
    }
    $payload['evidence_path'] = 'storage/app/nexora/qa/development-readiness.json';
}

if ($json) {
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    exit($requiredFailures === [] ? 0 : 1);
}

fwrite(STDOUT, "Nexora Development Readiness - {$status}\n");
foreach ($checks as $check) {
    $mark = $check['status'] === 'pass' ? 'PASS' : strtoupper($check['status']);
    fwrite(STDOUT, sprintf("%-8s %-28s %s\n", $mark, str_replace(["\r", "\n"], ' ', (string) $check['label']), str_replace(["\r", "\n"], ' ', (string) $check['detail'])));
}
if (isset($payload['evidence_path'])) {
    fwrite(STDOUT, "\nEvidence: {$payload['evidence_path']}\n");
}
fwrite(STDOUT, "\nThis command does not promote dependency locks or grant release certification.\n");
exit($requiredFailures === [] ? 0 : 1);