<?php

declare(strict_types=1);

/** @return array<string,mixed> */
function nexoraBuildInstallationProgress(string $root): array
{
    $lockPath = $root.'/storage/app/nexora/installed.lock';
    if (is_file($lockPath)) {
        return [
            'status' => 'committed',
            'percent' => 100,
            'stage' => 'lock',
            'run_id' => null,
            'message' => 'Permanent installation lock exists. Verify it with `php artisan nexora:install:lock-status --assert-valid`.',
        ];
    }

    $directory = $root.'/storage/app/nexora/installation-control';
    $files = is_dir($directory) ? (glob($directory.'/*.json') ?: []) : [];
    if ($files === []) {
        return [
            'status' => 'not-started',
            'percent' => 0,
            'stage' => null,
            'run_id' => null,
            'message' => 'No target installation run evidence exists for this exact runtime state.',
        ];
    }

    usort($files, static fn (string $left, string $right): int => (@filemtime($right) ?: 0) <=> (@filemtime($left) ?: 0));
    $state = null;
    foreach ($files as $file) {
        try {
            $decoded = json_decode((string) file_get_contents($file), true, 128, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            continue;
        }
        if (is_array($decoded)) {
            $state = $decoded;
            break;
        }
    }

    if (! is_array($state)) {
        return [
            'status' => 'unknown',
            'percent' => 0,
            'stage' => null,
            'run_id' => null,
            'message' => 'Installation control files exist but none are readable.',
        ];
    }

    $stage = (string) ($state['stage'] ?? 'starting');
    $status = (string) ($state['status'] ?? (($state['active'] ?? false) ? 'running' : 'unknown'));
    $weights = [
        'starting' => 0,
        'preflight' => 8,
        'database' => 18,
        'backup' => 24,
        'reset' => 32,
        'environment' => 42,
        'migrations' => 58,
        'seed' => 72,
        'admin' => 82,
        'runtime' => 90,
        'cleanup' => 96,
        'lock' => 98,
    ];
    $percent = $status === 'completed'
        ? 100
        : (int) ($weights[$stage] ?? 0);

    return [
        'status' => $status,
        'percent' => $percent,
        'stage' => $stage,
        'run_id' => $state['run_id'] ?? null,
        'active' => (bool) ($state['active'] ?? false),
        'protected_started' => (bool) ($state['protected_started'] ?? false),
        'platform_version' => $state['platform_version'] ?? null,
        'installer_protocol' => $state['installer_protocol'] ?? null,
        'blocker' => $state['failure_message'] ?? null,
        'failure_stage' => $state['failure_stage'] ?? null,
        'message' => $status === 'completed'
            ? 'Installation run completed.'
            : "Latest installation run is {$status} at stage {$stage}.",
    ];
}

/** @param array<string,mixed> $progress */
function nexoraPersistInstallationProgress(string $root, array $progress): void
{
    $path = $root.'/storage/app/nexora/n1-target-execution/installation-progress.json';
    $directory = dirname($path);
    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException('Unable to create installation progress directory.');
    }
    file_put_contents(
        $path,
        json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    );
}

/** @param array<string,mixed> $progress */
function nexoraRenderInstallationProgress(array $progress): string
{
    $percent = max(0, min(100, (int) ($progress['percent'] ?? 0)));
    $filled = (int) round($percent / 5);
    $bar = str_repeat('█', $filled).str_repeat('░', 20 - $filled);
    $stage = (string) ($progress['stage'] ?? 'not-started');
    $status = strtoupper((string) ($progress['status'] ?? 'unknown'));

    $blocker = trim((string) ($progress['blocker'] ?? ''));
    $suffix = $blocker !== ''
        ? "\nBlocker: ".mb_substr($blocker, 0, 240)
        : '';

    return "INSTALLATION {$percent}% · {$status} · stage={$stage}\n{$bar}{$suffix}";
}
