<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$repair = $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'rc93-post-install-identity-repair.php';
$errors = [];

if (! is_file($repair)) {
    $errors[] = 'missing rc93 repair adapter';
} else {
    $source = (string) file_get_contents($repair);
    foreach ([
        'function nexoraRc93RepairTargetFile(string $target, string $relative): ?string',
        'function nexoraRc93RepairTargetDirectory(string $target, array $segments, bool $create): ?string',
        'function nexoraRc93RepairContainedExistingFile(string $target, string $path): ?string',
        'nexoraRc93RepairTargetFile($target, $relative)',
        "nexoraRc93RepairTargetDirectory(\$target, ['storage', 'app', 'nexora', 'repair-backups'], true)",
        "nexoraRc93RepairTargetDirectory(\$target, ['storage', 'app', 'nexora', 'runtime', 'repair-receipts'], true)",
        'nexoraRc93RepairContainedExistingFile($target, $lockPath)',
        'hash_equals($candidatePath, $resolvedPath)',
        'str_starts_with($resolvedPath, $targetPrefix)',
    ] as $needle) {
        if (! str_contains($source, $needle)) {
            $errors[] = 'missing rc93 exact-target filesystem containment contract: '.$needle;
        }
    }

    if (str_contains($source, "\$path = \$target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, \$relative);")) {
        $errors[] = 'rc93 runtime files must not rely on lexical final-file validation';
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
$run = static function (string $target) use ($repair, $root): array {
    $stderrHandle = @tmpfile();
    if (! is_resource($stderrHandle)) {
        return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'unable to create stderr capture'];
    }
    $pipes = [];
    $process = @proc_open([
        PHP_BINARY,
        $repair,
        '--target='.$target,
        '--apply',
        '--confirm=REPAIR-RC93',
    ], [
        1 => ['pipe', 'w'],
        2 => $stderrHandle,
    ], $pipes, $root, null, ['bypass_shell' => true]);
    if (! is_resource($process)) {
        fclose($stderrHandle);
        return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'unable to start repair adapter'];
    }

    $stdout = is_resource($pipes[1] ?? null) ? trim((string) stream_get_contents($pipes[1])) : '';
    if (is_resource($pipes[1] ?? null)) fclose($pipes[1]);
    $exitCode = proc_close($process);
    $stderr = '';
    if (@rewind($stderrHandle)) $stderr = trim((string) stream_get_contents($stderrHandle));
    fclose($stderrHandle);

    return [
        'exit_code' => is_int($exitCode) ? $exitCode : 1,
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
};

if ($errors === [] && PHP_OS_FAMILY !== 'Windows' && function_exists('symlink') && function_exists('proc_open')) {
    $target = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-rc93-path-'.bin2hex(random_bytes(6));
    $outside = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-rc93-path-outside-'.bin2hex(random_bytes(6));
    try {
        $bootstrap = $target.DIRECTORY_SEPARATOR.'bootstrap';
        if ((! @mkdir($bootstrap, 0700, true) && ! is_dir($bootstrap))
            || (! @mkdir($outside, 0700, true) && ! is_dir($outside))) {
            $errors[] = 'unable to create rc93 path-containment behavioral directories';
        } else {
            $outsideMarker = $outside.DIRECTORY_SEPARATOR.'autoload-executed.marker';
            $autoload = "<?php\nfile_put_contents(__DIR__.DIRECTORY_SEPARATOR.'autoload-executed.marker', 'executed');\n";
            if (@file_put_contents($target.DIRECTORY_SEPARATOR.'artisan', "<?php\n") === false
                || @file_put_contents($bootstrap.DIRECTORY_SEPARATOR.'app.php', "<?php\n") === false
                || @file_put_contents($outside.DIRECTORY_SEPARATOR.'autoload.php', $autoload) === false
                || ! @symlink($outside, $target.DIRECTORY_SEPARATOR.'vendor')) {
                $errors[] = 'unable to populate rc93 path-containment behavioral target';
            } else {
                $result = $run($target);
                try {
                    $payload = json_decode($result['stderr'], true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    $payload = null;
                }

                if ($result['exit_code'] !== 1 || $result['stdout'] !== '') {
                    $errors[] = 'rc93 redirected vendor parent did not fail closed with exit 1';
                }
                if (! is_array($payload)
                    || ($payload['status'] ?? null) !== 'fail'
                    || ($payload['message'] ?? null) !== 'Target is missing or redirects a required regular Nexora runtime file.'
                    || ($payload['missing_or_unsafe'] ?? null) !== 'vendor/autoload.php') {
                    $errors[] = 'rc93 redirected vendor parent did not fail at exact-target runtime validation';
                }
                if (is_file($outsideMarker)) {
                    $errors[] = 'rc93 repair executed redirected outside autoload.php';
                }
                $repairBackups = $target.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'.DIRECTORY_SEPARATOR.'repair-backups';
                if (file_exists($repairBackups) || is_link($repairBackups)) {
                    $errors[] = 'rc93 repair created mutation evidence before runtime-file containment passed';
                }
            }
        }
    } finally {
        $removeTree($target);
        $removeTree($outside);
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora rc.93 Repair Path Containment] FAIL\n");
    foreach (array_values(array_unique($errors)) as $error) fwrite(STDERR, '- '.$error."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora rc.93 Repair Path Containment] PASS — standalone repair runtime files, installed lock, backup and receipt paths are exact-target constrained; redirected vendor parents cannot execute outside code.\n");
