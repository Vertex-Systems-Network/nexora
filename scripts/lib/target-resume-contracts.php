<?php

declare(strict_types=1);

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
    if (!is_dir($directory) || is_link($directory)
        || !is_file($manifestPath) || is_link($manifestPath)
        || is_file($root.'/public/hot')) {
        return null;
    }

    try {
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifest) || array_is_list($manifest)
            || !isset($manifest['resources/js/app.tsx'], $manifest['resources/css/app.css'])) {
            return null;
        }
        // Validate referenced outputs as well as the overall tree digest.
        $referencedAssets = [];
        foreach ($manifest as $entry) {
            if (!is_array($entry) || !is_string($entry['file'] ?? null)) {
                return null;
            }
            foreach (array_merge([$entry['file']], (array) ($entry['css'] ?? [])) as $relative) {
                if (!is_string($relative) || $relative === '' || str_contains($relative, "\0")
                    || str_contains($relative, '\\') || str_starts_with($relative, '/')
                    || preg_match('~(^|/)\\.{1,2}(/|$)~', $relative) === 1) {
                    return null;
                }
                $target = $directory.'/'.$relative;
                if (!is_file($target) || is_link($target)) {
                    return null;
                }
                $referencedAssets[$relative] = true;
            }
            foreach ((array) ($entry['imports'] ?? []) as $import) {
                if (!is_string($import) || !array_key_exists($import, $manifest)) {
                    return null;
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
            if (!$file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
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
    if ($buildHash !== null && is_file($reportFile) && !is_link($reportFile)
        && filesize($reportFile) <= 8 * 1024 * 1024) {
        try {
            $report = json_decode((string) file_get_contents($reportFile), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($report) && ($report['status'] ?? null) === 'pass'
                && ($report['source_tree_sha256'] ?? null) === ($environment['source_tree_sha256'] ?? null)
                && ($report['platform_version'] ?? null) === ($environment['platform_version'] ?? null)) {
                $reportHash = hash_file('sha256', $reportFile) ?: null;
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
