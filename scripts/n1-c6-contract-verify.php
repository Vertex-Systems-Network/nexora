<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-c6-contracts.php';$r=nexoraAnalyzeN10C6Contracts($root);fwrite(STDOUT,"[N1.0-C6 Contracts]\n");if($r['errors']!==[]){foreach($r['errors'] as $e)fwrite(STDERR," - {$e}\n");exit(1);}fwrite(STDOUT,"PASS — {$r['metrics']['prior_chunks']} prior chunks / {$r['metrics']['ha_checks']} HA checks / {$r['metrics']['ordered_gates']} ordered final gates / {$r['metrics']['evidence_bindings']} evidence bindings.\n");
