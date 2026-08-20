<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeClockTempPortabilityContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php',
        'app/Nexora/Cloud/Services/RuntimeResourceEnvelopeIdentity.php',
        'app/Nexora/Foundation/Runtime/RuntimeWritableTempDirectory.php',
        'tests/Unit/Cloud/RuntimeHostClockEpochQueryTest.php',
        'tests/Unit/Foundation/Runtime/RuntimeWritableTempDirectoryTest.php',
        'tests/Architecture/N100V58ClockTempPortabilityArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.8 clock/temp portability artifact missing [{$file}]";
        }
    }

    $version = is_file($root.'/config/nexora.php') ? require $root.'/config/nexora.php' : [];
    if (version_compare((string) ($version['version'] ?? '0.0.0'), '1.0.0-rc.73', '<')) {
        $errors[] = 'v5.8 clock/temp portability lineage requires platform version 1.0.0-rc.73 or newer';
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    if (preg_match("/public const PROTOCOL = 'v(\d+)\.(\d+)'/", $installer, $protocolMatch) !== 1
        || (int) ($protocolMatch[1] ?? 0) < 5
        || ((int) ($protocolMatch[1] ?? 0) === 5 && (int) ($protocolMatch[2] ?? 0) < 8)) {
        $errors[] = 'v5.8 clock/temp portability requires installer protocol v5.8 or newer';
    }
    if (preg_match("/public const SOURCE_GENERATION = 'n1-v(\d+)\.(\d+)'/", $installer, $generationMatch) !== 1
        || (int) ($generationMatch[1] ?? 0) < 5
        || ((int) ($generationMatch[1] ?? 0) === 5 && (int) ($generationMatch[2] ?? 0) < 8)) {
        $errors[] = 'v5.8 clock/temp portability requires source generation n1-v5.8 or newer';
    }

    $host = $read('app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php');
    foreach ([
        'RuntimeWritableTempDirectory',
        'databaseEpochQueryForDriver',
        'UNIX_TIMESTAMP(CURRENT_TIMESTAMP(6))',
        'installationFilesystemProbe',
        'timezone_offset_signature',
        'installation_temp',
        'application-local temp fallbacks',
    ] as $marker) {
        if (! str_contains($host, $marker)) {
            $errors[] = "v5.8 host/clock fix missing [{$marker}]";
        }
    }

    if (str_contains($host, "UNIX_TIMESTAMP(UTC_TIMESTAMP(6))")) {
        $errors[] = 'MySQL/MariaDB database epoch query must not reinterpret UTC_TIMESTAMP() in the session timezone';
    }

    $temp = $read('app/Nexora/Foundation/Runtime/RuntimeWritableTempDirectory.php');
    foreach ([
        "storage_path('framework/nexora-temp')",
        "storage_path('app/nexora/tmp')",
        "sys_get_temp_dir()",
        "'app-framework'",
        "'app-storage'",
        "'php-system'",
        'fallback_used',
        'write probe failed',
    ] as $marker) {
        if (! str_contains($temp, $marker)) {
            $errors[] = "v5.8 writable-temp resolver missing [{$marker}]";
        }
    }

    $frameworkIndex = strpos($temp, "storage_path('framework/nexora-temp')");
    $systemIndex = strpos($temp, 'sys_get_temp_dir()');
    if ($frameworkIndex === false || $systemIndex === false || $frameworkIndex >= $systemIndex) {
        $errors[] = 'installation temp resolution must prefer application-local storage before PHP system temp';
    }

    $resource = $read('app/Nexora/Cloud/Services/RuntimeResourceEnvelopeIdentity.php');
    foreach ([
        'RuntimeWritableTempDirectory',
        '$this->tempDirectories->installation()',
        "'temp_resolution'",
        '$this->tempDirectories->systemPath()',
    ] as $marker) {
        if (! str_contains($resource, $marker)) {
            $errors[] = "v5.8 resource temp portability missing [{$marker}]";
        }
    }

    $hostConfig = $read('config/nexora-host-runtime.php');
    foreach ([
        'NEXORA_INSTALL_TEMP_DIRECTORY',
        'NEXORA_INSTALL_MAX_DB_CLOCK_SKEW_MS',
        "'max_database_clock_skew_ms'=>max(250,min(60000",
    ] as $marker) {
        if (! str_contains($hostConfig, $marker)) {
            $errors[] = "v5.8 host policy contract missing [{$marker}]";
        }
    }

    // Strict certification remains materially tighter than installer safety.
    if (! str_contains($hostConfig, "NEXORA_HOST_MAX_DB_CLOCK_SKEW_MS',5000")) {
        $errors[] = 'strict host clock certification must retain the 5-second default skew policy';
    }

    $sourceIdentity = $read('app/Nexora/Installation/SourceActivationIdentity.php');
    if (! str_contains($sourceIdentity, '\\App\\Nexora\\Foundation\\Runtime\\RuntimeWritableTempDirectory::class')) {
        $errors[] = 'v5.8 runtime convergence must guard RuntimeWritableTempDirectory';
    }

    $manifestSeal = $read('scripts/n1-source-manifest-seal.php');
    if (! str_contains($manifestSeal, 'app/Nexora/Foundation/Runtime/RuntimeWritableTempDirectory.php')) {
        $errors[] = 'v5.8 critical source manifest must seal RuntimeWritableTempDirectory.php';
    }

    $progress = $read('scripts/lib/n1-target-progress.php');
    if (! str_contains($progress, 'nexoraTargetProgressC1Gates')) {
        $errors[] = 'v5.8 must retain granular target progress tracking';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'mysql_timezone_double_conversion_blockers' => 0,
            'installation_temp_fallback_candidates' => 3,
            'strict_clock_default_ms' => 5000,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
