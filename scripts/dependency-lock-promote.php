<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/dependency-lock-intake.php';
require_once $root.'/scripts/lib/dependency-toolchain.php';
require_once $root.'/scripts/lib/dependency-candidate-supply-chain.php';

$reviewer = '';
$confirm = '';
$jsonOnly = in_array('--json', $argv, true);
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--reviewer=')) {
        $reviewer = trim(substr($argument, 11));
    } elseif (str_starts_with($argument, '--confirm=')) {
        $confirm = trim(substr($argument, 10));
    }
}

if ($reviewer === '' || strlen($reviewer) > 120) {
    fwrite(STDERR, "[Nexora Dependency Lock Promote] --reviewer=<name> is required and must be <=120 characters.\n");
    exit(2);
}
if ($confirm !== 'PROMOTE-REVIEWED') {
    fwrite(STDERR, "[Nexora Dependency Lock Promote] Explicit --confirm=PROMOTE-REVIEWED is required after human review of both candidate lockfiles.\n");
    exit(2);
}

$baseDirectory = $root.'/storage/app/nexora/dependency-intake';
$candidateDirectory = $baseDirectory.'/candidates';
$candidateMetadataPath = $candidateDirectory.'/candidate.json';
$journalPath = $baseDirectory.'/lock-promotion-journal.json';
if (is_file($journalPath)) {
    try {
        $existingJournal = json_decode((string) file_get_contents($journalPath), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        $existingJournal = ['status' => 'invalid'];
    }
    if (! in_array((string) ($existingJournal['status'] ?? ''), ['complete', 'rolled-back'], true)) {
        fwrite(
            STDERR,
            "[Nexora Dependency Lock Promote] Incomplete prior promotion journal detected. "
            ."Run `scripts\\recover-dependency-lock-promotion.bat --confirm=ROLLBACK` first.\n",
        );
        exit(2);
    }
}
$errors = [];
$warnings = [];

try {
    if (! is_file($candidateMetadataPath)) {
        throw new RuntimeException('candidate.json is missing; refresh candidates first');
    }
    $candidate = json_decode((string) file_get_contents($candidateMetadataPath), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, "[Nexora Dependency Lock Promote] Candidate metadata missing or invalid: {$exception->getMessage()}\n");
    exit(1);
}
if (! is_array($candidate)) {
    fwrite(STDERR, "[Nexora Dependency Lock Promote] Candidate metadata is invalid.\n");
    exit(1);
}

$version = (string) ((require $root.'/config/nexora.php')['version'] ?? 'unknown');
$source = nexoraComputeSourceAttestation($root);
$manifestHashes = nexoraDependencyManifestHashes($root);
$candidateComposer = $candidateDirectory.'/composer.lock';
$candidateNpm = $candidateDirectory.'/package-lock.json';
$candidateHashes = [
    'composer_lock_sha256' => nexoraHashOptionalFile($candidateComposer),
    'package_lock_sha256' => nexoraHashOptionalFile($candidateNpm),
];
$toolchain = nexoraCollectDependencyToolchain($root);
$composer = nexoraLocateTargetComposer($root);
$candidateToolchainFingerprint = (string) ($candidate['toolchain_fingerprint_sha256'] ?? '');
$currentToolchainFingerprint = (string) ($toolchain['fingerprint_sha256'] ?? '');
$candidateSupplyChainFingerprint = (string) ($candidate['supply_chain_fingerprint_sha256'] ?? '');
$candidateProvenance = nexoraDependencyCandidateProvenance($root, $candidateComposer, $candidateNpm);

if (($candidate['status'] ?? null) !== 'review-required') {
    $errors[] = 'Candidate dossier is not in review-required state.';
}
if (($candidate['reproducible'] ?? false) !== true
    || ($candidate['reproduction']['exact_match'] ?? false) !== true) {
    $errors[] = 'Candidate lock pair does not carry a successful double-run reproducibility proof.';
}
if (($candidate['supply_chain']['status'] ?? null) !== 'pass'
    || ($candidate['supply_chain']['exact_match'] ?? false) !== true
    || $candidateSupplyChainFingerprint === '') {
    $errors[] = 'Candidate lock pair does not carry a passing candidate-stage supply-chain proof.';
}
if (($candidateProvenance['status'] ?? null) !== 'pass') {
    foreach ((array) ($candidateProvenance['errors'] ?? []) as $error) {
        $errors[] = 'Candidate provenance: '.trim((string) $error);
    }
}
if (($candidate['supply_chain']['workspace_a']['provenance']['fingerprint_sha256'] ?? null)
    !== ($candidateProvenance['fingerprint_sha256'] ?? null)) {
    $errors[] = 'Candidate lock provenance fingerprint changed since refresh.';
}
if (($toolchain['status'] ?? 'fail') !== 'pass') {
    foreach ((array) ($toolchain['errors'] ?? []) as $error) {
        $errors[] = 'Current dependency toolchain: '.trim((string) $error);
    }
}
if ($candidateToolchainFingerprint === ''
    || $currentToolchainFingerprint === ''
    || ! hash_equals($candidateToolchainFingerprint, $currentToolchainFingerprint)) {
    $errors[] = 'Dependency toolchain fingerprint changed since candidate generation; refresh the candidate on the current toolchain.';
}
if (($candidate['platform_version'] ?? null) !== $version) {
    $errors[] = 'Candidate platform version no longer matches current source.';
}
if (($candidate['source_tree_sha256'] ?? null) !== $source['tree_sha256']) {
    $errors[] = 'Candidate source-tree digest no longer matches current source.';
}
foreach ($manifestHashes as $key => $value) {
    if (($candidate['manifest_hashes'][$key] ?? null) !== $value) {
        $errors[] = "Candidate manifest hash mismatch [{$key}].";
    }
}
foreach ($candidateHashes as $key => $value) {
    if (($candidate['candidate_hashes'][$key] ?? null) !== $value || $value === null) {
        $errors[] = "Candidate lock hash mismatch [{$key}].";
    }
}

$validation = nexoraValidateDependencyLockPair($root, $candidateComposer, $candidateNpm, false);
$errors = array_merge($errors, $validation['errors']);
$warnings = array_merge($warnings, $validation['warnings']);

if ($errors !== []) {
    $payload = [
        'schema' => 1,
        'status' => 'blocked',
        'platform_version' => $version,
        'source_tree_sha256' => $source['tree_sha256'],
        'reviewer' => $reviewer,
        'candidate_hashes' => $candidateHashes,
        'candidate_toolchain_fingerprint_sha256' => $candidateToolchainFingerprint,
        'current_toolchain_fingerprint_sha256' => $currentToolchainFingerprint,
        'candidate_supply_chain_fingerprint_sha256' => $candidateSupplyChainFingerprint,
        'errors' => array_values(array_unique($errors)),
        'warnings' => array_values(array_unique($warnings)),
    ];
    fwrite(STDERR, "[Nexora Dependency Lock Promote] BLOCKED\n - ".implode("\n - ", $payload['errors'])."\n");
    exit(1);
}

$composerPath = $root.'/composer.lock';
$npmPath = $root.'/package-lock.json';
$refreshPath = $baseDirectory.'/lock-refresh.json';
$reviewPath = $baseDirectory.'/reviewed-locks.json';
$snapshots = [
    'composer' => nexoraCaptureFileSnapshot($composerPath),
    'npm' => nexoraCaptureFileSnapshot($npmPath),
    'refresh' => nexoraCaptureFileSnapshot($refreshPath),
    'review' => nexoraCaptureFileSnapshot($reviewPath),
];
$promotionRun = gmdate('Ymd\THis\Z').'-'.substr($source['tree_sha256'], 0, 12);
$promotionDirectory = $baseDirectory.'/promotion-runs/'.$promotionRun;
if (! is_dir($promotionDirectory) && ! mkdir($promotionDirectory, 0775, true) && ! is_dir($promotionDirectory)) {
    throw new RuntimeException('Unable to create dependency promotion evidence directory.');
}
$auditWorkspace = $promotionDirectory.'/candidate-supply-chain';
if (! is_dir($auditWorkspace) && ! mkdir($auditWorkspace, 0700, true) && ! is_dir($auditWorkspace)) {
    throw new RuntimeException('Unable to create candidate supply-chain promotion workspace.');
}
foreach (['composer.json', 'package.json'] as $manifest) {
    if (! copy($root.'/'.$manifest, $auditWorkspace.'/'.$manifest)) {
        throw new RuntimeException("Unable to stage {$manifest} for candidate supply-chain revalidation.");
    }
}
foreach (['composer.lock' => $candidateComposer, 'package-lock.json' => $candidateNpm] as $name => $sourcePath) {
    if (! copy($sourcePath, $auditWorkspace.'/'.$name)) {
        throw new RuntimeException("Unable to stage {$name} for candidate supply-chain revalidation.");
    }
}
$promotionSupplyChain = nexoraRunDependencyCandidateSupplyChain(
    $root,
    $auditWorkspace,
    (array) ($composer['command'] ?? []),
    NexoraBootstrapProcessEnvironment::build($root, $_ENV),
);
if (($promotionSupplyChain['status'] ?? null) !== 'pass'
    || ! hash_equals($candidateSupplyChainFingerprint, (string) ($promotionSupplyChain['fingerprint_sha256'] ?? ''))) {
    $details = implode('; ', array_map('strval', (array) ($promotionSupplyChain['errors'] ?? [])));
    fwrite(STDERR, "[Nexora Dependency Lock Promote] Candidate supply-chain revalidation failed before root mutation"
        .($details !== '' ? ": {$details}" : '.')."
");
    exit(1);
}
file_put_contents(
    $promotionDirectory.'/candidate-supply-chain.json',
    json_encode($promotionSupplyChain, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    LOCK_EX,
);
$backupDirectory = $promotionDirectory.'/before';
if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0700, true) && ! is_dir($backupDirectory)) {
    throw new RuntimeException('Unable to create durable dependency promotion backup directory.');
}
$durableTargets = [
    'composer.lock' => [$composerPath, $snapshots['composer']],
    'package-lock.json' => [$npmPath, $snapshots['npm']],
    'lock-refresh.json' => [$refreshPath, $snapshots['refresh']],
    'reviewed-locks.json' => [$reviewPath, $snapshots['review']],
];
foreach ($durableTargets as $name => [$path, $snapshot]) {
    if (($snapshot['exists'] ?? false) === true) {
        if (file_put_contents($backupDirectory.'/'.$name, (string) ($snapshot['contents'] ?? ''), LOCK_EX) === false) {
            throw new RuntimeException("Unable to persist durable promotion backup [{$name}].");
        }
    }
}
$journal = [
    'schema' => 1,
    'status' => 'prepared',
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'promotion_run_id' => $promotionRun,
    'promotion_directory' => 'storage/app/nexora/dependency-intake/promotion-runs/'.$promotionRun,
    'reviewer' => $reviewer,
    'candidate_hashes' => $candidateHashes,
    'toolchain_fingerprint_sha256' => $currentToolchainFingerprint,
    'candidate_supply_chain_fingerprint_sha256' => $candidateSupplyChainFingerprint,
    'candidate_provenance_fingerprint_sha256' => $candidateProvenance['fingerprint_sha256'] ?? null,
    'candidate_supply_chain_evidence_sha256' => nexoraHashOptionalFile($promotionDirectory.'/candidate-supply-chain.json'),
    'before' => [
        'composer' => ['exists' => $snapshots['composer']['exists'], 'sha256' => $snapshots['composer']['sha256']],
        'npm' => ['exists' => $snapshots['npm']['exists'], 'sha256' => $snapshots['npm']['sha256']],
        'refresh' => ['exists' => $snapshots['refresh']['exists'], 'sha256' => $snapshots['refresh']['sha256']],
        'review' => ['exists' => $snapshots['review']['exists'], 'sha256' => $snapshots['review']['sha256']],
    ],
    'updated_at' => gmdate(DATE_ATOM),
];
$writeJournal = static function (string $status) use (&$journal, $journalPath): void {
    $journal['status'] = $status;
    $journal['updated_at'] = gmdate(DATE_ATOM);
    nexoraWriteFileReplace(
        $journalPath,
        json_encode($journal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    );
};
$writeJournal('prepared');

$promoted = false;
$reviewExit = null;
try {
    $composerContents = file_get_contents($candidateComposer);
    $npmContents = file_get_contents($candidateNpm);
    if (! is_string($composerContents) || ! is_string($npmContents)) {
        throw new RuntimeException('Unable to read candidate lock pair for promotion.');
    }

    nexoraWriteFileReplace($composerPath, $composerContents);
    $writeJournal('composer-promoted');
    nexoraWriteFileReplace($npmPath, $npmContents);
    $writeJournal('pair-promoted');

    $rootHashes = nexoraDependencyRootLockHashes($root);
    foreach ($candidateHashes as $key => $hash) {
        if (($rootHashes[$key] ?? null) !== $hash) {
            throw new RuntimeException("Promoted root lock hash mismatch [{$key}].");
        }
    }

    $refresh = [
        'schema' => 2,
        'mode' => 'reviewed-candidate-promotion',
        'status' => 'review-required',
        'platform_version' => $version,
        'source_tree_sha256' => $source['tree_sha256'],
        'manifest_hashes' => $manifestHashes,
        'run_id' => (string) ($candidate['run_id'] ?? ''),
        'promoted_at' => gmdate(DATE_ATOM),
        'promoted_by' => $reviewer,
        'toolchain_fingerprint_sha256' => $currentToolchainFingerprint,
        'candidate_reproducible' => true,
        'candidate_supply_chain_status' => 'pass',
        'candidate_supply_chain_fingerprint_sha256' => $candidateSupplyChainFingerprint,
        'candidate_provenance_fingerprint_sha256' => $candidateProvenance['fingerprint_sha256'] ?? null,
        'candidate_supply_chain_evidence_sha256' => nexoraHashOptionalFile($promotionDirectory.'/candidate-supply-chain.json'),
        'before' => [
            'composer_lock_sha256' => $snapshots['composer']['sha256'],
            'package_lock_sha256' => $snapshots['npm']['sha256'],
        ],
        'after' => $candidateHashes,
        'changed' => [
            'composer_lock' => $snapshots['composer']['sha256'] !== $candidateHashes['composer_lock_sha256'],
            'package_lock' => $snapshots['npm']['sha256'] !== $candidateHashes['package_lock_sha256'],
        ],
        'candidate_review' => [
            'reviewer' => $reviewer,
            'confirmation' => 'PROMOTE-REVIEWED',
            'candidate_metadata_sha256' => nexoraHashOptionalFile($candidateMetadataPath),
        ],
    ];
    nexoraWriteFileReplace(
        $refreshPath,
        json_encode($refresh, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $strict = nexoraRunTargetCommand(
        [PHP_BINARY, 'scripts/dependency-contract-verify.php', '--strict-locks'],
        $root,
    );
    if ($strict['exit_code'] !== 0) {
        throw new RuntimeException('Strict root lock contract failed after candidate promotion: '
            .substr(trim($strict['stderr'] !== '' ? $strict['stderr'] : $strict['stdout']), 0, 1200));
    }
    $writeJournal('strict-validated');

    $review = nexoraRunTargetCommand(
        [
            PHP_BINARY,
            'scripts/dependency-lock-review.php',
            '--accept',
            '--reviewer='.$reviewer,
            '--confirm=REVIEWED',
            '--require-refresh-handoff',
            '--json',
        ],
        $root,
    );
    $reviewExit = $review['exit_code'];
    if ($review['exit_code'] !== 0) {
        throw new RuntimeException('Reviewed-lock attestation failed after promotion: '
            .substr(trim($review['stderr'] !== '' ? $review['stderr'] : $review['stdout']), 0, 1200));
    }
    $writeJournal('review-attested');

    $verify = nexoraRunTargetCommand(
        [PHP_BINARY, 'scripts/dependency-lock-review.php', '--verify-attestation', '--require-refresh-handoff', '--json'],
        $root,
    );
    if ($verify['exit_code'] !== 0) {
        throw new RuntimeException('Reviewed-lock attestation did not verify after promotion.');
    }

    $promoted = true;
    $writeJournal('complete');
} catch (Throwable $exception) {
    $errors[] = $exception->getMessage();

    $rollbackErrors = [];
    foreach ([
        [$composerPath, $snapshots['composer']],
        [$npmPath, $snapshots['npm']],
        [$refreshPath, $snapshots['refresh']],
        [$reviewPath, $snapshots['review']],
    ] as [$path, $snapshot]) {
        try {
            nexoraRestoreFileSnapshot((string) $path, (array) $snapshot);
        } catch (Throwable $rollbackException) {
            $rollbackErrors[] = $rollbackException->getMessage();
        }
    }
    if ($rollbackErrors !== []) {
        $errors[] = 'ROLLBACK FAILURE: '.implode('; ', $rollbackErrors);
        $writeJournal('rollback-failed');
    } else {
        $writeJournal('rolled-back');
    }
}

$rootAfter = nexoraDependencyRootLockHashes($root);
$payload = [
    'schema' => 1,
    'status' => $promoted ? 'reviewed-promoted' : 'rolled-back',
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'promotion_run_id' => $promotionRun,
    'candidate_run_id' => $candidate['run_id'] ?? null,
    'reviewer' => $reviewer,
    'candidate_hashes' => $candidateHashes,
    'toolchain_fingerprint_sha256' => $currentToolchainFingerprint,
    'candidate_reproducible' => ($candidate['reproducible'] ?? false) === true,
    'candidate_supply_chain_status' => ($promotionSupplyChain['status'] ?? null),
    'candidate_supply_chain_fingerprint_sha256' => $candidateSupplyChainFingerprint,
    'candidate_provenance_fingerprint_sha256' => $candidateProvenance['fingerprint_sha256'] ?? null,
    'candidate_supply_chain_evidence_sha256' => nexoraHashOptionalFile($promotionDirectory.'/candidate-supply-chain.json'),
    'root_hashes_after' => $rootAfter,
    'review_exit_code' => $reviewExit,
    'rollback_required' => ! $promoted,
    'rollback_verified' => ! $promoted
        ? $rootAfter['composer_lock_sha256'] === $snapshots['composer']['sha256']
            && $rootAfter['package_lock_sha256'] === $snapshots['npm']['sha256']
        : null,
    'errors' => array_values(array_unique($errors)),
    'warnings' => array_values(array_unique($warnings)),
    'next_action' => $promoted
        ? 'Reviewed root lock pair is ready. Run `scripts\\n1-target-fast-track.bat --install-deps --operator="YOUR NAME"`.'
        : 'Promotion was rolled back. Resolve the blocker, refresh/review a new candidate if necessary, and retry explicit promotion.',
    'finished_at' => gmdate(DATE_ATOM),
];
file_put_contents(
    $promotionDirectory.'/summary.json',
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    LOCK_EX,
);
file_put_contents(
    $baseDirectory.'/lock-promotion.json',
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    LOCK_EX,
);

if ($jsonOnly) {
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
} else {
    fwrite(STDOUT, '[Nexora Dependency Lock Promote] '.strtoupper($payload['status'])." — {$version}\n");
    foreach ($payload['errors'] as $error) {
        fwrite(STDERR, " - {$error}\n");
    }
    fwrite(STDOUT, "Evidence: storage/app/nexora/dependency-intake/lock-promotion.json\n");
    fwrite(STDOUT, "Next: {$payload['next_action']}\n");
}

exit($promoted ? 0 : 1);
