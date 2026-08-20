<?php

declare(strict_types=1);$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-c5-contracts.php';$r=nexoraAnalyzeN10C5Contracts($root);fwrite(STDOUT,"[N1.0-C5 Contracts]\n");if($r['errors']!==[]){foreach($r['errors'] as $e)fwrite(STDERR," - {$e}\n");exit(1);}fwrite(STDOUT,"PASS — {$r['metrics']['browsers']} browsers / {$r['metrics']['matrix_rows']} matrix rows / {$r['metrics']['accessibility_checks']} accessibility checks / {$r['metrics']['web_vital_metrics']} Web Vital metrics; {$r['metrics']['evidence_bindings']} evidence bindings.\n");
