<?php

declare(strict_types=1);$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-c2-contracts.php';$r=nexoraAnalyzeN10C2Contracts($root);if($r['errors']!==[]){fwrite(STDERR,"[N1.0-C2 Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[N1.0-C2 Contracts] PASS — {$r['metrics']['wrappers']} wrappers; {$r['metrics']['ordered_gates']} ordered Laravel/DB gates; dependency installs {$r['metrics']['direct_dependency_installs']}; DB matrix calls {$r['metrics']['db_matrix_calls']}; operator evidence calls {$r['metrics']['operator_evidence_calls']}.\n");
