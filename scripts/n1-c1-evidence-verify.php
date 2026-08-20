<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/n1-certified-toolchain.php';
require_once $root.'/scripts/lib/pkg1-build-identity.php';
$latest=$root.'/storage/app/nexora/n1-c1/latest.json';
$fail=static function(string $message):never{fwrite(STDERR,"[N1.0-C1 Evidence] FAIL — {$message}\n");exit(1);};
$read=static function(string $path)use($fail):array{if(!is_file($path))$fail('Required C1 artifact missing: '.$path);try{$data=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$fail('Invalid JSON '.$path.': '.$e->getMessage());}if(!is_array($data))$fail('Evidence root must be an object: '.$path);return $data;};
$report=$read($latest);$platform=require $root.'/config/nexora.php';$version=(string)($platform['version']??'unknown');$source=nexoraComputeSourceAttestation($root);
if(($report['status']??null)!=='pass')$fail('Latest C1 evidence is not PASS.');
if(($report['chunk']??null)!=='N1.0-C1')$fail('Latest C1 evidence has the wrong chunk identity.');
if(($report['platform_version']??null)!==$version)$fail('C1 evidence platform version does not match current source.');
if(($report['source_tree_sha256']??null)!==$source['tree_sha256'])$fail('C1 evidence source-tree digest does not match current source. Re-run C1 on this exact source tree.');
$hash=static fn(string $path):?string=>is_file($path)?(hash_file('sha256',$path)?:null):null;
$stepById=static function(array $report,string $id)use($fail):array{foreach((array)($report['steps']??[]) as $step){if(is_array($step)&&($step['id']??null)===$id)return $step;}$fail("Required C1 step [{$id}] is missing from PASS evidence.");};
$runId=trim((string)($report['run_id']??''));if($runId===''||preg_match('/^[A-Za-z0-9._-]+$/',$runId)!==1)$fail('C1 run_id is missing or unsafe.');
$runDirectory=$root.'/storage/app/nexora/n1-c1/'.$runId;
foreach([
    'typecheck'=>'frontend_typecheck_diagnostics_sha256',
    'vite-build'=>'frontend_vite_build_diagnostics_sha256',
] as $stepId=>$artifactKey){
    $step=$stepById($report,$stepId);
    if(!in_array(($step['status']??null),['pass','reused-pass'],true))$fail("Required frontend gate [{$stepId}] is not PASS.");
    $frontend=(array)($step['frontend_diagnostics']??[]);
    $relative=trim((string)($frontend['path']??''));
    if($relative===''||str_contains($relative,'..')||str_starts_with($relative,'/')||preg_match('/^[A-Za-z]:[\\\/]/',$relative)===1)$fail("Frontend diagnostics path for [{$stepId}] is missing or unsafe.");
    $diagnosticPath=$runDirectory.'/'.str_replace('/',DIRECTORY_SEPARATOR,$relative);
    $actualDiagnosticHash=$hash($diagnosticPath);
    if($actualDiagnosticHash===null)$fail("Frontend diagnostics artifact for [{$stepId}] is missing.");
    if(($frontend['sha256']??null)!==$actualDiagnosticHash)$fail("Frontend step diagnostic hash mismatch for [{$stepId}].");
    if(($report['artifacts'][$artifactKey]??null)!==$actualDiagnosticHash)$fail("C1 summary diagnostic binding mismatch for [{$stepId}].");
    $diagnostic=$read($diagnosticPath);
    if(($diagnostic['platform_version']??null)!==$version||($diagnostic['source_tree_sha256']??null)!==$source['tree_sha256']||($diagnostic['step_id']??null)!==$stepId)$fail("Frontend diagnostics identity mismatch for [{$stepId}].");
    if(($diagnostic['status']??null)!=='pass'||($diagnostic['compiler_clean']??false)!==true||(int)($diagnostic['diagnostic_count']??-1)!==0||(int)($diagnostic['historical_target_diagnostics']??-1)!==0)$fail("Frontend diagnostics are not compiler-clean for [{$stepId}].");
}
foreach(['composer_lock_sha256'=>$root.'/composer.lock','package_lock_sha256'=>$root.'/package-lock.json','reviewed_locks_sha256'=>$root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'] as $key=>$path){$actual=$hash($path);if($actual===null)$fail("Required C1 artifact [{$key}] is missing from the current target state.");if(($report['artifacts'][$key]??null)!==$actual)$fail("C1 immutable dependency binding mismatch for [{$key}]. Re-run C1 after lock/review changes.");}
$toolchainPath=nexoraCertifiedToolchainPath($root);$toolchainHash=$hash($toolchainPath);if($toolchainHash===null)$fail('Certified toolchain evidence is missing after C1 PASS.');if(($report['artifacts']['certified_toolchain_sha256']??null)!==$toolchainHash)$fail('C1 certified toolchain binding mismatch.');$toolchainErrors=nexoraValidateCertifiedToolchain($root);if($toolchainErrors!==[])$fail('Certified toolchain drift: '.implode('; ',$toolchainErrors));
$buildInputPath=$root.'/storage/app/nexora/certification/pkg1-build-input.json';$buildInput=$read($buildInputPath);$buildInputHash=$hash($buildInputPath);if($buildInputHash===null||($report['artifacts']['pkg1_build_input_sha256']??null)!==$buildInputHash)$fail('C1 PKG-1 build-input binding mismatch.');$currentBuildIdentity=nexoraPkg1BuildIdentity($root);if(($buildInput['status']??null)!=='pass'||($buildInput['platform_version']??null)!==$version||($buildInput['source_tree_sha256']??null)!==$source['tree_sha256']||($buildInput['identity_sha256']??null)!==$currentBuildIdentity['identity_sha256']||($buildInput['post_build_identity_sha256']??null)!==$currentBuildIdentity['identity_sha256']||($buildInput['identity_stable']??false)!==true)$fail('PKG-1 build provenance is stale or does not match the current exact source/lock/config identity.');
$build=$read($root.'/storage/app/nexora/certification/build-assets.json');if(($build['status']??null)!=='pass'||($build['platform_version']??null)!==$version||($build['source_tree_sha256']??null)!==$source['tree_sha256'])$fail('Current build-assets report is not PASS for this exact source.');foreach((array)($build['files']??[]) as $row){if(!is_array($row)||!isset($row['path'],$row['sha256']))continue;$path=$root.'/'.str_replace('/',DIRECTORY_SEPARATOR,(string)$row['path']);$actual=$hash($path);if($actual===null||!hash_equals(strtolower((string)$row['sha256']),strtolower($actual)))$fail('Current production asset does not match build-assets evidence: '.(string)$row['path']);}
$composerLock=$hash($root.'/composer.lock');$packageLock=$hash($root.'/package-lock.json');
foreach(['dependency-audit.json','dependency-provenance.json'] as $file){$data=$read($root.'/storage/app/nexora/certification/'.$file);if(($data['status']??null)!=='pass'||($data['platform_version']??null)!==$version)$fail("{$file} is not PASS for current platform.");if(($data['composer_lock_sha256']??null)!==$composerLock||($data['package_lock_sha256']??null)!==$packageLock)$fail("{$file} does not match current reviewed dependency locks.");}
$sbom=$read($root.'/storage/app/nexora/certification/dependency-sbom.json');if(($sbom['bomFormat']??null)!=='CycloneDX'||($sbom['specVersion']??null)!=='1.5')$fail('Dependency SBOM is missing or not CycloneDX 1.5.');$props=[];foreach((array)($sbom['metadata']['properties']??[]) as $property)if(is_array($property)&&isset($property['name'],$property['value']))$props[(string)$property['name']]=(string)$property['value'];if(($props['nexora:source_tree_sha256']??null)!==$source['tree_sha256']||($props['nexora:composer_lock_sha256']??null)!==$composerLock||($props['nexora:package_lock_sha256']??null)!==$packageLock)$fail('Dependency SBOM source/lock binding mismatch.');$sbomHash=$hash($root.'/storage/app/nexora/certification/dependency-sbom.json');if(($report['artifacts']['dependency_sbom_sha256']??null)!==$sbomHash)$fail('C1 dependency SBOM binding mismatch.');
if(!is_file($root.'/vendor/autoload.php'))$fail('vendor/autoload.php is missing after C1 PASS.');
if(!is_dir($root.'/node_modules'))$fail('node_modules is missing after C1 PASS.');
fwrite(STDOUT,"[N1.0-C1 Evidence] PASS — exact source, immutable reviewed locks, installed graph, semantic dependency reports and current built asset hashes are bound to C1.\n");
