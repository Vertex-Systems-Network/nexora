<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeN10InstallationResumeFastTrackContracts(string $root): array
{
    $errors = [];
    $read = static fn (string $path): string => is_file($path) ? (string) file_get_contents($path) : '';

    $required = [
        'app/Nexora/Installation/InstallationResumeIdentity.php',
        'app/Nexora/Installation/InstallationRunControl.php',
        'scripts/n1-target-fast-track.php',
        'scripts/n1-target-fast-track.bat',
        'scripts/n1-target-fast-track.ps1',
        'scripts/n1-target-fast-track.sh',
        'tests/Unit/InstallationRecoveryTest.php',
    ];
    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = 'missing v5.0 installation-resume/fast-track artifact ['.$file.']';
        }
    }

    $identity = $read($root.'/app/Nexora/Installation/InstallationResumeIdentity.php');
    foreach (['installer_protocol', 'migrations_sha256', 'core_seeders_sha256', 'composer_lock_sha256', 'package_lock_sha256'] as $marker) {
        if (! str_contains($identity, $marker)) {
            $errors[] = 'installation resume identity missing ['.$marker.']';
        }
    }

    $control = $read($root.'/app/Nexora/Installation/InstallationRunControl.php');
    foreach (['resume_fingerprint', 'recoveryForDatabase', 'resume_compatible', 'predates resume-provenance protection', 'different Nexora installation provenance'] as $marker) {
        if (! str_contains($control, $marker)) {
            $errors[] = 'installation run-control provenance boundary missing ['.$marker.']';
        }
    }

    $installer = $read($root.'/app/Nexora/Installation/Installer.php');
    if (preg_match("/public const PROTOCOL = 'v(\d+)\.(\d+)'/", $installer, $protocolMatch) !== 1
        || (int) ($protocolMatch[1] ?? 0) < 5) {
        $errors[] = 'installer protocol must remain v5.0 or newer';
    }
    if (! str_contains($installer, "existingAction === 'resume'") || ! str_contains($installer, "resume_reason")) {
        $errors[] = 'server installer must reject incompatible explicit Resume requests';
    }

    $controller = $read($root.'/app/Http/Controllers/Install/InstallerController.php');
    foreach (['interrupted_installation', 'recovery_compatible', 'recovery_reason', 'recoverable_platform_version', 'recoverable_installer_protocol'] as $marker) {
        if (! str_contains($controller, $marker)) {
            $errors[] = 'installer recovery API missing ['.$marker.']';
        }
    }

    $view = $read($root.'/resources/views/install/index.blade.php');
    foreach (['dbInterrupted', 'exact-source Resume is disabled', 'Previous run:', 'recoveryReset.checked=true'] as $marker) {
        if (! str_contains($view, $marker)) {
            $errors[] = 'installer UI incompatible-recovery handling missing ['.$marker.']';
        }
    }

    $fast = $read($root.'/scripts/n1-target-fast-track.php');
    foreach (['--resume-latest', '--install-deps', '--prepare-kits', 'Reviewed-lock attestation is not ready', 'fastTrackBar', 'target_progress'] as $marker) {
        if (! str_contains($fast, $marker)) {
            $errors[] = 'fast-track runner missing ['.$marker.']';
        }
    }
    foreach (['--confirm-review=REVIEWED', '--confirm-refresh=REFRESH', '--generate --confirm=GENERATE', 'migrate:fresh'] as $unsafe) {
        if (str_contains($fast, $unsafe)) {
            $errors[] = 'fast-track must not automate privileged/destructive transition ['.$unsafe.']';
        }
    }

    $plan = $read($root.'/scripts/lib/n1-target-plan.php');
    foreach (['target_progress', 'passedChunks', 'targetPercent'] as $marker) {
        if (! str_contains($plan, $marker)) {
            $errors[] = 'target plan progress output missing ['.$marker.']';
        }
    }

    $tests = $read($root.'/tests/Unit/InstallationRecoveryTest.php');
    foreach (['another_installer_provenance_cannot_resume', 'legacy_interrupted_run_without_resume_provenance_requires_start_clean'] as $marker) {
        if (! str_contains($tests, $marker)) {
            $errors[] = 'resume provenance regression test missing ['.$marker.']';
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => [],
        'metrics' => [
            'resume_identity_domains' => 7,
            'fast_track_wrappers' => 3,
            'automatic_lock_review' => 0,
            'automatic_destructive_reset' => 0,
        ],
    ];
}
