<?php

declare(strict_types=1);$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-maximum-closure-contracts.php';$r=nexoraAnalyzeN10TargetMaximumClosureContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[N1.0 Maximum Closure Contracts] FAIL
 - ".implode("
 - ",$r['errors'])."
");exit(1);}fwrite(STDOUT,"[N1.0 Maximum Closure Contracts] PASS — {$r['metrics']['freshness_domains']} freshness domains, {$r['metrics']['next_action_wrappers']} planner wrappers, target URL + release-input freeze enforced.
");
