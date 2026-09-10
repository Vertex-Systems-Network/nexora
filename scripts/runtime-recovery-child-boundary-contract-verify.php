<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$orchestrator = $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'runtime-recovery-orchestrator.php';
$errors = [];

if (! is_file($orchestrator)) {
    fwrite(STDERR, "Runtime Recovery Child Boundary FAIL\n - missing runtime recovery orchestrator\n");
    exit(1);
}
if (! function_exists('proc_open')) {
    fwrite(STDERR, "Runtime Recovery Child Boundary FAIL\n - proc_open is required for behavioral certification\n");
    exit(1);
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

$prepareTarget = static function (string $target, string $artisanSource): bool {
    $vendor = $target.DIRECTORY_SEPARATOR.'vendor';
    $bootstrap = $target.DIRECTORY_SEPARATOR.'bootstrap';
    if ((! @mkdir($vendor, 0700, true) && ! is_dir($vendor))
        || (! @mkdir($bootstrap, 0700, true) && ! is_dir($bootstrap))) {
        return false;
    }

    return @file_put_contents($target.DIRECTORY_SEPARATOR.'artisan', $artisanSource) !== false
        && @file_put_contents($vendor.DIRECTORY_SEPARATOR.'autoload.php', "<?php\n") !== false
        && @file_put_contents($bootstrap.DIRECTORY_SEPARATOR.'app.php', "<?php\n") !== false;
};

/**
 * @param array<string,string> $overrides
 * @return array{exit_code:int,stdout:string,stderr:string,outer_timeout:bool,elapsed:float}
 */
$runProbe = static function (string $target, array $overrides) use ($orchestrator, $root): array {
    $stderrHandle = @tmpfile();
    if (! is_resource($stderrHandle)) {
        return [
            'exit_code' => 127,
            'stdout' => '',
            'stderr' => 'unable to create verifier stderr capture',
            'outer_timeout' => false,
            'elapsed' => 0.0,
        ];
    }

    $environment = getenv();
    $environment = is_array($environment) ? $environment : [];
    foreach ($overrides as $name => $value) {
        $environment[$name] = $value;
    }

    $pipes = [];
    $startedAt = microtime(true);
    $process = @proc_open([
        PHP_BINARY,
        $orchestrator,
        '--target='.$target,
        '--apply',
        '--confirm=RECOVER-RUNTIME',
    ], [
        1 => ['pipe', 'w'],
        2 => $stderrHandle,
    ], $pipes, $root, $environment, ['bypass_shell' => true]);
    if (! is_resource($process)) {
        fclose($stderrHandle);
        return [
            'exit_code' => 127,
            'stdout' => '',
            'stderr' => 'unable to start orchestrator verifier probe',
            'outer_timeout' => false,
            'elapsed' => microtime(true) - $startedAt,
        ];
    }

    $stdout = '';
    $stdoutPipe = is_resource($pipes[1] ?? null) ? $pipes[1] : null;
    if (is_resource($stdoutPipe)) {
        @stream_set_blocking($stdoutPipe, false);
    }

    $outerTimeout = false;
    $observedExit = null;
    $outerDeadline = $startedAt + 8.0;
    while (true) {
        if (is_resource($stdoutPipe)) {
            $chunk = @stream_get_contents($stdoutPipe);
            if (is_string($chunk) && $chunk !== '') {
                $stdout .= $chunk;
            }
        }

        $status = @proc_get_status($process);
        if (! is_array($status) || ($status['running'] ?? false) !== true) {
            if (is_array($status) && is_int($status['exitcode'] ?? null) && $status['exitcode'] >= 0) {
                $observedExit = $status['exitcode'];
            }
            break;
        }
        if (microtime(true) >= $outerDeadline) {
            $outerTimeout = true;
            @proc_terminate($process);
            usleep(150000);
            $status = @proc_get_status($process);
            if (is_array($status) && ($status['running'] ?? false) === true) {
                @proc_terminate($process, 9);
            }
            break;
        }
        usleep(20000);
    }

    if (is_resource($stdoutPipe)) {
        $chunk = @stream_get_contents($stdoutPipe);
        if (is_string($chunk) && $chunk !== '') {
            $stdout .= $chunk;
        }
        fclose($stdoutPipe);
    }
    unset($pipes[1]);
    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }

    $exitCode = proc_close($process);
    if ($observedExit !== null && (! is_int($exitCode) || $exitCode < 0)) {
        $exitCode = $observedExit;
    }

    $stderr = '';
    if (@rewind($stderrHandle)) {
        $stderr = (string) stream_get_contents($stderrHandle);
    }
    fclose($stderrHandle);

    return [
        'exit_code' => is_int($exitCode) ? $exitCode : 1,
        'stdout' => trim($stdout),
        'stderr' => trim($stderr),
        'outer_timeout' => $outerTimeout,
        'elapsed' => microtime(true) - $startedAt,
    ];
};

