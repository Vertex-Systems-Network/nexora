<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/dependency-lock-intake.php';
require_once $root.'/scripts/lib/dependency-toolchain.php';
require_once $root.'/scripts/lib/dependency-candidate-supply-chain.php';

$confirm = '';
$jsonOnly = in_array('--json', $argv, true);
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--confirm=')) {
        $confirm = trim(substr($argument, 10));
    }
}

if ($confirm !== 'REFRESH') {
    fwrite(
        STDERR,
        "[Nexora Dependency Lock Refresh] Explicit --confirm=REFRESH is required. "
        ."Refresh runs only in isolated workspaces and never promotes root lockfiles.\n",
    );
    exit(2);
}

$version = (string) ((require $root.'/config/nexora.php')['version'] ?? 'unknown');
$source = nexoraComputeSourceAttestation($root);
$manifestHashes = nexoraDependencyManifestHashes($root);
$before = nexoraDependencyRootLockHashes($root);
$environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
$toolchain = nexoraCollectDependencyToolchain($root);
$composer = nexoraLocateTargetComposer($root);
$runId = gmdate('Ymd\\THis\\Z').'-'.substr($source['tree_sha256'], 0, 12);
$baseDirectory = $root.'/storage/app/nexora/dependency-intake';
$runDirectory = $baseDirectory.'/lock-refresh-runs/'.$runId;
$candidateDirectory = $baseDirectory.'/candidates';
$promotionJournalPath = $baseDirectory.'/lock-promotion-journal.json';
$steps = [];
$errors = array_values((array) ($toolchain['errors'] ?? []));
$warnings = [];

