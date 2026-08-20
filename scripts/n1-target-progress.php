<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-progress.php';

$json = in_array('--json', $argv, true);
$write = in_array('--write', $argv, true);
$progress = nexoraBuildN10GranularProgress($root);

if ($write) {
    nexoraPersistN10GranularProgress($root, $progress);
}

if ($json) {
    fwrite(STDOUT, json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
} else {
    fwrite(STDOUT, nexoraRenderN10GranularProgress($progress).PHP_EOL);
}

exit(((int) ($progress['passed'] ?? 0)) === ((int) ($progress['total'] ?? 1)) ? 0 : 2);
