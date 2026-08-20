<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root.'/bootstrap/nexora-runtime-bootstrap.php';

$json = in_array('--json', $argv, true);
$sourceOnly = in_array('--source-only', $argv, true);
$failures = [];
$warnings = [];
$checks = [];

$record = static function (string $name, bool $ok, string $message = '') use (&$checks, &$failures): void {
    $checks[] = ['name' => $name, 'ok' => $ok, 'message' => $message];
    if (! $ok) $failures[] = $name.($message !== '' ? ': '.$message : '');
};

require_once $root.'/scripts/lib/module-graph.php';

$moduleGraph = nexoraAnalyzeModuleGraph($root);
$record('modules.graph', $moduleGraph['ok'], $moduleGraph['ok'] ? count($moduleGraph['modules']).' modules; boot order resolved' : implode('; ', $moduleGraph['errors']));

require_once $root.'/scripts/lib/laravel-runtime-contracts.php';
$runtimeContracts = nexoraAnalyzeLaravelRuntimeContracts($root);
$record('laravel.runtime-contracts', $runtimeContracts['ok'], $runtimeContracts['ok'] ? 'middleware/routes/scheduler/jobs/providers source contracts resolved' : implode('; ', $runtimeContracts['errors']));

require_once $root.'/scripts/lib/database-contracts.php';
$databaseContracts = nexoraAnalyzeDatabaseContracts($root);
$record('database.contracts', $databaseContracts['errors'] === [], $databaseContracts['errors'] === [] ? $databaseContracts['metrics']['migrations'].' migrations; '.$databaseContracts['metrics']['tables'].' tables; tenant manifest aligned' : implode('; ', $databaseContracts['errors']));
foreach ($databaseContracts['warnings'] as $warning) $warnings[] = 'database.contracts: '.$warning;


require_once $root.'/scripts/lib/browser-ux-contracts.php';
$browserUxContracts = nexoraAnalyzeBrowserUxContracts($root);
$record('browser-ux.contracts', $browserUxContracts['ok'], $browserUxContracts['ok'] ? $browserUxContracts['metrics']['admin_files'].' Admin files; a11y/RTL/browser source contracts aligned' : implode('; ', $browserUxContracts['errors']));
foreach ($browserUxContracts['warnings'] as $warning) $warnings[] = 'browser-ux.contracts: '.$warning;

require_once $root.'/scripts/lib/inertia-frontend-contracts.php';
$inertiaFrontendContracts = nexoraAnalyzeInertiaFrontendContracts($root);
$record('inertia-frontend.contracts', $inertiaFrontendContracts['ok'], $inertiaFrontendContracts['ok'] ? $inertiaFrontendContracts['metrics']['laragon_error_files'].' Laragon build-error files; Inertia form/router/NavLink contracts aligned' : implode('; ', $inertiaFrontendContracts['errors']));
foreach ($inertiaFrontendContracts['warnings'] as $warning) $warnings[] = 'inertia-frontend.contracts: '.$warning;

require_once $root.'/scripts/lib/performance-contracts.php';
$performanceContracts = nexoraAnalyzePerformanceContracts($root);
$record('performance.contracts', $performanceContracts['ok'], $performanceContracts['ok'] ? $performanceContracts['metrics']['static_public_assets'].' static assets; cache/header/build/release policy aligned' : implode('; ', $performanceContracts['errors']));
foreach ($performanceContracts['warnings'] as $warning) $warnings[] = 'performance.contracts: '.$warning;

require_once $root.'/scripts/lib/final-closure-contracts.php';
$finalClosureContracts = nexoraAnalyzeFinalClosureContracts($root);
$record('final-closure.contracts', $finalClosureContracts['errors'] === [], $finalClosureContracts['errors'] === [] ? $finalClosureContracts['metrics']['closure_domains'].' domains; target closure harness aligned' : implode('; ', $finalClosureContracts['errors']));

require_once $root.'/scripts/lib/target-diagnostics-contracts.php';
$targetDiagnosticsContracts = nexoraAnalyzeTargetDiagnosticsContracts($root);
$record('target-diagnostics.contracts', $targetDiagnosticsContracts['errors'] === [], $targetDiagnosticsContracts['errors'] === [] ? $targetDiagnosticsContracts['metrics']['diagnostic_groups'].' diagnostic groups; safe capture harness aligned' : implode('; ', $targetDiagnosticsContracts['errors']));

require_once $root.'/scripts/lib/target-runtime-contracts.php';
$targetRuntimeContracts = nexoraAnalyzeTargetRuntimeContracts($root);
$record('target-runtime.contracts', $targetRuntimeContracts['errors'] === [], $targetRuntimeContracts['errors'] === [] ? $targetRuntimeContracts['metrics']['wrappers'].' wrappers; fail-fast target runtime gate aligned' : implode('; ', $targetRuntimeContracts['errors']));

require_once $root.'/scripts/lib/target-resume-contracts.php';
$targetResumeContracts = nexoraAnalyzeTargetResumeContracts($root);
$record('target-resume.contracts', $targetResumeContracts['errors'] === [], $targetResumeContracts['errors'] === [] ? $targetResumeContracts['metrics']['bootstrap_wrappers'].' bootstrap wrappers; resume/evidence boundary aligned' : implode('; ', $targetResumeContracts['errors']));

require_once $root.'/scripts/lib/target-intake-contracts.php';
$targetIntakeContracts = nexoraAnalyzeTargetIntakeContracts($root);
$record('target-intake.contracts', $targetIntakeContracts['errors'] === [], $targetIntakeContracts['errors'] === [] ? $targetIntakeContracts['metrics']['intake_wrappers'].' intake wrappers; reviewed-lock boundary aligned' : implode('; ', $targetIntakeContracts['errors']));