if (is_file($promotionJournalPath)) {
    try {
        $journal = json_decode(
            (string) file_get_contents($promotionJournalPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    } catch (Throwable) {
        $journal = ['status' => 'invalid'];
    }
    if (! in_array((string) ($journal['status'] ?? ''), ['complete', 'rolled-back'], true)) {
        fwrite(
            STDERR,
            "[Nexora Dependency Lock Refresh] Incomplete lock promotion journal detected. "
            ."Run `scripts\\recover-dependency-lock-promotion.bat --confirm=ROLLBACK` first.\n",
        );
        exit(2);
    }
}

if (! is_dir($runDirectory) && ! mkdir($runDirectory, 0775, true) && ! is_dir($runDirectory)) {
    throw new RuntimeException('Unable to create dependency lock refresh run directory.');
}

$record = static function (
    string $workspaceId,
    string $stepId,
    array $command,
    array $result,
) use (&$steps): void {
    $steps[] = [
        'workspace' => $workspaceId,
        'id' => $stepId,
        'command' => array_values(array_map(
            static fn (mixed $value): string => basename((string) $value) === 'composer.phar'
                ? 'composer.phar'
                : (string) $value,
            $command,
        )),
        'exit_code' => $result['exit_code'],
        'status' => $result['exit_code'] === 0 ? 'pass' : 'fail',
        'stderr_excerpt' => substr(trim((string) $result['stderr']), 0, 1200),
    ];
};

/**
 * @return array{
 *   workspace:string,
 *   hashes:array<string,?string>,
 *   semantic_hashes:array<string,?string>,
 *   errors:list<string>,
 *   validation:array<string,mixed>,
 *   supply_chain:array<string,mixed>
 * }
 */
$generate = static function (string $workspaceId) use (
    $root,
    $runDirectory,
    $environment,
    $composer,
    $record,
): array {
    $workspace = $runDirectory.'/workspace-'.$workspaceId;
    $localErrors = [];
    if (! is_dir($workspace) && ! mkdir($workspace, 0775, true) && ! is_dir($workspace)) {
        throw new RuntimeException("Unable to create isolated dependency workspace [{$workspaceId}].");
    }

    foreach (['composer.json', 'package.json'] as $manifest) {
        if (! is_file($root.'/'.$manifest) || ! copy($root.'/'.$manifest, $workspace.'/'.$manifest)) {
            $localErrors[] = "Unable to stage {$manifest} in workspace {$workspaceId}.";
        }
    }
    foreach (['composer.lock', 'package-lock.json'] as $lock) {
        if (is_file($root.'/'.$lock) && ! copy($root.'/'.$lock, $workspace.'/'.$lock)) {
            $localErrors[] = "Unable to stage existing {$lock} in workspace {$workspaceId}.";
        }
    }

    if ($localErrors === []) {
        $composerArguments = is_file($workspace.'/composer.lock')
            ? [
                'update', '--lock', '--no-install', '--no-interaction', '--prefer-dist',
                '--no-scripts', '--no-plugins', '--no-progress',
            ]
            : [
                'update', '--no-install', '--no-interaction', '--prefer-dist',
                '--no-scripts', '--no-plugins', '--no-progress',
            ];
        $command = array_merge((array) $composer['command'], $composerArguments);
        $result = nexoraRunTargetCommand($command, $workspace, $environment);
        $record($workspaceId, 'composer-candidate-lock', $command, $result);
        if ($result['exit_code'] !== 0) {
            $detail = trim($result['stderr'] !== '' ? $result['stderr'] : $result['stdout']);
            $localErrors[] = 'Composer candidate generation failed in workspace '
                .$workspaceId.($detail !== '' ? ': '.substr($detail, 0, 1200) : '.');
        }
    }

    if ($localErrors === []) {
        $command = ['npm', 'install', '--package-lock-only', '--ignore-scripts', '--no-audit', '--no-fund'];
        $result = nexoraRunTargetCommand($command, $workspace, $environment);
        $record($workspaceId, 'npm-candidate-lock', $command, $result);
        if ($result['exit_code'] !== 0) {
            $detail = trim($result['stderr'] !== '' ? $result['stderr'] : $result['stdout']);
            $localErrors[] = 'npm candidate generation failed in workspace '
                .$workspaceId.($detail !== '' ? ': '.substr($detail, 0, 1200) : '.');
        }
    }

    $validation = [
        'errors' => [],
        'warnings' => [],
        'composer_packages' => 0,
        'npm_packages' => 0,
        'laravel_framework_locked_version' => null,
        'npm_integrity_missing' => 0,
        'npm_unsafe_sources' => 0,
    ];
    if ($localErrors === []) {
        $validation = nexoraValidateDependencyLockPair(
            $root,
            $workspace.'/composer.lock',
            $workspace.'/package-lock.json',
            true,
        );
        $localErrors = array_merge($localErrors, (array) $validation['errors']);
    }

    $supplyChain = [
        'status' => 'not-run',
        'fingerprint_sha256' => null,
        'provenance' => [],
        'audit' => [],
        'errors' => [],
    ];
    if ($localErrors === []) {
        $supplyChain = nexoraRunDependencyCandidateSupplyChain(
            $root,
            $workspace,
            (array) $composer['command'],
            $environment,
        );
        $localErrors = array_merge($localErrors, (array) ($supplyChain['errors'] ?? []));
    }

    return [
        'workspace' => $workspace,
        'hashes' => [
            'composer_lock_sha256' => nexoraHashOptionalFile($workspace.'/composer.lock'),
            'package_lock_sha256' => nexoraHashOptionalFile($workspace.'/package-lock.json'),
        ],
        'semantic_hashes' => nexoraDependencyLockSemanticDigests(
            $workspace.'/composer.lock',
            $workspace.'/package-lock.json',
        ),
        'errors' => array_values(array_unique($localErrors)),
        'validation' => $validation,
        'supply_chain' => $supplyChain,
    ];
};

$runA = null;
$runB = null;
if ($errors === []) {
    $runA = $generate('A');
    $errors = array_merge($errors, $runA['errors']);
    $warnings = array_merge($warnings, (array) $runA['validation']['warnings']);
}
if ($errors === []) {
    $runB = $generate('B');
    $errors = array_merge($errors, $runB['errors']);
    $warnings = array_merge($warnings, (array) $runB['validation']['warnings']);
}

$reproducible = false;
$rawExactMatch = false;
$semanticExactMatch = false;
$after = [
    'composer_lock_sha256' => null,
    'package_lock_sha256' => null,
];
$validation = $runA['validation'] ?? [
    'errors' => [],
    'warnings' => [],
    'composer_packages' => 0,
    'npm_packages' => 0,
    'laravel_framework_locked_version' => null,
    'npm_integrity_missing' => 0,
    'npm_unsafe_sources' => 0,
];

if ($runA !== null) {
    $after = $runA['hashes'];
}
if ($errors === [] && $runA !== null && $runB !== null) {
    $rawExactMatch = $runA['hashes'] === $runB['hashes'];
    $semanticExactMatch = $runA['semantic_hashes'] === $runB['semantic_hashes']
        && is_string($runA['semantic_hashes']['composer_lock_semantic_sha256'] ?? null)
        && is_string($runA['semantic_hashes']['package_lock_semantic_sha256'] ?? null);
    $reproducible = $semanticExactMatch
        && ($runA['supply_chain']['status'] ?? null) === 'pass'
        && ($runB['supply_chain']['status'] ?? null) === 'pass'
        && is_string($runA['supply_chain']['fingerprint_sha256'] ?? null)
        && hash_equals(
            (string) $runA['supply_chain']['fingerprint_sha256'],
            (string) ($runB['supply_chain']['fingerprint_sha256'] ?? ''),
        );
    if (! $semanticExactMatch) {
        $composerSemanticDiff = nexoraDependencyVersionDiff(
            nexoraComposerLockVersions((string) $runA['workspace'].'/composer.lock'),
            nexoraComposerLockVersions((string) $runB['workspace'].'/composer.lock'),
        );
        $npmSemanticDiff = nexoraDependencyVersionDiff(
            nexoraNpmLockVersions((string) $runA['workspace'].'/package-lock.json'),
            nexoraNpmLockVersions((string) $runB['workspace'].'/package-lock.json'),
        );
        $errors[] = 'Dependency lock generation is not semantically reproducible: workspace A and B canonical dependency digests differ.';
        if ($composerSemanticDiff !== []) {
            $errors[] = 'Composer A/B semantic version diff: '.json_encode($composerSemanticDiff, JSON_UNESCAPED_SLASHES);
        }
        if ($npmSemanticDiff !== []) {
            $errors[] = 'npm A/B semantic version diff: '.json_encode($npmSemanticDiff, JSON_UNESCAPED_SLASHES);
        }
    } elseif (! $rawExactMatch) {
        $warnings[] = 'Workspace A/B raw lockfile bytes differ, but canonical semantic lock digests are identical.';
    }
}

$workspaceA = is_array($runA) ? (string) $runA['workspace'] : '';
$composerDiff = nexoraDependencyVersionDiff(
    nexoraComposerLockVersions($root.'/composer.lock'),
    nexoraComposerLockVersions($workspaceA.'/composer.lock'),
);
$npmDiff = nexoraDependencyVersionDiff(
    nexoraNpmLockVersions($root.'/package-lock.json'),
    nexoraNpmLockVersions($workspaceA.'/package-lock.json'),
);

$status = $errors === [] && $reproducible ? 'review-required' : 'blocked';
$candidatePublished = false;
if ($status === 'review-required') {
    if (! is_dir($candidateDirectory)
        && ! mkdir($candidateDirectory, 0775, true)
        && ! is_dir($candidateDirectory)) {
        throw new RuntimeException('Unable to create dependency candidate directory.');
    }
    foreach (['composer.lock', 'package-lock.json'] as $lock) {
        $contents = file_get_contents($workspaceA.'/'.$lock);
        if (! is_string($contents)) {
            throw new RuntimeException("Unable to read reproducible candidate {$lock}.");
        }
        nexoraWriteFileReplace($candidateDirectory.'/'.$lock, $contents);
    }
    $candidatePublished = true;
}

$payload = [
    'schema' => 4,
    'mode' => 'double-run-reproducible-candidate-refresh',
    'status' => $status,
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'manifest_hashes' => $manifestHashes,
    'run_id' => $runId,
    'refreshed_at' => gmdate(DATE_ATOM),
    'root_lockfiles_mutated' => false,
    'candidate_published' => $candidatePublished,
    'reproducible' => $reproducible,
    'toolchain' => $toolchain,
    'toolchain_fingerprint_sha256' => $toolchain['fingerprint_sha256'] ?? null,
    'before' => $before,
    'after' => $after,
    'reproduction' => [
        'workspace_a_hashes' => $runA['hashes'] ?? null,
        'workspace_b_hashes' => $runB['hashes'] ?? null,
        'workspace_a_semantic_hashes' => $runA['semantic_hashes'] ?? null,
        'workspace_b_semantic_hashes' => $runB['semantic_hashes'] ?? null,
        'raw_exact_match' => $rawExactMatch,
        'semantic_exact_match' => $semanticExactMatch,
        'exact_match' => $reproducible,
    ],
    'supply_chain' => [
        'status' => $runA['supply_chain']['status'] ?? 'not-run',
        'fingerprint_sha256' => $runA['supply_chain']['fingerprint_sha256'] ?? null,
        'workspace_a' => $runA['supply_chain'] ?? null,
        'workspace_b' => $runB['supply_chain'] ?? null,
        'exact_match' => $reproducible
            && ($runA['supply_chain']['fingerprint_sha256'] ?? null) === ($runB['supply_chain']['fingerprint_sha256'] ?? null),
    ],
    'changed' => [
        'composer_lock' => $before['composer_lock_sha256'] !== $after['composer_lock_sha256'],
        'package_lock' => $before['package_lock_sha256'] !== $after['package_lock_sha256'],
    ],
    'validation' => $validation,
    'diff' => [
        'composer' => $composerDiff,
        'npm' => $npmDiff,
    ],
    'steps' => $steps,
    'errors' => array_values(array_unique($errors)),
    'warnings' => array_values(array_unique($warnings)),
    'candidate_paths' => $candidatePublished ? [
        'composer_lock' => 'storage/app/nexora/dependency-intake/candidates/composer.lock',
        'package_lock' => 'storage/app/nexora/dependency-intake/candidates/package-lock.json',
    ] : null,
    'next_action' => $status === 'review-required'
        ? 'Review the reproducible candidate diff, registry/source provenance, vulnerability-audit PASS and bound toolchain fingerprint. Then run '
            .'`scripts\\promote-reviewed-dependency-locks.bat --reviewer="YOUR NAME" '
            .'--confirm=PROMOTE-REVIEWED` on the same exact dependency toolchain.'
        : 'Resolve the first staging/reproducibility blocker and rerun '
            .'`scripts\\refresh-dependency-locks.bat --confirm=REFRESH`. Root lockfiles were not modified.',
];

if (! is_dir($baseDirectory) && ! mkdir($baseDirectory, 0775, true) && ! is_dir($baseDirectory)) {
    throw new RuntimeException('Unable to create dependency-intake directory.');
}
foreach ([$runDirectory.'/summary.json', $baseDirectory.'/lock-refresh.json'] as $path) {
    file_put_contents(
        $path,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
        LOCK_EX,
    );
}

if ($candidatePublished) {
    $candidatePayload = [
        'schema' => 3,
        'status' => 'review-required',
        'platform_version' => $version,
        'source_tree_sha256' => $source['tree_sha256'],
        'manifest_hashes' => $manifestHashes,
        'run_id' => $runId,
        'created_at' => gmdate(DATE_ATOM),
        'candidate_hashes' => $after,
        'candidate_semantic_hashes' => $runA['semantic_hashes'] ?? null,
        'reproducible' => true,
        'reproduction' => $payload['reproduction'],
        'supply_chain' => $payload['supply_chain'],
        'supply_chain_fingerprint_sha256' => $payload['supply_chain']['fingerprint_sha256'] ?? null,
        'toolchain' => $toolchain,
        'toolchain_fingerprint_sha256' => $toolchain['fingerprint_sha256'] ?? null,
        'validation' => $validation,
        'diff' => $payload['diff'],
    ];
    file_put_contents(
        $candidateDirectory.'/candidate.json',
        json_encode($candidatePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
        LOCK_EX,
    );
}

$markdown = "# Nexora reproducible dependency lock refresh\n\n";
$markdown .= 'Status: **'.strtoupper($status)."**  \n";
$markdown .= "Platform: `{$version}`  \n";
$markdown .= "Root lockfiles mutated: **NO**  \n";
$markdown .= 'Toolchain fingerprint: `'.($toolchain['fingerprint_sha256'] ?? 'unavailable')."`  \n";
$markdown .= 'Double-run reproducible: **'.($reproducible ? 'YES' : 'NO')."**\n\n";
$markdown .= 'Candidate supply-chain: **'.(($payload['supply_chain']['status'] ?? null) === 'pass' ? 'PASS' : 'BLOCKED')."**\n\n";
$markdown .= '- Supply-chain fingerprint: `'.($payload['supply_chain']['fingerprint_sha256'] ?? 'missing')."`\n";
$markdown .= '- Composer provenance hosts: `'.implode(', ', (array) ($payload['supply_chain']['workspace_a']['provenance']['composer_hosts'] ?? []))."`\n";
$markdown .= '- npm provenance hosts: `'.implode(', ', (array) ($payload['supply_chain']['workspace_a']['provenance']['npm_hosts'] ?? []))."`\n";
$markdown .= '- Composer audit exit: `'.(string) ($payload['supply_chain']['workspace_a']['audit']['composer']['exit_code'] ?? 'not-run')."`\n";
$markdown .= '- npm audit exit: `'.(string) ($payload['supply_chain']['workspace_a']['audit']['npm']['exit_code'] ?? 'not-run')."`\n\n";

$markdown .= "| Lock | Root SHA before | Candidate SHA | Changed |\n|---|---|---|---:|\n";
foreach (['composer_lock' => 'composer_lock_sha256', 'package_lock' => 'package_lock_sha256'] as $label => $key) {
    $markdown .= '| '.$label.' | `'.($before[$key] ?? 'missing').'` | `'.($after[$key] ?? 'missing').'` | '
        .(($payload['changed'][$label] ?? false) ? 'yes' : 'no')." |\n";
}
$markdown .= "\n## Reproduction\n\n";
$markdown .= '- Workspace A: `'.json_encode($payload['reproduction']['workspace_a_hashes'], JSON_UNESCAPED_SLASHES)."`\n";
$markdown .= '- Workspace B: `'.json_encode($payload['reproduction']['workspace_b_hashes'], JSON_UNESCAPED_SLASHES)."`\n";
$markdown .= '- Workspace A semantic: `'.json_encode($payload['reproduction']['workspace_a_semantic_hashes'], JSON_UNESCAPED_SLASHES)."`\n";
$markdown .= '- Workspace B semantic: `'.json_encode($payload['reproduction']['workspace_b_semantic_hashes'], JSON_UNESCAPED_SLASHES)."`\n";
$markdown .= '- Raw byte match: **'.(($payload['reproduction']['raw_exact_match'] ?? false) ? 'YES' : 'NO')."**\n";
$markdown .= '- Semantic match: **'.(($payload['reproduction']['semantic_exact_match'] ?? false) ? 'YES' : 'NO')."**\n";
$markdown .= "\n## Next action\n\n{$payload['next_action']}\n";
if ($errors !== []) {
    $markdown .= "\n## Blockers\n";
    foreach ($errors as $error) {
        $markdown .= '- '.$error."\n";
    }
}
file_put_contents($runDirectory.'/summary.md', $markdown, LOCK_EX);
file_put_contents($baseDirectory.'/lock-refresh.md', $markdown, LOCK_EX);

if ($jsonOnly) {
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
} else {
    fwrite(STDOUT, '[Nexora Reproducible Lock Refresh] '.strtoupper($status)." — {$version}\n");
    fwrite(STDOUT, 'Root lockfiles mutated: NO'.PHP_EOL);
    fwrite(STDOUT, 'Double-run reproducible: '.($reproducible ? 'YES' : 'NO').PHP_EOL);
    foreach ($errors as $error) {
        fwrite(STDERR, " - {$error}\n");
    }
    fwrite(STDOUT, "Dossier: storage/app/nexora/dependency-intake/lock-refresh.md\n");
    fwrite(STDOUT, "Next: {$payload['next_action']}\n");
}

exit($status === 'review-required' ? 2 : 1);
