<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/module-graph.php';

$json = in_array('--json', $argv ?? [], true);
$result = nexoraAnalyzeModuleGraph($root);

if ($json) {
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
} else {
    if ($result['ok']) {
        fwrite(STDOUT, '[Nexora Module Graph] PASS — '.count($result['modules'])." modules; boot order resolved.\n");
    } else {
        fwrite(STDERR, "[Nexora Module Graph] FAILED\n - ".implode("\n - ", $result['errors'])."\n");
    }
}

exit($result['ok'] ? 0 : 1);