require_once $root.'/scripts/lib/target-remediation-contracts.php';
$targetRemediationContracts = nexoraAnalyzeTargetRemediationContracts($root);
$record('target-remediation.contracts', $targetRemediationContracts['errors'] === [], $targetRemediationContracts['errors'] === [] ? $targetRemediationContracts['metrics']['wrappers'].' wrappers; reversible Laragon prerequisite remediation aligned' : implode('; ', $targetRemediationContracts['errors']));

require_once $root.'/scripts/lib/n1-c1-contracts.php';
$c1Contracts=nexoraAnalyzeN10C1Contracts($root);
$record('n1-c1.contracts', $c1Contracts['errors'] === [], $c1Contracts['errors'] === [] ? $c1Contracts['metrics']['ordered_gates'].' ordered dependency/build gates aligned' : implode('; ', $c1Contracts['errors']));
foreach ($c1Contracts['warnings'] as $warning) $warnings[] = 'n1-c1.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-c2-contracts.php';
$c2Contracts=nexoraAnalyzeN10C2Contracts($root);
$record('n1-c2.contracts', $c2Contracts['errors'] === [], $c2Contracts['errors'] === [] ? $c2Contracts['metrics']['ordered_gates'].' ordered Laravel/runtime/core-DB gates aligned' : implode('; ', $c2Contracts['errors']));
foreach ($c2Contracts['warnings'] as $warning) $warnings[] = 'n1-c2.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-c3-contracts.php';
$c3Contracts=nexoraAnalyzeN10C3Contracts($root);
$record('n1-c3.contracts', $c3Contracts['errors'] === [], $c3Contracts['errors'] === [] ? $c3Contracts['metrics']['required_database_families'].' strict database families; C3 matrix aligned' : implode('; ', $c3Contracts['errors']));
foreach ($c3Contracts['warnings'] as $warning) $warnings[] = 'n1-c3.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-c4-contracts.php';
$c4Contracts=nexoraAnalyzeN10C4Contracts($root);
$record('n1-c4.contracts', $c4Contracts['errors'] === [], $c4Contracts['errors'] === [] ? $c4Contracts['metrics']['operator_domains'].' operational recovery domains; C4 aligned' : implode('; ', $c4Contracts['errors']));
foreach ($c4Contracts['warnings'] as $warning) $warnings[] = 'n1-c4.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-c5-contracts.php';
$c5Contracts=nexoraAnalyzeN10C5Contracts($root);
$record('n1-c5.contracts', $c5Contracts['errors'] === [], $c5Contracts['errors'] === [] ? $c5Contracts['metrics']['matrix_rows'].' browser matrix rows; C5 browser/performance aligned' : implode('; ', $c5Contracts['errors']));
foreach ($c5Contracts['warnings'] as $warning) $warnings[] = 'n1-c5.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-c6-contracts.php';
$c6Contracts=nexoraAnalyzeN10C6Contracts($root);
$record('n1-c6.contracts', $c6Contracts['errors'] === [], $c6Contracts['errors'] === [] ? $c6Contracts['metrics']['ordered_gates'].' final HA/release gates; C6 aligned' : implode('; ', $c6Contracts['errors']));
foreach ($c6Contracts['warnings'] as $warning) $warnings[] = 'n1-c6.contracts: '.$warning;

require_once $root.'/scripts/lib/target-evidence-contracts.php';
$targetEvidenceContracts = nexoraAnalyzeTargetEvidenceContracts($root);
$record('target-evidence.contracts', $targetEvidenceContracts['errors'] === [], $targetEvidenceContracts['errors'] === [] ? $targetEvidenceContracts['metrics']['known_evidence'].' evidence types; unified operator intake aligned' : implode('; ', $targetEvidenceContracts['errors']));
foreach ($targetEvidenceContracts['warnings'] as $warning) $warnings[] = 'target-evidence.contracts: '.$warning;

require_once $root.'/scripts/lib/target-orchestrator-contracts.php';
$targetOrchestratorContracts = nexoraAnalyzeTargetOrchestratorContracts($root);
$record('target-orchestrator.contracts', $targetOrchestratorContracts['errors'] === [], $targetOrchestratorContracts['errors'] === [] ? $targetOrchestratorContracts['metrics']['ordered_release_gates'].' ordered target gates; one-command operator flow aligned' : implode('; ', $targetOrchestratorContracts['errors']));
foreach ($targetOrchestratorContracts['warnings'] as $warning) $warnings[] = 'target-orchestrator.contracts: '.$warning;

require_once $root.'/scripts/lib/upgrade-contracts.php';
$upgradeContracts = nexoraAnalyzeUpgradeContracts($root);
$record('upgrade.contracts', $upgradeContracts['errors'] === [], $upgradeContracts['errors'] === [] ? $upgradeContracts['metrics']['commands'].' commands; backup/compatibility/rollback policy aligned' : implode('; ', $upgradeContracts['errors']));
foreach ($upgradeContracts['warnings'] as $warning) $warnings[] = 'upgrade.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-target-distributed-upgrade-contracts.php';
$distributedUpgradeContracts = nexoraAnalyzeDistributedUpgradeContracts($root);
$record('distributed-upgrade.contracts', $distributedUpgradeContracts['errors'] === [], $distributedUpgradeContracts['errors'] === [] ? 'global lease + migration ledger + explicit peer quiescence aligned' : implode('; ', $distributedUpgradeContracts['errors']));
foreach ($distributedUpgradeContracts['warnings'] as $warning) $warnings[] = 'distributed-upgrade.contracts: '.$warning;

require_once $root.'/scripts/lib/environment-contracts.php';
$environmentContracts = nexoraAnalyzeEnvironmentContracts($root);
$record('environment.contracts', $environmentContracts['errors'] === [], $environmentContracts['errors'] === [] ? $environmentContracts['metrics']['runtime_env_calls'].' runtime env() calls; config-cache/environment policy aligned' : implode('; ', $environmentContracts['errors']));
foreach ($environmentContracts['warnings'] as $warning) $warnings[] = 'environment.contracts: '.$warning;

