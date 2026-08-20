<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/filesystem-contracts.php';
$result=nexoraAnalyzeFilesystemContracts($root);
if($result['ok']){
    $m=$result['metrics'];
    fwrite(STDOUT,"[Nexora Filesystem Contracts] PASS — {$m['repository_entries']} paths; max {$m['max_relative_path']} chars; {$m['psr4_classes']} PSR-4 classes; {$m['app_imports']} App imports; 0 case/Windows path conflicts.\n");
    exit(0);
}
fwrite(STDERR,"[Nexora Filesystem Contracts] FAIL\n");
foreach($result['errors'] as $error)fwrite(STDERR," - {$error}\n");
exit(1);
