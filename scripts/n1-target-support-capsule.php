<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/target-support-capsule.php';

$base = $root.'/storage/app/nexora/n1-target-execution';
$latest = $base.'/latest.json';
if (! is_file($latest)) {
    fwrite(STDERR, "[Nexora Target Support Capsule] No target execution summary exists. Run n1-target-execution first.\n");
    exit(2);
}
try {
    $summary = json_decode((string) file_get_contents($latest), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, "[Nexora Target Support Capsule] Invalid latest target execution summary: {$exception->getMessage()}\n");
    exit(1);
}
if (! is_array($summary) || ! isset($summary['run_id'])) {
    fwrite(STDERR, "[Nexora Target Support Capsule] Latest summary is missing run_id.\n");
    exit(1);
}
$runDir = $base.'/'.basename((string) $summary['run_id']);
if (! is_dir($runDir)) {
    fwrite(STDERR, "[Nexora Target Support Capsule] Target execution run directory is missing.\n");
    exit(1);
}
$capsule = nexoraBuildTargetSupportCapsule($root, $runDir, $summary);
$json = json_encode($capsule, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
$path = $runDir.'/support-capsule.json';
file_put_contents($path, $json);
file_put_contents($runDir.'/support-capsule.sha256', hash('sha256', $json)."  support-capsule.json\n");
file_put_contents($base.'/latest-support.json', $json);
file_put_contents($base.'/latest-support.sha256', hash('sha256', $json)."  latest-support.json\n");
fwrite(STDOUT, "[Nexora Target Support Capsule] PASS\nUpload this single file for troubleshooting:\n  storage/app/nexora/n1-target-execution/latest-support.json\nSHA-256: ".hash('sha256', $json)."\n");