require_once $root.'/scripts/lib/dependency-contracts.php';
$dependencyContracts = nexoraAnalyzeDependencyContracts($root, !$sourceOnly);
$record('dependency.contracts', $dependencyContracts['errors'] === [], $dependencyContracts['errors'] === [] ? 'lockfile/reproducibility policy aligned'.($dependencyContracts['metrics']['composer_lock']&&$dependencyContracts['metrics']['npm_lock']?' ; locks present':' ; locks pending') : implode('; ', $dependencyContracts['errors']));
foreach ($dependencyContracts['warnings'] as $warning) $warnings[] = 'dependency.contracts: '.$warning;

require_once $root.'/scripts/lib/filesystem-contracts.php';
$filesystemContracts = nexoraAnalyzeFilesystemContracts($root);
$record('filesystem.contracts', $filesystemContracts['ok'], $filesystemContracts['ok'] ? $filesystemContracts['metrics']['repository_entries'].' paths; max '.$filesystemContracts['metrics']['max_relative_path'].' chars; case/Windows portability aligned' : implode('; ', $filesystemContracts['errors']));
foreach ($filesystemContracts['warnings'] as $warning) $warnings[] = 'filesystem.contracts: '.$warning;

require_once $root.'/scripts/lib/transfer-contracts.php';
$transferContracts = nexoraAnalyzeTransferContracts($root);
$record('transfer.contracts', $transferContracts['ok'], $transferContracts['ok'] ? $transferContracts['metrics']['transfer_surfaces'].' transfer surfaces; bounded archive/backup streaming aligned' : implode('; ', $transferContracts['errors']));
foreach ($transferContracts['warnings'] as $warning) $warnings[] = 'transfer.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-target-resource-envelope-contracts.php';
$resourceEnvelopeContracts=nexoraAnalyzeResourceEnvelopeContracts($root);
$record('resource-envelope.contracts',$resourceEnvelopeContracts['errors']===[],$resourceEnvelopeContracts['errors']===[]?'live capacity/resource policy admission aligned':implode('; ',$resourceEnvelopeContracts['errors']));
foreach($resourceEnvelopeContracts['warnings'] as $warning)$warnings[]='resource-envelope.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-target-policy-plane-contracts.php';
$policyPlaneContracts=nexoraAnalyzePolicyPlaneContracts($root);
$record('policy-plane.contracts',$policyPlaneContracts['errors']===[],$policyPlaneContracts['errors']===[]?'effective fail-closed runtime policy convergence aligned':implode('; ',$policyPlaneContracts['errors']));
foreach($policyPlaneContracts['warnings'] as $warning)$warnings[]='policy-plane.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-target-process-plane-contracts.php';
$processPlaneContracts=nexoraAnalyzeProcessPlaneContracts($root);
$record('process-plane.contracts',$processPlaneContracts['errors']===[],$processPlaneContracts['errors']===[]?'web/queue/scheduler process-role liveness policy aligned':implode('; ',$processPlaneContracts['errors']));
foreach($processPlaneContracts['warnings'] as $warning)$warnings[]='process-plane.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-target-framework-dependency-contracts.php';
$frameworkDependencyContracts=nexoraAnalyzeFrameworkDependencyContracts($root);
$record('framework-dependency.contracts',$frameworkDependencyContracts['errors']===[],$frameworkDependencyContracts['errors']===[]?'Laravel 13.x reviewed dependency reconciliation and critical code readability aligned':implode('; ',$frameworkDependencyContracts['errors']));
foreach($frameworkDependencyContracts['warnings'] as $warning)$warnings[]='framework-dependency.contracts: '.$warning;

require_once $root.'/scripts/lib/n1-target-tenant-seed-typescript-contracts.php';
$tenantSeedTypeScriptContracts=nexoraAnalyzeTenantSeedTypeScriptContracts($root);
$record('tenant-seed-typescript.contracts',$tenantSeedTypeScriptContracts['errors']===[],$tenantSeedTypeScriptContracts['errors']===[]?'stale tenant seed isolation and historical TypeScript regression boundaries aligned':implode('; ',$tenantSeedTypeScriptContracts['errors']));
foreach($tenantSeedTypeScriptContracts['warnings'] as $warning)$warnings[]='tenant-seed-typescript.contracts: '.$warning;

require_once $root.'/scripts/lib/runtime-safety-contracts.php';
$runtimeSafetyContracts = nexoraAnalyzeRuntimeSafetyContracts($root);
$record('runtime-safety.contracts', $runtimeSafetyContracts['ok'], $runtimeSafetyContracts['ok'] ? $runtimeSafetyContracts['metrics']['queue_jobs'].' queue jobs; request/proxy/timeout/cancellation policy aligned' : implode('; ', $runtimeSafetyContracts['errors']));
foreach ($runtimeSafetyContracts['warnings'] as $warning) $warnings[] = 'runtime-safety.contracts: '.$warning;

require_once $root.'/scripts/lib/concurrency-contracts.php';
$concurrencyContracts = nexoraAnalyzeConcurrencyContracts($root);
$record('concurrency.contracts', $concurrencyContracts['ok'], $concurrencyContracts['ok'] ? $concurrencyContracts['metrics']['critical_surfaces'].' critical surfaces; deadlock/idempotency/claim policy aligned' : implode('; ', $concurrencyContracts['errors']));
foreach ($concurrencyContracts['warnings'] as $warning) $warnings[] = 'concurrency.contracts: '.$warning;

require_once $root.'/scripts/lib/zero-install-contracts.php';
$zeroInstallContracts = nexoraAnalyzeZeroInstallContracts($root);
$record('zero-install.contracts', $zeroInstallContracts['ok'], $zeroInstallContracts['ok'] ? $zeroInstallContracts['metrics']['required_artifacts'].' artifacts; deployment + installer recovery aligned' : implode('; ', $zeroInstallContracts['errors']));
foreach ($zeroInstallContracts['warnings'] as $warning) $warnings[] = 'zero-install.contracts: '.$warning;

