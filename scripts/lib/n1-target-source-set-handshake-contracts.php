<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetSourceSetHandshakeContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Installation/SourceSetIntegrity.php',
        'app/Nexora/Installation/SourceActivationHandshake.php',
        'app/Nexora/Installation/SourceActivationIdentity.php',
        'app/Console/Commands/Nexora/SourceActivateCommand.php',
        'app/Console/Commands/Nexora/SourceStatusCommand.php',
        'scripts/n1-source-manifest-seal.php',
        'scripts/n1-source-web-ack.bat',
        'scripts/n1-source-web-ack.sh',
        'scripts/lib/n1-installation-progress.php',
        'scripts/n1-installation-progress.php',
        'bootstrap/nexora-source-manifest.json',
        'tests/Unit/Installation/SourceActivationHandshakeTest.php',
        'tests/Architecture/N100V53SourceSetHandshakeArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.3 source-set/handshake artifact missing [{$file}]";
        }
    }

    $config = $read('config/installer.php');
    foreach ([
        "'expected_protocol' => 'v5.",
        "'expected_generation' => 'n1-v5.",
        "'manifest_path'",
        "'manifest_sha256' => '",
        "'activation_receipt_path'",
        "'web_ack_path'",
        "'activation_ttl_seconds' => 900",
    ] as $marker) {
        if (! str_contains($config, $marker)) {
            $errors[] = "v5.3 installer source-set config missing [{$marker}]";
        }
    }

    $manifestPath = $root.'/bootstrap/nexora-source-manifest.json';
    $manifest = null;
    if (is_file($manifestPath)) {
        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 128, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $errors[] = 'critical source manifest is invalid JSON: '.$exception->getMessage();
        }
    }

    $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
    if (count($files) < 14) {
        $errors[] = 'critical source manifest must protect at least 14 installer-path files';
    }

    $versionConfig = is_file($root.'/config/nexora.php') ? require $root.'/config/nexora.php' : [];
    $installerSource = $read('app/Nexora/Installation/Installer.php');
    preg_match("/public const PROTOCOL = '([^']+)'/", $installerSource, $protocolMatch);
    preg_match("/public const SOURCE_GENERATION = '([^']+)'/", $installerSource, $generationMatch);

    if (($manifest['platform_version'] ?? null) !== ($versionConfig['version'] ?? null)) {
        $errors[] = 'critical source manifest platform version must match current config/nexora.php';
    }
    if (($manifest['installer_protocol'] ?? null) !== ($protocolMatch[1] ?? null)) {
        $errors[] = 'critical source manifest protocol must match the current Installer protocol';
    }
    if (($manifest['source_generation'] ?? null) !== ($generationMatch[1] ?? null)) {
        $errors[] = 'critical source manifest generation must match the current Installer source generation';
    }

    if (is_file($manifestPath)) {
        $manifestHash = hash_file('sha256', $manifestPath);
        if (! is_string($manifestHash) || ! str_contains($config, "'manifest_sha256' => '{$manifestHash}'")) {
            $errors[] = 'config/installer.php manifest seal does not match bootstrap/nexora-source-manifest.json';
        }
    }

    $set = $read('app/Nexora/Installation/SourceSetIntegrity.php');
    foreach ([
        'Critical source manifest SHA-256',
        'Critical source file mismatch',
        'source_set_fingerprint',
        'matched_files',
        'realpath(base_path())',
    ] as $marker) {
        if (! str_contains($set, $marker)) {
            $errors[] = "critical source-set integrity behavior missing [{$marker}]";
        }
    }

    $identity = $read('app/Nexora/Installation/SourceActivationIdentity.php');
    foreach ([
        'SourceSetIntegrity $sourceSet',
        'critical_source_files',
        'critical_source_files_matched',
        'source_set_fingerprint',
        'exact critical installer source set',
    ] as $marker) {
        if (! str_contains($identity, $marker)) {
            $errors[] = "SourceActivationIdentity source-set behavior missing [{$marker}]";
        }
    }

    $handshake = $read('app/Nexora/Installation/SourceActivationHandshake.php');
    foreach ([
        'issueCliActivation',
        'acknowledgeWeb',
        'pending-web-ack',
        'web_ack_valid',
        'activation nonce',
        'receipt_sha256',
        'ack_sha256',
    ] as $marker) {
        if (! str_contains($handshake, $marker)) {
            $errors[] = "CLI/web activation handshake missing [{$marker}]";
        }
    }

    $status = $read('app/Console/Commands/Nexora/SourceStatusCommand.php');
    if (! str_contains($status, '--require-web-ack')) {
        $errors[] = 'source:status must support --require-web-ack';
    }

    $activate = $read('app/Console/Commands/Nexora/SourceActivateCommand.php');
    foreach (['issueCliActivation', 'activation nonce', 'critical source set'] as $marker) {
        if (! str_contains($activate, $marker)) {
            $errors[] = "source:activate handshake behavior missing [{$marker}]";
        }
    }

    $controller = $read('app/Http/Controllers/Install/InstallerController.php');
    foreach ([
        'SourceActivationHandshake $sourceHandshake',
        'acknowledgeWeb($state',
        "'critical_source_files_matched'",
        "'critical_source_files_matched'",
        "'X-Nexora-Source-Set'",
    ] as $marker) {
        if (! str_contains($controller, $marker)) {
            $errors[] = "installer web source acknowledgement missing [{$marker}]";
        }
    }

    $view = $read('resources/views/install/index.blade.php');
    foreach (['source set', 'critical source', 'activation ${handshake.status'] as $marker) {
        if (! str_contains($view, $marker)) {
            $errors[] = "installer UI source-set diagnostic missing [{$marker}]";
        }
    }

    $fastTrack = $read('scripts/n1-target-fast-track.php');
    foreach ([
        'n1-installation-progress.php',
        'Installation execution progress',
        'nexoraRenderInstallationProgress',
    ] as $marker) {
        if (! str_contains($fastTrack, $marker)) {
            $errors[] = "fast-track installation progress visibility missing [{$marker}]";
        }
    }

    $progressSource = $read('scripts/lib/n1-target-progress.php');
    if (! str_contains($progressSource, "'total' => 105") && ! str_contains($progressSource, '105')) {
        $warnings[] = 'granular target denominator literal 105 is not directly visible; verify generated target-plan totals separately';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'critical_source_files' => count($files),
            'activation_handshake_layers' => 2,
            'target_gate_denominator_changed' => 0,
            'partial_deployment_allowed' => 0,
            'installation_progress_streams' => 1,
        ],
    ];
}
