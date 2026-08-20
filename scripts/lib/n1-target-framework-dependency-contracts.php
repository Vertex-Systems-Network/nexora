<?php

declare(strict_types=1);

/**
 * Source-only v4.4 contract checks.
 *
 * These checks protect the dependency transition path that lets reviewed
 * Laravel 13.x lock updates move to a new deployment generation without
 * weakening the immutable dependency hashes that already protect Nexora.
 *
 * @return array{
 *     errors:list<string>,
 *     warnings:list<string>,
 *     metrics:array<string,int>
 * }
 */
function nexoraAnalyzeFrameworkDependencyContracts(string $root): array
{
    $errors = [];
    $warnings = [];

    $read = static function (string $relative) use ($root): string {
        $path = $root.'/'.$relative;

        return is_file($path) ? (string) file_get_contents($path) : '';
    };

    $requiredFiles = [
        'config/nexora-framework.php',
        'app/Nexora/Foundation/Runtime/FrameworkCompatibility.php',
        'app/Nexora/Foundation/Runtime/ReviewedDependencyState.php',
        'app/Nexora/Foundation/Runtime/DependencyDeploymentReconciler.php',
        'app/Console/Commands/Nexora/RuntimeCompatibilityStatusCommand.php',
        'app/Console/Commands/Nexora/RuntimeDependencyStatusCommand.php',
        'app/Console/Commands/Nexora/RuntimeDependencyReconcileCommand.php',
    ];

    foreach ($requiredFiles as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "framework/dependency artifact missing [{$file}]";
        }
    }

    $composer = decodeJsonFile($root.'/composer.json', $errors, 'composer.json');
    $constraint = $composer['require']['laravel/framework'] ?? null;
    if ($constraint !== '^13.24') {
        $errors[] = 'composer.json must allow reviewed Laravel 13.24+ updates with ^13.24.';
    }

    $policy = $read('config/nexora-framework.php');
    foreach ([
        "'minimum' => '13.24.0'",
        "'maximum_exclusive' => '14.0.0'",
        "'composer_constraint' => '^13.24'",
        "'require_reviewed_locks' => true",
        "'require_maintenance_mode' => true",
    ] as $marker) {
        if (! str_contains($policy, $marker)) {
            $errors[] = "framework compatibility policy missing [{$marker}]";
        }
    }

    $framework = $read('app/Nexora/Foundation/Runtime/FrameworkCompatibility.php');
    foreach ([
        'Application::VERSION',
        'version_compare',
        'maximum_exclusive',
        'manifest_constraint_matches_policy',
        'assertCompatible',
    ] as $marker) {
        if (! str_contains($framework, $marker)) {
            $errors[] = "framework runtime compatibility check missing [{$marker}]";
        }
    }

    $reviewed = $read('app/Nexora/Foundation/Runtime/ReviewedDependencyState.php');
    foreach ([
        'reviewed-locks.json',
        'composer_lock_sha256',
        'package_lock_sha256',
        'laravel_framework_locked_version',
        'lockedLaravelVersion',
    ] as $marker) {
        if (! str_contains($reviewed, $marker)) {
            $errors[] = "reviewed dependency state missing [{$marker}]";
        }
    }

    $deployment = $read('app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');
    foreach ([
        'candidateForInstalledSource',
        'installedDriftAssessment',
        'generation_matches_declared',
        'composer_lock_sha256',
        'package_lock_sha256',
        'framework_policy_sha256',
    ] as $marker) {
        if (! str_contains($deployment, $marker)) {
            $errors[] = "deployment reconciliation identity missing [{$marker}]";
        }
    }

    if (str_contains(
        $deployment,
        "throw new RuntimeException('Installed deployment generation does not match local runtime materials.')",
    )) {
        $errors[] = 'installed dependency-generation drift must be diagnosable, not thrown as an opaque identity exception.';
    }

    $reconciler = $read('app/Nexora/Foundation/Runtime/DependencyDeploymentReconciler.php');
    foreach ([
        'assertMaintenanceMode',
        'assertReviewedDependencies',
        'assertInstalledFrameworkMatchesLock',
        'assertDependencyOnlyDrift',
        "reason: 'dependency-reconcile'",
        "runArtisanOrFail('queue:restart')",
        'runtime-dependency-transition.json',
        'maintenance_mode_remains_enabled',
    ] as $marker) {
        if (! str_contains($reconciler, $marker)) {
            $errors[] = "safe dependency reconciliation missing [{$marker}]";
        }
    }

    if (str_contains($reconciler, "runArtisanOrFail('up')")) {
        $errors[] = 'dependency reconciliation must never restore traffic automatically.';
    }

    $middleware = $read('app/Http/Middleware/RuntimeNodeHeartbeat.php');
    foreach ([
        'X-Nexora-Compatibility-Mismatch',
        'nexora:runtime:compatibility-status --deep',
        'nexora:runtime:dependency-reconcile',
    ] as $marker) {
        if (! str_contains($middleware, $marker)) {
            $errors[] = "runtime compatibility response missing actionable diagnostic [{$marker}]";
        }
    }

    $provider = $read('app/Providers/NexoraServiceProvider.php');
    foreach ([
        'RuntimeCompatibilityStatusCommand::class',
        'RuntimeDependencyStatusCommand::class',
        'RuntimeDependencyReconcileCommand::class',
    ] as $marker) {
        if (! str_contains($provider, $marker)) {
            $errors[] = "Nexora service provider missing command registration [{$marker}]";
        }
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    $upgrade = $read('app/Nexora/Foundation/Upgrade/UpgradeManager.php');
    foreach ([
        'runtime_dependency_fingerprint',
        'laravel_framework_version',
        'composer_lock_sha256',
        'package_lock_sha256',
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "installer dependency lineage missing [{$marker}]";
        }
        if (! str_contains($upgrade, $marker)) {
            $errors[] = "upgrade dependency lineage missing [{$marker}]";
        }
    }

    $generation = $read('scripts/lib/deployment-generation.php');
    foreach ([
        'composer_lock_sha256',
        'package_lock_sha256',
        'framework_policy_sha256',
    ] as $marker) {
        if (! str_contains($generation, $marker)) {
            $errors[] = "deployment generation must retain dependency material [{$marker}]";
        }
    }

    $c2 = $read('scripts/n1-c2-laravel-runtime-certify.php');
    foreach ([
        "'runtime-dependency-status'",
        "'runtime-compatibility-status'",
    ] as $marker) {
        if (! str_contains($c2, $marker)) {
            $errors[] = "C2 framework/dependency gate missing [{$marker}]";
        }
    }

    $c4 = $read('scripts/n1-c4-evidence-prepare.php');
    foreach ([
        'laravel_13_minor_update_reviewed',
        'dependency_only_generation_drift_detected',
        'dependency_reconcile_requires_maintenance',
        'unreviewed_lock_rejected',
        'locked_framework_runtime_match_verified',
        'dependency_reconcile_activation_rotated',
        'dependency_reconcile_queue_restart_signaled',
        'dependency_reconcile_receipt_verified',
        'laravel_14_major_rejected',
        'compatibility_mismatch_diagnostics_verified',
    ] as $marker) {
        if (! str_contains($c4, $marker)) {
            $errors[] = "C4 dependency reconciliation rehearsal missing [{$marker}]";
        }
    }

    $c6 = $read('scripts/n1-c6-evidence-prepare.php');
    foreach ([
        'laravel_framework_version_consistency',
        'runtime_dependency_fingerprint_consistency',
        'dependency_review_status_pass',
        'laravel_framework_locked_version',
    ] as $marker) {
        if (! str_contains($c6, $marker)) {
            $errors[] = "C6 framework/dependency convergence missing [{$marker}]";
        }
    }

    $builder = $read('scripts/build-production-release.php');
    foreach ([
        'frameworkPolicyHash',
        'framework_policy_sha256',
        'framework_dependency_contract',
        "'composer_constraint'=>'^13.24'",
    ] as $marker) {
        if (! str_contains($builder, $marker)) {
            $errors[] = "production release framework binding missing [{$marker}]";
        }
    }

    $humanReadableFiles = [
        'app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php',
        'app/Nexora/Cloud/Services/RuntimeVersionGuard.php',
        'app/Http/Middleware/RuntimeNodeHeartbeat.php',
        'app/Nexora/Foundation/Runtime/FrameworkCompatibility.php',
        'app/Nexora/Foundation/Runtime/ReviewedDependencyState.php',
        'app/Nexora/Foundation/Runtime/DependencyDeploymentReconciler.php',
        'app/Console/Commands/Nexora/RuntimeCompatibilityStatusCommand.php',
        'app/Console/Commands/Nexora/RuntimeDependencyStatusCommand.php',
        'app/Console/Commands/Nexora/RuntimeDependencyReconcileCommand.php',
        'app/Console/Commands/Nexora/RuntimeDeploymentStatusCommand.php',
        'app/Console/Commands/Nexora/UpgradeCutoverStatusCommand.php',
        'app/Console/Commands/Nexora/UpgradeLineageExportCommand.php',
        'scripts/dependency-lock-refresh.php',
        'scripts/dependency-lock-review.php',
        'scripts/release-provenance.php',
        'scripts/n1-c1-installed-dependency-verify.php',
        'scripts/n1-c4-evidence-prepare.php',
        'scripts/n1-c6-evidence-prepare.php',
    ];

    foreach ($humanReadableFiles as $file) {
        $content = $read($file);
        $line = longestLine($content);
        if ($line > 180) {
            $errors[] = "v4.4 critical file is not human-readable enough [{$file}; max line {$line}]";
        }

        if (preg_match('/;[ \t]*\$[A-Za-z_][A-Za-z0-9_]*[ \t]*=/', $content) === 1) {
            $errors[] = "v4.4 critical file contains generated-looking multi-statement chaining [{$file}]";
        }
    }

    return [
        'errors' => $errors,
        'warnings' => $warnings,
        'metrics' => [
            'laravel_major' => 13,
            'laravel_minimum_minor' => 24,
            'dependency_reconcile' => 1,
            'human_readable_critical_files' => count($humanReadableFiles),
            'automatic_framework_update' => 0,
            'automatic_traffic_restore' => 0,
        ],
    ];
}

/** @param list<string> $errors @return array<string, mixed> */
function decodeJsonFile(string $path, array &$errors, string $label): array
{
    try {
        $decoded = json_decode(
            (string) file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    } catch (Throwable $exception) {
        $errors[] = "{$label} invalid: {$exception->getMessage()}";

        return [];
    }

    return is_array($decoded) ? $decoded : [];
}

function longestLine(string $content): int
{
    $maximum = 0;
    foreach (preg_split('/\R/', $content) ?: [] as $line) {
        $maximum = max($maximum, strlen($line));
    }

    return $maximum;
}