$platform = require $root.'/config/nexora.php';
$version = trim((string) ($platform['version'] ?? ''));
$record('platform.version', preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $version) === 1, $version);
$record('php.version', version_compare(PHP_VERSION, '8.3.0', '>='), PHP_VERSION);
foreach (['ctype','fileinfo','filter','hash','json','mbstring','openssl','pdo','session','tokenizer','zip'] as $extension) {
    $loaded = extension_loaded($extension);
    if ($sourceOnly && ! $loaded) {
        $checks[] = ['name'=>'php.ext.'.$extension,'ok'=>true,'message'=>'missing in source-check host; required for full certification'];
        $warnings[] = 'php.ext.'.$extension.' is missing; full certification cannot pass on this host.';
    } else {
        $record('php.ext.'.$extension, $loaded, $loaded ? 'loaded' : 'missing');
    }
}

foreach ([
    'artisan','composer.json','package.json','tsconfig.json','phpunit.xml','.env.example',
    'bootstrap/app.php','bootstrap/nexora-runtime-bootstrap.php','routes/web.php','routes/console.php',
    'scripts/source-guard.php','scripts/module-graph-verify.php','scripts/frontend-contract-verify.php','scripts/laravel-runtime-contract-verify.php','scripts/database-contract-verify.php','scripts/zero-install-contract-verify.php','scripts/security-contract-verify.php','scripts/browser-ux-contract-verify.php','scripts/inertia-frontend-contract-verify.php','scripts/browser-evidence-verify.php','scripts/performance-contract-verify.php','scripts/performance-build-verify.php','scripts/final-closure-contract-verify.php','scripts/final-closure-status.php','scripts/final-target-run.php','scripts/target-diagnostics.php','scripts/target-diagnostics-contract-verify.php','scripts/target-runtime-run.php','scripts/target-runtime-run.bat','scripts/target-runtime-run.ps1','scripts/target-runtime-run.sh','scripts/target-runtime-contract-verify.php','scripts/lib/target-runtime-contracts.php','scripts/target-environment-bootstrap.php','scripts/target-environment-bootstrap.bat','scripts/target-environment-bootstrap.ps1','scripts/target-environment-bootstrap.sh','scripts/target-runtime-evidence-verify.php','scripts/target-resume-contract-verify.php','scripts/lib/target-resume-contracts.php','scripts/target-prerequisite-intake.php','scripts/target-prerequisite-intake.bat','scripts/target-prerequisite-intake.ps1','scripts/target-prerequisite-intake.sh','scripts/target-prerequisite-remediate.php','scripts/target-prerequisite-remediate.bat','scripts/target-prerequisite-remediate.ps1','scripts/target-prerequisite-remediate.sh','scripts/target-remediation-contract-verify.php','scripts/lib/target-remediation-contracts.php','scripts/dependency-lock-review.php','scripts/dependency-lock-review.bat','scripts/dependency-lock-review.ps1','scripts/dependency-lock-review.sh','scripts/target-intake-contract-verify.php','scripts/lib/target-intake-contracts.php','scripts/target-evidence-intake.php','scripts/target-evidence-intake.bat','scripts/target-evidence-intake.ps1','scripts/target-evidence-intake.sh','scripts/closure-dashboard.php','scripts/n1-c1-dependency-certify.php','scripts/n1-c1-dependency-certify.bat','scripts/n1-c1-dependency-certify.ps1','scripts/n1-c1-dependency-certify.sh','scripts/n1-c1-installed-dependency-verify.php','scripts/n1-c1-contract-verify.php','scripts/lib/n1-c1-contracts.php','scripts/n1-c1-evidence-verify.php','scripts/n1-c2-laravel-runtime-certify.php','scripts/n1-c2-laravel-runtime-certify.bat','scripts/n1-c2-laravel-runtime-certify.ps1','scripts/n1-c2-laravel-runtime-certify.sh','scripts/n1-c2-evidence-verify.php','scripts/n1-c2-contract-verify.php','scripts/lib/n1-c2-contracts.php','scripts/n1-c3-database-matrix-certify.php','scripts/n1-c3-database-matrix-certify.bat','scripts/n1-c3-database-matrix-certify.ps1','scripts/n1-c3-database-matrix-certify.sh','scripts/n1-c3-matrix-prerequisite.php','scripts/n1-c3-database-matrix-evidence-verify.php','scripts/n1-c3-contract-verify.php','scripts/lib/n1-c3-contracts.php','scripts/n1-c4-operations-certify.php','scripts/n1-c4-operations-certify.bat','scripts/n1-c4-operations-certify.ps1','scripts/n1-c4-operations-certify.sh','scripts/n1-c4-evidence-prepare.php','scripts/n1-c4-evidence-verify.php','scripts/n1-c4-contract-verify.php','scripts/lib/n1-c4-contracts.php','scripts/n1-c5-browser-performance-certify.php','scripts/n1-c5-browser-performance-certify.bat','scripts/n1-c5-browser-performance-certify.ps1','scripts/n1-c5-browser-performance-certify.sh','scripts/n1-c5-evidence-prepare.php','scripts/n1-c5-evidence-import.php','scripts/n1-c5-browser-evidence-verify.php','scripts/n1-c5-web-vitals-evidence-verify.php','scripts/n1-c5-evidence-verify.php','scripts/n1-c5-contract-verify.php','scripts/lib/n1-c5-contracts.php','scripts/lib/n1-c5-browser-performance.php','config/nexora-browser-certification.php','scripts/target-evidence-contract-verify.php','scripts/lib/target-evidence-contracts.php','scripts/target-certification-orchestrator.php','scripts/target-certification-orchestrator.bat','scripts/target-certification-orchestrator.ps1','scripts/target-certification-orchestrator.sh','scripts/target-orchestrator-contract-verify.php','scripts/lib/target-orchestrator-contracts.php','scripts/upgrade-contract-verify.php','scripts/n1-target-distributed-upgrade-contract-verify.php','scripts/n1-target-database-data-plane-contract-verify.php','scripts/lib/n1-target-database-data-plane-contracts.php','scripts/database-data-plane-certify.php','config/nexora-database-runtime.php','scripts/n1-target-host-clock-contract-verify.php','scripts/lib/n1-target-host-clock-contracts.php','config/nexora-host-runtime.php','app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php','app/Console/Commands/Nexora/RuntimeHostStatusCommand.php','app/Nexora/Installation/Database/DatabaseDataPlaneIdentity.php','app/Console/Commands/Nexora/DatabaseDataPlaneStatusCommand.php','scripts/lib/n1-target-distributed-upgrade-contracts.php','app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php','app/Nexora/Foundation/Upgrade/UpgradeMigrationLedger.php','app/Console/Commands/Nexora/UpgradeClusterStatusCommand.php','app/Console/Commands/Nexora/UpgradeNodeStatusCommand.php','app/Console/Commands/Nexora/UpgradeClusterLockCommand.php','app/Console/Commands/Nexora/UpgradeSchedulerLeaseCommand.php','config/nexora-upgrade.php','scripts/environment-contract-verify.php','config/nexora-environment.php','.env.production.example','scripts/dependency-contract-verify.php','scripts/dependency-runtime-verify.php','scripts/dependency-provenance.php','scripts/dependency-audit.php','config/nexora-dependencies.php','config/nexora-framework.php','scripts/n1-target-framework-dependency-contract-verify.php','scripts/lib/n1-target-framework-dependency-contracts.php','app/Nexora/Foundation/Runtime/FrameworkCompatibility.php','app/Nexora/Foundation/Runtime/ReviewedDependencyState.php','app/Nexora/Foundation/Runtime/DependencyDeploymentReconciler.php','app/Console/Commands/Nexora/RuntimeCompatibilityStatusCommand.php','app/Console/Commands/Nexora/RuntimeDependencyStatusCommand.php','app/Console/Commands/Nexora/RuntimeDependencyReconcileCommand.php','scripts/n1-target-tenant-seed-typescript-contract-verify.php','scripts/lib/n1-target-tenant-seed-typescript-contracts.php','tests/Architecture/N100V45TenantSeedTypeScriptArchitectureTest.php','scripts/n1-target-tenant-execution-contract-verify.php','scripts/lib/n1-target-tenant-execution-contracts.php','tests/Architecture/N100V46TenantExecutionBoundaryArchitectureTest.php','app/Nexora/Enterprise/Services/TenantExecutionScope.php','scripts/n1-target-fresh-install-dependency-trust-contract-verify.php','scripts/lib/n1-target-fresh-install-dependency-trust-contracts.php','tests/Architecture/N100V47FreshInstallDependencyTrustArchitectureTest.php','app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php','app/Nexora/Foundation/Runtime/DependencyReviewSynchronizer.php','app/Console/Commands/Nexora/RuntimeDependencyReviewSyncCommand.php','scripts/n1-target-installation-commit-contract-verify.php','scripts/lib/n1-target-installation-commit-contracts.php','tests/Architecture/N100V48InstallationCommitBoundaryArchitectureTest.php','app/Console/Commands/Nexora/InstallationLockStatusCommand.php','scripts/n1-target-installer-consent-flow-contract-verify.php','scripts/lib/n1-target-installer-consent-flow-contracts.php','tests/Architecture/N100V49InstallerConsentFlowArchitectureTest.php','scripts/filesystem-contract-verify.php','scripts/lib/filesystem-contracts.php','config/nexora-filesystem.php','scripts/transfer-contract-verify.php','scripts/lib/transfer-contracts.php','config/nexora-transfers.php','app/Nexora/Foundation/Transfers/TransferSafety.php','app/Console/Commands/Nexora/TransferDoctorCommand.php','scripts/runtime-safety-contract-verify.php','scripts/lib/runtime-safety-contracts.php','config/nexora-runtime.php','app/Nexora/Foundation/Runtime/RuntimeLimitsDoctor.php','app/Console/Commands/Nexora/RuntimeDoctorCommand.php','scripts/concurrency-contract-verify.php','scripts/lib/concurrency-contracts.php','config/nexora-concurrency.php','app/Nexora/Foundation/Database/ConcurrencyGuard.php','app/Nexora/Foundation/Database/ConcurrencyDoctor.php','app/Console/Commands/Nexora/ConcurrencyDoctorCommand.php','app/Http/Middleware/ConfigureTrustedProxies.php','app/Http/Middleware/EnforceRequestLimits.php','scripts/refresh-dependency-locks.bat','scripts/refresh-dependency-locks.ps1','scripts/refresh-dependency-locks.sh','scripts/target-diagnostics.bat','scripts/target-diagnostics.ps1','scripts/target-diagnostics.sh','config/nexora-update-trust.php','scripts/lib/trusted-update.php','scripts/trusted-update-trust-anchor.php','scripts/trusted-update-admit.php','scripts/trusted-update-stage.php','scripts/trusted-update-cleanup.php','scripts/trusted-update-cleanup.bat','scripts/trusted-update-cleanup.ps1','scripts/trusted-update-cleanup.sh','scripts/trusted-update-candidate.php','scripts/trusted-update-admit-candidate.php','scripts/n1-target-update-trust-contract-verify.php','scripts/lib/n1-target-update-trust-contracts.php','app/Nexora/Foundation/Upgrade/TrustedUpdateAdmission.php','app/Nexora/Foundation/Upgrade/UpgradeTransactionJournal.php','app/Nexora/Foundation/Upgrade/UpgradeMaintenanceLease.php','app/Nexora/Foundation/Upgrade/UpgradePostHealthCheck.php','app/Nexora/Foundation/Upgrade/UpgradeRecoveryDecisionStore.php','app/Console/Commands/Nexora/UpgradeRecoveryStatusCommand.php','app/Console/Commands/Nexora/UpgradeRecoveryRecordCommand.php','app/Console/Commands/Nexora/UpgradeMaintenanceLeaseCommand.php','app/Console/Commands/Nexora/UpgradeLineageExportCommand.php','scripts/build-production-release.php','docs/NEXORA_PLAN_STATUS.md',
] as $relative) {
    $record('file.'.$relative, is_file($root.'/'.$relative) && filesize($root.'/'.$relative) > 0, $relative);
}

