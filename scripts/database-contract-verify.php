<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require $root.'/scripts/lib/database-contracts.php';
$result=nexoraAnalyzeDatabaseContracts($root);
fwrite(STDOUT,"[Nexora Database Contracts]\n");
foreach($result['warnings'] as $warning)fwrite(STDOUT,"WARN: {$warning}\n");
if($result['errors']!==[]){foreach($result['errors'] as $error)fwrite(STDERR,"FAIL: {$error}\n");exit(1);}
$m=$result['metrics'];
fwrite(STDOUT,"PASS — {$m['migrations']} migrations; {$m['tables']} tables; {$m['foreign_targets']} foreign targets; {$m['tenant_tables']} tenant tables/models aligned.\n");
