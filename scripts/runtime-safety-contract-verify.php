<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/runtime-safety-contracts.php';
$result=nexoraAnalyzeRuntimeSafetyContracts($root);
if($result['ok']){
    $m=$result['metrics'];
    fwrite(STDOUT,"[Nexora Runtime Safety Contracts] PASS — {$m['queue_jobs']} queue jobs; max timeout {$m['max_job_timeout']}s; {$m['request_limit_middlewares']} request/proxy guards; {$m['graceful_cancellation_surfaces']} cancellation surfaces.\n");
    exit(0);
}
fwrite(STDERR,"[Nexora Runtime Safety Contracts] FAIL\n");
foreach($result['errors'] as $error)fwrite(STDERR," - {$error}\n");
exit(1);
