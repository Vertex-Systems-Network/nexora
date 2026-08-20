<?php

declare(strict_types=1);

require_once __DIR__.'/dependency-lock-intake.php';

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeSemanticLockReproducibilityContracts(string $root): array
{
    $errors = [];
    $read = static fn (string $path): string => is_file($root.'/'.$path) ? (string) file_get_contents($root.'/'.$path) : '';

    foreach ([
        'scripts/lib/dependency-lock-intake.php',
        'scripts/dependency-lock-refresh.php',
        'scripts/lib/dependency-candidate-supply-chain.php',
    ] as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "semantic lock reproducibility artifact missing [{$file}]";
        }
    }

    $intake = $read('scripts/lib/dependency-lock-intake.php');
    foreach ([
        'nexoraCanonicalizeDependencyLockValue',
        'nexoraDependencyLockSemanticSha',
        'nexoraDependencyLockSemanticDigests',
        "['packages', 'packages-dev']",
        'ksort($value, SORT_STRING)',
    ] as $marker) {
        if (! str_contains($intake, $marker)) {
            $errors[] = "semantic lock canonicalization missing [{$marker}]";
        }
    }

    $refresh = $read('scripts/dependency-lock-refresh.php');
    foreach ([
        "'semantic_hashes' => nexoraDependencyLockSemanticDigests(",
        '$rawExactMatch',
        '$semanticExactMatch',
        'workspace A and B canonical dependency digests differ',
        "'raw_exact_match' =>",
        "'semantic_exact_match' =>",
        "'candidate_semantic_hashes' =>",
    ] as $marker) {
        if (! str_contains($refresh, $marker)) {
            $errors[] = "semantic lock refresh binding missing [{$marker}]";
        }
    }

    $provenance = $read('scripts/lib/dependency-candidate-supply-chain.php');
    foreach ([
        'composer_lock_semantic_sha256',
        'package_lock_semantic_sha256',
        '$fingerprintPayload = $summary;',
        '$fingerprintPayload[\'composer_lock_sha256\']',
        '$fingerprintPayload[\'package_lock_sha256\']',
    ] as $marker) {
        if (! str_contains($provenance, $marker)) {
            $errors[] = "semantic candidate provenance binding missing [{$marker}]";
        }
    }


    $temporary = sys_get_temp_dir().'/nexora-semantic-lock-contract-'.bin2hex(random_bytes(5));
    if (! mkdir($temporary, 0700, true) && ! is_dir($temporary)) {
        $errors[] = 'unable to create semantic lock regression fixture directory';
    } else {
        $a = $temporary.'/a.json';
        $b = $temporary.'/b.json';
        $c = $temporary.'/c.json';
        file_put_contents($a, '{"packages":[{"version":"1.0.0","name":"b"},{"name":"a","version":"1.0.0"}],"content-hash":"x","packages-dev":[]}');
        file_put_contents($b, "{\n  \"packages-dev\": [], \"content-hash\": \"x\", \"packages\": [{\"name\":\"a\",\"version\":\"1.0.0\"},{\"name\":\"b\",\"version\":\"1.0.0\"}]\n}");
        file_put_contents($c, '{"packages":[{"name":"a","version":"1.0.0"},{"name":"b","version":"2.0.0"}],"content-hash":"x","packages-dev":[]}');
        $shaA = nexoraDependencyLockSemanticSha($a);
        $shaB = nexoraDependencyLockSemanticSha($b);
        $shaC = nexoraDependencyLockSemanticSha($c);
        if (! is_string($shaA) || $shaA !== $shaB) {
            $errors[] = 'canonical semantic digest must ignore object/package ordering differences';
        }
        if ($shaA === $shaC) {
            $errors[] = 'canonical semantic digest must change when a dependency version changes';
        }
        @unlink($a); @unlink($b); @unlink($c); @rmdir($temporary);
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => [],
        'metrics' => [
            'independent_generation_runs' => 2,
            'raw_hashes_recorded' => 2,
            'semantic_hashes_compared' => 2,
            'c1_certification_gates' => 14,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
