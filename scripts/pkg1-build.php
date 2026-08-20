<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/pkg1-build-identity.php';

$before = nexoraPkg1BuildIdentity($root);
$reportPath = $root.'/storage/app/nexora/certification/pkg1-build-input.json';
$directory = dirname($reportPath);
if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
    fwrite(STDERR, "[NEXORA_BUILD_IDENTITY] unable to create certification directory.\n");
    exit(2);
}

fwrite(STDOUT, "NEXORA_BUILD_IDENTITY\n");
fwrite(STDOUT, 'platform='.$before['platform_version']."\n");
fwrite(STDOUT, 'protocol='.$before['installer_protocol']."\n");
fwrite(STDOUT, 'generation='.$before['source_generation']."\n");
fwrite(STDOUT, 'source='.$before['source_tree_sha256']."\n");
fwrite(STDOUT, 'identity='.$before['identity_sha256']."\n\n");

$environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
$command = PHP_OS_FAMILY === 'Windows'
    ? 'npm.cmd run build:raw'
    : 'npm run build:raw';
$process = proc_open(
    $command,
    [0 => STDIN, 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    $root,
    $environment,
    ['bypass_shell' => false],
);
if (! is_resource($process)) {
    $exitCode = 127;
    $childStdout = '';
    $childStderr = 'Unable to start npm build process.';
} else {
    $childStdout = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $childStderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
}
if ($childStdout !== '') {
    fwrite(STDOUT, $childStdout);
}
if ($childStderr !== '') {
    fwrite(STDERR, $childStderr);
}

$after = nexoraPkg1BuildIdentity($root);
$stable = hash_equals((string) $before['identity_sha256'], (string) $after['identity_sha256']);
$status = $exitCode === 0 && $stable ? 'pass' : 'fail';
$report = $before + [
    'status' => $status,
    'build_exit_code' => $exitCode,
    'post_build_identity_sha256' => $after['identity_sha256'],
    'identity_stable' => $stable,
    'generated_at' => gmdate(DATE_ATOM),
];
file_put_contents(
    $reportPath,
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    LOCK_EX,
);

if (! $stable) {
    fwrite(STDERR, "[NEXORA_BUILD_IDENTITY] source/lock/config identity changed during build.\n");
    exit(1);
}

exit($exitCode === 0 ? 0 : 1);
