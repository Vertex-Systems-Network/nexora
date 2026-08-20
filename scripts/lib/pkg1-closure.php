<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2).'/scripts/lib/source-attestation.php';
require_once dirname(__DIR__, 2).'/scripts/lib/dependency-lock-intake.php';

/** @return array{exit_code:int,stdout:string,stderr:string,duration_seconds:float} */
function nexoraPkg1Run(array $parts, string $cwd, array $environment = []): array
{
    $command = implode(' ', array_map(
        static fn (mixed $part): string => escapeshellarg((string) $part),
        $parts,
    ));
    $started = microtime(true);
    $process = @proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $cwd,
        $environment === [] ? null : $environment,
        ['bypass_shell' => false],
    );

    if (! is_resource($process)) {
        return [
            'exit_code' => 127,
            'stdout' => '',
            'stderr' => 'Unable to start process.',
            'duration_seconds' => round(microtime(true) - $started, 3),
        ];
    }

    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exit = proc_close($process);

    return [
        'exit_code' => $exit,
        'stdout' => $stdout,
        'stderr' => $stderr,
        'duration_seconds' => round(microtime(true) - $started, 3),
    ];
}

function nexoraPkg1Redact(string $text): string
{
    foreach ([
        '/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i',
        '/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i',
    ] as $pattern) {
        $text = (string) preg_replace($pattern, '$1[REDACTED]', $text);
    }

    return $text;
}

/** @param array<string,mixed> $payload */
function nexoraPkg1WriteJson(string $path, array $payload): void
{
    $directory = dirname($path);
    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException("Unable to create PKG-1 evidence directory [{$directory}].");
    }

    file_put_contents(
        $path,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
        LOCK_EX,
    );
}

/** @return array<string,mixed> */
function nexoraPkg1BaseState(string $root): array
{
    $platform = require $root.'/config/nexora.php';
    $source = nexoraComputeSourceAttestation($root);

    return [
        'schema' => 1,
        'package' => 'PKG-1',
        'scope' => 'Usable Release + C1 Closure',
        'platform_version' => (string) ($platform['version'] ?? 'unknown'),
        'source_tree_sha256' => $source['tree_sha256'],
        'status' => 'running',
        'phase' => 'starting',
        'progress_percent' => 0,
        'steps' => [],
        'first_blocker' => null,
        'updated_at' => gmdate(DATE_ATOM),
    ];
}

/** @param array<string,mixed> $state */
function nexoraPkg1Persist(string $root, array $state): void
{
    $state['updated_at'] = gmdate(DATE_ATOM);
    nexoraPkg1WriteJson(
        $root.'/storage/app/nexora/pkg1/latest.json',
        $state,
    );
}

/** @param array<string,mixed> $state */
function nexoraPkg1Render(array $state): string
{
    $percent = max(0, min(100, (int) ($state['progress_percent'] ?? 0)));
    $filled = (int) floor($percent / 5);
    $bar = str_repeat('█', $filled).str_repeat('░', 20 - $filled);

    return sprintf(
        "PKG-1 %d%% · %s · phase=%s\n%s",
        $percent,
        strtoupper((string) ($state['status'] ?? 'unknown')),
        (string) ($state['phase'] ?? 'unknown'),
        $bar,
    );
}

/**
 * Inspect the unpromoted dependency candidate without network access.
 *
 * @return array{present:bool,valid:bool,errors:list<string>,candidate_path:string,composer_lock_path:string,package_lock_path:string}
 */
function nexoraPkg1DependencyCandidateState(string $root): array
{
    $directory = $root.'/storage/app/nexora/dependency-intake/candidates';
    $candidatePath = $directory.'/candidate.json';
    $composerPath = $directory.'/composer.lock';
    $packagePath = $directory.'/package-lock.json';
    $present = is_file($candidatePath) || is_file($composerPath) || is_file($packagePath);
    $errors = [];

    if (! $present) {
        return [
            'present' => false,
            'valid' => false,
            'errors' => [],
            'candidate_path' => $candidatePath,
            'composer_lock_path' => $composerPath,
            'package_lock_path' => $packagePath,
        ];
    }

    if (! is_file($candidatePath)) $errors[] = 'candidate.json missing';
    if (! is_file($composerPath)) $errors[] = 'candidate composer.lock missing';
    if (! is_file($packagePath)) $errors[] = 'candidate package-lock.json missing';

    $candidate = [];
    if ($errors === []) {
        try {
            $decoded = json_decode((string) file_get_contents($candidatePath), true, 512, JSON_THROW_ON_ERROR);
            $candidate = is_array($decoded) ? $decoded : [];
            if ($candidate === []) $errors[] = 'candidate.json must contain a non-empty object';
        } catch (Throwable $exception) {
            $errors[] = 'candidate.json invalid: '.$exception->getMessage();
        }
    }

    if ($candidate !== []) {
        $platform = require $root.'/config/nexora.php';
        $source = nexoraComputeSourceAttestation($root);
        $composerHash = hash_file('sha256', $composerPath) ?: null;
        $packageHash = hash_file('sha256', $packagePath) ?: null;
        if (($candidate['status'] ?? null) !== 'review-required') $errors[] = 'candidate status is not review-required';
        if (($candidate['platform_version'] ?? null) !== (string) ($platform['version'] ?? 'unknown')) $errors[] = 'candidate platform version drift';
        if (($candidate['source_tree_sha256'] ?? null) !== $source['tree_sha256']) $errors[] = 'candidate source-tree drift';
        if (($candidate['reproducible'] ?? false) !== true) $errors[] = 'candidate is not reproducible';
        if (($candidate['supply_chain']['status'] ?? null) !== 'pass') $errors[] = 'candidate supply-chain admission is not PASS';
        if (! is_string($composerHash) || ($candidate['candidate_hashes']['composer_lock_sha256'] ?? null) !== $composerHash) $errors[] = 'candidate composer.lock hash mismatch';
        if (! is_string($packageHash) || ($candidate['candidate_hashes']['package_lock_sha256'] ?? null) !== $packageHash) $errors[] = 'candidate package-lock.json hash mismatch';
        $manifestHashes = nexoraDependencyManifestHashes($root);
        foreach ($manifestHashes as $key => $value) {
            if (($candidate['manifest_hashes'][$key] ?? null) !== $value) $errors[] = 'candidate manifest binding drift ['.$key.']';
        }
        $lockValidation = nexoraValidateDependencyLockPair($root, $composerPath, $packagePath, false);
        foreach ((array) ($lockValidation['errors'] ?? []) as $error) {
            $errors[] = 'candidate lock validation: '.(string) $error;
        }
    }

    return [
        'present' => true,
        'valid' => $errors === [],
        'errors' => array_values(array_unique($errors)),
        'candidate_path' => $candidatePath,
        'composer_lock_path' => $composerPath,
        'package_lock_path' => $packagePath,
    ];
}

/** @return string|null relative quarantine path */
function nexoraPkg1QuarantineStaleCandidate(string $root): ?string
{
    $source = $root.'/storage/app/nexora/dependency-intake/candidates';
    if (! is_dir($source)) return null;
    $base = $root.'/storage/app/nexora/dependency-intake/stale-candidates';
    if (! is_dir($base) && ! mkdir($base, 0775, true) && ! is_dir($base)) {
        throw new RuntimeException('Unable to create stale dependency-candidate quarantine directory.');
    }
    $name = gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
    $destination = $base.'/'.$name;
    if (! @rename($source, $destination)) {
        throw new RuntimeException('Unable to quarantine stale dependency candidate directory.');
    }
    return 'storage/app/nexora/dependency-intake/stale-candidates/'.$name;
}

