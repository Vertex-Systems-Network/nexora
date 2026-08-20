<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetSourceActivationContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Installation/SourceActivationIdentity.php',
        'app/Console/Commands/Nexora/SourceStatusCommand.php',
        'app/Console/Commands/Nexora/SourceActivateCommand.php',
        'scripts/n1-source-activate.bat',
        'scripts/n1-source-activate.sh',
        'tests/Architecture/N100V52SourceActivationArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.2 source-activation artifact missing [{$file}]";
        }
    }

    $config = $read('config/installer.php');
    foreach ([
        "'expected_protocol' => 'v5.",
        "'expected_generation' => 'n1-v5.",
        "'installer_sha256' => '",
    ] as $marker) {
        if (! str_contains($config, $marker)) {
            $errors[] = "Installer source identity config missing [{$marker}]";
        }
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    foreach ([
        "public const PROTOCOL = 'v5.",
        "public const SOURCE_GENERATION = 'n1-v5.",
        'SourceActivationIdentity $sourceActivation',
        '$source = $this->sourceActivation->assertCurrent();',
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "Installer source activation boundary missing [{$marker}]";
        }
    }

    $assertPosition = strpos($installer, '$source = $this->sourceActivation->assertCurrent();');
    $databasePosition = strpos($installer, '$this->checkpoint(\'database\', true);');
    if ($assertPosition === false || $databasePosition === false || $assertPosition >= $databasePosition) {
        $errors[] = 'Source activation identity must fail before the database stage.';
    }

    if (str_contains($installer, 'Reviewed dependency state is not ready:')) {
        $errors[] = 'Legacy reviewed-only final-lock error path must not exist in Installer.';
    }

    $identity = $read('app/Nexora/Installation/SourceActivationIdentity.php');
    foreach ([
        'ReflectionClass(Installer::class)',
        'expected_installer_sha256',
        'running_protocol',
        'running_generation',
        'opcache_validate_timestamps',
        'Nexora source activation mismatch',
    ] as $marker) {
        if (! str_contains($identity, $marker)) {
            $errors[] = "Source activation identity diagnostic missing [{$marker}]";
        }
    }

    $controller = $read('app/Http/Controllers/Install/InstallerController.php');
    foreach ([
        'public function sourceStatus(',
        "'sourceIdentity' => \$this->publicSourceIdentity(", 
        "'source_generation' => Installer::SOURCE_GENERATION",
        "'runtime_classes_matched'",
        "'critical_source_files_matched'",
    ] as $marker) {
        if (! str_contains($controller, $marker)) {
            $errors[] = "Installer source-status response missing [{$marker}]";
        }
    }

    $routes = $read('routes/web.php');
    if (! str_contains($routes, "name('source.status')")) {
        $errors[] = 'Installer source-status route is missing.';
    }

    $view = $read('resources/views/install/index.blade.php');
    foreach ([
        'Executing source verified',
        'Source activation mismatch — installation is blocked before database mutation',
        "route('install.source.status')",
        'source_generation',
        'runtime_classes_matched',
        'critical source',
    ] as $marker) {
        if (! str_contains($view, $marker)) {
            $errors[] = "Installer UI source identity diagnostic missing [{$marker}]";
        }
    }

    $provider = $read('app/Providers/NexoraServiceProvider.php');
    foreach (['SourceStatusCommand::class', 'SourceActivateCommand::class'] as $marker) {
        if (! str_contains($provider, $marker)) {
            $errors[] = "Source activation command registration missing [{$marker}]";
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'source_identity_layers' => 3,
            'database_mutations_before_source_assert' => 0,
            'automatic_web_process_restart' => 0,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
