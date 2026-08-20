<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/dependency-contracts.php';
$strict=in_array('--strict-locks',$argv,true);
$result=nexoraAnalyzeDependencyContracts($root,$strict);
foreach($result['warnings'] as $warning)fwrite(STDOUT,"[Nexora Dependency Contracts] WARNING — {$warning}\n");
if($result['errors']!==[]){fwrite(STDERR,"[Nexora Dependency Contracts] FAILED\n - ".implode("\n - ",$result['errors'])."\n");exit(1);}
fwrite(STDOUT,"[Nexora Dependency Contracts] PASS — composer.lock=".($result['metrics']['composer_lock']?'present':'pending')."; package-lock.json=".($result['metrics']['npm_lock']?'present':'pending')."; direct prod={$result['metrics']['direct_prod_dependencies']}; direct dev={$result['metrics']['direct_dev_dependencies']}.\n");
