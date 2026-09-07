<?php

declare(strict_types=1);

/**
 * Execute one child process with finite wall-clock and retained-output bounds.
 *
 * Both child streams are regular temporary files rather than anonymous pipes.
 * That avoids pipe back-pressure and permits the parent to poll process state,
 * elapsed monotonic time, and stream sizes without blocking on child output.
 *
 * @param list<string> $command
 * @return array{
 *   exit_code:int,
 *   stdout:string,
 *   stderr:string,
 *   failure:?string,
 *   timed_out:bool,
 *   stdout_bytes:int,
 *   stderr_bytes:int,
 *   stdout_truncated:bool,
 *   stderr_truncated:bool,
 *   termination_escalated:bool,
 *   cleanup_complete:bool
 * }
 */
function nexoraRuntimeRecoveryExecuteBoundedChild(
    array $command,
    string $cwd,
    int $timeoutMilliseconds,
    int $outputLimitBytes,
): array {
    if (! function_exists('proc_open')
        || $command === []
        || $timeoutMilliseconds < 1
        || $outputLimitBytes < 1
        || ! is_dir($cwd)) {
        return nexoraRuntimeRecoveryBoundedChildFailure(
            'invalid_boundary',
            $outputLimitBytes,
        );
    }

    $stdoutHandle = @tmpfile();
    $stderrHandle = @tmpfile();
    if (! is_resource($stdoutHandle) || ! is_resource($stderrHandle)) {
        if (is_resource($stdoutHandle)) {
            fclose($stdoutHandle);
        }
        if (is_resource($stderrHandle)) {
            fclose($stderrHandle);
        }

        return nexoraRuntimeRecoveryBoundedChildFailure(
            'capture_unavailable',
            $outputLimitBytes,
        );
    }

    $descriptors = [1 => $stdoutHandle, 2 => $stderrHandle];
    $pipes = [];
    $process = @proc_open(
        $command,
        $descriptors,
        $pipes,
        $cwd,
        null,
        ['bypass_shell' => true],
    );
    if (! is_resource($process)) {
        fclose($stdoutHandle);
        fclose($stderrHandle);

        return nexoraRuntimeRecoveryBoundedChildFailure(
            'start_failed',
            $outputLimitBytes,
        );
    }

    $startedAt = hrtime(true);
    $deadline = $startedAt + ($timeoutMilliseconds * 1_000_000);
    $failure = null;
    $exitCode = null;
    $stdoutBytes = 0;
    $stderrBytes = 0;
    $terminationEscalated = false;
    $cleanupComplete = false;

    while (true) {
        [$stdoutBytes, $stderrBytes] = nexoraRuntimeRecoveryBoundedChildSizes(
            $stdoutHandle,
            $stderrHandle,
        );

        if ($stdoutBytes > $outputLimitBytes || $stderrBytes > $outputLimitBytes) {
            $failure = 'output_limit';
            break;
        }

        $status = @proc_get_status($process);
        if (! is_array($status)) {
            $failure = 'status_unavailable';
            break;
        }
        if (($status['running'] ?? false) !== true) {
            $statusExit = $status['exitcode'] ?? null;
            if (is_int($statusExit) && $statusExit >= 0) {
                $exitCode = $statusExit;
            }
            $cleanupComplete = true;
            break;
        }

        if (hrtime(true) >= $deadline) {
            $failure = 'timeout';
            break;
        }

        usleep(10_000);
    }

    if ($failure !== null) {
        @proc_terminate($process);
        $cleanupComplete = nexoraRuntimeRecoveryWaitForChildExit($process, 500);

        if (! $cleanupComplete) {
            $terminationEscalated = true;
            @proc_terminate($process, 9);
            $cleanupComplete = nexoraRuntimeRecoveryWaitForChildExit($process, 750);
        }
    }

    if ($cleanupComplete) {
        $closeCode = @proc_close($process);
        if ($exitCode === null && is_int($closeCode) && $closeCode >= 0) {
            $exitCode = $closeCode;
        }
    } else {
        // A process that cannot be observed as terminated must never be reported
        // as successful. Do not wait indefinitely in proc_close after both
        // bounded termination attempts failed.
        $failure = 'termination_failed';
    }

    [$stdoutBytes, $stderrBytes] = nexoraRuntimeRecoveryBoundedChildSizes(
        $stdoutHandle,
        $stderrHandle,
    );
    $stdoutTruncated = $stdoutBytes > $outputLimitBytes;
    $stderrTruncated = $stderrBytes > $outputLimitBytes;

    if ($stdoutTruncated) {
        @ftruncate($stdoutHandle, $outputLimitBytes);
        $stdoutBytes = $outputLimitBytes;
    }
    if ($stderrTruncated) {
        @ftruncate($stderrHandle, $outputLimitBytes);
        $stderrBytes = $outputLimitBytes;
    }

    $stdout = nexoraRuntimeRecoveryReadBoundedChildStream(
        $stdoutHandle,
        $outputLimitBytes,
    );
    $stderr = nexoraRuntimeRecoveryReadBoundedChildStream(
        $stderrHandle,
        $outputLimitBytes,
    );
    fclose($stdoutHandle);
    fclose($stderrHandle);

    if ($failure !== null) {
        $exitCode = match ($failure) {
            'timeout' => 124,
            'output_limit' => 125,
            'termination_failed' => 126,
            default => 127,
        };
    } elseif ($exitCode === null) {
        $exitCode = 1;
    }

    return [
        'exit_code' => $exitCode,
        'stdout' => trim($stdout),
        'stderr' => trim($stderr),
        'failure' => $failure,
        'timed_out' => $failure === 'timeout',
        'stdout_bytes' => $stdoutBytes,
        'stderr_bytes' => $stderrBytes,
        'stdout_truncated' => $stdoutTruncated,
        'stderr_truncated' => $stderrTruncated,
        'termination_escalated' => $terminationEscalated,
        'cleanup_complete' => $cleanupComplete,
    ];
}

