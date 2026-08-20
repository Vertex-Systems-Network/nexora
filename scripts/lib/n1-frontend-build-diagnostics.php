<?php

declare(strict_types=1);

/**
 * Historical Laragon TypeScript failure baseline captured from the authoritative
 * target build. This is a regression ledger, not certification evidence.
 *
 * @return array<string,array{historical_errors:int,codes:array<string,int>,family:string}>
 */
function nexoraFrontendBuildHistoricalBaseline(): array
{
    return [
        'resources/js/admin/pages/Admin/Automation/Form.tsx' => [
            'historical_errors' => 50,
            'codes' => ['TS2339' => 24, 'TS2345' => 13, 'TS7006' => 12, 'TS2322' => 1],
            'family' => 'Inertia useForm inference and nested FormDataConvertible typing',
        ],
        'resources/js/admin/pages/Admin/Cloud/Index.tsx' => [
            'historical_errors' => 1,
            'codes' => ['TS2345' => 1],
            'family' => 'Inertia router RequestPayload typing',
        ],
        'resources/js/admin/pages/Admin/Discovery/Index.tsx' => [
            'historical_errors' => 1,
            'codes' => ['TS2345' => 1],
            'family' => 'Inertia router RequestPayload typing',
        ],
        'resources/js/admin/pages/Admin/Distribution/Index.tsx' => [
            'historical_errors' => 1,
            'codes' => ['TS2339' => 1],
            'family' => 'useForm transform() chaining',
        ],
        'resources/js/admin/pages/Admin/Documents/Form.tsx' => [
            'historical_errors' => 3,
            'codes' => ['TS2344' => 1, 'TS2339' => 2],
            'family' => 'nested form serializability and form error typing',
        ],
        'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx' => [
            'historical_errors' => 14,
            'codes' => ['TS2339' => 6, 'TS2345' => 6, 'TS2322' => 2],
            'family' => 'enterprise SSO nested FormDataConvertible typing',
        ],
        'resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx' => [
            'historical_errors' => 1,
            'codes' => ['TS2322' => 1],
            'family' => 'NavLink component API mismatch',
        ],
        'resources/js/admin/pages/Admin/Media/Index.tsx' => [
            'historical_errors' => 1,
            'codes' => ['TS2339' => 1],
            'family' => 'useForm transform() chaining',
        ],
        'resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx' => [
            'historical_errors' => 1,
            'codes' => ['TS2322' => 1],
            'family' => 'NavLink component API mismatch',
        ],
        'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx' => [
            'historical_errors' => 1,
            'codes' => ['TS2339' => 1],
            'family' => 'useForm transform() chaining',
        ],
        'resources/js/admin/pages/Admin/Studio/Editor.tsx' => [
            'historical_errors' => 2,
            'codes' => ['TS2339' => 2],
            'family' => 'useForm transform() chaining',
        ],
    ];
}

/**
 * Parse TypeScript diagnostics from plain or pretty CLI output.
 *
 * Supported forms include:
 *   file.tsx:17:10 - error TS2339: message
 *   file.tsx(17,10): error TS2339: message
 *
 * @return list<array{file:string,line:int,column:int,code:string,message:string}>
 */
function nexoraParseTypeScriptDiagnostics(string $output, string $root = ''): array
{
    $output = nexoraStripAnsi($output);
    $diagnostics = [];

    $patterns = [
        '/^(.+?\.(?:ts|tsx)):(\d+):(\d+)\s+-\s+error\s+(TS\d+):\s*(.+)$/mi',
        '/^(.+?\.(?:ts|tsx))\((\d+),(\d+)\):\s+error\s+(TS\d+):\s*(.+)$/mi',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $output, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                $diagnostics[] = [
                    'file' => nexoraNormalizeTypeScriptDiagnosticPath((string) $match[1], $root),
                    'line' => (int) $match[2],
                    'column' => (int) $match[3],
                    'code' => strtoupper((string) $match[4]),
                    'message' => trim((string) $match[5]),
                ];
            }
        }
    }

    // Both patterns cannot match the same diagnostic shape, but stable-sort and
    // de-duplicate defensively so merged stdout/stderr remains deterministic.
    $unique = [];
    foreach ($diagnostics as $diagnostic) {
        $key = implode('|', [
            strtolower($diagnostic['file']),
            (string) $diagnostic['line'],
            (string) $diagnostic['column'],
            $diagnostic['code'],
            $diagnostic['message'],
        ]);
        $unique[$key] = $diagnostic;
    }

    $diagnostics = array_values($unique);
    usort($diagnostics, static function (array $left, array $right): int {
        return [strtolower($left['file']), $left['line'], $left['column'], $left['code']]
            <=> [strtolower($right['file']), $right['line'], $right['column'], $right['code']];
    });

    return $diagnostics;
}

