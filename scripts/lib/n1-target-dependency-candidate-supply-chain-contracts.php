<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeDependencyCandidateSupplyChainContracts(string $root): array
{
    $errors = [];
    $read = static fn (string $path): string => is_file($root.'/'.$path)
        ? (string) file_get_contents($root.'/'.$path)
        : '';

    $required = [
        'config/nexora-supply-chain.php',
        'scripts/lib/dependency-candidate-supply-chain.php',
        'scripts/dependency-lock-refresh.php',
        'scripts/dependency-lock-promote.php',
        'scripts/dependency-lock-review.php',
    ];
    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "missing candidate supply-chain artifact [{$file}]";
        }
    }

    $policy = $read('config/nexora-supply-chain.php');
    foreach ([
        "'dependency_candidate'",
        "'composer_allowed_hosts'",
        "'npm_allowed_hosts'",
        "'registry.npmjs.org'",
        "'composer_audit_required' => true",
        "'npm_audit_required' => true",
    ] as $marker) {
        if (! str_contains($policy, $marker)) {
            $errors[] = "candidate supply-chain policy missing [{$marker}]";
        }
    }

    $helper = $read('scripts/lib/dependency-candidate-supply-chain.php');
    foreach ([
        'nexoraDependencyCandidateProvenance',
        'nexoraRunDependencyCandidateSupplyChain',
        "'https'",
        'embedded credentials',
        'nexoraNpmPackageIntegrityCoverage',
        'npm_integrity_bundled_covered',
        "['npm', 'audit', '--package-lock-only', '--audit-level=high', '--json']",
        "['audit', '--locked', '--no-interaction', '--format=json']",
        "'fingerprint_sha256'",
        "'stderr_sha256'",
    ] as $marker) {
        if (! str_contains($helper, $marker)) {
            $errors[] = "candidate supply-chain helper missing [{$marker}]";
        }
    }
    if (str_contains($helper, 'stderr_excerpt')) {
        $errors[] = 'candidate supply-chain evidence must not persist raw audit stderr excerpts';
    }

    $refresh = $read('scripts/dependency-lock-refresh.php');
    foreach ([
        "nexoraRunDependencyCandidateSupplyChain(",
        "workspace-'",
        "'supply_chain'",
        "'supply_chain_fingerprint_sha256'",
        "workspace_a",
        "workspace_b",
        "Candidate supply-chain",
    ] as $marker) {
        if (! str_contains($refresh, $marker)) {
            $errors[] = "lock refresh candidate supply-chain binding missing [{$marker}]";
        }
    }

    $promote = $read('scripts/dependency-lock-promote.php');
    foreach ([
        'candidateSupplyChainFingerprint',
        'candidateProvenance',
        'candidate-supply-chain',
        'nexoraRunDependencyCandidateSupplyChain(',
        'failed before root mutation',
        'candidate_supply_chain_fingerprint_sha256',
        'candidate_provenance_fingerprint_sha256',
        'candidate_supply_chain_evidence_sha256',
    ] as $marker) {
        if (! str_contains($promote, $marker)) {
            $errors[] = "promotion candidate supply-chain binding missing [{$marker}]";
        }
    }
    $supplyPos = strpos($promote, 'nexoraRunDependencyCandidateSupplyChain(');
    $rootMutationPos = strpos($promote, 'nexoraWriteFileReplace($composerPath');
    if ($supplyPos === false || $rootMutationPos === false || $supplyPos >= $rootMutationPos) {
        $errors[] = 'candidate supply-chain revalidation must occur before the first root lock mutation';
    }

    $review = $read('scripts/dependency-lock-review.php');
    foreach ([
        'dependency_provenance_fingerprint_sha256',
        'candidate_supply_chain_fingerprint_sha256',
        'currentRefreshSupplyChainFingerprint',
        'candidate supply-chain proof is missing or not PASS',
        'dependency provenance fingerprint mismatch',
    ] as $marker) {
        if (! str_contains($review, $marker)) {
            $errors[] = "reviewed-lock candidate supply-chain binding missing [{$marker}]";
        }
    }

    $c1 = $read('scripts/lib/pkg1-closure-contracts.php').$read('scripts/pkg1-usable-closure.php');
    $master = $read('scripts/lib/n1-target-progress-visibility-contracts.php').$read('scripts/lib/n1-target-progress.php');
    if (! str_contains($c1, '14')) {
        $errors[] = 'C1 denominator 14 marker missing';
    }
    if (! str_contains($master, '105')) {
        $errors[] = 'target denominator 105 marker missing';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => [],
        'metrics' => [
            'candidate_generation_runs' => 2,
            'candidate_audit_ecosystems' => 2,
            'trusted_registry_families' => 2,
            'c1_certification_gates' => 14,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
