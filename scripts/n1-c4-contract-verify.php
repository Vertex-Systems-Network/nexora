<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-c4-contracts.php';$r=nexoraAnalyzeN10C4Contracts($root);
if($r['errors']!==[]){fwrite(STDERR,"[N1.0-C4 Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[N1.0-C4 Contracts] PASS — {$r['metrics']['operator_domains']} operational domains, {$r['metrics']['evidence_bindings']} evidence bindings, {$r['metrics']['wrappers']} wrappers.\n");
