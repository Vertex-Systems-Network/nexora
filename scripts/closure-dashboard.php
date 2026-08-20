<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/target-evidence-intake.php';
require_once $root.'/scripts/lib/n1-target-plan.php';
$fingerprint=nexoraTargetEvidenceFingerprint($root);$closure=nexoraEvaluateFinalClosure($root);$lockErrors=nexoraValidateReviewedLockAttestation($root);
$target=nexoraEvidenceJson($root.'/storage/app/nexora/certification/target-runtime-evidence.json');
$c1=nexoraEvidenceJson($root.'/storage/app/nexora/n1-c1/latest.json');
$c2=nexoraEvidenceJson($root.'/storage/app/nexora/n1-c2/latest.json');
$c3=nexoraEvidenceJson($root.'/storage/app/nexora/certification/database-matrix.json');
$c4=nexoraEvidenceJson($root.'/storage/app/nexora/n1-c4/c4-evidence.json');
$c5=nexoraEvidenceJson($root.'/storage/app/nexora/n1-c5/c5-evidence.json');
$c6=nexoraEvidenceJson($root.'/storage/app/nexora/n1-c6/c6-evidence.json');
$plan=nexoraBuildN10TargetPlan($root);
$dashboard=[
 'schema'=>1,'platform_version'=>$fingerprint['platform_version'],'source_tree_sha256'=>$fingerprint['source_tree_sha256'],'checked_at'=>gmdate(DATE_ATOM),
 'n1_c1'=>['status'=>(($c1['status']??null)==='pass')?'pass':'pending','first_blocker'=>$c1['first_blocker']??null],
 'n1_c2'=>['status'=>(($c2['status']??null)==='pass')?'pass':'pending','first_blocker'=>$c2['first_blocker']??null],
 'n1_c3'=>['status'=>(($c3['status']??null)==='pass'&&($c3['chunk']??null)==='N1.0-C3')?'pass':'pending'],
 'n1_c4'=>['status'=>(($c4['status']??null)==='pass')?'pass':'pending'],
 'n1_c5'=>['status'=>(($c5['status']??null)==='pass')?'pass':'pending'],
 'n1_c6'=>['status'=>(($c6['status']??null)==='pass'&&($c6['n1_0_done']??false)===true)?'pass':'pending'],
 'dependency_locks'=>['status'=>$lockErrors===[]?'pass':'blocked','composer_lock_sha256'=>$fingerprint['composer_lock_sha256'],'package_lock_sha256'=>$fingerprint['package_lock_sha256'],'errors'=>$lockErrors],
 'target_runtime'=>['status'=>(($target['status']??null)==='pass'&&in_array(($target['runtime_status']??null),['target-readiness-pass','target-certification-pass'],true))?'pass':'pending','runtime_status'=>$target['runtime_status']??null],
 'closure'=>$closure,'next_action'=>$plan['next_action']??null,
];
$dir=$root.'/storage/app/nexora/certification';if(!is_dir($dir))@mkdir($dir,0775,true);file_put_contents($dir.'/closure-dashboard.json',json_encode($dashboard,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
$md="# Nexora N1.0 Target Closure Dashboard\n\nPlatform: `{$dashboard['platform_version']}`  \nSource: `{$dashboard['source_tree_sha256']}`  \nC1: **".strtoupper($dashboard['n1_c1']['status'])."**  \nC2: **".strtoupper($dashboard['n1_c2']['status'])."**  \nC3: **".strtoupper($dashboard['n1_c3']['status'])."**  \nC4: **".strtoupper($dashboard['n1_c4']['status'])."**  \nC5: **".strtoupper($dashboard['n1_c5']['status'])."**  \nC6: **".strtoupper($dashboard['n1_c6']['status'])."**  \nLocks: **".strtoupper($dashboard['dependency_locks']['status'])."**  \nTarget runtime: **".strtoupper($dashboard['target_runtime']['status'])."**  \nClosure: **{$closure['status']}**\n\n| Domain | Status | Detail |\n|---|---:|---|\n";
foreach($closure['domains'] as $name=>$domain)$md.='| '.str_replace('_',' ',$name).' | '.strtoupper((string)$domain['status']).' | '.str_replace('|','\\|',(string)$domain['detail'])." |\n";
$nextCommand=str_replace('`','',(string)($plan['next_action']['command']??''));$nextReason=(string)($plan['next_action']['reason']??'');$md.="\nNext action: **`{$nextCommand}`**  \nReason: {$nextReason}\n\nN1.0 DONE: **".(($closure['n1_0_done']??false)?'YES':'NO')."**\n";file_put_contents($dir.'/closure-dashboard.md',$md);
fwrite(STDOUT,$md);exit(($closure['n1_0_done']??false)?0:2);
