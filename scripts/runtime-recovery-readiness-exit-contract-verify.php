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
        "return \$result['exit_code'] === 1",
        'nexoraRuntimeRecoveryNeedsReceiptRefresh($readinessObserved)',
        'nexoraRuntimeRecoveryNeedsReceiptRefresh($readiness)',
    ] as $needle) {
        if (! str_contains($source, $needle)) {
            $errors[] = 'missing stale-readiness child-exit binding: '.$needle;
        }
    }
    if (str_contains($source, "nexoraRuntimeRecoveryNeedsReceiptRefresh(\$readiness['payload'])")
        || str_contains($source, "nexoraRuntimeRecoveryNeedsReceiptRefresh(\$readinessObserved['payload'])")) {
        $errors[] = 'stale-receipt eligibility must receive the full child result, not payload-only state';
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

if ($errors === []) {
    if (! function_exists('proc_open')) {
        $errors[] = 'behavioral stale-readiness exit probe requires proc_open';
    } else {
        $target = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-readiness-exit-'.bin2hex(random_bytes(6));
        try {
            $vendor = $target.DIRECTORY_SEPARATOR.'vendor';
            $bootstrap = $target.DIRECTORY_SEPARATOR.'bootstrap';
            if ((! @mkdir($vendor, 0700, true) && ! is_dir($vendor))
                || (! @mkdir($bootstrap, 0700, true) && ! is_dir($bootstrap))) {
                $errors[] = 'unable to create stale-readiness behavioral target';
            } else {
                $artisan = $target.DIRECTORY_SEPARATOR.'artisan';
                $marker = $target.DIRECTORY_SEPARATOR.'reconcile-called.marker';
                $fakeArtisan = <<<'PHP'
<?php

declare(strict_types=1);

$command = $argv[1] ?? '';
if ($command === 'nexora:runtime:compatibility-status') {
    echo json_encode([
        'status' => 'pass',
        'mismatches' => [],
        'runtime' => [
            'compatible' => true,
            'mode' => 'installed-data-plane',
            'current_version' => '1.0.0-rc.94',
            'installed_version' => '1.0.0-rc.94',
        ],
    ], JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(0);
}
if ($command === 'nexora:runtime:post-install-status') {
    echo json_encode([
        'status' => 'receipt-refresh-required',
        'ready' => false,
        'runtime_ready' => true,
        'receipt_current' => false,
        'errors' => ['stale receipt'],
    ], JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(9);
}
if ($command === 'nexora:runtime:post-install-reconcile') {
    file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'reconcile-called.marker', 'called');
    echo json_encode(['status' => 'pass'], JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(0);
}

echo json_encode(['status' => 'fail', 'command' => $command], JSON_UNESCAPED_SLASHES).PHP_EOL;
exit(8);
PHP;

                if (@file_put_contents($artisan, $fakeArtisan) === false
                    || @file_put_contents($vendor.DIRECTORY_SEPARATOR.'autoload.php', "<?php\n") === false
                    || @file_put_contents($bootstrap.DIRECTORY_SEPARATOR.'app.php', "<?php\n") === false) {
                    $errors[] = 'unable to populate stale-readiness behavioral target';
                } else {
                    $result = $run($target);
                    try {
                        $payload = json_decode($result['stderr'], true, 512, JSON_THROW_ON_ERROR);
                    } catch (Throwable) {
                        $payload = null;
                    }

                    if ($result['exit_code'] !== 1 || $result['stdout'] !== '') {
                        $errors[] = 'abnormal stale-readiness child did not fail closed with orchestrator exit 1';
                    }
                    if (! is_array($payload)
                        || ($payload['status'] ?? null) !== 'fail'
                        || ($payload['mode'] ?? null) !== 'applied'
                        || ($payload['message'] ?? null) !== 'Runtime recovery did not reach an approved readiness state.'
                        || ($payload['readiness_exit_code'] ?? null) !== 9
                        || ($payload['steps']['readiness_before_reconcile']['exit_code'] ?? null) !== 9
                        || ($payload['mutation_performed'] ?? true) !== false
                        || ($payload['target_verification_complete'] ?? true) !== false) {
                        $errors[] = 'abnormal stale-readiness failure evidence did not preserve the expected exit-9 fail-closed context';
                    }
                    if (is_file($marker)) {
                        $errors[] = 'post-install reconcile executed despite abnormal stale-readiness child exit';
                    }
                }
            }
        } finally {
            $removeTree($target);
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Runtime Recovery Readiness Exit Contract] FAIL\n");
    foreach (array_values(array_unique($errors)) as $error) {
        fwrite(STDERR, '- '.$error."\n");
    }
    exit(1);
}

fwrite(STDOUT, "[Nexora Runtime Recovery Readiness Exit Contract] PASS — stale-receipt reconciliation requires the expected child exit 1; stale-shaped JSON with abnormal exit cannot trigger reconcile mutation.\n");
