<?php

declare(strict_types=1);

require_once __DIR__.'/performance-build-manifest-contracts.php';

/**
 * Only completed, required, zero-exit steps from the exact prior run may
 * skip expensive dependency/build work. A malformed, conflicting or duplicated
 * receipt fails closed to no resumable steps; the runner must execute normally.
 *
 * This checks internal consistency, not authenticity of an external actor.
 *
 * @param array<string,mixed> $summary
 * @param array<string,bool> $allowlist
 * @return array<string,true>
 */
function nexoraTargetReusableStepIds(array $summary, array $allowlist, string $runId): array
{
    if (($summary['run_id'] ?? null) !== $runId
        || !in_array($summary['status'] ?? null, ['blocked', 'target-readiness-pass', 'target-certification-pass'], true)
        || !is_array($summary['steps'] ?? null)
        || !array_is_list($summary['steps'])) {
        return [];
    }

    $seen = [];
    $reusable = [];
    foreach ($summary['steps'] as $step) {
        if (!is_array($step)
            || !is_string($step['id'] ?? null)
            || preg_match('/^[a-z0-9-]+$/D', $step['id']) !== 1
            || isset($seen[$step['id']])
            || !is_bool($step['required'] ?? null)
            || !in_array($step['status'] ?? null, ['pass', 'fail'], true)
            || !is_int($step['exit_code'] ?? null)
            || (($step['status'] === 'pass') !== ($step['exit_code'] === 0))) {
            return [];
        }
        $id = $step['id'];
        $seen[$id] = true;
        if (($allowlist[$id] ?? false) && $step['required']
            && $step['status'] === 'pass' && $step['exit_code'] === 0) {
            $reusable[$id] = true;
        }
    }

    return $reusable;
}

/**
 * A blocked run may contain genuinely successful steps, but they are only
 * resumable if every earlier mandatory producer step ran successfully in the
 * correct order. A detached PASS step in a partial or reordered receipt is
 * not enough to skip dependency installation, typechecking or a Vite build.
 *
 * Receipt/log integrity and environment drift are checked separately by the
 * runner. This function establishes *execution lineage*, not authenticity.
 *
 * @param array<string,mixed> $summary
 * @param array<string,bool> $allowlist
 * @return array<string,bool>
 */
function nexoraTargetResumeLineageAllowlist(array $summary, array $allowlist): array
{
    $environment = $summary['environment'] ?? null;
    if (($summary['schema'] ?? null) !== 2 || !is_array($environment)
        || !is_bool($environment['install_dependencies_requested'] ?? null)
        || !is_array($summary['steps'] ?? null) || !array_is_list($summary['steps'])) {
        return [];
    }
    // This sequence must match the mandatory phases in target-runtime-run.php.
    // Prerequisite failure receipts have additional IDs and deliberately break
    // the PASS prefix; nothing after that failure is eligible for fast reuse.
    $mandatory = [
        'source-preflight', 'target-bootstrap', 'target-runtime-contract',
        'inertia-contract', 'dependency-lock-review',
        'dependency-contract-strict', 'dependency-runtime',
    ];
    if ($environment['install_dependencies_requested']) {
        array_push($mandatory, 'composer-install', 'npm-ci');
    }
    array_push($mandatory, 'frontend-typecheck', 'frontend-tests',
        'frontend-build', 'frontend-build-budget');

    $proved = [];
    $next = 0;
    foreach ($summary['steps'] as $step) {
        $expected = $mandatory[$next] ?? null;
        if ($expected === null) break;
        if (!is_array($step) || ($step['id'] ?? null) !== $expected
            || ($step['required'] ?? null) !== true
            || ($step['status'] ?? null) !== 'pass'
            || ($step['exit_code'] ?? null) !== 0) {
            break;
        }
        $proved[$expected] = true;
        ++$next;
    }
    foreach ($allowlist as $id => $enabled) {
        if ($enabled !== true || !isset($proved[$id])) {
            unset($allowlist[$id]);
        }
    }
    return $allowlist;
}

/**
 * Compare the execution environment before reusing any previous PASS step.
 * Missing keys must never behave like matching null-valued fingerprints.
 *
 * @param array<string,mixed> $previous
 * @param array<string,mixed> $current
 * @return ?string First missing/drifted fingerprint key, null only on exact match.
 */
