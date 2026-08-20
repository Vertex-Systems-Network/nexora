<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeInstallationCommitContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Installation/InstallationState.php',
        'app/Nexora/Installation/Installer.php',
        'app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php',
        'app/Http/Controllers/Install/InstallerController.php',
        'app/Http/Middleware/RedirectIfNotInstalled.php',
        'app/Console/Commands/Nexora/InstallationLockStatusCommand.php',
        'tests/Unit/InstallationStateIntegrityTest.php',
        'tests/Unit/FreshInstallDependencyReceiptCommitTest.php',
        'tests/Feature/Certification/InstallationLockIntegrityCertificationTest.php',
        'tests/Architecture/N100V48InstallationCommitBoundaryArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v4.8 installation commit artifact missing [{$file}]";
        }
    }

    $state = $read('app/Nexora/Installation/InstallationState.php');
    foreach ([
        "private const HASH_KEY = '_lock_sha256'",
        "private const SCHEMA_KEY = '_lock_schema'",
        "'sealed-valid'",
        "'legacy-unsealed'",
        'failed integrity validation',
        'writeSealed',
        'canonicalize',
    ] as $marker) {
        if (! str_contains($state, $marker)) {
            $errors[] = "sealed installation lock contract missing [{$marker}]";
        }
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    foreach ([
        'discardOrphanedBootstrapReceipt',
        'commitBootstrapReceipt',
        'installationReadiness->assertReady',
        'finalizeCommittedInstallation',
        'installed.lock is the durable commit point',
        'nexora:install:lock-status',
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "installer commit boundary missing [{$marker}]";
        }
    }

    $metadataStart = strpos($installer, 'private function buildRuntimeInstallationMetadata');
    $metadataEnd = strpos($installer, 'private function assertInstallerIsOpen', $metadataStart === false ? 0 : $metadataStart);
    $metadataBody = ($metadataStart !== false && $metadataEnd !== false)
        ? substr($installer, $metadataStart, $metadataEnd - $metadataStart)
        : '';
    $commitReceipt = strpos($metadataBody, 'commitBootstrapReceipt');
    $finalAttestation = strrpos($metadataBody, 'installationReadiness->assertReady');
    $markInstalled = strpos($installer, '$this->state->markInstalled([');
    $postCommit = strpos($installer, 'finalizeCommittedInstallation($input)');
    if ($finalAttestation === false || $commitReceipt === false || $commitReceipt <= $finalAttestation) {
        $errors[] = 'bootstrap receipt must publish only after all final runtime attestations';
    }
    if ($markInstalled === false || $postCommit === false || $markInstalled >= $postCommit) {
        $errors[] = 'sealed installed.lock must remain the durable commit point before best-effort housekeeping';
    }

    $controllerForHandoff = $read('app/Http/Controllers/Install/InstallerController.php');
    $handoffRoute = strpos($controllerForHandoff, 'public function runtimeHandoff()');
    $handoffFinalize = strpos($controllerForHandoff, '$this->postInstallHandoff->verifyAndRecord();', $handoffRoute === false ? 0 : $handoffRoute);
    if ($handoffRoute === false || $handoffFinalize === false || $handoffFinalize <= $handoffRoute) {
        $errors[] = 'sealed installed.lock must transition through a fresh-request runtime handoff before login';
    }

    $trust = $read('app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php');
    foreach ([
        'buildBootstrapReceipt',
        'commitBootstrapReceipt',
        'discardOrphanedBootstrapReceipt',
        'failed integrity validation before publication',
    ] as $marker) {
        if (! str_contains($trust, $marker)) {
            $errors[] = "bootstrap receipt staging boundary missing [{$marker}]";
        }
    }

    $middleware = $read('app/Http/Middleware/RedirectIfNotInstalled.php');
    foreach ([
        'installation lock failed integrity validation',
        'X-Nexora-Installation-Lock',
        '503',
    ] as $marker) {
        if (! str_contains($middleware, $marker)) {
            $errors[] = "invalid install-lock HTTP fail-closed boundary missing [{$marker}]";
        }
    }

    $controller = $read('app/Http/Controllers/Install/InstallerController.php');
    foreach ([
        'installationCommitIsValid',
        'finishRunBestEffort',
        'completed-with-warning',
        'post-commit bookkeeping',
    ] as $marker) {
        if (! str_contains($controller, $marker)) {
            $errors[] = "post-commit controller recovery missing [{$marker}]";
        }
    }

    $provider = $read('app/Providers/NexoraServiceProvider.php');
    if (! str_contains($provider, 'InstallationLockStatusCommand::class')) {
        $errors[] = 'installation lock status command is not registered';
    }

    $c2 = $read('scripts/n1-c2-laravel-runtime-certify.php');
    foreach ([
        'installation-lock-status',
        'installation-lock-integrity-test',
        'installation-bootstrap-receipt-test',
        'installation-lock-http-failclosed-test',
    ] as $marker) {
        if (! str_contains($c2, $marker)) {
            $errors[] = "C2 installation commit gate missing [{$marker}]";
        }
    }

    $c4 = $read('scripts/n1-c4-evidence-prepare.php');
    foreach ([
        'sealed_install_lock_schema_verified',
        'sealed_install_lock_sha256_verified',
        'corrupt_install_lock_fail_closed',
        'corrupt_install_lock_does_not_reopen_installer',
        'legacy_install_lock_backward_compatibility_verified',
        'legacy_install_lock_resealed_on_metadata_update',
        'bootstrap_receipt_staged_until_final_attestation',
        'orphan_bootstrap_receipt_cleared_before_retry',
        'bootstrap_receipt_integrity_verified_before_publish',
        'installed_lock_commit_point_verified',
        'post_commit_cleanup_failure_nonfatal',
        'post_commit_run_telemetry_failure_nonfatal',
    ] as $marker) {
        if (! str_contains($c4, $marker)) {
            $errors[] = "C4 installation commit rehearsal missing [{$marker}]";
        }
    }

    $humanReadableFiles = [
        'app/Nexora/Installation/InstallationState.php',
        'app/Nexora/Installation/Installer.php',
        'app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php',
        'app/Http/Controllers/Install/InstallerController.php',
        'app/Http/Middleware/RedirectIfNotInstalled.php',
        'app/Console/Commands/Nexora/InstallationLockStatusCommand.php',
    ];

    foreach ($humanReadableFiles as $file) {
        $content = $read($file);
        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            if (strlen($line) > 180) {
                $errors[] = "v4.8 critical install file exceeds readable line length [{$file}]";
                break;
            }
        }
    }

    return [
        'errors' => $errors,
        'warnings' => $warnings,
        'metrics' => [
            'install_lock_schema' => 2,
            'c2_commit_gates' => 4,
            'c4_commit_checks' => 12,
            'automatic_corrupt_lock_reopen' => 0,
            'human_readable_files' => count($humanReadableFiles),
        ],
    ];
}
