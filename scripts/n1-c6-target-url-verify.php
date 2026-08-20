<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/final-evidence.php';
$expected='';foreach($argv as $arg)if(str_starts_with($arg,'--base-url='))$expected=trim(substr($arg,11));
$expected=nexoraNormalizeEvidenceBaseUrl($expected!==''?$expected:(getenv('NEXORA_CERT_BASE_URL')?:''));
if($expected===null){fwrite(STDERR,"[N1.0-C6 Target URL] FAIL — valid --base-url is required.
");exit(2);} 
$policy=require $root.'/config/nexora-certification-evidence.php';if(($policy['final_target_https_required']??true)&&!str_starts_with($expected,'https://')){fwrite(STDERR,"[N1.0-C6 Target URL] FAIL — final target must use HTTPS.
");exit(1);} 
$files=['http-performance'=>$root.'/storage/app/nexora/certification/http-performance.json','browser'=>$root.'/storage/app/nexora/certification/browser-evidence.json','web-vitals'=>$root.'/storage/app/nexora/certification/web-vitals-evidence.json','ha'=>$root.'/storage/app/nexora/certification/ha-evidence.json'];$errors=[];
foreach($files as $label=>$path){$data=nexoraEvidenceJson($path);if($data===null){$errors[]="{$label} evidence missing/invalid";continue;}$actual=nexoraNormalizeEvidenceBaseUrl($data['base_url']??null);if($actual===null)$errors[]="{$label} base_url invalid";elseif(!hash_equals($expected,$actual))$errors[]="{$label} target mismatch [{$actual}] != [{$expected}]";}
if($errors!==[]){fwrite(STDERR,"[N1.0-C6 Target URL] FAIL
 - ".implode("
 - ",$errors)."
");exit(1);}fwrite(STDOUT,"[N1.0-C6 Target URL] PASS — C5 HTTP/browser/Web-Vitals and C6 HA evidence share one target: {$expected}
");
