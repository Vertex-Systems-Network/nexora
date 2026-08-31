<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$orchestrator = $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'runtime-recovery-orchestrator.php';
$errors = [];

if (! is_file($orchestrator)) {
    $errors[] = 'missing runtime recovery orchestrator';
} else {
    $source = (string) file_get_contents($orchestrator);
    foreach ([
        'function nexoraRuntimeRecoveryTargetRuntimeFile(string $target, string $relative): ?string',
        'function nexoraRuntimeRecoveryAssertTargetRuntimeFiles(string $target): void',
        'function nexoraRuntimeRecoveryRunTarget(array $command, string $target): array',
        'nexoraRuntimeRecoveryTargetRuntimeFile($target, $relative)',
        'nexoraRuntimeRecoveryAssertTargetRuntimeFiles($target)',
        'realpath($candidate)',
        'hash_equals($candidatePath, $resolvedPath)',
        'str_starts_with($resolvedPath, $targetPrefix)',
        "Target is missing or redirects a required regular runtime file.",
    ] as $needle) {
        if (! str_contains($source, $needle)) {
            $errors[] = 'missing required target runtime-file containment contract: '.$needle;
        }
    }

    if (substr_count($source, 'nexoraRuntimeRecoveryRunTarget(') < 7) {
        $errors[] = 'every target-owned PHP child path must pass through immediate runtime-file revalidation';
    }
    if (str_contains($source, "\$path = \$target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, \$relative);")) {
        $errors[] = 'required runtime files must not be trusted by lexical final-file checks alone';
    }
}

$removeTree = static function (string $path): void {
    if (is_link($path)) {
        @unlink($path);

        return;
    }
    if (! is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        $itemPath = $item->getPathname();
        if ($item->isLink() || $item->isFile()) {
            @unlink($itemPath);
        } else {
            @rmdir($itemPath);
        }
    }
    @rmdir($path);
};

/** @return array{exit_code:int,stdout:string,stderr:string} */
$run = static function (string $target) use ($orchestrator, $root): array {
    $stderrHandle = @tmpfile();
    if (! is_resource($stderrHandle)) {
        return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'unable to create stderr capture'];
    }

    $pipes = [];
    $process = @proc_open([
        PHP_BINARY,
        $orchestrator,
        '--target='.$target,
        '--apply',
        '--confirm=RECOVER-RUNTIME',
    ], [
        1 => ['pipe', 'w'],
        2 => $stderrHandle,
    ], $pipes, $root, null, ['bypass_shell' => true]);
    if (! is_resource($process)) {
        fclose($stderrHandle);

        return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'unable to start orchestrator'];
    }

    $stdout = is_resource($pipes[1] ?? null) ? trim((string) stream_get_contents($pipes[1])) : '';
    if (is_resource($pipes[1] ?? null)) {
        fclose($pipes[1]);
    }
    $exitCode = proc_close($process);
    $stderr = '';
    if (@rewind($stderrHandle)) {
        $stderr = trim((string) stream_get_contents($stderrHandle));
    }
    fclose($stderrHandle);

    return [
        'exit_code' => is_int($exitCode) ? $exitCode : 1,
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
};

if ($errors === [] && PHP_OS_FAMILY !== 'Windows' && function_exists('symlink') && function_exists('proc_open')) {
    $target = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-target-file-'.bin2hex(random_bytes(6));
    $outside = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-target-file-outside-'.bin2hex(random_bytes(6));
    try {
        $bootstrap = $target.DIRECTORY_SEPARATOR.'bootstrap';
        if ((! @mkdir($bootstrap, 0700, true) && ! is_dir($bootstrap))
            || (! @mkdir($outside, 0700, true) && ! is_dir($outside))) {
            $errors[] = 'unable to create target-file containment behavioral directories';
        } else {
            $executionMarker = $target.DIRECTORY_SEPARATOR.'artisan-executed.marker';
            $artisan = <<<'PHP'
<?php
file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'artisan-executed.marker', 'executed');
echo json_encode(['status' => 'pass']).PHP_EOL;
exit(0);
PHP;
            if (@file_put_contents($target.DIRECTORY_SEPARATOR.'artisan', $artisan) === false
                || @file_put_contents($bootstrap.DIRECTORY_SEPARATOR.'app.php', "<?php\n") === false
                || @file_put_contents($outside.DIRECTORY_SEPARATOR.'autoload.php', "<?php\n") === false
                || ! @symlink($outside, $target.DIRECTORY_SEPARATOR.'vendor')) {
                $errors[] = 'unable to populate target-file containment behavioral target';
            } else {
                $result = $run($target);
                try {
                    $payload = json_decode($result['stderr'], true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    $payload = null;
                }

                $recoveryDirectory = $target.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'
                    .DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'recovery-orchestrator';

                if ($result['exit_code'] !== 1 || $result['stdout'] !== '') {
                    $errors[] = 'redirected vendor parent did not fail closed with exit 1 and empty stdout';
                }
                if (! is_array($payload)
                    || ($payload['status'] ?? null) !== 'fail'
                    || ($payload['message'] ?? null) !== 'Target is missing or redirects a required regular runtime file.'
                    || ($payload['missing_or_unsafe'] ?? null) !== 'vendor/autoload.php'
                    || array_key_exists('mode', $payload)
                    || array_key_exists('evidence_receipt', $payload)) {
                    $errors[] = 'redirected vendor parent did not fail during pre-lock target validation with expected evidence';
                }
                if (is_file($executionMarker)) {
                    $errors[] = 'target artisan executed despite redirected required runtime file';
                }
                if (file_exists($recoveryDirectory) || is_link($recoveryDirectory)) {
                    $errors[] = 'apply lock/recovery evidence storage was created before redirected runtime-file validation failed';
                }
            }
        }
    } finally {
        $removeTree($target);
        $removeTree($outside);
    }
}

