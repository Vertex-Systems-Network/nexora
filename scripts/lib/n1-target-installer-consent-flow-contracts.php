<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeInstallerConsentFlowContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'app/Nexora/Installation/Installer.php',
        'app/Http/Controllers/Install/InstallerController.php',
        'app/Nexora/Security/Password/PasswordStrengthEvaluator.php',
        'resources/views/install/index.blade.php',
        'tests/Architecture/N100V49InstallerConsentFlowArchitectureTest.php',
        'tests/Unit/Security/PasswordStrengthEvaluatorTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v4.9 installer consent artifact missing [{$file}]";
        }
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    if (preg_match("/public const PROTOCOL = 'v(\d+)\.(\d+)'/", $installer, $match) !== 1
        || ((int) $match[1] < 4)
        || ((int) $match[1] === 4 && (int) $match[2] < 9)) {
        $errors[] = 'Installer protocol must remain v4.9 or newer.';
    }
    foreach ([
        '$dependencyTrust = $this->installDependencyTrust->resolve();',
        "'database_install_action'",
        "'database_protection_mode'",
        "'admin_password_strength'",
        "'admin_password_risk_consent'",
        "'resume-interrupted'",
        "'discard-interrupted-and-reset'",
        "'verified-backup'",
        "'explicit-no-backup-consent'",
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "Installer v4.9 flow missing [{$marker}]";
        }
    }

    $trust = strpos($installer, '$dependencyTrust = $this->installDependencyTrust->resolve();');
    $database = strpos($installer, "\$this->checkpoint('database', true);");
    if ($trust === false || $database === false || $trust >= $database) {
        $errors[] = 'Runtime dependency trust must resolve before the installer enters the database stage.';
    }

    $metadataStart = strpos($installer, 'private function buildRuntimeInstallationMetadata');
    if ($metadataStart === false) {
        $errors[] = 'Runtime installation metadata builder is missing.';
    } else {
        $metadata = substr($installer, $metadataStart);
        if (! str_contains($metadata, 'array $dependencyTrust')) {
            $errors[] = 'Final metadata builder must consume the preflight dependency-trust snapshot.';
        }
        $methodEnd = strpos($metadata, 'private function assertInstallerIsOpen');
        $metadataBody = $methodEnd === false ? $metadata : substr($metadata, 0, $methodEnd);
        if (str_contains($metadataBody, 'installDependencyTrust->resolve()')
            && ! str_contains($installer, 'assertDependencySnapshotStable')) {
            $errors[] = 'Any final dependency re-resolution must be an exact stability recheck against the preflight trust snapshot.';
        }
    }

    if (str_contains($installer, 'Reviewed dependency state is not ready:')) {
        $errors[] = 'Legacy reviewed-only installation-lock blocker must not remain in Installer.';
    }

    $controller = $read('app/Http/Controllers/Install/InstallerController.php');
    foreach ([
        "'db_existing_action'",
        "Rule::in(['resume', 'reset'])",
        "'admin_password' => ['required', 'confirmed', 'string', 'min:10'",
        "'minimum_accepted'",
        "'_password_strength_consent'",
        "'installer_protocol' => Installer::PROTOCOL",
    ] as $marker) {
        if (! str_contains($controller, $marker)) {
            $errors[] = "Installer controller v4.9 validation missing [{$marker}]";
        }
    }

    $password = $read('app/Nexora/Security/Password/PasswordStrengthEvaluator.php');
    foreach ([
        "return 'blocked'",
        "return 'weak'",
        "return 'strong'",
        'strlen($password) >= 10',
        '$characterClasses >= 3',
        "'minimum_accepted'",
        "'consent_required' => \$minimumAccepted && \$level !== 'strong'",
    ] as $marker) {
        if (! str_contains($password, $marker)) {
            $errors[] = "Password consent policy missing [{$marker}]";
        }
    }

    $view = $read('resources/views/install/index.blade.php');
    foreach ([
        'name="db_existing_action"',
        'value="resume"',
        'value="reset"',
        'Discard partial schema and start clean',
        'id="db-protection-choice"',
        'id="password-consent-checkbox"',
        'Weak / Low / Medium require explicit risk consent',
        'id="install" hidden',
        'install.hidden = index !== 3',
        'next.hidden = index === 3',
        'minimumAccepted',
        'protocol ${e.installer_protocol',
    ] as $marker) {
        if (! str_contains($view, $marker)) {
            $errors[] = "Installer view v4.9 behavior missing [{$marker}]";
        }
    }

    if (str_contains($view, 'id="install" class="is-hidden"')) {
        $errors[] = 'Final Install button must not carry the !important is-hidden class.';
    }

    foreach ([
        'app/Nexora/Installation/Installer.php',
        'app/Http/Controllers/Install/InstallerController.php',
        'app/Nexora/Security/Password/PasswordStrengthEvaluator.php',
    ] as $file) {
        $lines = preg_split('/\R/', $read($file)) ?: [];
        foreach ($lines as $number => $line) {
            if (strlen($line) > 200) {
                $errors[] = sprintf('Human-readable v4.9 source line exceeds 200 chars [%s:%d].', $file, $number + 1);
            }
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'database_existing_actions' => 2,
            'password_consent_levels' => 3,
            'hard_password_floor' => 1,
            'dependency_preflight_before_database' => 1,
            'final_install_cta_guard' => 1,
        ],
    ];
}
