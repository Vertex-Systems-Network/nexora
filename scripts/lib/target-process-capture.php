<?php

declare(strict_types=1);

/**
 * Execute a normalized argv (never a shell command) while draining stdout and
 * stderr concurrently. Sequential stream_get_contents() calls can deadlock
 * when a child fills stderr before it closes stdout.
 *
 * @param list<string> $command
 * @param array<string,string> $env
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function nexoraTargetCaptureCommand(
    array $command,
    string $root,
    array $env,
    int $maxStreamBytes = 16 * 1024 * 1024,
    int $timeoutSeconds = 7200
): array {
    if ($command === [] || $maxStreamBytes < 1 || $maxStreamBytes > 16 * 1024 * 1024 || $timeoutSeconds < 1) {
        return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'Invalid target subprocess capture arguments.'];
    }
    // Windows .cmd/.bat wrappers need argument-vector normalization; native
    // PHP/Linux executables do not need an optional bootstrap dependency.
    if (PHP_OS_FAMILY === 'Windows') {
        require_once __DIR__.'/target-composer.php';
        $command = nexoraNormalizeTargetCommand($command, $root, $env);
    }
    $proc = @proc_open($command, [
        0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes, $root, $env, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'Unable to start target subprocess.'];
    }
    fclose($pipes[0]);
    $open = ['stdout' => $pipes[1], 'stderr' => $pipes[2]];
    $captured = ['stdout' => '', 'stderr' => ''];
    foreach ($open as $pipe) stream_set_blocking($pipe, false);
    $deadline = microtime(true) + $timeoutSeconds;
    $error = null;
    $finalStatus = null;

    try {
        while ($open !== []) {
            if (microtime(true) > $deadline) {
                $error = 'Target subprocess timed out.';
                break;
            }
            $read = array_values($open);
            $write = null;
            $except = null;
            $ready = @stream_select($read, $write, $except, 0, 200000);
            // stream_select can fail for Windows pipes: attempt nonblocking
            // reads for both streams rather than falling back to blocking IO.
            if ($ready === false) {
                $read = array_values($open);
                usleep(20000);
            }
            foreach ($open as $stream => $pipe) {
                if (in_array($pipe, $read, true)) {
                    $chunk = @fread($pipe, 32768);
                    if ($chunk !== false && $chunk !== '') {
                        if (strlen($captured[$stream]) > $maxStreamBytes - strlen($chunk)) {
                            $error = 'Target subprocess output exceeded 16 MiB per stream.';
                            break 2;
                        }
                        $captured[$stream] .= $chunk;
                    }
                }
                if (feof($pipe)) {
                    fclose($pipe);
                    unset($open[$stream]);
                }
            }
        }
    } finally {
        if ($error !== null) {
            @proc_terminate($proc);
        }
        foreach ($open as $pipe) {
            @fclose($pipe);
        }
        // proc_get_status caches the final child exit code on PHP 8.3+, but
        // on older runtimes proc_close() remains the authoritative fallback.
        $finalStatus = proc_get_status($proc);
        $closed = proc_close($proc);
    }
    if ($error !== null) {
        return ['exit_code' => 1, 'stdout' => $captured['stdout'], 'stderr' => $error];
    }
    $exit = $closed >= 0 ? $closed : (int)($finalStatus['exitcode'] ?? -1);
    return ['exit_code' => $exit >= 0 ? $exit : 127,
        'stdout' => $captured['stdout'], 'stderr' => $captured['stderr']];
}