function nexoraTargetResumeEnvironmentDrift(array $previous, array $current): ?string
{
    $fingerprints = [
        'platform_version', 'source_tree_sha256',
        'php_version', 'os_family', 'php_binary', 'php_extensions_sha256',
        'composer_version', 'node_version', 'npm_version',
        'composer_available', 'node_available', 'npm_available',
        'composer_lock_sha256', 'package_lock_sha256',
        'vendor_installed_sha256', 'node_modules_lock_sha256',
        'vendor_autoload', 'node_modules', 'install_dependencies_requested',
    ];

    foreach ($fingerprints as $fingerprint) {
        if (!array_key_exists($fingerprint, $previous)
            || !array_key_exists($fingerprint, $current)
            || $previous[$fingerprint] !== $current[$fingerprint]) {
            return $fingerprint;
        }
    }

    return null;
}

/**
 * A PASS receipt must not skip an installation or frontend step when the
 * currently installed dependency tree is incomplete, even when null hashes
 * happened to compare equal in an older or partially completed run.
 *
 * @param array<string,mixed> $environment
 * @param array<string,bool> $allowlist
 * @param array<string,mixed> $priorArtifacts
 * @param array<string,mixed> $currentArtifacts
 * @return array<string,bool>
 */
function nexoraTargetResumeSafeStepAllowlist(
    array $environment, array $allowlist, array $priorArtifacts = [], array $currentArtifacts = []
): array
{
    $validVersion = static fn(mixed $version): bool => is_string($version)
        && preg_match('/^\d+\.\d+(?:\.\d+)?$/D', $version) === 1;
    if (!$validVersion($environment['composer_version'] ?? null)) {
        unset($allowlist['composer-install']);
    }
    if (!$validVersion($environment['node_version'] ?? null)
        || !$validVersion($environment['npm_version'] ?? null)) {
        unset($allowlist['npm-ci'], $allowlist['frontend-typecheck'],
            $allowlist['frontend-tests'], $allowlist['frontend-build'],
            $allowlist['frontend-build-budget']);
    }
    if (($environment['vendor_autoload'] ?? false) !== true
        || !is_string($environment['vendor_installed_sha256'] ?? null)
        || ($environment['vendor_installed_sha256'] ?? '') === '') {
        unset($allowlist['composer-install']);
    }
    if (($environment['node_modules'] ?? false) !== true
        || !is_string($environment['node_modules_lock_sha256'] ?? null)
        || ($environment['node_modules_lock_sha256'] ?? '') === '') {
        unset($allowlist['npm-ci'], $allowlist['frontend-typecheck'],
            $allowlist['frontend-tests'], $allowlist['frontend-build'],
            $allowlist['frontend-build-budget']);
    }
    $build = $currentArtifacts['build_output_sha256'] ?? null;
    if (!is_string($build) || strlen($build) !== 64
        || ($priorArtifacts['build_output_sha256'] ?? null) !== $build) {
        unset($allowlist['frontend-build'], $allowlist['frontend-build-budget']);
    }
    $budget = $currentArtifacts['build_budget_report_sha256'] ?? null;
    if (!is_string($budget) || strlen($budget) !== 64
        || ($priorArtifacts['build_budget_report_sha256'] ?? null) !== $budget) {
        unset($allowlist['frontend-build-budget']);
    }
    return $allowlist;
}

/**
 * Hash the complete generated Vite build instead of trusting an old successful
 * build step when public/build has since been modified or removed. A symlink,
 * unreadable file or invalid manifest fails closed to "not reusable".
 */
