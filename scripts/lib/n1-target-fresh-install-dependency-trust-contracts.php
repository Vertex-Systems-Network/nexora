<?php

declare(strict_types=1);

/**
 * Protects the v4.7 fresh-install dependency trust boundary.
 *
 * A clean source package intentionally omits operator review evidence. Fresh
 * installation may therefore bootstrap runtime trust from deterministic locks,
 * but only when the running Composer package set and Laravel version match those
 * locks. Formal review remains mandatory for certification / HA closure.
 *
 * @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>}
 */
function nexoraAnalyzeFreshInstallDependencyTrustContracts(string $root): array
{
    $errors = [];
    $warnings = [];

    $read = static function (string $relative) use ($root): string {
        $path = $root.'/'.$relative;

        return is_file($path) ? (string) file_get_contents($path) : '';
    };

    $required = [
        'app/Nexora/Foundation/Runtime/ReviewedDependencyState.php',
        'app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php',
        'app/Nexora/Foundation/Runtime/DependencyReviewSynchronizer.php',
        'app/Console/Commands/Nexora/RuntimeDependencyReviewSyncCommand.php',
        'app/Console/Commands/Nexora/InstallerDoctorCommand.php',
        'tests/Architecture/N100V47FreshInstallDependencyTrustArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v4.7 fresh-install dependency trust artifact missing [{$file}]";
        }
    }

    $policy = $read('config/nexora-framework.php');
    foreach ([
        "'fresh_install_dependency_trust'",
        "'enabled' => true",
        "'require_installed_composer_match' => true",
        "'require_npm_manifest_lock_match' => true",
        'fresh-install-bootstrap.json',
    ] as $marker) {
        if (! str_contains($policy, $marker)) {
            $errors[] = "fresh-install dependency policy missing [{$marker}]";
        }
    }

    $state = $read('app/Nexora/Foundation/Runtime/ReviewedDependencyState.php');
    foreach ([
        "'runtime_status'",
        "'review_status'",
        "'review_required'",
        'identity_errors',
        'review_errors',
        "return ['state' => 'missing'",
        "'stale-or-invalid'",
    ] as $marker) {
        if (! str_contains($state, $marker)) {
            $errors[] = "reviewed dependency state separation missing [{$marker}]";
        }
    }

    $bootstrap = $read('app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php');
    foreach ([
        'bootstrap-verified',
        'Fresh-install dependency trust is not ready',
        'vendor/composer/installed.json',
        'Composer runtime package version mismatch',
        'package-lock.json root',
        'review_required_for_certification',
        'receipt_sha256',
        'Dependency review evidence exists but is unreadable, stale, or invalid',
    ] as $marker) {
        if (! str_contains($bootstrap, $marker)) {
            $errors[] = "fresh-install dependency bootstrap missing [{$marker}]";
        }
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    foreach ([
        'FreshInstallDependencyTrust',
        'dependency_trust_mode',
        'dependency_review_required',
        'dependency_bootstrap_receipt_sha256',
        'reviewed_locks_sha256',
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "installer dependency trust lineage missing [{$marker}]";
        }
    }

    if (str_contains($installer, 'Reviewed dependency state is not ready:')) {
        $errors[] = 'installer must not hard-fail solely because reviewed-lock attestation is missing';
    }

    $guard = $read('app/Nexora/Cloud/Services/RuntimeVersionGuard.php');
    foreach ([
        "'dependencies_status' => (string) (\$dependencies['runtime_status'] ?? 'fail')",
        "'dependency_review_status'",
        'runtime_dependencies_compatible',
    ] as $marker) {
        if (! str_contains($guard, $marker)) {
            $errors[] = "runtime dependency identity guard missing [{$marker}]";
        }
    }

    $doctor = $read('app/Console/Commands/Nexora/InstallerDoctorCommand.php');
    foreach ([
        'FreshInstallDependencyTrust',
        'fresh_install_dependency_trust',
        'bootstrap-verified dependency identity',
    ] as $marker) {
        if (! str_contains($doctor, $marker)) {
            $errors[] = "installer doctor dependency preflight missing [{$marker}]";
        }
    }

    $sync = $read('app/Nexora/Foundation/Runtime/DependencyReviewSynchronizer.php');
    foreach ([
        'installedDriftAssessment',
        'dependency_trust_mode',
        'reviewed_locks_sha256',
        'deployment_generation_changed',
        'dependency-review-sync.json',
    ] as $marker) {
        if (! str_contains($sync, $marker)) {
            $errors[] = "dependency review provenance synchronization missing [{$marker}]";
        }
    }

    $provider = $read('app/Providers/NexoraServiceProvider.php');
    if (! str_contains($provider, 'RuntimeDependencyReviewSyncCommand::class')) {
        $errors[] = 'dependency review sync command is not registered';
    }

    $reconciler = $read('app/Nexora/Foundation/Runtime/DependencyDeploymentReconciler.php');
    foreach ([
        'review-promotion-required',
        'review-sync-required',
        'dependency-review-sync',
    ] as $marker) {
        if (! str_contains($reconciler, $marker)) {
            $errors[] = "dependency status workflow missing [{$marker}]";
        }
    }

    $humanReadableFiles = [
        'app/Nexora/Foundation/Runtime/ReviewedDependencyState.php',
        'app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php',
        'app/Nexora/Foundation/Runtime/DependencyReviewSynchronizer.php',
        'app/Console/Commands/Nexora/RuntimeDependencyReviewSyncCommand.php',
        'app/Console/Commands/Nexora/InstallerDoctorCommand.php',
        'app/Nexora/Installation/Installer.php',
        'app/Nexora/Cloud/Services/RuntimeVersionGuard.php',
    ];

    foreach ($humanReadableFiles as $file) {
        $content = $read($file);
        if (nexoraV47LongestLine($content) > 180) {
            $errors[] = "v4.7 critical dependency trust file exceeds readable line length [{$file}]";
        }

        if (preg_match('/;[ \t]*\$[A-Za-z_][A-Za-z0-9_]*[ \t]*=/', $content) === 1) {
            $errors[] = "v4.7 critical dependency trust file contains generated-looking multi-statement lines [{$file}]";
        }
    }

    $c4 = $read('scripts/n1-c4-evidence-prepare.php');
    foreach ([
        'fresh_install_missing_review_bootstrap_verified',
        'fresh_install_bootstrap_requires_exact_lockfiles',
        'fresh_install_running_laravel_matches_lock',
        'fresh_install_installed_composer_runtime_matches_lock',
        'fresh_install_package_manifest_lock_match_verified',
        'fresh_install_corrupt_review_rejected',
        'fresh_install_bootstrap_receipt_verified',
        'bootstrap_install_runtime_identity_accepted_without_review_503',
        'reviewed_dependency_provenance_sync_verified',
        'review_sync_generation_unchanged',
        'installer_doctor_dependency_trust_preflight_verified',
        'installation_lock_retry_after_missing_review_succeeds',
    ] as $marker) {
        if (! str_contains($c4, $marker)) {
            $errors[] = "C4 fresh-install dependency trust rehearsal missing [{$marker}]";
        }
    }

    return [
        'errors' => $errors,
        'warnings' => $warnings,
        'metrics' => [
            'fresh_install_trust_modes' => 2,
            'runtime_review_state_separation' => 1,
            'composer_runtime_lock_match' => 1,
            'npm_manifest_lock_match' => 1,
            'review_sync_workflow' => 1,
            'automatic_human_review_fabrication' => 0,
            'human_readable_files' => count($humanReadableFiles),
        ],
    ];
}


function nexoraV47LongestLine(string $content): int
{
    $longest = 0;
    foreach (preg_split('/\R/', $content) ?: [] as $line) {
        $longest = max($longest, strlen($line));
    }

    return $longest;
}
