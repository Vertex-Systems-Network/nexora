<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeInstallRuntimeHandoffContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php',
        'app/Nexora/Cloud/Services/RuntimeVersionGuard.php',
        'app/Nexora/Installation/RuntimeInstallationReadiness.php',
        'app/Nexora/Installation/RuntimePostInstallHandoff.php',
        'app/Console/Commands/Nexora/RuntimePostInstallStatusCommand.php',
        'tests/Architecture/N100V57InstallRuntimeHandoffArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.7 install/runtime handoff artifact missing [{$file}]";
        }
    }

    $version = require $root.'/config/nexora.php';
    if (version_compare((string) ($version['version'] ?? '0.0.0'), '1.0.0-rc.72', '<')) {
        $errors[] = 'v5.7 install/runtime handoff lineage requires platform version 1.0.0-rc.72 or newer';
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    foreach ([
        "public const PROTOCOL = 'v5.",
        "public const SOURCE_GENERATION = 'n1-v5.",
        'deployment_source_attestation_status',
        'release_source_tree_sha256',
        'Runtime handoff',
        'post_install_identity_finalized',
        "'handoff'",
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "v5.7 installer handoff boundary missing [{$marker}]";
        }
    }

    $mark = strpos($installer, '$this->state->markInstalled([');
    if ($mark === false || ! str_contains($installer, 'next fresh handoff request before login')) {
        $errors[] = 'post-install runtime handoff must be deferred until a fresh request after the sealed installation commit point';
    }


    $controller = $read('app/Http/Controllers/Install/InstallerController.php');
    foreach ([
        'public function runtimeHandoff()',
        '$this->postInstallHandoff->verifyAndRecord();',
        'fresh HTTP request after the',
        "'committed' => true",
        "'recovery_url' => route('install.runtime.handoff')",
    ] as $marker) {
        if (! str_contains($controller, $marker)) {
            $errors[] = "fresh-request post-install handoff boundary missing [{$marker}]";
        }
    }

    $deployment = $read('app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');
    foreach ([
        "public const RUNTIME_SOURCE_GENERATION = 'n1-v5.",
        'currentSourceTreeSha256',
        "require_once base_path('scripts/lib/source-attestation.php')",
        'nexoraComputeSourceAttestation',
        'nexoraDeploymentMaterialsFromRoot(base_path(), $sourceTree)',
    ] as $marker) {
        if (! str_contains($deployment, $marker)) {
            $errors[] = "full source-tree deployment binding missing [{$marker}]";
        }
    }

    $readiness = $read('app/Nexora/Installation/RuntimeInstallationReadiness.php');
    foreach ([
        'RuntimeDeploymentIdentity',
        '$deployment = $this->deployment->deepVerify();',
        "'deployment' => \$this->component(",
        "'deployment' => \$deployment,",
    ] as $marker) {
        if (! str_contains($readiness, $marker)) {
            $errors[] = "installer full-source readiness component missing [{$marker}]";
        }
    }

    $guard = $read('app/Nexora/Cloud/Services/RuntimeVersionGuard.php');
    foreach (['host', 'resource', 'policy', 'process'] as $plane) {
        $pattern = "'{$plane}' => \$this->requiredFingerprintCompatible(";
        if (! str_contains($guard, $pattern)) {
            $errors[] = "runtime admission must compare {$plane} identity without requiring strict C2/C6 health status";
        }
    }
    foreach (['service', 'dependencies'] as $plane) {
        $pattern = "'{$plane}' => \$this->requiredHealthyFingerprintCompatible(";
        if (! str_contains($guard, $pattern)) {
            $errors[] = "runtime admission must remain fail-closed for {$plane} health";
        }
    }

    $post = $read('app/Nexora/Installation/RuntimePostInstallHandoff.php');
    foreach ([
        'forgetMemoizedIdentity',
        'adoptCurrentEpochForProcess',
        'deepVerify',
        '$this->versions->assess()',
        'installation_lock_sha256',
        'source_tree_sha256',
        'deployment_generation',
        'runtime_activation_fingerprint',
        'post-install-handoff.json',
        'receiptMatchesCurrent',
        'finalizeCommittedRuntimeIdentity',
        "'post_install_identity_finalized'",
        "\$allowed = ['environment', 'activation', 'service', 'process']",
        'immutable runtime identity changed',
    ] as $marker) {
        if (! str_contains($post, $marker)) {
            $errors[] = "post-install handoff proof missing [{$marker}]";
        }
    }

    $provider = $read('app/Providers/NexoraServiceProvider.php');
    if (! str_contains($provider, 'RuntimePostInstallStatusCommand::class')) {
        $errors[] = 'post-install runtime status command is not registered';
    }

    $manifestPath = $root.'/bootstrap/nexora-source-manifest.json';
    $manifest = is_file($manifestPath)
        ? json_decode((string) file_get_contents($manifestPath), true)
        : null;
    $manifestFiles = is_array($manifest) && is_array($manifest['files'] ?? null)
        ? $manifest['files']
        : [];
    foreach ([
        'app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php',
        'app/Nexora/Cloud/Services/RuntimeVersionGuard.php',
        'app/Nexora/Installation/RuntimePostInstallHandoff.php',
        'app/Console/Commands/Nexora/RuntimePostInstallStatusCommand.php',
    ] as $file) {
        if (! array_key_exists($file, $manifestFiles)) {
            $errors[] = "v5.7 critical source manifest does not seal runtime handoff source [{$file}]";
        }
    }

    $sourceActivation = $read('app/Nexora/Installation/SourceActivationIdentity.php');
    foreach ([
        'RuntimeDeploymentIdentity::class',
        'RuntimeVersionGuard::class',
        'RuntimePostInstallHandoff::class',
        'RuntimePostInstallStatusCommand::class',
    ] as $marker) {
        if (! str_contains($sourceActivation, $marker)) {
            $errors[] = "v5.7 loaded runtime convergence does not include [{$marker}]";
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'readiness_components_minimum' => 8,
            'source_tree_bound_to_deployment' => 1,
            'post_install_handoff_receipt' => 1,
            'strict_certification_planes_decoupled_from_request_admission' => 4,
            'one_time_post_install_identity_finalization' => 1,
            'target_denominator_change' => 0,
        ],
    ];
}
