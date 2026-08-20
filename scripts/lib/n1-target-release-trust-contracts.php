<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeN10TargetReleaseTrustContracts(string $root): array
{
    $errors=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=['config/nexora-release-trust.php','scripts/lib/release-trust-anchor.php','scripts/release-trust-anchor.php','scripts/lib/n1-certified-toolchain.php','scripts/n1-certified-toolchain.php','scripts/lib/release-signature.php','scripts/release-signing-key.php','scripts/release-signing-key.bat','scripts/release-signing-key.ps1','scripts/release-signing-key.sh','scripts/lib/release-archive-hygiene.php','scripts/release-offline-verify.php','scripts/release-offline-verify.bat','scripts/release-offline-verify.ps1','scripts/release-offline-verify.sh'];
    foreach($required as $file)if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="Missing release-trust artifact [{$file}].";
    $c1=$read($root.'/scripts/n1-c1-dependency-certify.php');foreach(['toolchain-freeze','n1-certified-toolchain.php','certified_toolchain_sha256'] as $m)if(!str_contains($c1,$m))$errors[]="C1 toolchain freeze missing [{$m}]";
    $session=$read($root.'/scripts/lib/n1-certification-session.php');foreach(['certified_toolchain_sha256','session-finalization.json','nexoraFinalizeCertificationSession','release_signature_sha256','release_public_key_sha256'] as $m)if(!str_contains($session,$m))$errors[]="certification session trust/finalization missing [{$m}]";
    $seal=$read($root.'/scripts/lib/final-release-seal.php');foreach(['release-seal.sig','release-public.pem','nexoraSignReleaseSeal','nexoraVerifyDetachedReleaseSignature','nexoraValidateZipArchiveHygiene','certified_toolchain_sha256'] as $m)if(!str_contains($seal,$m))$errors[]="signed final release seal missing [{$m}]";
    $offline=$read($root.'/scripts/release-offline-verify.php');foreach(['nexoraVerifyDetachedReleaseSignature','nexoraValidateZipArchiveHygiene','evidence-index.json','nexora-release.json','certified_toolchain_sha256'] as $m)if(!str_contains($offline,$m))$errors[]="offline release verifier missing [{$m}]";
    $c6=$read($root.'/scripts/n1-c6-final-certify.php');foreach(['release-signing-readiness','release-trust-anchor','session-finalize','release-signing-key.php','--finalize'] as $m)if(!str_contains($c6,$m))$errors[]="C6 signed release/session lifecycle missing [{$m}]";
    $release=$read($root.'/config/nexora-release.php');foreach(['config/nexora-release-trust.php','storage/app/nexora/release-signing/'] as $m)if(!str_contains($release,$m))$errors[]="release package policy missing trust boundary [{$m}]";
    $zero=$read($root.'/scripts/zero-state-verify.php');if(!str_contains($zero,'storage/app/nexora/release-signing'))$errors[]='strict source-zero must reject runtime release signing keys';
    $trust=$read($root.'/config/nexora-release-trust.php');foreach(['signature_required','rsa_bits','max_entries','reject_case_collisions','reject_symlinks'] as $m)if(!str_contains($trust,$m))$errors[]="release trust policy missing [{$m}]";
    return ['errors'=>$errors,'warnings'=>[],'metrics'=>['toolchain_freeze'=>str_contains($c1,'toolchain-freeze')?1:0,'signature_wrappers'=>count(array_filter(['scripts/release-signing-key.bat','scripts/release-signing-key.ps1','scripts/release-signing-key.sh'],fn($f)=>is_file($root.'/'.$f))),'offline_verify_wrappers'=>count(array_filter(['scripts/release-offline-verify.bat','scripts/release-offline-verify.ps1','scripts/release-offline-verify.sh'],fn($f)=>is_file($root.'/'.$f))),'session_finalization'=>str_contains($session,'nexoraFinalizeCertificationSession')?1:0,'archive_hygiene'=>str_contains($seal,'nexoraValidateZipArchiveHygiene')?1:0,'detached_signature'=>str_contains($seal,'nexoraSignReleaseSeal')?1:0]];
}
