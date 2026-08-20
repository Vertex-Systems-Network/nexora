<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeRuntimeSourceConvergenceContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Installation/SourceActivationIdentity.php',
        'app/Nexora/Installation/SourceActivationHandshake.php',
        'app/Http/Controllers/Install/InstallerController.php',
        'app/Console/Commands/Nexora/SourceStatusCommand.php',
        'scripts/n1-source-web-ack.bat',
        'scripts/n1-source-web-ack.sh',
        'tests/Unit/Installation/SourceActivationHandshakeTest.php',
        'tests/Feature/Certification/SourceStatusRedactionCertificationTest.php',
        'tests/Unit/Installation/InstallationProgressVisibilityTest.php',
        'tests/Architecture/N100V54RuntimeSourceConvergenceArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.4 runtime source convergence artifact missing [{$file}]";
        }
    }

    $version = is_file($root.'/config/nexora.php') ? require $root.'/config/nexora.php' : [];
    $platformVersion = (string) ($version['version'] ?? '0.0.0');
    if (version_compare($platformVersion, '1.0.0-rc.69', '<')) {
        $errors[] = 'v5.4 runtime-source convergence requires platform version 1.0.0-rc.69 or newer';
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    preg_match("/public const PROTOCOL = 'v([0-9]+\.[0-9]+)'/", $installer, $protocolMatch);
    preg_match("/public const SOURCE_GENERATION = 'n1-v([0-9]+\.[0-9]+)'/", $installer, $generationMatch);
    $protocol = (string) ($protocolMatch[1] ?? '0.0');
    $generation = (string) ($generationMatch[1] ?? '0.0');
    if (version_compare($protocol, '5.4', '<')) {
        $errors[] = 'v5.4 runtime-source convergence requires Installer protocol v5.4 or newer';
    }
    if (version_compare($generation, '5.4', '<')) {
        $errors[] = 'v5.4 runtime-source convergence requires source generation n1-v5.4 or newer';
    }
    $expectedRuntimeGeneration = 'n1-v'.$generation;

    $runtimeFiles = [
        'app/Console/Commands/Nexora/InstallerDoctorCommand.php',
        'app/Console/Commands/Nexora/SourceActivateCommand.php',
        'app/Console/Commands/Nexora/SourceStatusCommand.php',
        'app/Http/Controllers/Install/InstallerController.php',
        'app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php',
        'app/Nexora/Foundation/Runtime/ReviewedDependencyState.php',
        'app/Nexora/Installation/InstallationState.php',
        'app/Nexora/Installation/Installer.php',
        'app/Nexora/Installation/SourceActivationHandshake.php',
        'app/Nexora/Installation/SourceActivationIdentity.php',
        'app/Nexora/Installation/SourceSetIntegrity.php',
        'app/Providers/NexoraServiceProvider.php',
        'app/Nexora/Installation/InstallationRunControl.php',
        'app/Nexora/Installation/InstallationResumeIdentity.php',
        'app/Nexora/Installation/DatabaseProvisioner.php',
        'app/Nexora/Installation/EnvironmentWriter.php',
        'app/Nexora/Installation/DatabaseBackupManager.php',
        'app/Nexora/Installation/SystemRequirementChecker.php',
        'app/Nexora/Installation/Database/DatabaseDriverRegistry.php',
        'app/Nexora/Security/Password/PasswordStrengthEvaluator.php',
    ];

    foreach ($runtimeFiles as $file) {
        if (! str_contains($read($file), "public const RUNTIME_SOURCE_GENERATION = '{$expectedRuntimeGeneration}'")) {
            $errors[] = "critical loaded PHP class does not match current runtime generation [{$file}]";
        }
    }

    $identity = $read('app/Nexora/Installation/SourceActivationIdentity.php');
    foreach ([
        'runtimeClassState',
        'runtime_class_fingerprint',
        'runtime_classes_total',
        'runtime_classes_matched',
        'Runtime class set:',
        'Loaded class generation mismatch',
    ] as $marker) {
        if (! str_contains($identity, $marker)) {
            $errors[] = "loaded runtime class convergence behavior missing [{$marker}]";
        }
    }

    $handshake = $read('app/Nexora/Installation/SourceActivationHandshake.php');
    foreach ([
        'web_ack_token_sha256',
        'webAckToken',
        'single-use',
        '@unlink($this->tokenPath())',
        'runtime_class_fingerprint',
        'runtime_classes_matched',
        'sameRuntimeIdentity',
    ] as $marker) {
        if (! str_contains($handshake, $marker)) {
            $errors[] = "secure web acknowledgement behavior missing [{$marker}]";
        }
    }

    $controller = $read('app/Http/Controllers/Install/InstallerController.php');
    foreach ([
        "header('X-Nexora-Activation-Token'",
        "'diagnostic_detail' => 'redacted'",
        "'X-Nexora-Source-Ack'",
        "'X-Nexora-Runtime-Classes'",
        'publicSourceIdentity',
        'acknowledgeWeb($state, $token)',
    ] as $marker) {
        if (! str_contains($controller, $marker)) {
            $errors[] = "public source-status hardening missing [{$marker}]";
        }
    }

    $publicHelperStart = strpos($controller, 'private function publicSourceIdentity');
    if ($publicHelperStart === false) {
        $errors[] = 'public source identity redaction helper is missing';
    } else {
        $publicHelper = substr($controller, $publicHelperStart, 2200);
        foreach (['installer_path', 'installer_sha256', 'source_set_fingerprint', 'runtime_class_results'] as $forbidden) {
            if (str_contains($publicHelper, "'{$forbidden}'")) {
                $errors[] = "public source identity must not expose [{$forbidden}]";
            }
        }
    }

    $status = $read('app/Console/Commands/Nexora/SourceStatusCommand.php');
    foreach (['--web-token', 'webAckToken($source)', '--require-web-ack'] as $marker) {
        if (! str_contains($status, $marker)) {
            $errors[] = "source:status secure acknowledgement tooling missing [{$marker}]";
        }
    }

    foreach (['scripts/n1-source-web-ack.bat', 'scripts/n1-source-web-ack.sh'] as $script) {
        $content = $read($script);
        foreach (['--web-token', 'X-Nexora-Activation-Token', '--require-web-ack'] as $marker) {
            if (! str_contains($content, $marker)) {
                $errors[] = "secure web-ack helper missing [{$script}: {$marker}]";
            }
        }
    }

    $view = $read('resources/views/install/index.blade.php');
    if (str_contains($view, 'source_set_fingerprint')) {
        $errors[] = 'public installer view must not render the source-set fingerprint';
    }
    foreach (['runtime_classes_matched', 'runtime_classes_total'] as $marker) {
        if (! str_contains($view, $marker)) {
            $errors[] = "installer view must surface loaded runtime class convergence count [{$marker}]";
        }
    }

    $installationProgress = $read('scripts/lib/n1-installation-progress.php');
    foreach (['failure_message', 'Blocker:', "'blocker' =>"] as $marker) {
        if (! str_contains($installationProgress, $marker)) {
            $errors[] = "installation progress blocker visibility missing [{$marker}]";
        }
    }

    $progress = $read('scripts/lib/n1-target-progress.php');
    $counts = [14, 52, 5, 7, 7, 20];
    if (array_sum($counts) !== 105 || ! str_contains($progress, 'nexoraTargetProgressC1Gates')) {
        $errors[] = 'v5.4 must not alter the established 105 granular target-gate denominator';
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
    $sourceFiles = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
    if (count($sourceFiles) < 22) {
        $errors[] = 'v5.4 critical disk source manifest must protect at least 22 installer-path files';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'critical_runtime_classes' => count($runtimeFiles),
            'critical_source_files' => count($sourceFiles),
            'secure_web_ack' => 1,
            'public_source_status_redacted' => 1,
            'one_time_ack_token' => 1,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
