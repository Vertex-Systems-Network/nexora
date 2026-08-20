<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-c5-browser-performance.php';
$input='';foreach($argv as $arg)if(str_starts_with($arg,'--input='))$input=trim(substr($arg,8));$path=$input!==''?$input:$root.'/storage/app/nexora/certification/web-vitals-evidence.json';
if(!is_file($path)){fwrite(STDERR,"[N1.0-C5 Web Vitals] FAIL — evidence missing: {$path}\n");exit(1);}try{$data=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){fwrite(STDERR,"[N1.0-C5 Web Vitals] FAIL — invalid JSON: {$e->getMessage()}\n");exit(1);}if(!is_array($data)){fwrite(STDERR,"[N1.0-C5 Web Vitals] FAIL — evidence must be an object.\n");exit(1);}$errors=nexoraValidateC5WebVitalsEvidence($root,$data);if($errors!==[]){fwrite(STDERR,"[N1.0-C5 Web Vitals] FAIL\n - ".implode("\n - ",$errors)."\n");exit(1);}fwrite(STDOUT,"[N1.0-C5 Web Vitals] PASS — required routes meet C5 LCP/INP/CLS/TTFB ceilings with repeated observed runs.\n");
