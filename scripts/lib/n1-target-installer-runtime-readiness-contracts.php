<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeInstallerRuntimeReadinessContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Installation/RuntimeInstallationReadiness.php',
        'app/Console/Commands/Nexora/RuntimeInstallReadinessCommand.php',
        'app/Nexora/Cloud/Services/RuntimeResourceEnvelopeIdentity.php',
        'app/Nexora/Cloud/Services/RuntimePolicyPlaneIdentity.php',
        'app/Nexora/Cloud/Services/RuntimeProcessPlane.php',
        'app/Nexora/Cloud/Services/RuntimeActivationIdentity.php',
        'tests/Architecture/N100V56InstallerRuntimeReadinessArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.6 installer runtime-readiness artifact missing [{$file}]";
        }
    }

    $version = is_file($root.'/config/nexora.php') ? require $root.'/config/nexora.php' : [];
    if (version_compare((string) ($version['version'] ?? '0.0.0'), '1.0.0-rc.71', '<')) {
        $errors[] = 'v5.6 runtime-readiness lineage requires platform version 1.0.0-rc.71 or newer';
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    foreach ([
        "public const PROTOCOL = '",
        "public const SOURCE_GENERATION = 'n1-v5.",
        'Runtime readiness preflight',
        '$this->installationReadiness->assertReady($dependencyTrust)',
        'destructive reset, migration or seeding',
        'resource_installation_status',
        'policy_installation_status',
        'process_installation_status',
        'activation_installation_status',
        "['preflight', 'database', 'host-clock', 'runtime-readiness', 'backup']",
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "v5.6 installer readiness boundary missing [{$marker}]";
        }
    }

    $preflight = strpos($installer, "'Runtime readiness preflight'");
    $wipe = strpos($installer, '$this->database->wipe(');
    $migrate = strpos($installer, "Artisan::call('migrate'");
    if ($preflight === false || $wipe === false || $migrate === false || ! ($preflight < $wipe && $preflight < $migrate)) {
        $errors[] = 'installer runtime-readiness preflight must run before destructive reset and migrations';
    }

    if (str_contains($installer, "attestation failed before installation lock")) {
        $errors[] = 'generic late-only runtime attestation failure must not remain in Installer';
    }

    $runControl = $read('app/Nexora/Installation/InstallationRunControl.php');
    if (! str_contains($runControl, "['starting', 'preflight', 'database', 'host-clock', 'runtime-readiness', 'backup']")) {
        $errors[] = 'runtime-readiness must remain a cancellable pre-mutation installation stage';
    }

    $readiness = $read('app/Nexora/Installation/RuntimeInstallationReadiness.php');
    foreach ([
        "public const RUNTIME_SOURCE_GENERATION = 'n1-v5.",
        '$this->deployment->forgetMemoizedIdentity();',
        "'source' =>",
        "'dependencies' =>",
        "'host_clock' =>",
        "'resources' =>",
        "'policy' =>",
        "'processes' =>",
        "'activation' =>",
        'components_passed',
        'components_total',
        'blocking_reasons',
        'nexora:runtime:install-readiness --json',
    ] as $marker) {
        if (! str_contains($readiness, $marker)) {
            $errors[] = "v5.6 aggregated installer readiness missing [{$marker}]";
        }
    }

    $deploymentIdentity = $read('app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');
    foreach ([
        'source_fallback_identity_refreshed',
        'source re-attestation failed',
        "=== 'source-fallback'",
        '$this->forgetMemoizedIdentity();',
        'initial_source_tree_sha256',
    ] as $marker) {
        if (! str_contains($deploymentIdentity, $marker)) {
            $errors[] = "installer/browser deployment identity convergence missing [{$marker}]";
        }
    }

    foreach ([
        'app/Nexora/Cloud/Services/RuntimeResourceEnvelopeIdentity.php',
        'app/Nexora/Cloud/Services/RuntimePolicyPlaneIdentity.php',
        'app/Nexora/Cloud/Services/RuntimeProcessPlane.php',
        'app/Nexora/Cloud/Services/RuntimeActivationIdentity.php',
    ] as $file) {
        $source = $read($file);
        foreach ([
            "public const RUNTIME_SOURCE_GENERATION = 'n1-v5.",
            'function installationAttestation()',
            'installation_status',
            'installation_blocking_reasons',
            'installation_warnings',
        ] as $marker) {
            if (! str_contains($source, $marker)) {
                $errors[] = "v5.6 installer-safe split missing [{$file}: {$marker}]";
            }
        }
    }

    $resourceConfig = $read('config/nexora-resource-runtime.php');
    foreach ([
        'installation_minimum_memory_headroom_bytes',
        'installation_minimum_temp_free_bytes',
        'installation_minimum_storage_free_bytes',
        'installation_minimum_bootstrap_free_bytes',
    ] as $marker) {
        if (! str_contains($resourceConfig, $marker)) {
            $errors[] = "v5.6 installer resource policy missing [{$marker}]";
        }
    }

    $manifestPath = $root.'/bootstrap/nexora-source-manifest.json';
    $manifest = [];
    if (is_file($manifestPath)) {
        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 128, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $manifest = [];
        }
    }
    $manifestFiles = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
    foreach ([
        'app/Nexora/Cloud/Services/RuntimeResourceEnvelopeIdentity.php',
        'app/Nexora/Cloud/Services/RuntimePolicyPlaneIdentity.php',
        'app/Nexora/Cloud/Services/RuntimeProcessPlane.php',
        'app/Nexora/Cloud/Services/RuntimeActivationIdentity.php',
        'app/Nexora/Installation/RuntimeInstallationReadiness.php',
        'app/Console/Commands/Nexora/RuntimeInstallReadinessCommand.php',
    ] as $file) {
        if (! array_key_exists($file, $manifestFiles)) {
            $errors[] = "v5.6 critical source manifest must seal readiness source [{$file}]";
        }
    }
    if (count($manifestFiles) < 30) {
        $errors[] = 'v5.6 critical source manifest must protect at least 30 installer/runtime readiness files';
    }

    $command = $read('app/Console/Commands/Nexora/RuntimeInstallReadinessCommand.php');
    foreach ([
        'nexora:runtime:install-readiness',
        '--assert-ready',
        '--json',
        'Installation readiness:',
    ] as $marker) {
        if (! str_contains($command, $marker)) {
            $errors[] = "v5.6 readiness diagnostics command missing [{$marker}]";
        }
    }

    $provider = $read('app/Providers/NexoraServiceProvider.php');
    if (! str_contains($provider, 'RuntimeInstallReadinessCommand::class')) {
        $errors[] = 'v5.6 readiness command is not registered';
    }

    $sourceIdentity = $read('app/Nexora/Installation/SourceActivationIdentity.php');
    foreach ([
        '\\App\\Nexora\\Cloud\\Services\\RuntimeResourceEnvelopeIdentity::class',
        '\\App\\Nexora\\Cloud\\Services\\RuntimePolicyPlaneIdentity::class',
        '\\App\\Nexora\\Cloud\\Services\\RuntimeProcessPlane::class',
        '\\App\\Nexora\\Cloud\\Services\\RuntimeActivationIdentity::class',
        '\\App\\Nexora\\Installation\\RuntimeInstallationReadiness::class',
        '\\App\\Console\\Commands\\Nexora\\RuntimeInstallReadinessCommand::class',
    ] as $marker) {
        if (! str_contains($sourceIdentity, $marker)) {
            $errors[] = "v5.6 runtime convergence missing critical readiness class [{$marker}]";
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'readiness_components_minimum' => 7,
            'installer_safe_runtime_profiles' => 5,
            'critical_source_files_minimum' => 30,
            'critical_runtime_classes_minimum' => 28,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
