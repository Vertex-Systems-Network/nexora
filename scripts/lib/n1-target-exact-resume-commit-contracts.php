<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeExactResumeCommitContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Installation/InstallationResumeIdentity.php',
        'app/Nexora/Installation/InstallationRunControl.php',
        'app/Nexora/Installation/Installer.php',
        'app/Nexora/Installation/RuntimePostInstallHandoff.php',
        'app/Console/Commands/Nexora/RuntimePostInstallReconcileCommand.php',
        'app/Http/Controllers/Install/InstallerController.php',
        'resources/views/install/runtime-handoff.blade.php',
        'tests/Architecture/N100V59ExactResumeCommitArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.9 exact-resume/commit artifact missing [{$file}]";
        }
    }

    $version = require $root.'/config/nexora.php';
    if (version_compare((string) ($version['version'] ?? '0.0.0'), '1.0.0-rc.74', '<')) {
        $errors[] = 'v5.9 exact-resume/commit lineage requires platform version 1.0.0-rc.74 or newer';
    }

    $resume = $read('app/Nexora/Installation/InstallationResumeIdentity.php');
    foreach ([
        "'schema' => 2",
        'nexoraComputeSourceAttestation',
        'source_tree_sha256',
        'source_tree_file_count',
        'critical_source_manifest_sha256',
        'critical_source_file_count',
        'source_generation',
        'migrations_sha256',
        'core_seeders_sha256',
        'composer_lock_sha256',
        'package_lock_sha256',
    ] as $marker) {
        if (! str_contains($resume, $marker)) {
            $errors[] = "exact resume provenance missing [{$marker}]";
        }
    }

    $control = $read('app/Nexora/Installation/InstallationRunControl.php');
    foreach ([
        "'source_tree_sha256' =>",
        "'critical_source_manifest_sha256' =>",
        "'current_source_generation'",
        'exact full source tree',
    ] as $marker) {
        if (! str_contains($control, $marker)) {
            $errors[] = "run-control exact resume lineage missing [{$marker}]";
        }
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    if (preg_match("/public const PROTOCOL = 'v5\.(\d+)'/", $installer, $protocolMatch) !== 1
        || (int) $protocolMatch[1] < 9) {
        $errors[] = 'exact resume/commit lineage requires installer protocol v5.9 or newer';
    }
    if (preg_match("/public const SOURCE_GENERATION = 'n1-v5\.(\d+)'/", $installer, $generationMatch) !== 1
        || (int) $generationMatch[1] < 9
        || ($protocolMatch[1] ?? null) !== ($generationMatch[1] ?? null)) {
        $errors[] = 'exact resume/commit lineage requires matching source generation n1-v5.9 or newer';
    }
    foreach ([
        '$finalDependencyTrust = $this->installDependencyTrust->resolve();',
        'assertDependencySnapshotStable',
        'assertCommitSnapshotStable',
        'installation_commit_snapshot_status',
        'installation_preflight_source_tree_sha256',
        'installation_preflight_dependency_fingerprint',
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "commit snapshot stability missing [{$marker}]";
        }
    }

    $finalResolve = strpos($installer, '$finalDependencyTrust = $this->installDependencyTrust->resolve();');
    $metadataBuild = strpos($installer, '$runtimeMetadata = $this->buildRuntimeInstallationMetadata(');
    $markInstalled = strpos($installer, '$this->state->markInstalled([');
    if ($finalResolve === false || $metadataBuild === false || $markInstalled === false || $metadataBuild >= $markInstalled) {
        $errors[] = 'fresh dependency trust must be re-resolved inside final metadata construction before installed.lock publication';
    }

    $handoff = $read('app/Nexora/Installation/RuntimePostInstallHandoff.php');
    foreach ([
        "'receipt-refresh-required'",
        "'ready' => \$receiptCurrent",
        "'runtime_ready' => true",
        'reconcileReceipt',
        'post-install-reconcile --confirm=RECONCILE',
    ] as $marker) {
        if (! str_contains($handoff, $marker)) {
            $errors[] = "sealed handoff recovery missing [{$marker}]";
        }
    }

    $controller = $read('app/Http/Controllers/Install/InstallerController.php');
    foreach ([
        "'committed-runtime-pending'",
        "'committed' => true",
        "'runtime_handoff_ready' => false",
        "route('install.runtime.handoff')",
        "['id' => 'runtime-readiness'",
        "['id' => 'handoff'",
        'RuntimePostInstallHandoff',
    ] as $marker) {
        if (! str_contains($controller, $marker)) {
            $errors[] = "post-commit controller recovery missing [{$marker}]";
        }
    }
    if (str_contains($controller, "'committed' => true,\n                            'runtime_handoff_ready' => false,\n                            'progress' => 100,\n                            'redirect' => route('login')")) {
        $errors[] = 'committed-but-unready runtime must never redirect directly to login';
    }

    $view = $read('resources/views/install/index.blade.php');
    foreach (['runtime handoff pending', 'e.committed', 'runtime ${e.runtime_classes_matched', 'Do not retry installation'] as $marker) {
        if (! str_contains($view, $marker)) {
            $errors[] = "installer progress/recovery UI missing [{$marker}]";
        }
    }

    $provider = $read('app/Providers/NexoraServiceProvider.php');
    if (! str_contains($provider, 'RuntimePostInstallReconcileCommand::class')) {
        $errors[] = 'post-install reconcile command is not registered';
    }

    $progress = $read('scripts/lib/n1-target-progress.php');
    foreach (['return [', "'c1'", "'c2'", "'c3'", "'c4'", "'c5'", "'c6'"] as $marker) {
        if (! str_contains($progress, $marker)) {
            $errors[] = 'granular target progress contract changed unexpectedly';
            break;
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'resume_provenance_schema' => 2,
            'commit_snapshot_domains' => 2,
            'post_commit_login_redirect_on_handoff_failure' => 0,
            'target_denominator_change' => 0,
        ],
    ];
}
