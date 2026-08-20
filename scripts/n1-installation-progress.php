<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-installation-progress.php';
$progress = nexoraBuildInstallationProgress($root);
if (in_array('--write', $argv, true)) {
    nexoraPersistInstallationProgress($root, $progress);
}
if (in_array('--json', $argv, true)) {
    fwrite(STDOUT, json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
} else {
    fwrite(STDOUT, nexoraRenderInstallationProgress($progress).PHP_EOL);
    fwrite(STDOUT, (string) ($progress['message'] ?? '').PHP_EOL);
}
