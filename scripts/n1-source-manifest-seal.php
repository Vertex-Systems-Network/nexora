<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$files = [
    'app/Nexora/Installation/Installer.php',
    'app/Http/Controllers/Install/InstallerController.php',
    'app/Nexora/Foundation/Runtime/FreshInstallDependencyTrust.php',
    'app/Nexora/Foundation/Runtime/ReviewedDependencyState.php',
    'app/Nexora/Installation/InstallationState.php',
    'app/Nexora/Installation/SourceActivationIdentity.php',
    'app/Nexora/Installation/SourceSetIntegrity.php',
    'app/Nexora/Installation/SourceActivationHandshake.php',
    'app/Console/Commands/Nexora/SourceActivateCommand.php',
    'app/Console/Commands/Nexora/SourceStatusCommand.php',
    'app/Console/Commands/Nexora/InstallerDoctorCommand.php',
    'resources/views/install/index.blade.php',
    'routes/web.php',
    'app/Providers/NexoraServiceProvider.php',
    'app/Nexora/Installation/InstallationRunControl.php',
    'app/Nexora/Installation/InstallationResumeIdentity.php',
    'app/Nexora/Installation/DatabaseProvisioner.php',
    'app/Nexora/Installation/EnvironmentWriter.php',
    'app/Nexora/Installation/DatabaseBackupManager.php',
    'app/Nexora/Installation/SystemRequirementChecker.php',
    'app/Nexora/Installation/Database/DatabaseDriverRegistry.php',
    'app/Nexora/Security/Password/PasswordStrengthEvaluator.php',
    'app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php',
    'app/Nexora/Foundation/Runtime/RuntimeWritableTempDirectory.php',
    'app/Console/Commands/Nexora/RuntimeHostStatusCommand.php',
    'app/Nexora/Cloud/Services/RuntimeResourceEnvelopeIdentity.php',
    'app/Nexora/Cloud/Services/RuntimePolicyPlaneIdentity.php',
    'app/Nexora/Cloud/Services/RuntimeProcessPlane.php',
    'app/Nexora/Cloud/Services/RuntimeActivationIdentity.php',
    'app/Nexora/Installation/RuntimeInstallationReadiness.php',
    'app/Console/Commands/Nexora/RuntimeInstallReadinessCommand.php',
    'app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php',
    'app/Nexora/Cloud/Services/RuntimeVersionGuard.php',
    'app/Nexora/Installation/RuntimePostInstallHandoff.php',
    'app/Console/Commands/Nexora/RuntimePostInstallStatusCommand.php',
    'app/Console/Commands/Nexora/RuntimePostInstallReconcileCommand.php',
    'resources/views/install/runtime-handoff.blade.php',
];

$installerSource = (string) file_get_contents($root.'/app/Nexora/Installation/Installer.php');
preg_match("/public const PROTOCOL = '([^']+)'/", $installerSource, $protocolMatch);
preg_match("/public const SOURCE_GENERATION = '([^']+)'/", $installerSource, $generationMatch);
$versionConfig = require $root.'/config/nexora.php';
$hashes = [];

foreach ($files as $relative) {
    $path = $root.'/'.$relative;
    if (! is_file($path) || is_link($path)) {
        throw new RuntimeException("Critical source file is missing or invalid [{$relative}].");
    }
    $hash = hash_file('sha256', $path);
    if (! is_string($hash)) {
        throw new RuntimeException("Unable to hash critical source file [{$relative}].");
    }
    $hashes[$relative] = $hash;
}
ksort($hashes, SORT_STRING);
$manifest = [
    'schema' => 1,
    'platform_version' => (string) ($versionConfig['version'] ?? 'unknown'),
    'installer_protocol' => (string) ($protocolMatch[1] ?? 'unknown'),
    'source_generation' => (string) ($generationMatch[1] ?? 'unknown'),
    'files' => $hashes,
];
$manifestPath = $root.'/bootstrap/nexora-source-manifest.json';
file_put_contents(
    $manifestPath,
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
);
$manifestHash = hash_file('sha256', $manifestPath);
$installerHash = hash_file('sha256', $root.'/app/Nexora/Installation/Installer.php');
if (! is_string($manifestHash) || ! is_string($installerHash)) {
    throw new RuntimeException('Unable to seal source manifest hashes.');
}

$configPath = $root.'/config/installer.php';
$config = (string) file_get_contents($configPath);
$config = preg_replace(
    "/'installer_sha256' => '[^']*'/",
    "'installer_sha256' => '{$installerHash}'",
    $config,
    1,
);
$config = preg_replace(
    "/'manifest_sha256' => '[^']*'/",
    "'manifest_sha256' => '{$manifestHash}'",
    $config,
    1,
);
if (! is_string($config)) {
    throw new RuntimeException('Unable to update installer source seals.');
}
file_put_contents($configPath, $config);

fwrite(STDOUT, "Critical source manifest sealed.\n");
fwrite(STDOUT, "Files: ".count($hashes)."\n");
fwrite(STDOUT, "Installer SHA-256: {$installerHash}\n");
fwrite(STDOUT, "Manifest SHA-256: {$manifestHash}\n");
