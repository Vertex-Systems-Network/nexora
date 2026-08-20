<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/n1-frontend-build-diagnostics.php';
require_once $root.'/scripts/lib/n1-historical-typescript-remediation.php';

$options = nexoraFrontendDoctorOptions($argv);
$source = nexoraComputeSourceAttestation($root);
$historical = nexoraAnalyzeHistoricalTypeScriptRemediation($root);
$platform = require $root.'/config/nexora.php';
$version = (string) ($platform['version'] ?? 'unknown');
$reports = [];
$status = 'pass';

if ($options['log'] !== null) {
    $path = nexoraFrontendDoctorAbsolutePath($root, $options['log']);
    if (! is_file($path)) {
        fwrite(STDERR, "[N1.0-C1 Frontend Doctor] Log file does not exist [{$path}].\n");
        exit(2);
    }

    $output = (string) file_get_contents($path);
    $reports['log'] = nexoraAnalyzeFrontendBuildOutput(
        $output,
        $root,
        null,
        'external-log',
    );
    if (! $reports['log']['compiler_clean']) {
        $status = 'fail';
    }
}

if ($options['run']) {
    $environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
    $typecheck = nexoraFrontendDoctorRunCommand(
        ['npm', 'run', 'typecheck'],
        $root,
        $environment,
    );
    $reports['typecheck'] = nexoraAnalyzeFrontendBuildOutput(
        $typecheck['output'],
        $root,
        $typecheck['exit_code'],
        'npm run typecheck',
    );

    if ($typecheck['exit_code'] !== 0) {
        $status = 'fail';
        $reports['build'] = [
            'status' => 'not-run',
            'reason' => 'TypeScript typecheck failed; Vite build was not executed.',
        ];
    } else {
        $build = nexoraFrontendDoctorRunCommand(
            ['npm', 'run', 'build'],
            $root,
            $environment,
        );
        $reports['build'] = nexoraAnalyzeFrontendBuildOutput(
            $build['output'],
            $root,
            $build['exit_code'],
            'npm run build',
        );
        if ($build['exit_code'] !== 0) {
            $status = 'fail';
        }
    }
}

if ($options['log'] === null && ! $options['run']) {
    $status = $historical['errors'] === [] ? 'source-pass' : 'source-fail';
}

$summary = [
    'schema' => 1,
    'scope' => 'N1.0-C1 frontend build diagnostics',
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'status' => $status,
    'source_remediation' => [
        'historical_errors' => $historical['historical_error_total'],
        'historical_files' => $historical['historical_file_total'],
        'source_remediated_errors' => $historical['source_remediated_errors'],
        'source_remediated_files' => $historical['source_remediated_files'],
        'target_verified' => $historical['target_verified'],
    ],
    'reports' => $reports,
    'certification_effect' => 'This doctor never promotes C1. Only scripts/n1-c1-dependency-certify.php can produce ordered C1 target evidence.',
    'generated_at' => gmdate(DATE_ATOM),
];

if ($options['write']) {
    $runId = gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
    $path = $root.'/storage/app/nexora/n1-c1/frontend-build-doctor/'.$runId.'/summary.json';
    nexoraWriteFrontendBuildDiagnostics($path, $summary);
    $summary['written_to'] = str_replace('\\', '/', substr($path, strlen($root) + 1));
}

if ($options['json']) {
    fwrite(STDOUT, json_encode(
        $summary,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ).PHP_EOL);
} else {
    nexoraFrontendDoctorRender($summary);
}

if ($options['assertClean']) {
    exit(in_array($status, ['pass', 'source-pass'], true) ? 0 : 1);
}

exit(in_array($status, ['pass', 'source-pass'], true) ? 0 : 1);

