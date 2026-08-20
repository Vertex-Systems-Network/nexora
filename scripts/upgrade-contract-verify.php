<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/upgrade-contracts.php';
$result=nexoraAnalyzeUpgradeContracts($root);
if($result['errors']!==[]){fwrite(STDERR,"[Nexora Upgrade Contracts] FAILED\n - ".implode("\n - ",$result['errors'])."\n");exit(1);}
fwrite(STDOUT,"[Nexora Upgrade Contracts] PASS — {$result['metrics']['commands']} commands; {$result['metrics']['compatibility_domains']} compatibility domains; {$result['metrics']['backup_modes']} backup modes; crash-safe transaction journal enabled; automatic DB rollback disabled.\n");