function nexoraTargetResumeBuildDigest(string $root): ?string
{
    $directory = $root.'/public/build';
    $manifestPath = $directory.'/manifest.json';
    if (!is_dir($directory) || is_link($directory) || is_link($root.'/public')
        || !is_file($manifestPath) || is_link($manifestPath)
        || is_file($root.'/public/hot') || is_link($root.'/public/hot')) {
        return null;
    }

    try {
        // A hostile or corrupted manifest must never trigger an unbounded
        // allocation before the build tree's size budget can be enforced.
        clearstatcache(true, $manifestPath);
        $manifestBytes = @filesize($manifestPath);
        if (!is_int($manifestBytes) || $manifestBytes < 1 || $manifestBytes > 8 * 1024 * 1024) {
            return null;
        }
        $manifestSource = @file_get_contents($manifestPath, false, null, 0, 8 * 1024 * 1024 + 1);
        if (!is_string($manifestSource) || strlen($manifestSource) !== $manifestBytes) {
            return null;
        }
        $manifest = json_decode($manifestSource, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifest) || array_is_list($manifest) || count($manifest) > 10000
            || !isset($manifest['resources/js/app.tsx'], $manifest['resources/css/app.css'])) {
            return null;
        }
        if (!nexoraPerformanceBuildRequiredEntrypointsValid($manifest)) {
            return null;
        }
        // Validate referenced outputs as well as the overall tree digest.
        $referencedAssets = [];
        foreach ($manifest as $entry) {
            if (!is_array($entry) || !is_string($entry['file'] ?? null)) {
                return null;
            }
            // Vite emits list-valued css/assets and imports/dynamicImports.
            // Reject scalar or associative substitutions rather than letting a
            // malformed manifest silently omit lazy chunks or static media.
            $assets = [$entry['file']];
            foreach (['css', 'assets'] as $field) {
                if (!array_key_exists($field, $entry)) continue;
                if (!is_array($entry[$field]) || !array_is_list($entry[$field])) {
                    return null;
                }
                foreach ($entry[$field] as $asset) {
                    if (!is_string($asset)) return null;
                    $assets[] = $asset;
                }
            }
            foreach ($assets as $relative) {
                if (!is_string($relative) || $relative === '' || str_contains($relative, "\0")
                    || str_contains($relative, '\\') || str_starts_with($relative, '/')
                    || preg_match('~(^|/)\\.{1,2}(/|$)~', $relative) === 1) {
                    return null;
                }
                if (!nexoraPerformanceBuildSafeManifestAsset($directory, $relative)) {
                    return null;
                }
                $target = $directory.'/'.$relative;
                if (!is_file($target) || is_link($target)) {
                    return null;
                }
                $referencedAssets[$relative] = true;
            }
            foreach (['imports', 'dynamicImports'] as $field) {
                if (!array_key_exists($field, $entry)) continue;
                if (!is_array($entry[$field]) || !array_is_list($entry[$field])) {
                    return null;
                }
                foreach ($entry[$field] as $import) {
                    if (!is_string($import) || !array_key_exists($import, $manifest)) {
                        return null;
                    }
                }
            }
        }

        $files = [];
        $totalBytes = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                return null;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            if (!nexoraPerformanceBuildSafeOutputName($relative, $file->isDir())) {
                return null;
            }
            if ($file->isDir()) {
                continue;
            }
            if (!$file->isFile()) {
                return null;
            }
            $bytes = $file->getSize();
            if (!is_int($bytes) || $bytes < 0 || $totalBytes > (512 * 1024 * 1024) - $bytes
                || count($files) >= 10000 || !is_readable($file->getPathname())) {
                return null;
            }
            $sha = hash_file('sha256', $file->getPathname());
            if (!is_string($sha)) {
                return null;
            }
            $totalBytes += $bytes;
            $files[$relative] = [$bytes, $sha];
        }
        if (!isset($files['manifest.json'])) {
            return null;
        }
        // A symlinked *parent* directory would not be traversed, although
        // is_file($directory.'/'.$relative) may still follow it.
        foreach ($referencedAssets as $relative => $_) {
            if (!isset($files[$relative])) {
                return null;
            }
        }
        ksort($files, SORT_STRING);
        $context = hash_init('sha256');
        foreach ($files as $path => [$bytes, $sha]) {
            hash_update($context, $path."\0".$bytes."\0".$sha."\n");
        }
        return hash_final($context);
    } catch (Throwable) {
        return null;
    }
}

/**
 * This report is the successful output of performance-build-verify.php, not a
 * substitute for that verifier. Only exact unchanged reports are reusable.
 *
 * @param array<string,mixed> $environment
 * @return array{build_output_sha256:?string,build_budget_report_sha256:?string}
 */