/**
 * @return array{
 *   exit_code:int,
 *   stdout:string,
 *   stderr:string,
 *   failure:string,
 *   timed_out:bool,
 *   stdout_bytes:int,
 *   stderr_bytes:int,
 *   stdout_truncated:bool,
 *   stderr_truncated:bool,
 *   termination_escalated:bool,
 *   cleanup_complete:bool
 * }
 */
function nexoraRuntimeRecoveryBoundedChildFailure(
    string $failure,
    int $outputLimitBytes,
): array {
    return [
        'exit_code' => 127,
        'stdout' => '',
        'stderr' => '',
        'failure' => $failure,
        'timed_out' => false,
        'stdout_bytes' => 0,
        'stderr_bytes' => 0,
        'stdout_truncated' => false,
        'stderr_truncated' => false,
        'termination_escalated' => false,
        'cleanup_complete' => true,
    ];
}

/** @return array{0:int,1:int} */
function nexoraRuntimeRecoveryBoundedChildSizes(
    mixed $stdoutHandle,
    mixed $stderrHandle,
): array {
    $stdoutStat = is_resource($stdoutHandle) ? @fstat($stdoutHandle) : false;
    $stderrStat = is_resource($stderrHandle) ? @fstat($stderrHandle) : false;

    return [
        is_array($stdoutStat) ? max(0, (int) ($stdoutStat['size'] ?? 0)) : 0,
        is_array($stderrStat) ? max(0, (int) ($stderrStat['size'] ?? 0)) : 0,
    ];
}

function nexoraRuntimeRecoveryWaitForChildExit(
    mixed $process,
    int $graceMilliseconds,
): bool {
    if (! is_resource($process)) {
        return true;
    }

    $deadline = hrtime(true) + ($graceMilliseconds * 1_000_000);
    do {
        $status = @proc_get_status($process);
        if (! is_array($status) || ($status['running'] ?? false) !== true) {
            return true;
        }
        usleep(10_000);
    } while (hrtime(true) < $deadline);

    $status = @proc_get_status($process);

    return ! is_array($status) || ($status['running'] ?? false) !== true;
}

function nexoraRuntimeRecoveryReadBoundedChildStream(
    mixed $handle,
    int $limitBytes,
): string {
    if (! is_resource($handle) || ! @rewind($handle)) {
        return '';
    }

    $value = @stream_get_contents($handle, $limitBytes);

    return is_string($value) ? $value : '';
}
