<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/ha-final-contracts.php';
$result=nexoraAnalyzeHaFinalContracts($root);
fwrite(STDOUT,"[Nexora HA/Final Evidence Contracts]\n");
if($result['ok']){fwrite(STDOUT,'PASS — '.$result['metrics']['ha_checks'].' HA checks; '.$result['metrics']['operator_commands'].' operator commands; '.$result['metrics']['manual_evidence_domains'].' final evidence domains.'.PHP_EOL);exit(0);}
foreach($result['errors'] as $error)fwrite(STDERR,' - '.$error.PHP_EOL);
exit(1);
