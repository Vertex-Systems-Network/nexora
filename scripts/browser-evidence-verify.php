<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/final-evidence.php';
$path=(string)(getenv('NEXORA_BROWSER_EVIDENCE') ?: $root.'/storage/app/nexora/certification/browser-evidence.json');
$data=nexoraEvidenceJson($path);
if($data===null){
    fwrite(STDERR,"[Nexora Browser Evidence] Missing or invalid evidence: {$path}\nCopy docs/browser-certification-evidence.example.json and replace example values with observed results.\n");
    exit(1);
}
$errors=nexoraValidateBrowserEvidenceForFinal($root,$data);
if($errors!==[]){
    fwrite(STDERR,"[Nexora Browser Evidence] FAIL\n - ".implode("\n - ",$errors)."\n");
    exit(1);
}
fwrite(STDOUT,"[Nexora Browser Evidence] PASS — 36 Chrome/Edge/Firefox responsive/RTL/theme matrix rows + 10 operator checks + assistive-technology evidence, bound to the exact source tree.\n");
