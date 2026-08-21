<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$servicePath = $root.'/app/Nexora/Forge/Services/ForgeExtensionScaffolder.php';
$commandPath = $root.'/app/Console/Commands/Nexora/MakeExtensionCommand.php';
$validatorPath = $root.'/app/Nexora/Extensions/Services/ExtensionManifestValidator.php';
$installerPath = $root.'/app/Nexora/Extensions/Services/ExtensionPackageInstaller.php';
$testPath = $root.'/tests/Feature/Forge/ForgeDeveloperExperienceTest.php';
$guidePath = $root.'/docs/FORGE_DEVELOPER_GUIDE.md';

$failures = [];
$read = static function (string $path) use (&$failures): string {
    if (! is_file($path)) {
        $failures[] = 'Missing required file: '.str_replace(dirname(__DIR__).'/', '', $path);
        return '';
    }

    $content = file_get_contents($path);
    if (! is_string($content)) {
        $failures[] = 'Unable to read required file: '.str_replace(dirname(__DIR__).'/', '', $path);
        return '';
    }

    return $content;
};
$requires = static function (string $content, string $needle, string $label) use (&$failures): void {
    if (! str_contains($content, $needle)) {
        $failures[] = $label;
    }
};
$forbids = static function (string $content, string $needle, string $label) use (&$failures): void {
    if (str_contains($content, $needle)) {
        $failures[] = $label;
    }
};

$service = $read($servicePath);
$command = $read($commandPath);
$validator = $read($validatorPath);
$installer = $read($installerPath);
$test = $read($testPath);
$guide = $read($guidePath);

// Stable command + source-only trust boundary.
$requires($command, 'nexora:make:extension', 'Forge command signature is missing.');
$requires($command, '{--dry-run', 'Forge command must expose zero-write --dry-run.');
$requires($command, '{--force', 'Forge command must expose guarded --force refresh.');
$requires($command, 'Forge only generates source', 'Forge CLI must state the source-only trust boundary.');
$requires($command, 'Forge never installs, enables, grants trust or bypasses Sentinel.', 'Forge CLI must explicitly preserve Sentinel/install trust boundaries.');
$requires($command, 'ForgeExtensionScaffolder', 'Forge CLI must delegate filesystem behavior to the Forge scaffolder service.');

// Filesystem ownership/path safety and deterministic scaffold contract.
$requires($service, "private const MARKER = '.nexora-forge.json';", 'Forge ownership marker is missing.');
$requires($service, "'schema' => 'nexora.forge.scaffold.v1'", 'Forge marker schema is missing.');
$requires($service, 'PortablePath::join(', 'Forge scaffold paths must use PortablePath.');
$requires($service, 'PortablePath::assertNoExistingSymlinkTraversal(', 'Forge must reject existing symlink traversal.');
$requires($service, 'if (is_link($workspace))', 'Forge workspace symlinks must fail closed.');
$requires($service, 'if ($plan[\'exists\'] && $force && ! $plan[\'forge_owned\'])', 'Forge --force must require Forge ownership.');
$requires($service, 'is a file, not a directory', 'Forge must reject file-vs-directory target conflicts.');
$requires($service, 'src/.gitkeep', 'Forge stable source directory placeholder is missing.');
$requires($service, 'resources/.gitkeep', 'Forge stable resources directory placeholder is missing.');
$requires($service, 'database/migrations/.gitkeep', 'Forge stable migration directory placeholder is missing.');
$requires($service, 'tests/.gitkeep', 'Forge stable test directory placeholder is missing.');
$requires($service, '$this->manifests->validate($manifest);', 'Generated manifests must self-validate through ExtensionManifestValidator.');
$forbids($service, 'File::deleteDirectory($target)', 'Forge must not delete the developer scaffold directory during refresh.');
$forbids($service, 'ExtensionPackageInstaller', 'Forge scaffolding must not call the extension installer.');
$forbids($service, 'SupplyChainArtifact', 'Forge scaffolding must not fabricate or manipulate trusted supply-chain artifacts.');

// Authoritative lifecycle remains separate from Forge.
$requires($validator, "['extension','app','integration','studio-pack']", 'Extension manifest validator package-type lifecycle guard is missing.');
$requires($installer, 'Sentinel must return ALLOW before an extension can be installed.', 'Normal extension install must continue requiring Sentinel ALLOW.');

// Acceptance source protects the real developer-facing invariants.
foreach ([
    'test_dry_run_is_zero_write_and_reports_deterministic_plan',
    'test_traversal_and_non_directory_destinations_are_rejected',
    'test_arbitrary_existing_directory_cannot_be_force_overwritten',
    'test_forge_owned_force_refresh_is_deterministic_and_preserves_developer_files',
    'test_service_plan_and_create_share_the_same_stable_file_contract',
] as $testMethod) {
    $requires($test, $testMethod, 'Missing Forge acceptance coverage: '.$testMethod);
}
$requires($test, 'assertDirectoryDoesNotExist', 'Forge acceptance must prove dry-run does not create the target.');
$requires($test, 'Custom.php', 'Forge acceptance must prove developer-created files survive force refresh.');
$requires($test, 'ExtensionManifestValidator::class', 'Forge acceptance must validate generated manifest through the authoritative validator.');

// Developer contract documents supported behavior without promising internal APIs.
$requires($guide, 'Nexora Forge is a **source scaffolding** workflow', 'Forge developer guide must define source-scaffolding scope.');
$requires($guide, '`--dry-run` is zero-write', 'Forge developer guide must document zero-write dry-run.');
$requires($guide, '`--force` requires same-identifier Forge ownership', 'Forge developer guide must document guarded force refresh.');
$requires($guide, 'Forge never installs, enables, signs, trusts, or grants capabilities', 'Forge developer guide must preserve package trust boundaries.');
$requires($guide, 'without making internal Core service classes or Eloquent models public SDK APIs', 'Forge guide must keep internal Core classes outside the public SDK contract.');

if ($failures !== []) {
    fwrite(STDERR, "Nexora Forge / Developer Experience Product Contract: FAIL\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - '.$failure."\n");
    }
    exit(1);
}

fwrite(STDOUT, "Nexora Forge / Developer Experience Product Contract: PASS\n");
fwrite(STDOUT, " - deterministic source scaffold + zero-write dry-run\n");
fwrite(STDOUT, " - Forge-owned guarded refresh + path/symlink safety\n");
fwrite(STDOUT, " - authoritative manifest validation + developer-file preservation\n");
fwrite(STDOUT, " - Sentinel/install trust boundary remains mandatory\n");
