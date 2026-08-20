<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/final-closure.php';
$payload=nexoraEvaluateFinalClosure($root);
nexoraWriteClosureStatus($root,$payload);
fwrite(STDOUT,"[Nexora Final Closure] {$payload['status']} — {$payload['platform_version']}\n");
foreach($payload['domains'] as $name=>$domain) fwrite(STDOUT,sprintf(" - %-24s %-7s %s\n",$name,strtoupper((string)$domain['status']),(string)$domain['detail']));
if($payload['blocking_domains']!==[]){fwrite(STDERR,"[Nexora Final Closure] Blocking: ".implode(', ',$payload['blocking_domains'])."\n");exit(2);}
exit(0);
