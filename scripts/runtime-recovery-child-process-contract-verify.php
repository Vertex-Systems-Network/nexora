<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$helper = $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'runtime-recovery-child-process.php';
$errors = [];

if (! is_file($helper)) {
    $errors[] = 'bounded child-process helper is missing';
} else {
    require_once $helper;
}

if ($errors === [] && ! function_exists('nexoraRuntimeRecoveryExecuteBoundedChild')) {
    $errors[] = 'bounded child-process execution function is unavailable';
}

if ($errors === []) {
    $normal = nexoraRuntimeRecoveryExecuteBoundedChild([
        PHP_BINARY,
        '-r',
        'fwrite(STDOUT,"ok");fwrite(STDERR,"warn");exit(7);',
    ], $root, 2_000, 4_096);

    if ($normal['exit_code'] !== 7
        || $normal['stdout'] !== 'ok'
        || $normal['stderr'] !== 'warn'
        || $normal['failure'] !== null
        || $normal['cleanup_complete'] !== true
        || $normal['stdout_truncated'] !== false
        || $normal['stderr_truncated'] !== false) {
        $errors[] = 'normal child execution did not preserve bounded exit/output semantics';
    }
}

if ($errors === []) {
    $marker = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-child-timeout-'.bin2hex(random_bytes(6));
    @unlink($marker);
    $markerLiteral = var_export($marker, true);
    $started = hrtime(true);
    $timeout = nexoraRuntimeRecoveryExecuteBoundedChild([
        PHP_BINARY,
        '-r',
        'usleep(900000);file_put_contents('.$markerLiteral.',"late");',
    ], $root, 150, 4_096);
    $elapsedMilliseconds = (int) ((hrtime(true) - $started) / 1_000_000);

    // Give a wrongly orphaned child enough time to create the marker after the
    // bounded helper already returned.
    usleep(1_000_000);
    if ($timeout['failure'] !== 'timeout'
        || $timeout['exit_code'] !== 124
        || $timeout['timed_out'] !== true
        || $timeout['cleanup_complete'] !== true
        || $elapsedMilliseconds > 1_700
        || file_exists($marker)) {
        $errors[] = 'hanging child was not terminated and cleaned up within the bounded timeout contract';
    }
    @unlink($marker);
}

if ($errors === []) {
    $limit = 1_024;
    $flood = nexoraRuntimeRecoveryExecuteBoundedChild([
        PHP_BINARY,
        '-r',
        'fwrite(STDOUT,str_repeat("o",65536));fwrite(STDERR,str_repeat("e",65536));usleep(300000);',
    ], $root, 2_000, $limit);

    if ($flood['failure'] !== 'output_limit'
        || $flood['exit_code'] !== 125
        || $flood['cleanup_complete'] !== true
        || $flood['stdout_truncated'] !== true
        || $flood['stderr_truncated'] !== true
        || strlen($flood['stdout']) > $limit
        || strlen($flood['stderr']) > $limit) {
        $errors[] = 'stdout/stderr flood did not fail closed at the declared retained-output bound';
    }
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, 'ERROR: '.$error.PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "Runtime Recovery Child Process Contract PASS\n");
fwrite(STDOUT, "- normal child exit/output preserved\n");
fwrite(STDOUT, "- hanging child bounded and cleaned up\n");
fwrite(STDOUT, "- stdout/stderr retained output hard-capped\n");