foreach ([
    'app/Nexora/Installation/InstallationResumeIdentity.php',
    'scripts/n1-target-fast-track.php',
    'scripts/n1-target-fast-track.bat',
    'scripts/n1-target-fast-track.ps1',
    'scripts/n1-target-fast-track.sh',
    'scripts/lib/n1-target-installation-resume-fast-track-contracts.php',
    'scripts/n1-target-installation-resume-fast-track-contract-verify.php',
    'tests/Architecture/N100V50InstallationResumeFastTrackArchitectureTest.php',
    'scripts/lib/n1-target-progress.php',
    'scripts/n1-target-progress.php',
    'scripts/lib/n1-historical-typescript-remediation.php',
    'scripts/n1-historical-typescript-remediation.php',
    'scripts/lib/n1-target-progress-visibility-contracts.php',
    'scripts/n1-target-progress-visibility-contract-verify.php',
    'tests/Architecture/N100V51TargetProgressVisibilityArchitectureTest.php',
    'app/Nexora/Installation/SourceActivationIdentity.php',
    'app/Console/Commands/Nexora/SourceStatusCommand.php',
    'app/Console/Commands/Nexora/SourceActivateCommand.php',
    'scripts/n1-source-activate.bat',
    'scripts/n1-source-activate.sh',
    'scripts/lib/n1-target-source-activation-contracts.php',
    'scripts/n1-target-source-activation-contract-verify.php',
    'tests/Architecture/N100V52SourceActivationArchitectureTest.php',
    'app/Nexora/Installation/SourceSetIntegrity.php',
    'app/Nexora/Installation/SourceActivationHandshake.php',
    'bootstrap/nexora-source-manifest.json',
    'scripts/n1-source-manifest-seal.php',
    'scripts/n1-source-web-ack.bat',
    'scripts/n1-source-web-ack.sh',
    'scripts/lib/n1-installation-progress.php',
    'scripts/n1-installation-progress.php',
    'scripts/lib/n1-target-source-set-handshake-contracts.php',
    'scripts/n1-target-source-set-handshake-contract-verify.php',
    'tests/Unit/Installation/SourceActivationHandshakeTest.php',
    'tests/Architecture/N100V53SourceSetHandshakeArchitectureTest.php',
    'scripts/lib/n1-target-runtime-source-convergence-contracts.php',
    'scripts/n1-target-runtime-source-convergence-contract-verify.php',
    'tests/Architecture/N100V54RuntimeSourceConvergenceArchitectureTest.php',
    'tests/Feature/Certification/SourceStatusRedactionCertificationTest.php',
    'tests/Unit/Installation/InstallationProgressVisibilityTest.php',
    'scripts/lib/n1-target-installer-host-clock-contracts.php',
    'scripts/n1-target-installer-host-clock-contract-verify.php',
    'scripts/lib/n1-target-installer-runtime-readiness-contracts.php',
    'scripts/n1-target-installer-runtime-readiness-contract-verify.php',
    'tests/Architecture/N100V56InstallerRuntimeReadinessArchitectureTest.php',
    'scripts/lib/n1-target-install-runtime-handoff-contracts.php',
    'scripts/n1-target-install-runtime-handoff-contract-verify.php',
    'scripts/lib/n1-target-clock-temp-portability-contracts.php',
    'scripts/n1-target-clock-temp-portability-contract-verify.php',
    'tests/Architecture/N100V58ClockTempPortabilityArchitectureTest.php',
    'app/Nexora/Foundation/Runtime/RuntimeWritableTempDirectory.php',
    'tests/Architecture/N100V57InstallRuntimeHandoffArchitectureTest.php',
    'app/Nexora/Installation/RuntimePostInstallHandoff.php',
    'app/Console/Commands/Nexora/RuntimePostInstallStatusCommand.php',
    'app/Console/Commands/Nexora/RuntimePostInstallReconcileCommand.php',
    'scripts/lib/n1-target-exact-resume-commit-contracts.php',
    'scripts/n1-target-exact-resume-commit-contract-verify.php',
    'tests/Architecture/N100V59ExactResumeCommitArchitectureTest.php',
    'resources/views/install/runtime-handoff.blade.php',
    'scripts/lib/n1-frontend-build-diagnostics.php',
    'scripts/n1-c1-frontend-build-doctor.php',
    'scripts/n1-c1-frontend-build-doctor.bat',
    'scripts/n1-c1-frontend-build-doctor.ps1',
    'scripts/n1-c1-frontend-build-doctor.sh',
    'scripts/lib/n1-target-frontend-build-closure-contracts.php',
    'scripts/n1-target-frontend-build-closure-contract-verify.php',
    'tests/Architecture/N100V510FrontendBuildClosureArchitectureTest.php',
    'tests/Unit/Certification/FrontendBuildDiagnosticsTest.php',
    'scripts/lib/dependency-lock-intake.php',
    'scripts/dependency-lock-refresh.php',
    'scripts/dependency-lock-promote.php',
    'scripts/promote-reviewed-dependency-locks.bat',
    'scripts/promote-reviewed-dependency-locks.ps1',
    'scripts/promote-reviewed-dependency-locks.sh',
    'scripts/dependency-lock-promotion-recover.php',
    'scripts/recover-dependency-lock-promotion.bat',
    'scripts/recover-dependency-lock-promotion.ps1',
    'scripts/recover-dependency-lock-promotion.sh',
    'scripts/lib/n1-target-transactional-lock-intake-contracts.php',
    'scripts/n1-target-transactional-lock-intake-contract-verify.php',
    'tests/Architecture/N100V511TransactionalLockIntakeArchitectureTest.php',
    'scripts/lib/dependency-toolchain.php',
    'scripts/lib/n1-target-reproducible-dependency-toolchain-contracts.php',
    'scripts/n1-target-reproducible-dependency-toolchain-contract-verify.php',
    'tests/Architecture/N100V512ReproducibleDependencyToolchainArchitectureTest.php',
    'scripts/lib/dependency-candidate-supply-chain.php',
    'scripts/lib/n1-target-dependency-candidate-supply-chain-contracts.php',
    'scripts/n1-target-dependency-candidate-supply-chain-contract-verify.php',
    'scripts/lib/n1-target-windows-npm-bridge-contracts.php',
    'scripts/n1-target-windows-npm-bridge-contract-verify.php',
    'tests/Architecture/N100V520WindowsNpmBridgeArchitectureTest.php',
    'scripts/lib/n1-target-npm-bundled-integrity-contracts.php',
    'scripts/n1-target-npm-bundled-integrity-contract-verify.php',
    'tests/Architecture/N100V521NpmBundledIntegrityArchitectureTest.php',
    'tests/Architecture/N100V515DependencyCandidateSupplyChainArchitectureTest.php',
    'scripts/lib/n1-target-semantic-lock-reproducibility-contracts.php',
    'scripts/n1-target-semantic-lock-reproducibility-contract-verify.php',
    'tests/Architecture/N100V522SemanticLockReproducibilityArchitectureTest.php',
    'scripts/lib/n1-target-typescript-depth-contracts.php',
    'scripts/n1-target-typescript-depth-contract-verify.php',
    'tests/Architecture/N100V522TypeScriptDepthArchitectureTest.php',
    'scripts/lib/pkg1-closure.php',
    'scripts/lib/pkg1-closure-contracts.php',
    'scripts/pkg1-closure-contract-verify.php',
    'scripts/pkg1-usable-closure.php',
    'scripts/pkg1-usable-closure.bat',
    'scripts/pkg1-usable-closure.ps1',
    'scripts/pkg1-usable-closure.sh',
    'scripts/pkg1-usable-smoke.php',
    'scripts/pkg1-closure-evidence-verify.php',
    'scripts/pkg1-status.php',
    'scripts/pkg1-run.bat',
    'scripts/pkg1-run.php',
    'scripts/pkg1-launcher-contract-verify.php',
    'scripts/pkg1-status.bat',
    'scripts/pkg1-status.ps1',
    'scripts/pkg1-status.sh',
    'scripts/pkg1-build.php',
    'scripts/lib/pkg1-build-identity.php',
    'scripts/pkg1-finalize-login-smoke.bat',
    'scripts/pkg1-finalize-login-smoke.ps1',
    'tests/Architecture/N100Pkg1UsableClosureArchitectureTest.php',
    'app/Nexora/Installation/RuntimeInstallationReadiness.php',
    'app/Console/Commands/Nexora/RuntimeInstallReadinessCommand.php',
    'tests/Architecture/N100V55InstallerHostClockArchitectureTest.php',
] as $relative) {
    $record('file.'.$relative, is_file($root.'/'.$relative) && filesize($root.'/'.$relative) > 0, $relative);
}