/** @return array<string,mixed> */
function nexoraAnalyzeFrontendBuildOutput(
    string $output,
    string $root = '',
    ?int $exitCode = null,
    string $command = 'unknown',
): array {
    $baseline = nexoraFrontendBuildHistoricalBaseline();
    $diagnostics = nexoraParseTypeScriptDiagnostics($output, $root);
    $byFile = [];
    $byCode = [];
    $historicalDiagnostics = 0;
    $historicalFiles = [];
    $historicalCodeMatches = 0;

    foreach ($diagnostics as $diagnostic) {
        $file = $diagnostic['file'];
        $code = $diagnostic['code'];
        $byFile[$file] = ($byFile[$file] ?? 0) + 1;
        $byCode[$code] = ($byCode[$code] ?? 0) + 1;

        if (isset($baseline[$file])) {
            $historicalDiagnostics++;
            $historicalFiles[$file] = true;
            if (isset($baseline[$file]['codes'][$code])) {
                $historicalCodeMatches++;
            }
        }
    }

    ksort($byFile, SORT_STRING);
    ksort($byCode, SORT_STRING);

    $historicalRows = [];
    foreach ($baseline as $file => $entry) {
        $current = $byFile[$file] ?? 0;
        $historicalRows[] = [
            'file' => $file,
            'family' => $entry['family'],
            'historical_errors' => $entry['historical_errors'],
            'current_diagnostics' => $current,
            'regressed' => $current > 0,
        ];
    }

    $dependencyGraphMissing = preg_match(
        "/Cannot find type definition file for ['\"]vite\\/client['\"]|Cannot find module ['\"][^'\"]+['\"]|Cannot find type definition file for/i",
        $output,
    ) === 1;

    $compilerClean = $diagnostics === [] && ($exitCode === null || $exitCode === 0);
    $historicalClean = $historicalDiagnostics === 0;

    return [
        'schema' => 1,
        'command' => $command,
        'exit_code' => $exitCode,
        'status' => $compilerClean ? 'pass' : 'fail',
        'compiler_clean' => $compilerClean,
        'dependency_graph_missing' => $dependencyGraphMissing,
        'diagnostic_count' => count($diagnostics),
        'diagnostic_file_count' => count($byFile),
        'diagnostics_by_file' => $byFile,
        'diagnostics_by_code' => $byCode,
        'historical_baseline_error_count' => array_sum(array_column($baseline, 'historical_errors')),
        'historical_baseline_file_count' => count($baseline),
        'historical_target_diagnostics' => $historicalDiagnostics,
        'historical_target_files_with_diagnostics' => count($historicalFiles),
        'historical_family_code_matches' => $historicalCodeMatches,
        'historical_targets_clean' => $historicalClean,
        'historical_rows' => $historicalRows,
        'first_diagnostic' => $diagnostics[0] ?? null,
        'diagnostics' => $diagnostics,
        'output_sha256' => hash('sha256', $output),
        'certification_effect' => 'diagnostic-only; C1 PASS still requires the ordered C1 typecheck and Vite build gates to exit 0 on the exact target source/dependency graph',
    ];
}

function nexoraWriteFrontendBuildDiagnostics(string $path, array $diagnostics): string
{
    $directory = dirname($path);
    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException("Unable to create frontend diagnostic directory [{$directory}].");
    }

    $json = json_encode(
        $diagnostics,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ).PHP_EOL;
    if (file_put_contents($path, $json, LOCK_EX) === false) {
        throw new RuntimeException("Unable to write frontend build diagnostics [{$path}].");
    }

    return hash('sha256', $json);
}

function nexoraNormalizeTypeScriptDiagnosticPath(string $path, string $root = ''): string
{
    $path = trim(str_replace('\\', '/', nexoraStripAnsi($path)));
    $root = rtrim(str_replace('\\', '/', trim($root)), '/');

    if ($root !== '' && str_starts_with(strtolower($path), strtolower($root.'/'))) {
        $path = substr($path, strlen($root) + 1);
    }

    $resourcePosition = stripos($path, 'resources/js/');
    if ($resourcePosition !== false) {
        $path = substr($path, $resourcePosition);
    }

    return ltrim($path, './');
}

function nexoraStripAnsi(string $value): string
{
    return (string) preg_replace('/\x1B(?:[@-_][0-?]*[ -\/]*[@-~]|\][^\x07]*(?:\x07|\x1B\\\\))/', '', $value);
}
