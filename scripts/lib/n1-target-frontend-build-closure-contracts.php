<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeFrontendBuildClosureContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'scripts/lib/n1-frontend-build-diagnostics.php',
        'scripts/n1-c1-frontend-build-doctor.php',
        'scripts/n1-c1-frontend-build-doctor.bat',
        'scripts/n1-c1-frontend-build-doctor.ps1',
        'scripts/n1-c1-frontend-build-doctor.sh',
        'scripts/n1-c1-dependency-certify.php',
        'scripts/n1-c1-evidence-verify.php',
        'scripts/lib/n1-historical-typescript-remediation.php',
        'tests/Architecture/N100V510FrontendBuildClosureArchitectureTest.php',
        'tests/Unit/Certification/FrontendBuildDiagnosticsTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.10 frontend build closure artifact missing [{$file}]";
        }
    }

    $platform = is_file($root.'/config/nexora.php') ? require $root.'/config/nexora.php' : [];
    if (version_compare((string) ($platform['version'] ?? '0.0.0'), '1.0.0-rc.75', '<')) {
        $errors[] = 'v5.10 frontend build closure requires platform version 1.0.0-rc.75 or newer';
    }

    $diagnostics = $read('scripts/lib/n1-frontend-build-diagnostics.php');
    foreach ([
        'nexoraFrontendBuildHistoricalBaseline',
        'nexoraParseTypeScriptDiagnostics',
        'nexoraAnalyzeFrontendBuildOutput',
        'historical_target_diagnostics',
        'historical_target_files_with_diagnostics',
        'dependency_graph_missing',
        'diagnostic-only; C1 PASS still requires',
    ] as $marker) {
        if (! str_contains($diagnostics, $marker)) {
            $errors[] = "v5.10 frontend diagnostic boundary missing [{$marker}]";
        }
    }

    require_once $root.'/scripts/lib/n1-frontend-build-diagnostics.php';
    $baseline = nexoraFrontendBuildHistoricalBaseline();
    $baselineErrors = array_sum(array_map(
        static fn (array $entry): int => (int) ($entry['historical_errors'] ?? 0),
        $baseline,
    ));
    if (count($baseline) !== 11 || $baselineErrors !== 76) {
        $errors[] = "historical frontend compiler baseline must remain exactly 76 errors across 11 files [{$baselineErrors}/".count($baseline).']';
    }

    $expectedCounts = [50, 1, 1, 1, 3, 14, 1, 1, 1, 1, 2];
    $actualCounts = array_values(array_map(
        static fn (array $entry): int => (int) $entry['historical_errors'],
        $baseline,
    ));
    if ($actualCounts !== $expectedCounts) {
        $errors[] = 'historical frontend per-file compiler counts changed unexpectedly';
    }

    $doctor = $read('scripts/n1-c1-frontend-build-doctor.php');
    foreach ([
        '--log=',
        '--run',
        '--write',
        '--assert-clean',
        'This doctor never promotes C1',
        'nexoraAnalyzeFrontendBuildOutput',
    ] as $marker) {
        if (! str_contains($doctor, $marker)) {
            $errors[] = "v5.10 frontend doctor missing [{$marker}]";
        }
    }

    $c1 = $read('scripts/n1-c1-dependency-certify.php');
    foreach ([
        "'typecheck'",
        "'vite-build'",
        'attachFrontendDiagnostics',
        'typecheck.diagnostics.json',
        'vite-build.diagnostics.json',
        'frontend_typecheck_diagnostics_sha256',
        'frontend_vite_build_diagnostics_sha256',
        'historical_target_diagnostics',
        'historical_target_files_with_diagnostics',
        "'schema' => 2",
    ] as $marker) {
        if (! str_contains($c1, $marker)) {
            $errors[] = "v5.10 C1 frontend evidence integration missing [{$marker}]";
        }
    }

    $evidence = $read('scripts/n1-c1-evidence-verify.php');
    foreach ([
        'frontend_typecheck_diagnostics_sha256',
        'frontend_vite_build_diagnostics_sha256',
        'frontend_diagnostics',
        'compiler_clean',
        'historical_target_diagnostics',
        'source_tree_sha256',
    ] as $marker) {
        if (! str_contains($evidence, $marker)) {
            $errors[] = "v5.10 C1 evidence revalidation missing [{$marker}]";
        }
    }

    $progress = $read('scripts/lib/n1-target-progress.php');
    if (! preg_match('/function\s+nexoraTargetProgressC1Gates\(\):\s*array\s*\{(.*?)\n\}/s', $progress, $match)) {
        $errors[] = 'unable to inspect C1 target gate denominator';
        $c1GateCount = -1;
    } else {
        preg_match_all("/'([^']+)'/", $match[1], $gateMatches);
        $c1GateCount = count($gateMatches[1] ?? []);
        if ($c1GateCount !== 14) {
            $errors[] = "C1 target gate denominator changed unexpectedly [{$c1GateCount}/14]";
        }
    }

    $package = json_decode($read('package.json'), true);
    $buildWrapper = $read('scripts/pkg1-build.php');
    $buildIsDirect = is_array($package)
        && ($package['scripts']['build'] ?? null) === 'tsc --noEmit && vite build';
    $buildIsProvenanceWrapped = is_array($package)
        && ($package['scripts']['build'] ?? null) === 'php scripts/pkg1-build.php'
        && ($package['scripts']['build:raw'] ?? null) === 'tsc --noEmit && vite build'
        && str_contains($buildWrapper, 'NEXORA_BUILD_IDENTITY')
        && str_contains($buildWrapper, 'npm run build:raw');
    if ((! $buildIsDirect && ! $buildIsProvenanceWrapped)
        || ($package['scripts']['typecheck'] ?? null) !== 'tsc --noEmit') {
        $errors[] = 'package build/typecheck commands must retain real tsc + Vite execution';
    }

    $historical = $read('scripts/lib/n1-historical-typescript-remediation.php');
    if (! str_contains($historical, 'Only a dependency-backed C1 typecheck + Vite build')) {
        $errors[] = 'historical remediation must not claim target verification from source patterns';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'historical_errors' => $baselineErrors,
            'historical_files' => count($baseline),
            'c1_target_gates' => $c1GateCount,
            'frontend_diagnostic_artifacts' => 2,
            'target_denominator' => 105,
            'target_denominator_changed' => 0,
        ],
    ];
}
