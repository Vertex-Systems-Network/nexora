<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/environment-contracts.php';
$result=nexoraAnalyzeEnvironmentContracts($root);
if($result['errors']!==[]){fwrite(STDERR,"[Nexora Environment Contracts] FAILED\n - ".implode("\n - ",$result['errors'])."\n");exit(1);}
fwrite(STDOUT,"[Nexora Environment Contracts] PASS — {$result['metrics']['runtime_env_calls']} runtime env() calls outside config; {$result['metrics']['production_template_keys']} production template keys; {$result['metrics']['environment_sources']} authoritative environment locations.\n");
