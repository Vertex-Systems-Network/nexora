<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/target-composer.php';

$jsonOnly = in_array('--json', $argv, true);
$ticketPath = $root.'/storage/app/nexora/target-remediation/restart-ticket.json';
$verifiedPath = $root.'/storage/app/nexora/target-remediation/restart-verified.json';
$fail = static function (string $message, int $exit = 1) use ($jsonOnly): never {
    $payload = ['schema'=>1,'status'=>'fail','error'=>$message];
    if ($jsonOnly) fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);
    else fwrite(STDERR, "[Nexora Restart Verification] FAIL — {$message}\n");
    exit($exit);
};
if (! is_file($ticketPath)) $fail('No restart ticket exists. Run target-prerequisite-remediate --apply-extensions only when remediation is required.', 2);
try {$ticket=json_decode((string)file_get_contents($ticketPath), true, 512, JSON_THROW_ON_ERROR);} catch (Throwable $e) {$fail('Restart ticket is invalid JSON: '.$e->getMessage());}
if (! is_array($ticket) || ($ticket['status']??null)!=='restart-required') $fail('Restart ticket is not in restart-required state.');
$version=(string)((require $root.'/config/nexora.php')['version']??'unknown');
$source=nexoraComputeSourceAttestation($root);
if (($ticket['platform_version']??null)!==$version) $fail('Restart ticket platform version does not match current source. Re-run remediation on this exact package.');
if (($ticket['source_tree_sha256']??null)!==$source['tree_sha256']) $fail('Restart ticket source digest does not match current source. Re-run remediation on this exact package.');
$normalize = static fn (?string $path): string => strtolower(str_replace('\\','/',trim((string)$path)));
$activeIni = php_ini_loaded_file() ?: null;
if ($activeIni===null || !is_file($activeIni)) $fail('No active readable php.ini is loaded after restart.');
if ($normalize($activeIni)!==$normalize((string)($ticket['php_ini']??''))) $fail('Active php.ini path changed after remediation; review the selected Laragon PHP build and rerun remediation.');
if ($normalize(PHP_BINARY)!==$normalize((string)($ticket['php_binary']??''))) $fail('Active PHP binary changed after remediation; rerun remediation for the selected PHP build.');
$expectedIni=(string)($ticket['php_ini_sha256_after']??'');
$actualIni=hash_file('sha256',$activeIni)?:'';
if ($expectedIni==='' || !hash_equals(strtolower($expectedIni),strtolower($actualIni))) $fail('Active php.ini digest does not match the verified remediation output.');
$manifest=json_decode((string)file_get_contents($root.'/composer.json'),true,512,JSON_THROW_ON_ERROR);
$required=[];foreach((array)($manifest['require']??[]) as $name=>$constraint)if(str_starts_with((string)$name,'ext-'))$required[]=substr((string)$name,4);sort($required);
$ticketRequired=array_values(array_unique(array_map('strval',(array)($ticket['required_extensions']??[]))));sort($ticketRequired);
if($ticketRequired!==$required)$fail('Restart ticket required-extension set does not match current composer.json. Re-run remediation on this exact source.');
$missing=[];foreach($required as $extension) if($extension!==''&&!extension_loaded($extension)) $missing[]=$extension;
if ($missing!==[]) $fail('Required extension(s) are still not loaded after restart: '.implode(', ',$missing).'.');
$composer=nexoraLocateTargetComposer($root);
$payload=[
    'schema'=>1,'status'=>'pass','platform_version'=>$version,'source_tree_sha256'=>$source['tree_sha256'],
    'restart_ticket_sha256'=>hash_file('sha256',$ticketPath)?:null,'verified_at'=>gmdate(DATE_ATOM),
    'php_binary'=>PHP_BINARY,'php_ini'=>$activeIni,'php_ini_sha256'=>$actualIni,
    'required_extensions'=>$required,'missing_extensions'=>[],
    'composer_available'=>(bool)($composer['available']??false),'composer_version'=>$composer['version']??null,
    'next_action'=>'Restart verification passed. Continue prerequisite intake / reviewed-lock handoff / C1 certification.'
];
file_put_contents($verifiedPath,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
if($jsonOnly) fwrite(STDOUT,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
else fwrite(STDOUT,"[Nexora Restart Verification] PASS — required PHP extensions are loaded from the verified remediated php.ini.\nEvidence: storage/app/nexora/target-remediation/restart-verified.json\n");
exit(0);
