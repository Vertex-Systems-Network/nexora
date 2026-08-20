<?php

declare(strict_types=1);
function nexoraAnalyzeN10TargetMaximumClosureContracts(string $root): array
{
    $errors=[];$runner=(string)@file_get_contents($root.'/scripts/n1-target-execution.php');$final=(string)@file_get_contents($root.'/scripts/final-evidence-verify.php');$builder=(string)@file_get_contents($root.'/scripts/build-production-release.php');$artifact=(string)@file_get_contents($root.'/scripts/lib/release-artifact.php');$support=(string)@file_get_contents($root.'/scripts/lib/target-support-capsule.php');$upgrade=(string)@file_get_contents($root.'/scripts/lib/final-evidence.php');
    foreach(['scripts/n1-target-next-action.php','scripts/n1-target-next-action.bat','scripts/n1-target-next-action.ps1','scripts/n1-target-next-action.sh','scripts/n1-c6-target-url-verify.php','config/nexora-certification-evidence.php'] as $file)if(!is_file($root.'/'.$file))$errors[]='missing maximum-closure artifact ['.$file.']';
    foreach(['--plan','nexoraBuildN10TargetPlan','--prepare-kits requires a real'] as $marker)if(!str_contains($runner,$marker))$errors[]='target runner missing ['.$marker.']';
    foreach(['http_performance evidence must be recent','nexoraEvidenceBaseUrlErrors'] as $marker)if(!str_contains($final,$marker))$errors[]='final evidence missing ['.$marker.']';
    foreach(['Release inputs changed while the production archive was being built','certification_evidence_policy_sha256','release_inputs'] as $marker)if(!str_contains($builder,$marker))$errors[]='release builder missing ['.$marker.']';
    foreach(['current-host report hash mismatch','certification_evidence_policy_sha256'] as $marker)if(!str_contains($artifact,$marker))$errors[]='artifact verifier missing ['.$marker.']';
    if(!str_contains($support,"'target_plan' =>"))$errors[]='support capsule must embed target_plan';
    foreach(['supported upgrade window','source_version must be older'] as $marker)if(!str_contains($upgrade,$marker))$errors[]='upgrade evidence validation missing ['.$marker.']';
    return ['errors'=>$errors,'warnings'=>[],'metrics'=>['next_action_wrappers'=>count(array_filter(['bat','ps1','sh'],fn($ext)=>is_file($root.'/scripts/n1-target-next-action.'.$ext))),'freshness_domains'=>count((array)((require $root.'/config/nexora-certification-evidence.php')['max_age_hours']??[])),'target_url_gate'=>is_file($root.'/scripts/n1-c6-target-url-verify.php')?1:0,'release_input_freeze'=>str_contains($builder,'$currentInputs !== $releaseInputs')?1:0,'automatic_lock_acceptance'=>preg_match('/--confirm-review=REVIEWED[^
]*automatic/i',$runner)===1?1:0]];
}
