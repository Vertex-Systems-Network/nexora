<?php

declare(strict_types=1);

require_once __DIR__.'/n1-certification-session.php';
require_once __DIR__.'/n1-target-run-lock.php';
require_once __DIR__.'/n1-target-progress.php';
require_once __DIR__.'/source-attestation.php';require_once __DIR__.'/target-composer.php';require_once __DIR__.'/target-evidence-intake.php';require_once __DIR__.'/n1-c6-final.php';require_once __DIR__.'/release-signature.php';require_once __DIR__.'/release-trust-anchor.php';

/** @return array{exit_code:int,stdout:string,stderr:string} */
function nexoraN10PlanProbe(string $root,array $cmd): array
{
    $line=implode(' ',array_map(static fn($v)=>escapeshellarg((string)$v),$cmd));$p=@proc_open($line,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,null,['bypass_shell'=>false]);
    if(!is_resource($p))return ['exit_code'=>127,'stdout'=>'','stderr'=>'unable to start'];fclose($pipes[0]);$out=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);$err=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);return ['exit_code'=>proc_close($p),'stdout'=>$out,'stderr'=>$err];
}

/** @return array<string,mixed> */
function nexoraBuildN10TargetPlan(string $root): array
{
    $platform=require $root.'/config/nexora.php';$source=nexoraComputeSourceAttestation($root);$required=['fileinfo','mbstring','openssl','pdo','zip'];$extensions=[];foreach($required as $ext)$extensions[$ext]=extension_loaded($ext);
    $composer=nexoraLocateTargetComposer($root);$lockErrors=nexoraValidateReviewedLockAttestation($root);$restart=is_file($root.'/storage/app/nexora/target-remediation/restart-ticket.json');
    $probe=static fn(string $script)=>nexoraN10PlanProbe($root,[PHP_BINARY,$script]);
    $c1=$probe('scripts/n1-c1-evidence-verify.php');$c2=$probe('scripts/n1-c2-evidence-verify.php');$c3=$probe('scripts/n1-c3-database-matrix-evidence-verify.php');
    $c4Data=nexoraEvidenceJson($root.'/storage/app/nexora/n1-c4/c4-evidence.json');$c5Data=nexoraEvidenceJson($root.'/storage/app/nexora/n1-c5/c5-evidence.json');$c6Data=nexoraEvidenceJson($root.'/storage/app/nexora/n1-c6/c6-evidence.json');
    $session=nexoraCertificationSessionRead($root);$sessionErrors=is_array($session)?nexoraValidateCertificationSession($root,$session):['missing'];$finalization=nexoraCertificationSessionFinalizationRead($root);$finalizationErrors=is_array($finalization)?nexoraValidateCertificationSessionFinalization($root,$finalization):['missing'];$executionActive=nexoraTargetExecutionLockActive($root);$signing=nexoraReleaseSigningConfig($root);$signingKeyReady=is_file($signing['private'])&&is_file($signing['public']);$anchor=nexoraReleaseTrustAnchorRead($root);$anchorErrors=nexoraValidateReleaseTrustAnchor($root,$anchor);$signingReady=$signingKeyReady&&$anchorErrors===[];
    $next='';$reason='';
    if($restart){$next='scripts\target-prerequisite-restart-verify.bat';$reason='A prerequisite-remediation restart ticket is pending verification.';}
    elseif(in_array(false,$extensions,true)){$next='scripts\n1-target-execution.bat --apply-extensions';$reason='Required PHP extensions are not all loaded. Apply only when Laragon reports matching DLLs, then restart.';}
    elseif(!($composer['available']??false)){$next='Install/expose Composer 2.x in Laragon, then rerun the planner.';$reason='Composer is unavailable.';}
    elseif(!is_file($root.'/composer.lock')||!is_file($root.'/package-lock.json')){$next='scripts\n1-target-execution.bat --refresh-locks --confirm-refresh=REFRESH';$reason='Reviewed dependency lockfiles do not exist yet.';}
    elseif($lockErrors!==[]){$next='Review lockfile diffs, then run scripts\n1-target-execution.bat --review-locks --reviewer="YOUR NAME" --confirm-review=REVIEWED';$reason='Lockfiles exist but the exact reviewed-lock attestation is missing/stale.';}
    elseif($c1['exit_code']!==0){$next='scripts\n1-target-execution.bat --install-deps --resume-latest';$reason='C1 dependency/build evidence is not a valid PASS for this exact source.';}
    elseif($c2['exit_code']!==0){$next='scripts\n1-target-execution.bat --resume-latest';$reason='C2 Laravel/runtime/core-DB evidence is not a valid PASS.';}
    elseif($c3['exit_code']!==0){$next='scripts\n1-target-execution.bat --resume-latest';$reason='C3 strict five-database matrix evidence is not a valid PASS.';}
    elseif(!is_array($session)||$sessionErrors!==[]){$next='scripts\n1-target-execution.bat --prepare-kits --operator="YOUR NAME" --resume-latest';$reason='C1-C3 are green but the certification session is missing, stale or fingerprint-mismatched. Start a fresh session/operator evidence cycle.';}
    elseif(($c4Data['status']??null)!=='pass'||($c5Data['status']??null)!=='pass'){$next='scripts\n1-target-execution.bat --prepare-kits --operator="YOUR NAME" --resume-latest';$reason='C1-C3 are green; real C4/C5/C6 operator evidence is the next release gate.';}
    elseif(($signing['required']??true)&&!$signingKeyReady){$next='scripts\release-signing-key.bat --generate --confirm=GENERATE';$reason='C1-C5 are green; generate the runtime-only RSA release signing key before final C6 closure.';}
    elseif(($signing['required']??true)&&$anchorErrors!==[]){$next='scripts\release-trust-anchor.bat --register --confirm=TRUST --key-id=nexora-release';$reason='The release key exists but signer identity is not anchored. Register the public-key fingerprint out-of-band before C6.';}
    elseif(($c6Data['status']??null)!=='pass'){$next='Complete C6 real 2+ node HA evidence, then run n1-target-execution with --base-url and all C4/C5/C6 evidence paths.';$reason='Only signed HA/final production closure remains.';}
    elseif($finalizationErrors!==[]){$next='php scripts\n1-certification-session.php --finalize';$reason='C1-C6 evidence exists but the signed release has not finalized the certification session.';}
    else{$next='php scripts\closure-dashboard.php';$reason='C1-C6 evidence and session finalization exist; verify the 11-domain final closure ledger.';}
    $chunks = [
        'c1' => $c1['exit_code'] === 0 ? 'pass' : 'pending',
        'c2' => $c2['exit_code'] === 0 ? 'pass' : 'pending',
        'c3' => $c3['exit_code'] === 0 ? 'pass' : 'pending',
        'c4' => (($c4Data['status'] ?? null) === 'pass') ? 'pass' : 'pending',
        'c5' => (($c5Data['status'] ?? null) === 'pass') ? 'pass' : 'pending',
        'c6' => (($c6Data['status'] ?? null) === 'pass') ? 'pass' : 'pending',
    ];
    $passedChunks = count(array_filter($chunks, static fn (string $status): bool => $status === 'pass'));
    $targetPercent = (int) floor(($passedChunks / 6) * 100);
    $granularProgress = nexoraBuildN10GranularProgress($root);

    return [
        'certification_session'=>['status'=>is_array($session)&&$sessionErrors===[]?($finalizationErrors===[]?'finalized':'valid-open'):'missing-or-invalid','session_id'=>is_array($session)?($session['session_id']??null):null,'finalization_valid'=>$finalizationErrors===[]],
        'release_signing'=>['required'=>(bool)($signing['required']??true),'key_pair_ready'=>$signingKeyReady,'trust_anchor_ready'=>$anchorErrors===[],'ready'=>$signingReady,'key_id'=>is_array($anchor)?($anchor['key_id']??null):null],
        'target_execution_lock_active'=>$executionActive,'schema'=>1,'platform_version'=>(string)($platform['version']??'unknown'),'source_tree_sha256'=>$source['tree_sha256'],'generated_at'=>gmdate(DATE_ATOM),'prerequisites'=>['extensions'=>$extensions,'composer_available'=>(bool)($composer['available']??false),'composer_version'=>$composer['version']??null,'restart_ticket'=>$restart],'locks'=>['composer_lock'=>is_file($root.'/composer.lock'),'package_lock'=>is_file($root.'/package-lock.json'),'reviewed'=>$lockErrors===[],'errors'=>$lockErrors],'chunks'=>$chunks,'target_progress'=>['passed'=>$passedChunks,'total'=>6,'percent'=>$targetPercent,'granular'=>$granularProgress],'next_action'=>['command'=>$next,'reason'=>$reason]];
}