function nexoraTargetResumeOutputArtifacts(string $root, array $environment): array
{
    $buildHash = nexoraTargetResumeBuildDigest($root);
    $reportHash = null;
    $reportFile = $root.'/storage/app/nexora/certification/build-assets.json';
    // Reject symlinked ancestors, not just the final JSON path: otherwise
    // a lookalike PASS report outside this runtime can be reused silently.
    $reportParentsSafe = true;
    foreach (['storage', 'storage/app', 'storage/app/nexora',
              'storage/app/nexora/certification'] as $relative) {
        if (is_link($root.'/'.$relative)) {
            $reportParentsSafe = false;
            break;
        }
    }
    if ($buildHash !== null && $reportParentsSafe && is_file($reportFile) && !is_link($reportFile)) {
        try {
            // A size check by itself cannot bound a concurrent file read.
            // Read no more than the supported report size plus one sentinel
            // byte and hash exactly the bytes that were structurally checked.
            clearstatcache(true, $reportFile);
            $size = @filesize($reportFile);
            if (is_int($size) && $size > 0 && $size <= 8 * 1024 * 1024) {
                $data = @file_get_contents($reportFile, false, null, 0, 8 * 1024 * 1024 + 1);
                if (is_string($data) && strlen($data) === $size) {
                    $report = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                    // A PASS label on a malformed/contradictory report is NOT
                    // sufficient to reuse the build budget verification gate.
                    if (is_array($report) && ($report['schema'] ?? null) === 2
                        && ($report['status'] ?? null) === 'pass'
                        && ($report['errors'] ?? null) === []
                        && is_array($report['budgets'] ?? null)
                        && is_array($report['totals'] ?? null)
                        && is_array($report['counts'] ?? null)
                        && is_array($report['files'] ?? null)
                        && array_is_list($report['files']) && $report['files'] !== []
                        && is_array($report['initial_javascript_assets'] ?? null)
                        && ($report['source_tree_sha256'] ?? null) === ($environment['source_tree_sha256'] ?? null)
                        && ($report['platform_version'] ?? null) === ($environment['platform_version'] ?? null)) {
                        $reportHash = hash('sha256', $data);
                    }
                }
            }
        } catch (Throwable) {
            // Invalid or stale report must be regenerated on resume.
        }
    }
    return ['build_output_sha256' => $buildHash, 'build_budget_report_sha256' => $reportHash];
}

/** @return array{errors:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetResumeContracts(string $root): array
{
    $errors=[];
    $required=[
        'scripts/target-environment-bootstrap.php','scripts/target-environment-bootstrap.bat','scripts/target-environment-bootstrap.ps1','scripts/target-environment-bootstrap.sh',
        'scripts/target-runtime-evidence-verify.php','scripts/lib/target-resume-contracts.php','scripts/target-resume-contract-verify.php',
    ];
    foreach($required as $file) if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0) $errors[]="missing RC23 target bootstrap/resume artifact [{$file}]";
    $bootstrap=(string)@file_get_contents($root.'/scripts/target-environment-bootstrap.php');
    foreach(['php_ini_loaded_file','composer.lock','package-lock.json','ext-','certified range','dependency-lock refresh workflow','--write'] as $marker) if(!str_contains($bootstrap,$marker)) $errors[]="target bootstrap missing marker [{$marker}]";
    if(preg_match('/https?:\/\//i',$bootstrap)===1) $errors[]='target bootstrap may diagnose tools but must not auto-download executables/dependencies';
    $runner=(string)@file_get_contents($root.'/scripts/target-runtime-run.php');
    foreach(['--resume-latest','--resume=','source_tree_sha256','vendor_installed_sha256','node_modules_lock_sha256','resume_from','target-environment-bootstrap.php'] as $marker) if(!str_contains($runner,$marker)) $errors[]="target runtime resume boundary missing marker [{$marker}]";
    $evidence=(string)@file_get_contents($root.'/scripts/target-runtime-evidence-verify.php');
    foreach(['--input=','--require-pass','--seal','source_tree_sha256','composer_lock_sha256','package_lock_sha256','Unsafe ZIP path','target-runtime-evidence.json'] as $marker) if(!str_contains($evidence,$marker)) $errors[]="target runtime evidence verifier missing marker [{$marker}]";
    $zero=(string)@file_get_contents($root.'/scripts/zero-state-verify.php');
    if(!str_contains($zero,'storage/app/nexora/target-bootstrap')) $errors[]='strict source-zero state must reject generated target-bootstrap evidence';
    $release=(string)@file_get_contents($root.'/config/nexora-release.php');
    if(!str_contains($release,'storage/app/nexora/target-bootstrap/')) $errors[]='production release policy must exclude target-bootstrap evidence';
    foreach(['bat','ps1','sh'] as $kind){
        $file=$root.'/scripts/target-environment-bootstrap.'.$kind;
        if(is_file($file)&&!str_contains((string)file_get_contents($file),'target-environment-bootstrap.php')) $errors[]="{$kind} target bootstrap wrapper must delegate to PHP implementation";
    }
    return ['errors'=>array_values(array_unique($errors)),'metrics'=>['bootstrap_wrappers'=>3,'resume_fingerprints'=>6,'evidence_bindings'=>3]];
}
