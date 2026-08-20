<?php

declare(strict_types=1);
function nexoraAnalyzeN10C3Contracts(string $root):array{
 $errors=[];$runner=(string)@file_get_contents($root.'/scripts/n1-c3-database-matrix-certify.php');$matrix=(string)@file_get_contents($root.'/scripts/certify-database-matrix.php');
 $required=['scripts/n1-c3-database-matrix-certify.php','scripts/n1-c3-database-matrix-certify.bat','scripts/n1-c3-database-matrix-certify.ps1','scripts/n1-c3-database-matrix-certify.sh','scripts/n1-c3-matrix-prerequisite.php','scripts/n1-c3-database-matrix-evidence-verify.php','scripts/database-data-plane-certify.php'];foreach($required as $f)if(!is_file($root.'/'.$f))$errors[]="Missing {$f}.";
 $gates=['c2-evidence','installed-dependencies','matrix-prerequisites','strict-five-db-matrix','matrix-evidence'];foreach($gates as $g)if(!str_contains($runner,"'{$g}'"))$errors[]="C3 runner missing ordered gate [{$g}].";
 foreach(['mysql,mariadb,pgsql,sqlite,sqlsrv','--strict','n1-c2-evidence-verify.php','certify-database-matrix.php'] as $m)if(!str_contains($runner,$m))$errors[]="C3 strict-matrix boundary missing [{$m}].";
 foreach(['CommerceAdminFlowTest.php','CrmAdminFlowTest.php','AutomationFlowTest.php','EnterpriseFlowTest.php','StudioFlowTest.php','ConcurrencyCertificationTest.php','--testsuite=Compatibility','migrate:fresh','migrate:reset','data-plane-baseline','data-plane-rebuild-compare','database-data-plane-certify.php'] as $m)if(!str_contains($matrix,$m))$errors[]="C3 matrix missing high-risk/round-trip marker [{$m}].";
 foreach(['composer install','composer update','npm ci','npm install','browser-evidence-verify.php','backup-restore-evidence-verify.php','ha-evidence-verify.php','zero-install-evidence-verify.php','upgrade-rehearsal-evidence-verify.php'] as $f)if(stripos($runner,$f)!==false)$errors[]="C3 must not own C1/C4-C6 gate [{$f}].";
 $release=(string)@file_get_contents($root.'/config/nexora-release.php');if(!str_contains($release,'storage/app/nexora/n1-c3/'))$errors[]='Release policy must exclude C3 runtime evidence.';
 $zero=(string)@file_get_contents($root.'/scripts/zero-state-verify.php');if(!str_contains($zero,'storage/app/nexora/n1-c3'))$errors[]='Strict zero-state must reject C3 runtime evidence.';
 return ['errors'=>$errors,'warnings'=>[],'metrics'=>['wrappers'=>count(array_filter(['scripts/n1-c3-database-matrix-certify.bat','scripts/n1-c3-database-matrix-certify.ps1','scripts/n1-c3-database-matrix-certify.sh'],fn($f)=>is_file($root.'/'.$f))),'ordered_gates'=>count($gates),'required_database_families'=>5,'high_risk_flows'=>6,'dependency_installs'=>preg_match('/composer install|npm ci/i',$runner)===1?1:0,'operator_evidence_calls'=>preg_match('/browser-evidence|backup-restore-evidence|ha-evidence/i',$runner)===1?1:0]];
}
