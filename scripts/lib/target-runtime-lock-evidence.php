<?php

declare(strict_types=1);

/**
 * Lockfile inputs are intentionally excluded from the general source-tree
 * attestation; a claimed PASS runtime receipt must supply both exact digest
 * bindings independently. An absent/null fingerprint must never silently
 * disable verification for PASS evidence.
 *
 * Blocked diagnostics may have been produced before dependency preflight, so
 * missing lock fingerprints are tolerated for blocked receipts ONLY. A blocked
 * receipt remains ineligible for --require-pass and target acceptance.
 *
 * @param array<string,mixed> $environment
 * @return list<string>
 */
function nexoraTargetRuntimeLockEvidenceErrors(string $root, array $environment, bool $requireComplete): array
{
    $bindings = [
        'composer_lock_sha256' => 'composer.lock',
        'package_lock_sha256' => 'package-lock.json',
    ];
    $errors = [];
    foreach ($bindings as $key => $relative) {
        $expected = $environment[$key] ?? null;
        if ($expected === null && !$requireComplete) {
            continue;
        }
        if (!is_string($expected) || preg_match('/^[a-f0-9]{64}$/D', $expected) !== 1) {
            $errors[] = "Evidence missing or invalid {$relative} digest.";
            continue;
        }
        $file = $root.'/'.$relative;
        if (is_link($file) || !is_file($file) || !is_readable($file)) {
            $errors[] = "Evidence {$relative} source is missing or unsafe.";
            continue;
        }
        $actual = @hash_file('sha256', $file);
        if (!is_string($actual) || !hash_equals($expected, $actual)) {
            $errors[] = "Evidence {$relative} digest does not match current source.";
        }
    }
    return $errors;
}