foreach (['composer.json','package.json','public/site.webmanifest'] as $relative) {
    try {
        json_decode((string) file_get_contents($root.'/'.$relative), true, 512, JSON_THROW_ON_ERROR);
        $record('json.'.$relative, true, 'valid');
    } catch (Throwable $e) {
        $record('json.'.$relative, false, $e->getMessage());
    }
}

foreach (['bootstrap/cache','storage/app','storage/app/nexora','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs'] as $relative) {
    $path = $root.'/'.$relative;
    $record('runtime.writable.'.$relative, is_dir($path) && is_writable($path), $relative);
}

// Database migration portability, rollback coverage, FK ordering and tenant-table/model parity are owned by database.contracts above.
$checks[] = ['name' => 'migration.count', 'ok' => true, 'message' => (string) $databaseContracts['metrics']['migrations']];

// Schedule callback regression: CallbackEvent + withoutOverlapping requires an explicit name in current Laravel.
$console = (string) file_get_contents($root.'/routes/console.php');
if (preg_match_all('/Schedule::call\((.*?)\)->([^;]+);/s', $console, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        $chain = (string) ($match[2] ?? '');
        if (str_contains($chain, 'withoutOverlapping(') && ! str_contains($chain, 'name(')) {
            $failures[] = 'scheduler.unnamed-callback-with-overlap';
        }
    }
}

