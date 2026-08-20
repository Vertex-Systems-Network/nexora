<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/source-attestation.php';require_once $root.'/scripts/lib/n1-certified-toolchain.php';
$path=$root.'/storage/app/nexora/n1-c2/latest.json';$fail=static function(string $m):never{fwrite(STDERR,"[N1.0-C2 Evidence] FAIL — {$m}\n");exit(1);};
if(!is_file($path))$fail('C2 evidence is missing.');try{$r=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$fail('Invalid JSON: '.$e->getMessage());}
if(!is_array($r)||($r['status']??null)!=='pass'||($r['chunk']??null)!=='N1.0-C2')$fail('Latest C2 evidence is not a PASS report.');
$v=(string)((require $root.'/config/nexora.php')['version']??'unknown');$s=nexoraComputeSourceAttestation($root);if(($r['platform_version']??null)!==$v)$fail('Platform version mismatch.');if(($r['source_tree_sha256']??null)!==$s['tree_sha256'])$fail('Source-tree digest mismatch.');
$hash=static fn(string $f):?string=>is_file($f)?(hash_file('sha256',$f)?:null):null;foreach(['c1_evidence_sha256'=>$root.'/storage/app/nexora/n1-c1/latest.json','composer_lock_sha256'=>$root.'/composer.lock','package_lock_sha256'=>$root.'/package-lock.json','reviewed_locks_sha256'=>$root.'/storage/app/nexora/dependency-intake/reviewed-locks.json','certified_toolchain_sha256'=>nexoraCertifiedToolchainPath($root)] as $key=>$file){$actual=$hash($file);if($actual===null||($r['artifacts'][$key]??null)!==$actual)$fail("Artifact binding mismatch [{$key}].");}
$toolchainErrors=nexoraValidateCertifiedToolchain($root);if($toolchainErrors!==[])$fail('Certified toolchain drift: '.implode('; ',$toolchainErrors));
fwrite(STDOUT,"[N1.0-C2 Evidence] PASS — Laravel runtime/core DB evidence is bound to exact source, C1 and reviewed locks.\n");
