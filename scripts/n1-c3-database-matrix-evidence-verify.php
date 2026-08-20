<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/source-attestation.php';require_once $root.'/scripts/lib/final-evidence.php';require_once $root.'/scripts/lib/n1-certified-toolchain.php';
$fail=static function(string $m):never{fwrite(STDERR,"[N1.0-C3 Evidence] FAIL — {$m}\n");exit(1);};
$matrixPath=$root.'/storage/app/nexora/certification/database-matrix.json';if(!is_file($matrixPath))$fail('database-matrix.json is missing.');
try{$m=json_decode((string)file_get_contents($matrixPath),true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$fail('Invalid matrix JSON: '.$e->getMessage());}
if(!is_array($m))$fail('Matrix evidence must be an object.');$errors=nexoraValidateDatabaseMatrixEvidence($root,$m);if($errors)$fail(implode('; ',$errors));
if(($m['chunk']??null)!=='N1.0-C3')$fail('Matrix chunk binding is missing.');
$hash=static fn(string $f):?string=>is_file($f)?(hash_file('sha256',$f)?:null):null;
$bindings=['c2_evidence_sha256'=>$root.'/storage/app/nexora/n1-c2/latest.json','composer_lock_sha256'=>$root.'/composer.lock','package_lock_sha256'=>$root.'/package-lock.json','reviewed_locks_sha256'=>$root.'/storage/app/nexora/dependency-intake/reviewed-locks.json','certified_toolchain_sha256'=>nexoraCertifiedToolchainPath($root)];
foreach($bindings as $key=>$file){$actual=$hash($file);if($actual===null||($m['artifacts'][$key]??null)!==$actual)$fail("Artifact binding mismatch [{$key}].");}
$toolchainErrors=nexoraValidateCertifiedToolchain($root);if($toolchainErrors!==[])$fail('Certified toolchain drift: '.implode('; ',$toolchainErrors));
fwrite(STDOUT,"[N1.0-C3 Evidence] PASS — all five database families pass and evidence is bound to exact source, C2 and reviewed locks.\n");