// Admin UI governance: feature pages must consume the shared component layer rather than raw controls.
$rawControlFiles = [];
$nativeDateFiles = [];
$adminRoot = $root.'/resources/js/admin';
if (is_dir($adminRoot)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['ts','tsx'], true)) continue;
        $relative = str_replace('\\','/',substr($file->getPathname(), strlen($root)+1));
        if (str_contains($relative, '/ui/')) continue;
        $source = (string) file_get_contents($file->getPathname());
        if (preg_match('/<(button|input|select|textarea)\b/', $source) === 1) $rawControlFiles[] = $relative;
        if (preg_match('/type=[\'\"](?:date|time|datetime-local|month|week)[\'\"]/i', $source) === 1) $nativeDateFiles[] = $relative;
    }
}
$record('ui.raw-feature-controls', $rawControlFiles === [], $rawControlFiles === [] ? '0' : implode(', ', array_slice($rawControlFiles,0,10)));
$record('ui.native-date-time-inputs', $nativeDateFiles === [], $nativeDateFiles === [] ? '0' : implode(', ', array_slice($nativeDateFiles,0,10)));

// Internal TS import graph without requiring npm dependencies.
$missingImports = [];
$importCount = 0;
$extensions = ['','.ts','.tsx','.js','.jsx'];
foreach (glob($root.'/resources/js/**/*.ts') ?: [] as $_) { /* recursive glob is not portable; handled below */ }
if (is_dir($root.'/resources/js')) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/js', FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['ts','tsx'], true)) continue;
        $source = (string) file_get_contents($file->getPathname());
        if (! preg_match_all('/(?:from\s+|import\s*\(\s*)[\'\"]([^\'\"]+)[\'\"]/', $source, $matches)) continue;
        foreach ($matches[1] as $specifier) {
            if (! str_starts_with($specifier, '.')) continue;
            $importCount++;
            $base = dirname($file->getPathname()).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $specifier);
            $candidates = [];
            foreach ($extensions as $extension) $candidates[] = $base.$extension;
            foreach (['index.ts','index.tsx','index.js','index.jsx'] as $index) $candidates[] = rtrim($base, '\\/').DIRECTORY_SEPARATOR.$index;
            if (! array_filter($candidates, 'is_file')) {
                $missingImports[] = str_replace('\\','/',substr($file->getPathname(), strlen($root)+1)).': '.$specifier;
            }
        }
    }
}
$record('typescript.local-imports', $missingImports === [], $importCount.' checked'.($missingImports ? '; missing '.implode(', ', array_slice($missingImports,0,10)) : ''));

