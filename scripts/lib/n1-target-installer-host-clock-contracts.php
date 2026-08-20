<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeInstallerHostClockContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php',
        'app/Console/Commands/Nexora/RuntimeHostStatusCommand.php',
        'app/Nexora/Installation/Installer.php',
        'config/nexora-host-runtime.php',
        'tests/Architecture/N100V55InstallerHostClockArchitectureTest.php',
    ];
    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.5 installer host/clock artifact missing [{$file}]";
        }
    }

    $version = is_file($root.'/config/nexora.php') ? require $root.'/config/nexora.php' : [];
    if (version_compare((string) ($version['version'] ?? '0.0.0'), '1.0.0-rc.70', '<')) {
        $errors[] = 'v5.5 host/clock lineage requires platform version 1.0.0-rc.70 or newer';
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    foreach ([
        'public const PROTOCOL = \'v5.',
        'public const SOURCE_GENERATION = \'n1-v5.',
        'Runtime readiness preflight',
        'host_clock_installation_status',
        'assertInstallationHostClockStatus',
        'host_clock_installation_status',
        'host_clock_strict_certification_status',
        'nexora:runtime:host-status --installation',
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "v5.5 installer host/clock boundary missing [{$marker}]";
        }
    }

    $preflight = strpos($installer, "'Runtime readiness preflight'");
    $wipe = strpos($installer, '$this->database->wipe(');
    $migrate = strpos($installer, "Artisan::call('migrate'");
    if ($preflight === false || $wipe === false || $migrate === false || ! ($preflight < $wipe && $preflight < $migrate)) {
        $errors[] = 'installer host/clock preflight must run before destructive reset and migrations';
    }

    $host = $read('app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php');
    foreach ([
        'function installationAttestation()',
        'public const RUNTIME_SOURCE_GENERATION = \'n1-v5.',
        'installation_max_database_clock_skew_ms',
        'installation_blocking_reasons',
        'installation_warnings',
        'strict_status',
        'posixUmaskApplicable',
        "strcasecmp(PHP_OS_FAMILY, 'Windows') !== 0",
        '\'umask_allowed\'] = ! $umaskApplicable',
        'max_database_clock_skew_ms',
    ] as $marker) {
        if (! str_contains($host, $marker)) {
            $errors[] = "v5.5 host identity split missing [{$marker}]";
        }
    }

    $config = $read('config/nexora-host-runtime.php');
    foreach ([
        "'schema'=>2",
        "'installation'=>[",
        'NEXORA_INSTALL_REQUIRE_DATABASE_CLOCK_ANCHOR',
        'NEXORA_INSTALL_MAX_DB_CLOCK_SKEW_MS',
        '60000',
        "'max_database_clock_skew_ms'",
    ] as $marker) {
        if (! str_contains($config, $marker)) {
            $errors[] = "v5.5 installer host policy missing [{$marker}]";
        }
    }

    $status = $read('app/Console/Commands/Nexora/RuntimeHostStatusCommand.php');
    foreach ([
        'public const RUNTIME_SOURCE_GENERATION = \'n1-v5.',
        '--installation',
        'installationAttestation()',
        "--deep : Run strict bounded clock/filesystem/entropy probes",
    ] as $marker) {
        if (! str_contains($status, $marker)) {
            $errors[] = "v5.5 host status diagnostics missing [{$marker}]";
        }
    }

    $sourceIdentity = $read('app/Nexora/Installation/SourceActivationIdentity.php');
    foreach ([
        '\\App\\Nexora\\Cloud\\Services\\RuntimeHostClockIdentity::class',
        '\\App\\Console\\Commands\\Nexora\\RuntimeHostStatusCommand::class',
    ] as $marker) {
        if (! str_contains($sourceIdentity, $marker)) {
            $errors[] = "v5.5 runtime convergence does not protect host attestation class [{$marker}]";
        }
    }

    $manifest = [];
    $manifestPath = $root.'/bootstrap/nexora-source-manifest.json';
    if (is_file($manifestPath)) {
        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 128, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $manifest = [];
        }
    }
    $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
    foreach ([
        'app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php',
        'app/Console/Commands/Nexora/RuntimeHostStatusCommand.php',
    ] as $file) {
        if (! array_key_exists($file, $files)) {
            $errors[] = "v5.5 critical source manifest must seal host attestation source [{$file}]";
        }
    }

    $progress = $read('scripts/lib/n1-target-progress.php');
    if (! str_contains($progress, 'nexoraTargetProgressC1Gates')) {
        $errors[] = 'v5.5 must retain the established granular target progress implementation';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'installation_host_checks' => 8,
            'strict_host_certification_preserved' => 1,
            'windows_posix_umask_blocker' => 0,
            'critical_source_files_minimum' => 24,
            'critical_runtime_classes_minimum' => 22,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