// TOCTOU regression: initial validation passes, then the first target child swaps
// vendor to an outside symlink. The next target child must be rejected before it
// executes; initial containment is not a durable trust decision.
if ($errors === [] && PHP_OS_FAMILY !== 'Windows' && function_exists('symlink') && function_exists('proc_open')) {
    $target = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-target-file-race-'.bin2hex(random_bytes(6));
    $outside = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-target-file-race-outside-'.bin2hex(random_bytes(6));
    try {
        $vendor = $target.DIRECTORY_SEPARATOR.'vendor';
        $bootstrap = $target.DIRECTORY_SEPARATOR.'bootstrap';
        if ((! @mkdir($vendor, 0700, true) && ! is_dir($vendor))
            || (! @mkdir($bootstrap, 0700, true) && ! is_dir($bootstrap))
            || (! @mkdir($outside, 0700, true) && ! is_dir($outside))) {
            $errors[] = 'unable to create runtime-file TOCTOU behavioral directories';
        } else {
            $readinessMarker = $target.DIRECTORY_SEPARATOR.'readiness-executed.marker';
            $outsideLiteral = var_export($outside, true);
            $artisan = <<<'PHP'
<?php

declare(strict_types=1);

$command = (string) ($argv[1] ?? '');
if ($command === 'nexora:runtime:compatibility-status') {
    $vendor = __DIR__.DIRECTORY_SEPARATOR.'vendor';
    $original = __DIR__.DIRECTORY_SEPARATOR.'vendor-before-swap';
    if (! @rename($vendor, $original) || ! @symlink(__OUTSIDE__, $vendor)) {
        fwrite(STDERR, json_encode(['status' => 'fail', 'error' => 'unable to create synthetic vendor swap']));
        exit(18);
    }
    echo json_encode([
        'status' => 'pass',
        'mismatches' => [],
        'runtime' => [
            'compatible' => true,
            'mode' => 'installed-data-plane',
            'current_version' => '1.0.0-rc.94',
            'installed_version' => '1.0.0-rc.94',
            'mismatches' => [],
        ],
    ], JSON_UNESCAPED_SLASHES);
    exit(0);
}

if ($command === 'nexora:runtime:post-install-status') {
    file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'readiness-executed.marker', 'executed');
    echo json_encode([
        'status' => 'fail',
        'ready' => false,
        'runtime_ready' => false,
        'receipt_current' => false,
        'errors' => ['should never execute'],
    ], JSON_UNESCAPED_SLASHES);
    exit(1);
}

fwrite(STDERR, json_encode(['status' => 'fail', 'unexpected_command' => $command]));
exit(19);
PHP;
            $artisan = str_replace('__OUTSIDE__', $outsideLiteral, $artisan);
            if (@file_put_contents($target.DIRECTORY_SEPARATOR.'artisan', $artisan) === false
                || @file_put_contents($vendor.DIRECTORY_SEPARATOR.'autoload.php', "<?php\n") === false
                || @file_put_contents($bootstrap.DIRECTORY_SEPARATOR.'app.php', "<?php\n") === false
                || @file_put_contents($outside.DIRECTORY_SEPARATOR.'autoload.php', "<?php\n") === false) {
                $errors[] = 'unable to populate runtime-file TOCTOU behavioral target';
            } else {
                $result = $run($target);
                try {
                    $payload = json_decode($result['stderr'], true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    $payload = null;
                }

                if ($result['exit_code'] !== 1 || $result['stdout'] !== '') {
                    $errors[] = 'runtime-file TOCTOU probe did not fail closed with exit 1 and empty stdout';
                }
                if (! is_array($payload)
                    || ($payload['status'] ?? null) !== 'fail'
                    || ($payload['mode'] ?? null) !== 'applied'
                    || ($payload['message'] ?? null) !== 'Target is missing or redirects a required regular runtime file.'
                    || ($payload['failure_context']['missing_or_unsafe'] ?? null) !== 'vendor/autoload.php'
                    || ($payload['steps']['apply_lock']['status'] ?? null) !== 'pass'
                    || ($payload['steps']['compatibility_before']['status'] ?? null) !== 'pass'
                    || ($payload['mutation_attempted'] ?? true) !== false
                    || ($payload['mutation_performed'] ?? true) !== false
                    || ($payload['mutation_may_have_occurred'] ?? true) !== false
                    || ($payload['evidence_write_status'] ?? null) !== 'pass') {
                    $errors[] = 'runtime-file TOCTOU probe did not preserve the expected post-lock containment failure evidence';
                }
                if (is_file($readinessMarker)) {
                    $errors[] = 'readiness child executed after compatibility child redirected vendor outside the target';
                }
                $receipt = is_array($payload) ? (string) ($payload['evidence_receipt'] ?? '') : '';
                if ($receipt === '' || ! is_file($receipt)) {
                    $errors[] = 'runtime-file TOCTOU post-lock failure did not produce protected target-owned evidence';
                }
            }
        }
    } finally {
        $removeTree($target);
        $removeTree($outside);
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Runtime Recovery Target File Containment] FAIL\n");
    foreach (array_values(array_unique($errors)) as $error) {
        fwrite(STDERR, '- '.$error."\n");
    }
    exit(1);
}

fwrite(STDOUT, "[Nexora Runtime Recovery Target File Containment] PASS — required runtime files are component-contained initially and immediately before every target-owned PHP child; redirected parent directories and post-validation swaps cannot reach child execution.\n");
