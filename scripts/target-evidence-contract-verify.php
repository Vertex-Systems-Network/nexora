<?php

declare(strict_types=1);$root=dirname(__DIR__);require_once $root.'/scripts/lib/target-evidence-contracts.php';$r=nexoraAnalyzeTargetEvidenceContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Target Evidence Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Target Evidence Contracts] PASS — {$r['metrics']['known_evidence']} evidence types; {$r['metrics']['operator_evidence']} operator domains; {$r['metrics']['fingerprint_bindings']} fingerprint bindings.\n");
