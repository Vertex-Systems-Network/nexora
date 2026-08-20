<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/laravel-runtime-contracts.php';

$result = nexoraAnalyzeLaravelRuntimeContracts($root);
if (! $result['ok']) {
    fwrite(STDERR, "[Nexora Laravel Runtime Contracts] FAILED\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

$c = $result['checks'];
fwrite(STDOUT, sprintf(
    "[Nexora Laravel Runtime Contracts] PASS — middleware %d/%d, aliases %d, scheduled commands %d, callbacks %d, queue jobs %d, providers %d.\n",
    $c['middleware_entries'],
    $c['middleware_files'],
    $c['route_middleware_aliases'],
    $c['scheduled_commands'],
    $c['scheduled_callbacks'],
    $c['queue_jobs'],
    $c['service_providers'],
));