/** @return array<string,mixed>|null */
$decodeFailure = static function (array $probe): ?array {
    if (($probe['outer_timeout'] ?? true) === true
        || ($probe['exit_code'] ?? null) !== 1
        || ($probe['stdout'] ?? '') !== '') {
        return null;
    }

    try {
        $payload = json_decode((string) ($probe['stderr'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    return is_array($payload) ? $payload : null;
};

$assertBoundaryFailure = static function (
    string $label,
    array $probe,
    string $reason,
    bool $stdoutTruncated,
    bool $stderrTruncated,
) use (&$errors, $decodeFailure): ?array {
    $payload = $decodeFailure($probe);
    if (! is_array($payload)) {
        $errors[] = $label.' did not fail closed with parseable exit-1 JSON evidence';
        return null;
    }

    $boundary = is_array($payload['failure_context']['child_boundary'] ?? null)
        ? $payload['failure_context']['child_boundary']
        : [];
    if (($payload['status'] ?? null) !== 'fail'
        || ($payload['mode'] ?? null) !== 'applied'
        || ($payload['target_verification_complete'] ?? true) !== false
        || ($payload['steps']['apply_lock']['status'] ?? null) !== 'pass'
        || ($payload['evidence_write_status'] ?? null) !== 'pass'
        || ($boundary['reason'] ?? null) !== $reason
        || ($boundary['output_limit_bytes'] ?? null) !== 4096
        || ($boundary['stdout_truncated'] ?? null) !== $stdoutTruncated
        || ($boundary['stderr_truncated'] ?? null) !== $stderrTruncated
        || ($boundary['termination']['cleanup_complete'] ?? false) !== true) {
        $errors[] = $label.' did not preserve the required bounded child/lock/evidence contract';
    }

    if (($boundary['stdout_bytes_captured'] ?? 0) > 4096
        || ($boundary['stderr_bytes_captured'] ?? 0) > 4096
        || strlen((string) ($payload['failure_context']['stdout'] ?? '')) > 4096
        || strlen((string) ($payload['failure_context']['stderr'] ?? '')) > 4096) {
        $errors[] = $label.' exceeded the declared captured/evidence output bound';
    }

    $receipt = $payload['evidence_receipt'] ?? null;
    if (! is_string($receipt) || ! is_file($receipt)) {
        $errors[] = $label.' did not persist its sealed apply-mode failure receipt';
    }

    return $payload;
};

$base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-child-boundary-'.bin2hex(random_bytes(6));
$timeoutTarget = $base.DIRECTORY_SEPARATOR.'timeout';
$stdoutTarget = $base.DIRECTORY_SEPARATOR.'stdout';
$stderrTarget = $base.DIRECTORY_SEPARATOR.'stderr';

try {
    $heartbeat = $timeoutTarget.DIRECTORY_SEPARATOR.'child-heartbeat.txt';
    $heartbeatLiteral = var_export($heartbeat, true);
    $timeoutArtisan = "<?php\n"
        .'$heartbeat='.$heartbeatLiteral.";\n"
        ."for (\$i = 0; \$i < 500; \$i++) { file_put_contents(\$heartbeat, (string) \$i, LOCK_EX); usleep(20000); }\n"
        ."fwrite(STDOUT, json_encode(['status' => 'pass']));\nexit(0);\n";
    if (! $prepareTarget($timeoutTarget, $timeoutArtisan)) {
        $errors[] = 'unable to prepare timeout child-boundary target';
    } else {
        $timeout = $runProbe($timeoutTarget, [
            'NEXORA_RUNTIME_RECOVERY_CHILD_DEADLINE_SECONDS' => '1',
            'NEXORA_RUNTIME_RECOVERY_CHILD_OUTPUT_LIMIT_BYTES' => '4096',
        ]);
        $assertBoundaryFailure('timeout probe', $timeout, 'timeout', false, false);
        if (($timeout['elapsed'] ?? 99.0) > 4.0) {
            $errors[] = 'timeout probe exceeded the bounded wall-clock envelope';
        }

        $heartbeatBefore = is_file($heartbeat) ? (string) file_get_contents($heartbeat) : null;
        usleep(300000);
        $heartbeatAfter = is_file($heartbeat) ? (string) file_get_contents($heartbeat) : null;
        if ($heartbeatBefore === null || $heartbeatAfter !== $heartbeatBefore) {
            $errors[] = 'timeout probe child continued running after orchestrator termination';
        }

        $invalidArtisan = "<?php\nfwrite(STDOUT, \"not-json\\n\");\nexit(9);\n";
        if (@file_put_contents($timeoutTarget.DIRECTORY_SEPARATOR.'artisan', $invalidArtisan) === false) {
            $errors[] = 'unable to replace timeout probe child for lock-release verification';
        } else {
            $afterTimeout = $runProbe($timeoutTarget, [
                'NEXORA_RUNTIME_RECOVERY_CHILD_DEADLINE_SECONDS' => '2',
                'NEXORA_RUNTIME_RECOVERY_CHILD_OUTPUT_LIMIT_BYTES' => '4096',
            ]);
            $afterPayload = $decodeFailure($afterTimeout);
            if (! is_array($afterPayload)
                || ($afterPayload['failure_context']['exit_code'] ?? null) !== 9
                || ($afterPayload['failure_context']['stdout'] ?? null) !== 'not-json'
                || str_contains((string) ($afterTimeout['stderr'] ?? ''), 'Another apply-mode runtime recovery is already active')) {
                $errors[] = 'apply lock was not cleanly reusable after a timed-out child failure';
            }
        }
    }

    $stdoutArtisan = "<?php\nfwrite(STDOUT, str_repeat('A', 131072));\nfflush(STDOUT);\nusleep(5000000);\n";
    if (! $prepareTarget($stdoutTarget, $stdoutArtisan)) {
        $errors[] = 'unable to prepare stdout-limit child-boundary target';
    } else {
        $stdoutProbe = $runProbe($stdoutTarget, [
            'NEXORA_RUNTIME_RECOVERY_CHILD_DEADLINE_SECONDS' => '5',
            'NEXORA_RUNTIME_RECOVERY_CHILD_OUTPUT_LIMIT_BYTES' => '4096',
        ]);
        $assertBoundaryFailure('stdout-limit probe', $stdoutProbe, 'stdout-limit', true, false);
    }

    $stderrArtisan = "<?php\nfwrite(STDERR, str_repeat('B', 131072));\nfflush(STDERR);\nusleep(5000000);\n";
    if (! $prepareTarget($stderrTarget, $stderrArtisan)) {
        $errors[] = 'unable to prepare stderr-limit child-boundary target';
    } else {
        $stderrProbe = $runProbe($stderrTarget, [
            'NEXORA_RUNTIME_RECOVERY_CHILD_DEADLINE_SECONDS' => '5',
            'NEXORA_RUNTIME_RECOVERY_CHILD_OUTPUT_LIMIT_BYTES' => '4096',
        ]);
        $assertBoundaryFailure('stderr-limit probe', $stderrProbe, 'stderr-limit', false, true);
    }
} finally {
    $removeTree($base);
}

if ($errors !== []) {
    fwrite(STDERR, "Runtime Recovery Child Boundary FAIL\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(STDOUT, "Runtime Recovery Child Boundary PASS — finite deadline, bounded stdout/stderr, deterministic child cleanup and post-failure lock reuse are behaviorally enforced.\n");
