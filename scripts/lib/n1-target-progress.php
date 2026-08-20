<?php

declare(strict_types=1);

require_once __DIR__.'/source-attestation.php';

/** @return array<string,mixed>|null */
function nexoraTargetProgressReadJson(string $path): ?array
{
    if (! is_file($path)) {
        return null;
    }

    try {
        $decoded = json_decode(
            (string) file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    } catch (Throwable) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

/** @return array{version:string,source_tree_sha256:string} */
function nexoraTargetProgressIdentity(string $root): array
{
    $platform = require $root.'/config/nexora.php';
    $source = nexoraComputeSourceAttestation($root);

    return [
        'version' => (string) ($platform['version'] ?? 'unknown'),
        'source_tree_sha256' => (string) $source['tree_sha256'],
    ];
}

/** @param array<string,mixed> $data @param array{version:string,source_tree_sha256:string} $identity */
function nexoraTargetProgressMatchesIdentity(array $data, array $identity): bool
{
    return ($data['platform_version'] ?? null) === $identity['version']
        && ($data['source_tree_sha256'] ?? null) === $identity['source_tree_sha256'];
}

/** @param list<string> $ids @param array<string,mixed>|null $report */
function nexoraTargetProgressFromSteps(array $ids, ?array $report, array $identity): array
{
    $passed = 0;
    $statusById = [];

    if (is_array($report) && nexoraTargetProgressMatchesIdentity($report, $identity)) {
        foreach ((array) ($report['steps'] ?? []) as $step) {
            if (! is_array($step)) {
                continue;
            }

            $id = (string) ($step['id'] ?? '');
            if ($id === '' || ! in_array($id, $ids, true)) {
                continue;
            }

            $status = (string) ($step['status'] ?? '');
            if (in_array($status, ['pass', 'reused-pass'], true)) {
                $statusById[$id] = 'pass';
            } elseif (! isset($statusById[$id])) {
                $statusById[$id] = $status !== '' ? $status : 'pending';
            }
        }

        foreach ($ids as $id) {
            if (($statusById[$id] ?? 'pending') === 'pass') {
                $passed++;
            }
        }

        if (($report['status'] ?? null) === 'pass') {
            // A canonical PASS report is authoritative for the complete chunk.
            $passed = count($ids);
        }
    }

    $total = count($ids);
    $percent = $total > 0 ? (int) floor(($passed / $total) * 100) : 0;

    return [
        'passed' => $passed,
        'total' => $total,
        'percent' => $percent,
        'status' => $passed === $total && $total > 0 ? 'pass' : ($passed > 0 ? 'partial' : 'pending'),
        'first_blocker' => is_array($report) && nexoraTargetProgressMatchesIdentity($report, $identity)
            ? ($report['first_blocker'] ?? null)
            : null,
    ];
}

/** @return list<string> */
function nexoraTargetProgressC1Gates(): array
{
    // composer-install/npm-ci are setup actions, not certification gates.
    return [
        'prerequisite-intake',
        'reviewed-locks',
        'strict-locks',
        'runtime-policy',
        'installed-state',
        'inertia-contract',
        'typecheck',
        'frontend-tests',
        'vite-build',
        'dependency-provenance',
        'dependency-audit',
        'dependency-sbom',
        'asset-budgets',
        'toolchain-freeze',
    ];
}

/** @return list<string> */
function nexoraTargetProgressC2Gates(): array
{
    return [
        'c1-evidence','installed-dependencies','package-discover','installation-lock-status',
        'installation-lock-integrity-test','installation-bootstrap-receipt-test',
        'installation-lock-http-failclosed-test','installer-consent-flow-test',
        'installation-resume-provenance-test','password-risk-consent-test','optimize-clear-before',
        'artisan-about','route-list','schedule-list','database-prepare','database-version-doctor',
        'migrate-fresh-seed','seed-idempotency','tenant-seed-isolation-test',
        'tenant-execution-boundary-test','database-data-plane-baseline','migration-reset',
        'migration-rebuild','seed-rebuild','database-data-plane-rebuild','runtime-sync','runtime-cache',
        'database-isolation-test','environment-doctor','filesystem-doctor','transfer-doctor',
        'runtime-doctor','runtime-engine-status','database-data-plane-status','runtime-storage-status',
        'runtime-service-status','runtime-host-status','runtime-resource-status','runtime-policy-status',
        'runtime-process-status','runtime-dependency-status','runtime-compatibility-status',
        'concurrency-doctor','phpunit-full','pint','artisan-optimize','optimized-about',
        'optimized-route-list','optimized-schedule-list','optimized-environment-doctor',
        'optimized-database-doctor','optimize-clear-final',
    ];
}

/** @return list<string> */
function nexoraTargetProgressC3Gates(): array
{
    return ['c2-evidence','matrix-prerequisite','strict-five-db-matrix','matrix-evidence','matrix-source-binding'];
}

/** @return list<string> */
function nexoraTargetProgressC4Gates(): array
{
    return ['c2-evidence','zero-install-contract','upgrade-contract','distributed-upgrade-contract','backup-contract','operator-evidence','c4-evidence'];
}

/** @return list<string> */
function nexoraTargetProgressC5Gates(): array
{
    return ['c2-evidence','browser-source','performance-source','security-source','build-assets','http-performance','c5-evidence'];
}

/** @return list<string> */
function nexoraTargetProgressC6Gates(): array
{
    return [
        'c1-evidence','c2-evidence','c3-evidence','c4-evidence','c5-evidence','ha-source',
        'ha-readiness','ha-rehearsal','ha-evidence','target-url-consistency','target-evidence-intake',
        'release-signing-readiness','release-trust-anchor','production-runtime-stage','release-provenance',
        'final-target','production-artifact','session-finalize','closure-dashboard','c6-evidence',
    ];
}

/** @return array<string,mixed> */
function nexoraBuildN10GranularProgress(string $root): array
{
    $identity = nexoraTargetProgressIdentity($root);
    $definitions = [
        'c1' => [nexoraTargetProgressC1Gates(), $root.'/storage/app/nexora/n1-c1/latest.json'],
        'c2' => [nexoraTargetProgressC2Gates(), $root.'/storage/app/nexora/n1-c2/latest.json'],
        'c3' => [nexoraTargetProgressC3Gates(), $root.'/storage/app/nexora/n1-c3/latest.json'],
        'c4' => [nexoraTargetProgressC4Gates(), $root.'/storage/app/nexora/n1-c4/latest.json'],
        'c5' => [nexoraTargetProgressC5Gates(), $root.'/storage/app/nexora/n1-c5/latest.json'],
        'c6' => [nexoraTargetProgressC6Gates(), $root.'/storage/app/nexora/n1-c6/latest.json'],
    ];

    $chunks = [];
    $passed = 0;
    $total = 0;

    foreach ($definitions as $chunk => [$ids, $path]) {
        $progress = nexoraTargetProgressFromSteps(
            $ids,
            nexoraTargetProgressReadJson($path),
            $identity,
        );
        $chunks[$chunk] = $progress;
        $passed += (int) $progress['passed'];
        $total += (int) $progress['total'];
    }

    return [
        'schema' => 1,
        'platform_version' => $identity['version'],
        'source_tree_sha256' => $identity['source_tree_sha256'],
        'passed' => $passed,
        'total' => $total,
        'percent' => $total > 0 ? (int) floor(($passed / $total) * 100) : 0,
        'chunks' => $chunks,
        'generated_at' => gmdate(DATE_ATOM),
        'meaning' => 'Granular progress counts exact-source target-runner certification gates. It does not replace strict C1-C6 chunk PASS status.',
    ];
}

/** @param array<string,mixed> $progress */
function nexoraPersistN10GranularProgress(string $root, array $progress): string
{
    $directory = $root.'/storage/app/nexora/n1-target-execution';
    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException("Unable to create target progress directory [{$directory}].");
    }

    $path = $directory.'/target-progress.json';
    $temporary = $path.'.tmp.'.getmypid();
    $payload = json_encode(
        $progress,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ).PHP_EOL;

    if (file_put_contents($temporary, $payload, LOCK_EX) === false) {
        throw new RuntimeException("Unable to write target progress snapshot [{$temporary}].");
    }

    if (! @rename($temporary, $path)) {
        @unlink($temporary);
        throw new RuntimeException("Unable to publish target progress snapshot [{$path}].");
    }

    return $path;
}

function nexoraTargetProgressBar(int $percent): string
{
    $bounded = max(0, min(100, $percent));
    $filled = (int) round($bounded / 5);

    return str_repeat('█', $filled).str_repeat('░', 20 - $filled);
}

/** @param array<string,mixed> $progress */
function nexoraRenderN10GranularProgress(array $progress): string
{
    $lines = [];
    $lines[] = sprintf(
        'N1.0 granular target gates: %d/%d — %d%% %s',
        (int) ($progress['passed'] ?? 0),
        (int) ($progress['total'] ?? 0),
        (int) ($progress['percent'] ?? 0),
        nexoraTargetProgressBar((int) ($progress['percent'] ?? 0)),
    );

    foreach (['c1','c2','c3','c4','c5','c6'] as $chunk) {
        $row = (array) ($progress['chunks'][$chunk] ?? []);
        $lines[] = sprintf(
            '%s %d/%d — %d%% %s',
            strtoupper($chunk),
            (int) ($row['passed'] ?? 0),
            (int) ($row['total'] ?? 0),
            (int) ($row['percent'] ?? 0),
            nexoraTargetProgressBar((int) ($row['percent'] ?? 0)),
        );
    }

    return implode(PHP_EOL, $lines);
}
