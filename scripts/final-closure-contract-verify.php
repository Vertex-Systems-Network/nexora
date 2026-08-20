<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/final-closure-contracts.php';
$result=nexoraAnalyzeFinalClosureContracts($root);
if($result['errors']!==[]){fwrite(STDERR,"[Nexora Final Closure Contracts] FAILED\n - ".implode("\n - ",$result['errors'])."\n");exit(1);}
fwrite(STDOUT,"[Nexora Final Closure Contracts] PASS — {$result['metrics']['closure_domains']} closure domains / {$result['metrics']['runner_modes']} runner modes.\n");
