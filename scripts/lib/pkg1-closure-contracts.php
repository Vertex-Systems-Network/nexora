<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzePkg1ClosureContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'scripts/lib/pkg1-closure.php',
        'scripts/pkg1-usable-closure.php',
        'scripts/pkg1-usable-closure.bat',
        'scripts/pkg1-usable-closure.ps1',
        'scripts/pkg1-usable-closure.sh',
        'scripts/pkg1-usable-smoke.php',
        'scripts/pkg1-closure-evidence-verify.php',
        'scripts/pkg1-finalize-login-smoke.bat',
        'scripts/pkg1-finalize-login-smoke.ps1',
        'scripts/composer-bootstrap.php',
        'scripts/pkg1-build.php',
        'scripts/pkg1-status.php',
        'scripts/pkg1-status.bat',
        'scripts/pkg1-status.ps1',
        'scripts/pkg1-status.sh',
        'scripts/pkg1-run.bat',
        'scripts/pkg1-run.php',
        'scripts/pkg1-launcher-contract-verify.php',
        'scripts/lib/pkg1-build-identity.php',
        'scripts/composer-bootstrap.bat',
        'scripts/composer-bootstrap.ps1',
        'scripts/composer-bootstrap.sh',
        'tests/Architecture/N100Pkg1UsableClosureArchitectureTest.php',
    ];
    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "PKG-1 closure artifact missing [{$file}]";
        }
    }

    $platform = is_file($root.'/config/nexora.php') ? require $root.'/config/nexora.php' : [];
    if (version_compare((string) ($platform['version'] ?? '0'), '1.0.0-rc.94', '<')) {
        $errors[] = 'PKG-1 requires platform version 1.0.0-rc.94 or newer';
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    foreach ([
        "public const PROTOCOL = 'v5.29'",
        "public const SOURCE_GENERATION = 'n1-v5.29'",
    ] as $marker) {
        if (! str_contains($installer, $marker)) {
            $errors[] = "PKG-1 installer identity missing [{$marker}]";
        }
    }

    $runner = $read('scripts/pkg1-usable-closure.php');
    foreach ([
        'nexora:source:status',
        '--require-web-ack',
        'composer-bootstrap.php',
        'Verified Composer availability/bootstrap',
        'dependency-lock-refresh.php',
        '--confirm=REFRESH',
        '--promote-reviewed',
        'dependency-lock-promote.php',
        '--confirm=PROMOTE-REVIEWED',
        'n1-c1-dependency-certify.php',
        '--install-deps',
        'n1-c1-evidence-verify.php',
        'nexora:runtime:install-readiness',
        '--assert-ready',
        'nexora:install:lock-status',
        'nexora:runtime:post-install-status',
        'pkg1-usable-smoke.php',
        'waiting-review',
        'waiting-install',
        'waiting-auth-smoke',
        'PKG-1 COMPLETE',
        'closure.json',
        'pkg1-closure-evidence-verify.php',
        'closure-fast-resume',
        'c1-fast-resume',
        'dependency/network stages were skipped',
        'nexoraPkg1DependencyCandidateState',
        'nexoraPkg1QuarantineStaleCandidate',
        'Human review itself must not require Composer or registry access',
        "'c1' => '14/14'",
        "'installer' => '100%'",
    ] as $marker) {
        if (! str_contains($runner, $marker)) {
            $errors[] = "PKG-1 orchestrator guarantee missing [{$marker}]";
        }
    }

    if (! str_contains($runner, '--confirm=PROMOTE-REVIEWED')
        || ! str_contains($runner, '--reviewer="REAL NAME"')
        || ! str_contains($runner, 'after human review')) {
        $errors[] = 'PKG-1 must preserve explicit human reviewed-lock promotion';
    }

    $statusTool = $read('scripts/pkg1-status.php');
    foreach ([
        'NEXT_ACTION=',
        'closure_receipt_valid',
        'c1_evidence_valid',
        'WAITING_REVIEW',
        'WAITING_INSTALL',
        'WAITING_AUTH_SMOKE',
        'READY_COMPOSER_BOOTSTRAP',
        'FINALIZE_LOGIN_SMOKE',
        'RESUME_COMMAND=',
        'pkg1-run.bat',
    ] as $marker) {
        if (! str_contains($statusTool, $marker)) {
            $errors[] = "PKG-1 live status/resume doctor missing [{$marker}]";
        }
    }
    $closureFast = strpos($runner, 'closure-fast-resume');
    $composerPosition = strpos($runner, 'Verified Composer availability/bootstrap');
    $c1Fast = strpos($runner, 'c1-fast-resume');
    if ($closureFast === false || $composerPosition === false || $closureFast >= $composerPosition) {
        $errors[] = 'PKG-1 sealed closure must short-circuit before Composer/network work';
    }
    if ($c1Fast === false || $composerPosition === false || $c1Fast >= $composerPosition) {
        $errors[] = 'PKG-1 reusable C1 evidence must be checked before Composer/network work';
    }

    $candidateInspect = strpos($runner, 'nexoraPkg1DependencyCandidateState');
    $candidateQuarantine = strpos($runner, 'nexoraPkg1QuarantineStaleCandidate');
    if ($candidateInspect === false || $composerPosition === false || $candidateInspect >= $composerPosition) {
        $errors[] = 'PKG-1 candidate state must be inspected before Composer/network work';
    }
    if ($candidateQuarantine === false || $composerPosition === false || $candidateQuarantine >= $composerPosition) {
        $errors[] = 'PKG-1 stale candidate quarantine must occur before Composer/network work';
    }

    $launcher = $read('scripts/pkg1-run.php');
    foreach ([
        'nexoraPkg1LauncherStatus',
        'nexoraPkg1LauncherStopOnBlock',
        'WAITING_REVIEW',
        'PROMOTE-REVIEWED',
        'WAITING_RECOVERY',
        'ROLLBACK',
        'WAITING_SOURCE_RESTART',
        'WAITING_INSTALL',
        'WAITING_AUTH_SMOKE',
        'pkg1-finalize-login-smoke.ps1',
        'pkg1-closure-evidence-verify.php',
        'PKG-1 COMPLETE',
    ] as $marker) {
        if (! str_contains($launcher, $marker)) {
            $errors[] = "PKG-1 PHP interactive launcher missing [{$marker}]";
        }
    }
    if (preg_match('/[\x80-\xFF]/', $launcher) === 1) {
        $errors[] = 'PKG-1 PHP interactive launcher must remain ASCII-only for Windows console portability';
    }
    $launcherBat = $read('scripts/pkg1-run.bat');
    if (! str_contains($launcherBat, 'pkg1-run.php') || stripos($launcherBat, 'powershell') !== false) {
        $errors[] = 'PKG-1 primary batch launcher must invoke PHP directly and must not depend on PowerShell';
    }
    if (is_file($root.'/scripts/pkg1-run.ps1')) {
        $errors[] = 'legacy primary PowerShell launcher must not ship';
    }
    $launcherContract = $read('scripts/pkg1-launcher-contract-verify.php');
    foreach (['primary Laragon launcher is PHP-only', 'ParseFile', 'nexoraPkg1LauncherStopOnBlock'] as $marker) {
        if (! str_contains($launcherContract, $marker)) {
            $errors[] = "PKG-1 launcher contract verifier missing [{$marker}]";
        }
    }

    $finalizer = $read('scripts/pkg1-finalize-login-smoke.ps1');
    foreach (['Read-Host', '-AsSecureString', 'NEXORA_PKG1_SMOKE_PASSWORD', 'ZeroFreeBSTR'] as $marker) {
        if (! str_contains($finalizer, $marker)) {
            $errors[] = "PKG-1 hidden-password finalizer missing [{$marker}]";
        }
    }

    $smoke = $read('scripts/pkg1-usable-smoke.php');
    foreach ([
        'installed-lock',
        'installer-admin-present',
        'installer-admin-active',
        'installer-admin-verified',
        'installer-admin-super-admin',
        'database-readable',
        'nexora:runtime:post-install-status',
        'scripts/http-smoke.php',
        'NEXORA_PKG1_SMOKE_PASSWORD',
        "curl_init",
        "'/login'",
        "'/admin'",
        'admin_http_status',
        'waiting-auth-smoke',
    ] as $marker) {
        if (! str_contains($smoke, $marker)) {
            $errors[] = "PKG-1 non-destructive usable smoke missing [{$marker}]";
        }
    }
    $c1Position = strpos($runner, 'N1.0 C1 dependency + TypeScript + Vitest + Vite closure');
    $sourcePosition = strpos($runner, 'Exact source + web-process acknowledgement');
    $lockPosition = strpos($runner, 'Permanent installation lock validity');
    $readinessPosition = strpos($runner, 'Reverify installer-safe runtime readiness on committed database configuration');
    if ($c1Position === false || $sourcePosition === false || $c1Position >= $sourcePosition) {
        $errors[] = 'PKG-1 clean package must install/close C1 before Artisan source activation/web acknowledgement';
    }
    if ($lockPosition === false || $readinessPosition === false || $lockPosition >= $readinessPosition) {
        $errors[] = 'PKG-1 fresh install must let the browser installer evaluate selected-DB readiness before committed-state CLI readiness recheck';
    }

    if (str_contains($smoke, 'RefreshDatabase')) {
        $errors[] = 'PKG-1 live smoke must never use RefreshDatabase on the authoritative target';
    }
    if (preg_match('/--(?:password|secret)=/i', $smoke) === 1) {
        $errors[] = 'PKG-1 live smoke must not accept password/secret on the command line';
    }
    if (str_contains($smoke, 'sys_get_temp_dir()')) {
        $errors[] = 'PKG-1 live auth cookie must use Nexora app-local writable temp, not the Windows system temp';
    }

    $composerBootstrap = $read('scripts/composer-bootstrap.php');
    foreach ([
        'https://composer.github.io/installer.sig',
        'https://getcomposer.org/installer',
        "hash('sha384'",
        'hash_equals',
        '--install-dir=',
        '--filename=composer.phar',
        "'--2'",
        'composer_phar_sha256',
        'bootstrap-attestation.json',
    ] as $marker) {
        if (! str_contains($composerBootstrap, $marker)) {
            $errors[] = "PKG-1 verified Composer bootstrap missing [{$marker}]";
        }
    }
    if (str_contains($composerBootstrap, 'CURLOPT_SSL_VERIFYPEER => false')
        || str_contains($composerBootstrap, "'verify_peer' => false")
        || str_contains($composerBootstrap, "'allow_self_signed' => true")) {
        $errors[] = 'PKG-1 Composer bootstrap must never disable TLS verification';
    }

    $targetComposer = $read('scripts/lib/target-composer.php');
    foreach ([
        'storage',
        'tools',
        'composer.phar',
        "'Nexora-local'",
    ] as $marker) {
        if (! str_contains($targetComposer, $marker)) {
            $errors[] = "PKG-1 target Composer locator missing local verified candidate [{$marker}]";
        }
    }

    foreach ([
        'scripts/pkg1-usable-closure.php',
        'scripts/dependency-lock-refresh.php',
        'scripts/dependency-lock-promote.php',
        'scripts/lib/dependency-lock-intake.php',
    ] as $bootstrapFile) {
        $content = $read($bootstrapFile);
        if (preg_match('/\bmb_(?:substr|strlen)\s*\(/', $content) === 1) {
            $errors[] = "PKG-1 pre-vendor path must not require mbstring [{$bootstrapFile}]";
        }
    }

    $buildWrapper = $read('scripts/pkg1-build.php');
    $buildIdentity = $read('scripts/lib/pkg1-build-identity.php');
    foreach (['NEXORA_BUILD_IDENTITY', 'pkg1-build-input.json', 'identity_stable', 'post_build_identity_sha256'] as $buildMarker) {
        if (! str_contains($buildWrapper, $buildMarker)) {
            $errors[] = "PKG-1 build provenance wrapper missing [{$buildMarker}]";
        }
    }
    foreach (['composer.lock', 'package-lock.json', 'tsconfig.json', 'vite.config.ts', 'Automation/Form.tsx', 'Studio/Editor.tsx'] as $buildMarker) {
        if (! str_contains($buildIdentity, $buildMarker)) {
            $errors[] = "PKG-1 build identity input missing [{$buildMarker}]";
        }
    }

    $package = json_decode($read('package.json'), true);
    if (! is_array($package)
        || ($package['scripts']['close:pkg1'] ?? null) !== 'php scripts/pkg1-usable-closure.php'
        || ($package['scripts']['smoke:pkg1'] ?? null) !== 'php scripts/pkg1-usable-smoke.php'
        || ($package['scripts']['verify:pkg1'] ?? null) !== 'php scripts/pkg1-closure-evidence-verify.php'
        || ($package['scripts']['bootstrap:composer'] ?? null) !== 'php scripts/composer-bootstrap.php'
        || ($package['scripts']['status:pkg1'] ?? null) !== 'php scripts/pkg1-status.php'
        || ($package['scripts']['run:pkg1'] ?? null) !== 'php scripts/pkg1-run.php'
        || ($package['scripts']['verify:pkg1-launcher'] ?? null) !== 'php scripts/pkg1-launcher-contract-verify.php'
        || ($package['scripts']['build'] ?? null) !== 'php scripts/pkg1-build.php'
        || ($package['scripts']['build:raw'] ?? null) !== 'tsc --noEmit && vite build'
        || ! str_contains((string) ($package['scripts']['finalize:pkg1'] ?? ''), 'pkg1-finalize-login-smoke.ps1')) {
        $errors[] = 'package.json must expose canonical PKG-1 closure/smoke commands';
    }

    $progress = $read('scripts/lib/n1-target-progress.php');
    if (! preg_match('/function\s+nexoraTargetProgressC1Gates\(\).*?return\s*\[(.*?)\];/s', $progress, $match)) {
        $errors[] = 'unable to inspect C1 gate denominator';
        $c1Count = 0;
    } else {
        $c1Count = substr_count($match[1], "'");
        $c1Count = intdiv($c1Count, 2);
        if ($c1Count !== 14) {
            $errors[] = "PKG-1 changed C1 denominator unexpectedly [{$c1Count}/14]";
        }
    }

    $visibility = $read('scripts/lib/n1-target-progress-visibility-contracts.php');
    if (! str_contains($visibility, "'C1' => 14")
        || ! str_contains($visibility, "'C2' => 52")
        || ! str_contains($visibility, "'C6' => 20")) {
        $errors[] = 'PKG-1 must preserve the existing C1-C6 granular denominator architecture';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'pkg1_acceptance_checks' => 5,
            'c1_gates' => $c1Count,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
