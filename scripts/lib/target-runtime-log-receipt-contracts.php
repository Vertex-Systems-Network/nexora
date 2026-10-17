<?php

declare(strict_types=1);

/**
 * Bind a PASS receipt's step logs to the exact bytes captured by the runner.
 * This is an internal integrity check: it does NOT cryptographically prove the
 * identity of the machine or person who submitted the evidence.
 *
 * The caller must first apply its own evidence-directory/ZIP safety checks.
 * We additionally constrain reads to the producer's canonical per-step paths.
 *
 * @param list<array<string,mixed>> $steps
 * @return list<string>
 */
function nexoraTargetRuntimeLogReceiptErrors(array $steps, string $sourceDir): array
{
    if (!is_dir($sourceDir) || is_link($sourceDir)
        || !is_dir($sourceDir.'/steps') || is_link($sourceDir.'/steps')) {
        return ['PASS evidence step-log directory is missing or unsafe'];
    }
    if (!array_is_list($steps) || $steps === [] || count($steps) > 511) {
        return ['PASS evidence step-log list is missing or outside limits'];
    }

    $seen = [];
    foreach ($steps as $step) {
        if (!is_array($step) || !is_string($step['id'] ?? null)
            || preg_match('/^[a-z0-9-]+$/D', $step['id']) !== 1
            || isset($seen[$step['id']])) {
            return ['PASS evidence log entry has an invalid or duplicate step id'];
        }
        $id = $step['id'];
        $seen[$id] = true;
        foreach (['stdout', 'stderr'] as $stream) {
            $relative = 'steps/'.$id.'.'.$stream.'.log';
            if (($step[$stream.'_log'] ?? null) !== $relative) {
                return ["PASS evidence step [{$id}] has a noncanonical {$stream} log path"];
            }
            $expectedHash = $step[$stream.'_sha256'] ?? null;
            if (!is_string($expectedHash) || preg_match('/^[a-f0-9]{64}$/D', $expectedHash) !== 1) {
                return ["PASS evidence step [{$id}] is missing a valid {$stream} log digest"];
            }
            $path = $sourceDir.'/'.$relative;
            if (!is_file($path) || is_link($path) || !is_readable($path)) {
                return ["PASS evidence step [{$id}] has a missing or unsafe {$stream} log"];
            }
            // A reused PHP process may have cached a prior small stat for this file.
            clearstatcache(true, $path);
            $bytes = @filesize($path);
            if (!is_int($bytes) || $bytes < 0 || $bytes > 16*1024*1024) {
                return ["PASS evidence step [{$id}] has an oversized {$stream} log"];
            }
            $actualHash = @hash_file('sha256', $path);
            if (!is_string($actualHash) || !hash_equals($expectedHash, $actualHash)) {
                return ["PASS evidence step [{$id}] has altered {$stream} log bytes"];
            }
        }
    }
    return [];
}
