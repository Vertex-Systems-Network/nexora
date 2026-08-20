<?php

declare(strict_types=1);

/** @return array{ok:bool,errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeZeroInstallContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $required = [
        'public/index.php', 'public/nexora-bootstrap.php',
        'bootstrap/nexora-runtime-bootstrap.php', 'bootstrap/nexora-installer-bootstrap.php',
        'app/Nexora/Installation/InstallationState.php', 'app/Nexora/Installation/InstallationRunControl.php',
        'app/Nexora/Installation/Installer.php', 'app/Http/Controllers/Install/InstallerController.php',
        'resources/views/install/index.blade.php',
        'scripts/zero-state-verify.php', 'scripts/setup-zero.bat', 'scripts/setup-zero.ps1', 'scripts/setup-zero.sh',
        'scripts/build-production-release.php', '.env.example',
    ];
    foreach ($required as $relative) {
        $path = $root.'/'.$relative;
        if (! is_file($path) || filesize($path) === 0) $errors[] = "Missing zero-install artifact: {$relative}";
    }

    $read = static fn (string $relative): string => is_file($root.'/'.$relative) ? (string) file_get_contents($root.'/'.$relative) : '';
    $index = $read('public/index.php');
    foreach (["storage/app/nexora/installed.lock", "vendor/autoload.php", "build/manifest.json", "require __DIR__.'/nexora-bootstrap.php'"] as $marker) {
        if (! str_contains($index, $marker)) $errors[] = "public/index.php is missing zero-install handoff marker: {$marker}";
    }
    if (! str_contains($index, '! file_exists($installedLock)') || ! str_contains($index, '! file_exists($autoload)') || ! str_contains($index, '! file_exists($frontendManifest)')) {
        $errors[] = 'Deployment bootstrap must run only before installed.lock and only when dependency/build artifacts are missing.';
    }

    $bootstrap = $read('bootstrap/nexora-installer-bootstrap.php');
    foreach (['$fallbackEnv', '$bootstrapKey', '$activeMarker', '.env.example', 'SESSION_DRIVER', 'CACHE_STORE', 'QUEUE_CONNECTION'] as $marker) {
        if (! str_contains($bootstrap, $marker)) $errors[] = "Installer bootstrap is missing resilient pre-.env marker: {$marker}";
    }

    $state = $read('app/Nexora/Installation/InstallationState.php');
    foreach (['markInstalled', 'AtomicFileWriter', '$this->files->write', '_lock_sha256', 'sealed-valid', 'legacy-unsealed'] as $marker) {
        if (! str_contains($state, $marker)) $errors[] = "InstallationState atomic lock contract is missing: {$marker}";
    }
    $atomicWriter = $read('app/Nexora/Foundation/Filesystem/AtomicFileWriter.php');
    foreach (['.nexora-atomic-', 'LOCK_EX', 'rename($temporary, $path)', 'fsync'] as $marker) {
        if (! str_contains($atomicWriter, $marker)) $errors[] = "Central atomic file writer contract is missing: {$marker}";
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    $mark = strpos($installer, '$this->state->markInstalled([');
    $cleanup = strpos($installer, "Artisan::call('optimize:clear')");
    if ($mark === false || $cleanup === false || $mark <= $cleanup) $errors[] = 'Permanent installed.lock must remain the final provisioning mutation after cleanup.';
    foreach (['bindDatabaseTarget', 'partial schema will be resumed', "config('nexora.version', 'unknown')"] as $marker) {
        if (! str_contains($installer, $marker)) $errors[] = "Installer recovery contract is missing: {$marker}";
    }
    if (! str_contains($installer, 'recoveryForDatabase') && ! str_contains($installer, 'recoverableForDatabase')) {
        $errors[] = 'Installer recovery contract is missing exact database recovery lookup.';
    }

    $run = $read('app/Nexora/Installation/InstallationRunControl.php');
    foreach (['recoverInterruptedRuns', 'installerMutexAvailable', 'protected_started', 'database_fingerprint', 'recovery_window_seconds', "['status'] = 'interrupted'"] as $marker) {
        if (! str_contains($run, $marker)) $errors[] = "Installation run recovery contract is missing: {$marker}";
    }

    $controller = $read('app/Http/Controllers/Install/InstallerController.php');
    foreach (['recoverySummary', 'recoverable_installation', 'installer controls are locked', 'finishRunBestEffort', 'installationCommitIsValid'] as $marker) {
        if (! str_contains($controller, $marker)) $errors[] = "Installer controller recovery/lockout contract is missing: {$marker}";
    }

    $view = $read('resources/views/install/index.blade.php');
    foreach (['dbRecoverable', 'db-recovery-state', 'Recoverable installation history detected', 'resume migrations/seeding without destructive reset'] as $marker) {
        if (! str_contains($view, $marker)) $errors[] = "Installer recovery UI contract is missing: {$marker}";
    }

    $deployment = $read('public/nexora-bootstrap.php');
    foreach (['nxRecoverInterruptedDeploymentState', 'nxDeploymentLockAvailable', 'deployment-last-interrupted.json', 'nxArchiveInterruptedDeploymentState', 'nxSourcePlatformVersion', "['status'] = 'interrupted'"] as $marker) {
        if (! str_contains($deployment, $marker)) $errors[] = "Deployment stale-run recovery contract is missing: {$marker}";
    }
    $stateWrite = strpos($deployment, 'nxWriteDeploymentState($root, $runId, false');
    $unlock = strpos($deployment, '@flock($lock, LOCK_UN)', $stateWrite === false ? 0 : $stateWrite);
    if ($stateWrite === false || $unlock === false || $stateWrite > $unlock) $errors[] = 'Deployment completion state must be persisted before the process lock is released.';

    $zero = $read('scripts/zero-state-verify.php');
    foreach (['environment/.env', 'environment/active', 'environment/bootstrap.key', 'deployment-last-interrupted.json', 'bootstrap/cache/nexora/runtime.php', '--strict-source'] as $marker) {
        if (! str_contains($zero, $marker)) $errors[] = "Zero-state verifier is missing persisted-state check: {$marker}";
    }
    foreach (['scripts/setup-zero.bat', 'scripts/setup-zero.ps1', 'scripts/setup-zero.sh'] as $relative) {
        $source = $read($relative);
        foreach (['vendor', 'node_modules', 'public/build', 'storage/app/nexora/environment', 'storage/app/nexora/tools', 'storage/app/nexora/target-runtime', 'storage/app/nexora/target-bootstrap', 'storage/app/nexora/target-intake', 'storage/app/nexora/dependency-intake', 'storage/app/nexora/target-remediation', 'zero-state-verify.php'] as $marker) {
            $normalized = str_replace('\\', '/', $source);
            if (! str_contains($normalized, $marker)) $errors[] = "{$relative} is missing true-zero cleanup marker: {$marker}";
        }
        if (! str_contains($source, '--strict-source')) $errors[] = "{$relative} must finish with strict zero-state verification.";
    }

    $release = $read('scripts/build-production-release.php');
    $releasePolicy = $read('config/nexora-release.php');
    if (! str_contains($release, 'config/nexora-release.php')) $errors[] = 'Production release builder must consume centralized config/nexora-release.php policy.';
    foreach (['deployment-access.key', 'deployment-last-run.json', 'deployment-last-interrupted.json', 'deployment-control/', 'installation-control/', 'database-backups/', 'environment/', 'target-runtime/', 'target-bootstrap/', 'target-intake/', 'dependency-intake/', 'target-remediation/'] as $marker) {
        if (! str_contains($releasePolicy, $marker)) $errors[] = "Production release exclusion is missing runtime installer state: {$marker}";
    }

    $config = $read('config/installer.php');
    foreach (['run_stale_seconds', 'recovery_window_seconds', 'lock_schema'] as $marker) {
        if (! str_contains($config, $marker)) $errors[] = "Installer recovery configuration is missing: {$marker}";
    }

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'warnings' => $warnings,
        'metrics' => [
            'required_artifacts' => count($required),
            'setup_runners' => 3,
            'recovery_layers' => 2,
        ],
    ];
}