/** @return array{log:?string,run:bool,write:bool,json:bool,assertClean:bool} */
function nexoraFrontendDoctorOptions(array $argv): array
{
    $options = [
        'log' => null,
        'run' => false,
        'write' => false,
        'json' => false,
        'assertClean' => false,
    ];

    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--run') {
            $options['run'] = true;
        } elseif ($argument === '--write') {
            $options['write'] = true;
        } elseif ($argument === '--json') {
            $options['json'] = true;
        } elseif ($argument === '--assert-clean') {
            $options['assertClean'] = true;
        } elseif (str_starts_with($argument, '--log=')) {
            $options['log'] = substr($argument, strlen('--log='));
        } else {
            fwrite(STDERR, "Unknown option [{$argument}].\n");
            exit(2);
        }
    }

    return $options;
}

function nexoraFrontendDoctorAbsolutePath(string $root, string $path): string
{
    if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $path) === 1) {
        return $path;
    }

    return $root.DIRECTORY_SEPARATOR.$path;
}

/** @return array{exit_code:int,output:string} */
function nexoraFrontendDoctorRunCommand(array $parts, string $root, array $environment): array
{
    $command = implode(' ', array_map(
        static fn (string $part): string => escapeshellarg($part),
        array_map('strval', $parts),
    ));

    $process = @proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $root,
        $environment,
        ['bypass_shell' => false],
    );

    if (! is_resource($process)) {
        return [
            'exit_code' => 127,
            'output' => 'Unable to start process.',
        ];
    }

    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    return [
        'exit_code' => proc_close($process),
        'output' => trim($stdout."\n".$stderr),
    ];
}

/** @param array<string,mixed> $summary */
function nexoraFrontendDoctorRender(array $summary): void
{
    $source = (array) ($summary['source_remediation'] ?? []);
    fwrite(STDOUT, sprintf(
        "Nexora frontend build doctor · %s\nSource remediation: %d/%d historical diagnostics across %d/%d files\n",
        (string) ($summary['platform_version'] ?? 'unknown'),
        (int) ($source['source_remediated_errors'] ?? 0),
        (int) ($source['historical_errors'] ?? 0),
        (int) ($source['source_remediated_files'] ?? 0),
        (int) ($source['historical_files'] ?? 0),
    ));

    foreach ((array) ($summary['reports'] ?? []) as $name => $report) {
        if (! is_array($report)) {
            continue;
        }
        if (($report['status'] ?? null) === 'not-run') {
            fwrite(STDOUT, strtoupper((string) $name).': NOT RUN — '.($report['reason'] ?? '')."\n");
            continue;
        }
        fwrite(STDOUT, sprintf(
            "%s: %s · diagnostics=%d · files=%d · historical-target diagnostics=%d/%d files\n",
            strtoupper((string) $name),
            strtoupper((string) ($report['status'] ?? 'unknown')),
            (int) ($report['diagnostic_count'] ?? 0),
            (int) ($report['diagnostic_file_count'] ?? 0),
            (int) ($report['historical_target_diagnostics'] ?? 0),
            (int) ($report['historical_target_files_with_diagnostics'] ?? 0),
        ));

        if (($report['dependency_graph_missing'] ?? false) === true) {
            fwrite(STDOUT, "  blocker: reviewed npm dependency graph is missing/incomplete\n");
        }
        if (is_array($report['first_diagnostic'] ?? null)) {
            $first = $report['first_diagnostic'];
            fwrite(STDOUT, sprintf(
                "  first: %s:%d:%d %s %s\n",
                (string) ($first['file'] ?? 'unknown'),
                (int) ($first['line'] ?? 0),
                (int) ($first['column'] ?? 0),
                (string) ($first['code'] ?? 'TS?'),
                (string) ($first['message'] ?? ''),
            ));
        }
    }

    fwrite(STDOUT, 'Status: '.strtoupper((string) ($summary['status'] ?? 'unknown'))."\n");
    fwrite(STDOUT, "C1 effect: diagnostic-only; ordered C1 gates remain authoritative.\n");
}
