<?php

declare(strict_types=1);
/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeN10TargetUpdateTrustContracts(string $root): array
{
    $errors=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'config/nexora-update-trust.php','scripts/lib/trusted-update.php','scripts/trusted-update-trust-anchor.php','scripts/trusted-update-admit.php','scripts/trusted-update-stage.php','scripts/trusted-update-candidate.php','scripts/trusted-update-admit-candidate.php','scripts/trusted-update-cleanup.php',
        'scripts/trusted-update-trust-anchor.bat','scripts/trusted-update-trust-anchor.ps1','scripts/trusted-update-trust-anchor.sh',
        'scripts/trusted-update-admit.bat','scripts/trusted-update-admit.ps1','scripts/trusted-update-admit.sh',
        'scripts/trusted-update-stage.bat','scripts/trusted-update-stage.ps1','scripts/trusted-update-stage.sh','scripts/trusted-update-cleanup.bat','scripts/trusted-update-cleanup.ps1','scripts/trusted-update-cleanup.sh','scripts/trusted-update-cleanup.bat','scripts/trusted-update-cleanup.ps1','scripts/trusted-update-cleanup.sh',
        'scripts/trusted-update-candidate.bat','scripts/trusted-update-candidate.ps1','scripts/trusted-update-candidate.sh','scripts/trusted-update-admit-candidate.bat','scripts/trusted-update-admit-candidate.ps1','scripts/trusted-update-admit-candidate.sh',
        'app/Nexora/Foundation/Upgrade/TrustedUpdateAdmission.php',
    ];
    foreach($required as $f)if(!is_file($root.'/'.$f)||filesize($root.'/'.$f)===0)$errors[]="missing v2.7 trusted-update artifact [{$f}]";
    $lib=$read($root.'/scripts/lib/trusted-update.php');foreach(['strict offline release verification failed','rollback blocked','same-version reinstall blocked','recipient update trust anchor already exists','trust-history','admission.json','rotation_sequence','previous_anchor_sha256','nexoraVerifyRecipientTrustLineage','stage-records'] as $m)if(!str_contains(strtolower($lib),strtolower($m)))$errors[]="trusted-update admission/rotation boundary missing [{$m}]";
    $stage=$read($root.'/scripts/trusted-update-stage.php');foreach(['quarantined','nexoraPublishUpdateStageRecord','Partial staging data is quarantined'] as $m)if(!str_contains($stage,$m))$errors[]="trusted-update staging quarantine missing [{$m}]";
    $cleanup=$read($root.'/scripts/trusted-update-cleanup.php');foreach(['managed_staging_root','--confirm=CLEAN','older_than_ttl','matching stage record missing'] as $m)if(!str_contains($cleanup,$m))$errors[]="trusted-update explicit cleanup boundary missing [{$m}]";
    $manager=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['TrustedUpdateAdmission','trusted_update','receipt_sha256','release_seal_sha256','release_signer_key_id','update_trust_anchor_sha256','$this->trustedUpdate->clear()'] as $m)if(!str_contains($manager,$m))$errors[]="upgrade manager trusted-release binding missing [{$m}]";
    $admission=$read($root.'/app/Nexora/Foundation/Upgrade/TrustedUpdateAdmission.php');foreach(['certification-candidate','allow_certification_candidate','nexora_test','nexora_cert','app.env'] as $m)if(!str_contains($admission,$m))$errors[]="certification candidate safety boundary missing [{$m}]";
    $builder=$read($root.'/scripts/build-production-release.php');foreach(["'schema' => 4",'update_trust_policy_sha256','signed_release_admission_required','trusted-update-admit.php','trusted-update-stage.php'] as $m)if(!str_contains($builder,$m))$errors[]="release builder trusted-update metadata missing [{$m}]";
    $seal=$read($root.'/scripts/lib/final-release-seal.php');foreach(["'schema'=>4",'update_trust_policy_sha256'] as $m)if(!str_contains($seal,$m))$errors[]="release seal v4 update-trust binding missing [{$m}]";
    $offline=$read($root.'/scripts/release-offline-verify.php');if(!str_contains($offline,"['schema']??null)!==4"))$errors[]='offline verifier must require signed seal schema 4';
    $release=$read($root.'/config/nexora-release.php');foreach(['config/nexora-update-trust.php','storage/app/nexora/update-trust/'] as $m)if(!str_contains($release,$m))$errors[]="release policy update-trust boundary missing [{$m}]";
    return ['errors'=>array_values(array_unique($errors)),'warnings'=>[],'metrics'=>[
        'trusted_update_workflows'=>7,
        'cross_platform_wrappers'=>count(array_filter(['scripts/trusted-update-trust-anchor.bat','scripts/trusted-update-trust-anchor.ps1','scripts/trusted-update-trust-anchor.sh','scripts/trusted-update-admit.bat','scripts/trusted-update-admit.ps1','scripts/trusted-update-admit.sh','scripts/trusted-update-stage.bat','scripts/trusted-update-stage.ps1','scripts/trusted-update-stage.sh','scripts/trusted-update-cleanup.bat','scripts/trusted-update-cleanup.ps1','scripts/trusted-update-cleanup.sh','scripts/trusted-update-cleanup.bat','scripts/trusted-update-cleanup.ps1','scripts/trusted-update-cleanup.sh','scripts/trusted-update-candidate.bat','scripts/trusted-update-candidate.ps1','scripts/trusted-update-candidate.sh','scripts/trusted-update-admit-candidate.bat','scripts/trusted-update-admit-candidate.ps1','scripts/trusted-update-admit-candidate.sh'],fn($f)=>is_file($root.'/'.$f))),
        'release_seal_schema'=>str_contains($seal,"'schema'=>4")?4:0,
        'silent_anchor_overwrite'=>str_contains($lib,'imports never overwrite silently')?0:1,
        'downgrade_allowed'=>str_contains($lib,'rollback blocked')?0:1,
    ]];
}