// RC regression: architecture tests must not freeze an old top-level platform version.
$staleVersions = [];
foreach (glob($root.'/tests/Architecture/*.php') ?: [] as $file) {
    $source = (string) file_get_contents($file);
    if (preg_match_all("/'version' => '(0\\.(?:[0-9]+)\\.0)'/", $source, $matches)) {
        foreach ($matches[1] as $stale) $staleVersions[] = basename($file).': '.$stale;
    }
}
$record('tests.no-stale-platform-version', $staleVersions === [], $staleVersions === [] ? 'current' : implode(', ', $staleVersions));

$releaseBuilder = (string) file_get_contents($root.'/scripts/build-production-release.php');
$record('release.dynamic-version', str_contains($releaseBuilder, "require $root.'/config/nexora.php'") || str_contains($releaseBuilder, "require \$configPath"), 'build-production-release reads config/nexora.php');
$record('release.certification-required', str_contains($releaseBuilder, 'certification-pass'), 'production packaging requires matching certification report');

$runtimeQuiescenceOut=[];$runtimeQuiescenceCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-runtime-quiescence-contract-verify.php').' 2>&1',$runtimeQuiescenceOut,$runtimeQuiescenceCode);$record('n1.runtime-quiescence-contract',$runtimeQuiescenceCode===0,$runtimeQuiescenceCode===0?'pass':implode(' | ',$runtimeQuiescenceOut));
$cutoverOut=[];$cutoverCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-cutover-barrier-contract-verify.php').' 2>&1',$cutoverOut,$cutoverCode);$record('n1.cutover-barrier-contract',$cutoverCode===0,$cutoverCode===0?'pass':implode(' | ',$cutoverOut));
$deploymentGenerationOut=[];$deploymentGenerationCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-deployment-generation-contract-verify.php').' 2>&1',$deploymentGenerationOut,$deploymentGenerationCode);$record('n1.deployment-generation-contract',$deploymentGenerationCode===0,$deploymentGenerationCode===0?'pass':implode(' | ',$deploymentGenerationOut));
$runtimeEnvironmentOut=[];$runtimeEnvironmentCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-runtime-environment-contract-verify.php').' 2>&1',$runtimeEnvironmentOut,$runtimeEnvironmentCode);$record('n1.runtime-environment-contract',$runtimeEnvironmentCode===0,$runtimeEnvironmentCode===0?'pass':implode(' | ',$runtimeEnvironmentOut));
$runtimeActivationOut=[];$runtimeActivationCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-runtime-activation-contract-verify.php').' 2>&1',$runtimeActivationOut,$runtimeActivationCode);$record('n1.runtime-activation-contract',$runtimeActivationCode===0,$runtimeActivationCode===0?'pass':implode(' | ',$runtimeActivationOut));
$runtimeEngineOut=[];$runtimeEngineCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-runtime-engine-contract-verify.php').' 2>&1',$runtimeEngineOut,$runtimeEngineCode);$record('n1.runtime-engine-contract',$runtimeEngineCode===0,$runtimeEngineCode===0?'pass':implode(' | ',$runtimeEngineOut));
$hostClockOut=[];$hostClockCode=0;exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/n1-target-host-clock-contract-verify.php').' 2>&1',$hostClockOut,$hostClockCode);$record('n1.host-clock-contract',$hostClockCode===0,$hostClockCode===0?'pass':implode(' | ',$hostClockOut));

$status = $failures === [] ? 'preflight-pass' : 'preflight-fail';
$payload = [
    'schema' => 1,
    'status' => $status,
    'platform_version' => $version,
    'php_version' => PHP_VERSION,
    'os_family' => PHP_OS_FAMILY,
    'checked_at' => gmdate(DATE_ATOM),
    'checks' => $checks,
    'failures' => array_values(array_unique($failures)),
    'warnings' => $warnings,
];

if ($json) {
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
} else {
    fwrite(STDOUT, "[Nexora RC Preflight] ".strtoupper($status)." — {$version}\n");
    foreach ($payload['failures'] as $failure) fwrite(STDERR, " - {$failure}\n");
}
exit($failures === [] ? 0 : 1);
