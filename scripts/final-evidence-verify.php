<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/final-evidence.php';require_once $root.'/scripts/lib/n1-certification-session.php';
require_once $root.'/scripts/lib/target-evidence-intake.php';
require_once $root.'/scripts/lib/n1-c6-final.php';
require_once $root.'/scripts/lib/n1-c5-browser-performance.php';
require_once $root.'/scripts/lib/n1-certified-toolchain.php';
$platform=require $root.'/config/nexora.php';
$version=(string)($platform['version']??'unknown');
$dir=$root.'/storage/app/nexora/certification';
$errors=[];$evidence=[];$toolchainErrors=nexoraValidateCertifiedToolchain($root);foreach($toolchainErrors as $e)$errors[]='certified toolchain: '.$e;$toolchainHash=is_file(nexoraCertifiedToolchainPath($root))?hash_file('sha256',nexoraCertifiedToolchainPath($root)):null;$session=nexoraCertificationSessionRead($root);if(!is_array($session))$errors[]='active certification session missing';else foreach(nexoraValidateCertificationSession($root,$session) as $e)$errors[]='certification session: '.$e;
$chunkErrors=nexoraValidateN10C6ChunkEvidence($root);
foreach($chunkErrors as $error)$errors[]='chunk evidence: '.$error;
$intakePath=$dir.'/target-evidence-intake.json';
$intake=nexoraEvidenceJson($intakePath);
if($intake===null)$errors[]='missing/invalid RC25 target evidence intake manifest';else $errors=array_merge($errors,nexoraValidateTargetEvidenceIntakeManifest($root,$intake));
$files=[
 'zero_install'=>(string)(getenv('NEXORA_ZERO_INSTALL_EVIDENCE') ?: $dir.'/zero-install-evidence.json'),
 'upgrade_rehearsal'=>(string)(getenv('NEXORA_UPGRADE_REHEARSAL_EVIDENCE') ?: $dir.'/upgrade-rehearsal-evidence.json'),
 'browser'=>(string)(getenv('NEXORA_BROWSER_EVIDENCE') ?: $dir.'/browser-evidence.json'),
 'web_vitals'=>$dir.'/web-vitals-evidence.json',
 'backup_restore'=>(string)(getenv('NEXORA_BACKUP_RESTORE_EVIDENCE') ?: $dir.'/backup-restore-evidence.json'),
 'ha'=>(string)(getenv('NEXORA_HA_EVIDENCE') ?: $dir.'/ha-evidence.json'),
 'http_performance'=>$dir.'/http-performance.json',
 'build_assets'=>$dir.'/build-assets.json',
 'database_matrix'=>$dir.'/database-matrix.json',
];
foreach($files as $name=>$path){$data=nexoraEvidenceJson($path);if($data===null){$errors[]="missing/invalid {$name} evidence";continue;}$evidence[$name]=$data;}
if(isset($evidence['zero_install'])) $errors=array_merge($errors,nexoraValidateZeroInstallEvidence($root,$evidence['zero_install']));
if(isset($evidence['upgrade_rehearsal'])) $errors=array_merge($errors,nexoraValidateUpgradeRehearsalEvidence($root,$evidence['upgrade_rehearsal']));
if(isset($evidence['browser'])) $errors=array_merge($errors,nexoraValidateBrowserEvidenceForFinal($root,$evidence['browser']));
if(isset($evidence['web_vitals'])) $errors=array_merge($errors,nexoraValidateC5WebVitalsEvidence($root,$evidence['web_vitals']));
if(isset($evidence['backup_restore'])) $errors=array_merge($errors,nexoraValidateBackupRestoreEvidence($root,$evidence['backup_restore']));
if(isset($evidence['ha'])) $errors=array_merge($errors,nexoraValidateHaEvidence($root,$evidence['ha']));
foreach(['http_performance','build_assets'] as $name){if(isset($evidence[$name])){if(($evidence[$name]['status']??null)!=='pass')$errors[]="{$name} status must be pass";if(($evidence[$name]['platform_version']??null)!==$version)$errors[]="{$name} platform_version mismatch";$errors=array_merge($errors,nexoraValidateEvidenceSourceBinding($root,$evidence[$name],$name));}}if(isset($evidence['http_performance'])&&!nexoraEvidenceTimestampFresh($evidence['http_performance']['checked_at']??null,nexoraEvidenceMaxAgeHours($root,'http_performance',24)))$errors[]='http_performance evidence must be recent';if(isset($evidence['http_performance'],$evidence['browser'],$evidence['web_vitals'],$evidence['ha'])){$target=nexoraNormalizeEvidenceBaseUrl($evidence['http_performance']['base_url']??null);if($target===null)$errors[]='http_performance base_url invalid';else{foreach(['browser','web_vitals','ha'] as $name)$errors=array_merge($errors,nexoraEvidenceBaseUrlErrors($root,$evidence[$name],$name.' evidence',$target,true));}}
if(isset($evidence['database_matrix'])) $errors=array_merge($errors,nexoraValidateDatabaseMatrixEvidence($root,$evidence['database_matrix']));
$sbomPath=$root.'/storage/app/nexora/certification/dependency-sbom.json';if(!is_file($sbomPath))$errors[]='dependency SBOM missing';else{try{$sbom=json_decode((string)file_get_contents($sbomPath),true,512,JSON_THROW_ON_ERROR);}catch(Throwable){$sbom=null;}if(!is_array($sbom)||($sbom['bomFormat']??null)!=='CycloneDX'||($sbom['specVersion']??null)!=='1.5')$errors[]='dependency SBOM must be CycloneDX 1.5';}
$payload=[
 'schema'=>5,'status'=>$errors===[]?'pass':'fail','platform_version'=>$version,'source_tree_sha256'=>nexoraCurrentSourceTreeSha256($root),'certification_session_id'=>is_array($session)?($session['session_id']??null):null,'certification_session_sha256'=>is_file(nexoraCertificationSessionPath($root))?hash_file('sha256',nexoraCertificationSessionPath($root)):null,'certified_toolchain_sha256'=>$toolchainHash,'dependency_sbom_sha256'=>is_file($sbomPath)?hash_file('sha256',$sbomPath):null,'checked_at'=>gmdate(DATE_ATOM),
 'evidence'=>array_map(static fn(string $path):array=>['file'=>basename($path),'sha256'=>is_file($path)?hash_file('sha256',$path):null],$files),
 'chunk_evidence'=>array_map(static fn(string $path):array=>['file'=>basename($path),'sha256'=>is_file($path)?hash_file('sha256',$path):null],nexoraN10C6ChunkEvidenceFiles($root)),
 'errors'=>$errors,
];
if(!is_dir($dir))@mkdir($dir,0775,true);
file_put_contents($dir.'/final-evidence.json',json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
if($errors!==[]){fwrite(STDERR,"[Nexora Final Evidence] FAIL\n - ".implode("\n - ",$errors)."\n");exit(1);}
fwrite(STDOUT,"[Nexora Final Evidence] PASS — zero-install + upgrade + browser/Web-Vitals + HTTP/build + five-DB matrix + backup/restore + multi-node HA evidence sealed for {$version}.\n");
