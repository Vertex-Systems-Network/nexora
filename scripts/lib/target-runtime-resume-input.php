<?php

declare(strict_types=1);

/**
 * Read prior target-runtime JSON only from canonical, bounded, non-symlinked
 * receipt paths. Neither a saved latest pointer nor a previous run is proof of
 * external target readiness: the normal environment/step drift gates remain.
 *
 * @return array<string,mixed>|null
 */
function nexoraTargetRuntimeReadResumeJson(string $base, string $relative, int $maxBytes): ?array
{
    if ($maxBytes < 1 || $maxBytes > 16 * 1024 * 1024) {
        return null;
    }
    // Only the two producer-defined input names are accepted; do not expose
    // this read helper as an arbitrary repository file reader.
    if ($relative !== 'latest.json'
        && preg_match('~^[0-9]{8}-[0-9]{6}-[a-f0-9]{6}/summary\.json$~D', $relative) !== 1) {
        return null;
    }
    // Reject linked ancestors, not just the final receipt file. Otherwise a
    // linked storage/app or per-run directory could escape the target checkout.
    $cursor = $base;
    while (true) {
        if (is_link($cursor)) return null;
        $parent = dirname($cursor);
        if ($parent === $cursor) break;
        $cursor = $parent;
    }
    if (!is_dir($base)) return null;
    $parentDir = $base;
    if ($relative !== 'latest.json') {
        $parentDir .= '/'.strtok($relative, '/');
        if (is_link($parentDir) || !is_dir($parentDir)) return null;
    }
    $path = $base.'/'.$relative;
    if (is_link($path) || !is_file($path) || !is_readable($path)) return null;
    $canonicalBase = realpath($base);
    $canonicalPath = realpath($path);
    if (!is_string($canonicalBase) || !is_string($canonicalPath)
        || !str_starts_with($canonicalPath, $canonicalBase.DIRECTORY_SEPARATOR)) return null;

    clearstatcache(true, $path);
    $expectedSize = @filesize($path);
    if (!is_int($expectedSize) || $expectedSize < 1 || $expectedSize > $maxBytes) return null;
    $stream = @fopen($path, 'rb');
    if (!is_resource($stream)) return null;
    try {
        $before = fstat($stream);
        if (!is_array($before) || (($before['mode'] ?? 0) & 0170000) !== 0100000
            || ($before['size'] ?? -1) !== $expectedSize) return null;
        $raw = stream_get_contents($stream, $maxBytes + 1);
        $after = fstat($stream);
        if (!is_string($raw) || strlen($raw) !== $expectedSize || !is_array($after)
            || $before['dev'] !== $after['dev'] || $before['ino'] !== $after['ino']
            || $before['size'] !== $after['size'] || $before['mtime'] !== $after['mtime']) return null;
    } finally {
        fclose($stream);
    }
    // Ensure the path did not change to another file or symlink while reading.
    clearstatcache(true, $path);
    if (is_link($path) || !is_file($path)) return null;
    $current = @stat($path);
    if (!is_array($current) || $current['dev'] !== $before['dev']
        || $current['ino'] !== $before['ino'] || $current['size'] !== $before['size']) return null;
    try {
        $value = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        return is_array($value) && !array_is_list($value) ? $value : null;
    } catch (Throwable) {
        return null;
    }
}

/**
 * Check prior-run receipts before their PASS step IDs may be reused.
 * This is local integrity checking, not independent operator attestation.
 * A blocked run can legitimately end after a partial set of steps, but each
 * recorded step still must be self-consistent and bound to its actual logs.
 *
 * @param array<string,mixed> $summary
 * @return list<string>
 */
function nexoraTargetRuntimeResumeReceiptErrors(array $summary, string $runDir, string $runId): array
{
    require_once __DIR__.'/target-runtime-log-receipt-contracts.php';
    require_once __DIR__.'/target-runtime-receipt-contracts.php';

    if (($summary['schema'] ?? null) !== 2 || ($summary['run_id'] ?? null) !== $runId
        || preg_match('/^[0-9]{8}-[0-9]{6}-[a-f0-9]{6}$/D', $runId) !== 1) {
        return ['Prior target-runtime receipt has invalid schema or run ID.'];
    }
    $status = $summary['status'] ?? null;
    if (!in_array($status, ['blocked', 'target-readiness-pass', 'target-certification-pass'], true)) {
        return ['Prior target-runtime receipt has invalid status.'];
    }
    $steps = $summary['steps'] ?? null;
    if (!is_array($steps) || !array_is_list($steps) || $steps === [] || count($steps) > 511) {
        return ['Prior target-runtime receipt contains invalid step list.'];
    }
    foreach ($steps as $step) {
        if (!is_array($step) || !is_bool($step['required'] ?? null)
            || !in_array($step['status'] ?? null, ['pass', 'fail'], true)
            || !is_int($step['exit_code'] ?? null)
            || (($step['status'] === 'pass') !== ($step['exit_code'] === 0))) {
            return ['Prior target-runtime receipt has inconsistent step result.'];
        }
    }
    // A claimed complete PASS cannot be a fabricated one-step or incomplete
    // receipt. The existing producer-order contract checks all mandatory gates.
    if ($status !== 'blocked') {
        $errors = nexoraTargetRuntimePassReceiptErrors($summary);
        if ($errors !== []) return $errors;
    }
    // Even a blocked partial run must not enable reuse after its log content
    // is altered, deleted, replaced with a symlink or rebound to other steps.
    return nexoraTargetRuntimeLogReceiptErrors($steps, $runDir);
}
