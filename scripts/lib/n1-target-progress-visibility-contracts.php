<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetProgressVisibilityContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'scripts/lib/n1-target-progress.php',
        'scripts/n1-target-progress.php',
        'scripts/lib/n1-historical-typescript-remediation.php',
        'scripts/n1-historical-typescript-remediation.php',
        'scripts/n1-target-fast-track.php',
        'scripts/n1-target-execution.php',
        'scripts/lib/n1-target-plan.php',
        'tests/Architecture/N100V51TargetProgressVisibilityArchitectureTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.1 progress visibility artifact missing [{$file}]";
        }
    }

    require_once $root.'/scripts/lib/n1-target-progress.php';
    $progress = nexoraBuildN10GranularProgress($root);
    $expectedTotals = [
        'C1' => 14,
        'C2' => 52,
        'C3' => 5,
        'C4' => 7,
        'C5' => 7,
        'C6' => 20,
    ];

    foreach ($expectedTotals as $chunk => $expected) {
        $actual = (int) ($progress['chunks'][strtolower($chunk)]['total'] ?? -1);
        if ($actual !== $expected) {
            $errors[] = "Granular target gate total drift [{$chunk}: {$actual} != {$expected}].";
        }
    }

    if ((int) ($progress['total'] ?? -1) !== 105) {
        $errors[] = 'Granular N1.0 target gate denominator must remain 105 for the current C1-C6 runners.';
    }

    $fastTrack = $read('scripts/n1-target-fast-track.php');
    foreach ([
        'Strict chunk certification',
        'Granular exact-source gate progress',
        'nexoraRenderN10GranularProgress',
        'target-progress.json',
    ] as $marker) {
        if (! str_contains($fastTrack, $marker)) {
            $errors[] = "Fast-track progress output missing [{$marker}].";
        }
    }

    $execution = $read('scripts/n1-target-execution.php');
    foreach ([
        'After C1',
        'After C2',
        'After C3',
        'After C4',
        'After C5',
        'After C6',
        "'granular_progress'",
    ] as $marker) {
        if (! str_contains($execution, $marker)) {
            $errors[] = "Target execution progress checkpoint missing [{$marker}].";
        }
    }

    $plan = $read('scripts/lib/n1-target-plan.php');
    if (! str_contains($plan, "'granular'=>\$granularProgress")) {
        $errors[] = 'Target plan must expose granular C1-C6 gate progress.';
    }

    require_once $root.'/scripts/lib/n1-historical-typescript-remediation.php';
    $typescript = nexoraAnalyzeHistoricalTypeScriptRemediation($root);

    if ((int) ($typescript['historical_error_total'] ?? -1) !== 76) {
        $errors[] = 'Historical TypeScript remediation denominator must remain the observed 76 compiler errors.';
    }
    if ((int) ($typescript['historical_file_total'] ?? -1) !== 11) {
        $errors[] = 'Historical TypeScript remediation must cover the observed 11 files.';
    }
    if ((int) ($typescript['source_remediated_errors'] ?? -1) !== 76) {
        $errors[] = 'All 76 historical compiler-error families must remain source-remediated.';
    }
    if ((int) ($typescript['source_remediated_files'] ?? -1) !== 11) {
        $errors[] = 'All 11 historical compiler-error files must remain source-remediated.';
    }

    $ledger = $read('scripts/lib/n1-historical-typescript-remediation.php');
    foreach ([
        "'typecheck'",
        "'vite-build'",
        'target_verified_errors',
        'source_remediated_errors',
    ] as $marker) {
        if (! str_contains($ledger, $marker)) {
            $errors[] = "Historical TypeScript ledger missing verification separation [{$marker}].";
        }
    }

    if (str_contains($ledger, "'target_verified_errors' => \$sourceRemediatedErrors")) {
        $errors[] = 'Source remediation must never be automatically promoted to real-target TypeScript verification.';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'granular_target_gates' => 105,
            'strict_chunks' => 6,
            'historical_typescript_errors' => 76,
            'historical_typescript_files' => 11,
            'automatic_source_to_target_promotion' => 0,
        ],
    ];
}
