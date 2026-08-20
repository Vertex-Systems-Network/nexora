<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/source-attestation.php';require_once $root.'/scripts/lib/n1-certification-session.php';require_once $root.'/scripts/lib/final-evidence.php';require_once $root.'/scripts/lib/target-evidence-intake.php';
$fail=static function(string $m):never{fwrite(STDERR,"[N1.0-C4 Evidence] FAIL — {$m}\n");exit(1);};$cert=$root.'/storage/app/nexora/certification';
$files=['zero_install_sha256'=>$cert.'/zero-install-evidence.json','upgrade_rehearsal_sha256'=>$cert.'/upgrade-rehearsal-evidence.json','backup_restore_sha256'=>$cert.'/backup-restore-evidence.json'];foreach($files as $key=>$file)if(!is_file($file))$fail("Missing operator evidence [{$file}].");
$read=static function(string $file)use($fail):array{try{$data=json_decode((string)file_get_contents($file),true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$fail('Invalid JSON '.$file.': '.$e->getMessage());}if(!is_array($data))$fail('Evidence must be an object: '.$file);return $data;};
$zero=$read($files['zero_install_sha256']);$upgrade=$read($files['upgrade_rehearsal_sha256']);$backup=$read($files['backup_restore_sha256']);
foreach([nexoraValidateZeroInstallEvidence($root,$zero),nexoraValidateUpgradeRehearsalEvidence($root,$upgrade),nexoraValidateBackupRestoreEvidence($root,$backup)] as $errors)if($errors!==[])$fail(implode('; ',$errors));
$lockErrors=nexoraValidateReviewedLockAttestation($root);if($lockErrors!==[])$fail('Reviewed-lock attestation invalid: '.implode('; ',$lockErrors));
$c2Path=$root.'/storage/app/nexora/n1-c2/latest.json';if(!is_file($c2Path))$fail('C2 PASS evidence is missing.');try{$c2=json_decode((string)file_get_contents($c2Path),true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$fail('Invalid C2 evidence JSON: '.$e->getMessage());}
$hash=static fn(string $f):?string=>is_file($f)?(hash_file('sha256',$f)?:null):null;$source=nexoraComputeSourceAttestation($root);$version=(string)((require $root.'/config/nexora.php')['version']??'unknown');if(!is_array($c2)||($c2['status']??null)!=='pass'||($c2['chunk']??null)!=='N1.0-C2'||($c2['platform_version']??null)!==$version||($c2['source_tree_sha256']??null)!==$source['tree_sha256'])$fail('C2 evidence is not a PASS bound to the current platform/source.');
$bindings=[
 'c2_evidence_sha256'=>$root.'/storage/app/nexora/n1-c2/latest.json',
 'composer_lock_sha256'=>$root.'/composer.lock',
 'package_lock_sha256'=>$root.'/package-lock.json',
 'reviewed_locks_sha256'=>$root.'/storage/app/nexora/dependency-intake/reviewed-locks.json',
 'certified_toolchain_sha256'=>$root.'/storage/app/nexora/certification/toolchain.json',
]+$files;
foreach($bindings as $key=>$file)if($hash($file)===null)$fail("Binding source missing [{$key}].");
$session=nexoraCertificationSessionRead($root);if(!is_array($session)||nexoraValidateCertificationSession($root,$session)!==[])$fail('Active certification session invalid.');$out=['schema'=>1,'chunk'=>'N1.0-C4','status'=>'pass','platform_version'=>$version,'source_tree_sha256'=>$source['tree_sha256'],'certification_session_id'=>$session['session_id'],'completed_at'=>gmdate(DATE_ATOM),'artifacts'=>['certification_session_sha256'=>$hash(nexoraCertificationSessionPath($root))],'checks'=>['zero_install_recovery'=>'pass','upgrade_rehearsal'=>'pass','backup_restore_rehearsal'=>'pass']];foreach($bindings as $key=>$file)$out['artifacts'][$key]=$hash($file);
$base=$root.'/storage/app/nexora/n1-c4';if(!is_dir($base)&&!mkdir($base,0775,true)&&!is_dir($base))$fail('Unable to create C4 evidence directory.');$json=json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;file_put_contents($base.'/c4-evidence.json',$json);fwrite(STDOUT,"[N1.0-C4 Evidence] PASS — zero-install/recovery, upgrade and backup/restore evidence are exact-source and dependency bound.\n");
