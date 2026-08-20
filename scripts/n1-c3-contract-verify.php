<?php

declare(strict_types=1);$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-c3-contracts.php';$r=nexoraAnalyzeN10C3Contracts($root);if($r['errors']){foreach($r['errors'] as $e)fwrite(STDERR,"[N1.0-C3 Contracts] FAIL — {$e}\n");exit(1);}fwrite(STDOUT,"[N1.0-C3 Contracts] PASS\n");foreach($r['metrics'] as $k=>$v)fwrite(STDOUT,"{$k}: {$v}\n");
