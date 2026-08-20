<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require $root.'/bootstrap/nexora-runtime-bootstrap.php';

if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR,"[Nexora DB Matrix] vendor/autoload.php is missing. Run composer install first.\n");
    exit(1);
}

$driversArg=null;
foreach($argv as $arg) if(str_starts_with($arg,'--drivers=')) $driversArg=substr($arg,10);
$drivers=array_values(array_filter(array_map('trim',explode(',', $driversArg ?: (string)(getenv('NEXORA_CERT_DB_MATRIX') ?: 'mysql,sqlite')))));
$strict=in_array('--strict',$argv,true);
$results=[];$failed=false;

$run=static function(array $command,array $env)use($root):array{
    $cmd=implode(' ',array_map('escapeshellarg',$command));
    $descriptor=[1=>['pipe','w'],2=>['pipe','w']];$process=proc_open($cmd,$descriptor,$pipes,$root,$env);
    if(!is_resource($process))return[127,'','Unable to start process.'];
    $out=stream_get_contents($pipes[1])?:'';$err=stream_get_contents($pipes[2])?:'';fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process);return[$code,$out,$err];
};

foreach($drivers as $driver){
    $driver=strtolower($driver);
    $pdoExt=match($driver){'mysql','mariadb'=>'pdo_mysql','pgsql'=>'pdo_pgsql','sqlite'=>'pdo_sqlite','sqlsrv'=>'pdo_sqlsrv',default=>''};
    if($pdoExt===''||!extension_loaded($pdoExt)){
        $status=$strict?'fail':'skip';$results[]=['driver'=>$driver,'status'=>$status,'message'=>$pdoExt===''?'unsupported driver':"{$pdoExt} is not loaded"];
        if($strict)$failed=true;continue;
    }
    $database=match($driver){
        'sqlite'=>$root.'/storage/app/nexora/certification/matrix-sqlite.sqlite',
        'pgsql'=>'nexora_certification_pgsql','sqlsrv'=>'nexora_certification_sqlsrv','mariadb'=>'nexora_certification_mariadb',default=>'nexora_certification_mysql'};
    $env=NexoraBootstrapProcessEnvironment::build($root,[
        'APP_ENV'=>'testing','APP_DEBUG'=>'false','NEXORA_INSTALLER_BYPASS'=>'true','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array','QUEUE_CONNECTION'=>'sync',
        'DB_CONNECTION'=>$driver,'DB_DATABASE'=>$database,
        'DB_HOST'=>(string)(getenv('NEXORA_CERT_'.strtoupper($driver).'_HOST')?:'127.0.0.1'),
        'DB_PORT'=>(string)(getenv('NEXORA_CERT_'.strtoupper($driver).'_PORT')?:match($driver){'pgsql'=>'5432','sqlsrv'=>'1433',default=>'3306'}),
        'DB_USERNAME'=>(string)(getenv('NEXORA_CERT_'.strtoupper($driver).'_USERNAME')?:match($driver){'pgsql'=>'postgres','sqlsrv'=>'sa',default=>'root'}),
        'DB_PASSWORD'=>(string)(getenv('NEXORA_CERT_'.strtoupper($driver).'_PASSWORD')?:match($driver){'mysql','mariadb'=>'root',default=>''}),
        'NEXORA_CERT_EXPECT_DB_CONNECTION'=>$driver,
        'NEXORA_CERT_EXPECT_DB_DATABASE'=>$database,
    ]);
    $driverResult=['driver'=>$driver,'status'=>'pass','steps'=>[]];
    $highRisk=[
        'tests/Feature/Commerce/CommerceAdminFlowTest.php',
        'tests/Feature/Crm/CrmAdminFlowTest.php',
        'tests/Feature/AutomationFlowTest.php',
        'tests/Feature/Enterprise/EnterpriseFlowTest.php',
        'tests/Feature/Studio/StudioFlowTest.php',
        'tests/Feature/Certification/ConcurrencyCertificationTest.php',
    ];
    $steps=[
        ['prepare',[PHP_BINARY,'scripts/create-certification-database.php']],
        ['database-version-doctor',[PHP_BINARY,'artisan','nexora:database:doctor']],
        ['migrate-seed',[PHP_BINARY,'artisan','migrate:fresh','--seed','--force']],
        ['seed-idempotency',[PHP_BINARY,'artisan','db:seed','--force']],
        ['data-plane-baseline',[PHP_BINARY,'scripts/database-data-plane-certify.php','--write=c3-'.preg_replace('/[^A-Za-z0-9._-]/','-',$driver).'-baseline.json']],
        ['compatibility-tests',[PHP_BINARY,'artisan','test','--testsuite=Compatibility']],
    ];
    foreach($highRisk as $file) $steps[]=['high-risk-'.basename($file,'.php'),[PHP_BINARY,'artisan','test',$file]];
    $steps=array_merge($steps,[
        ['migration-reset',[PHP_BINARY,'artisan','migrate:reset','--force']],
        ['migration-rebuild',[PHP_BINARY,'artisan','migrate','--force']],
        ['seed-rebuild',[PHP_BINARY,'artisan','db:seed','--force']],
        ['data-plane-rebuild-compare',[PHP_BINARY,'scripts/database-data-plane-certify.php','--compare=c3-'.preg_replace('/[^A-Za-z0-9._-]/','-',$driver).'-baseline.json']],
        ['compatibility-tests-rebuilt',[PHP_BINARY,'artisan','test','--testsuite=Compatibility']],
    ]);
    foreach($steps as [$label,$cmd]){
        [$code,$out,$err]=$run($cmd,$env);$driverResult['steps'][]=['label'=>$label,'exit_code'=>$code,'stdout_tail'=>substr($out,-2000),'stderr_tail'=>substr($err,-2000)];
        if($code!==0){$driverResult['status']='fail';$driverResult['message']=$label.' failed';$failed=true;break;}
    }
    $results[]=$driverResult;
    fwrite(STDOUT,"[Nexora DB Matrix] {$driver}: ".strtoupper($driverResult['status'])."\n");
}

$platform=require $root.'/config/nexora.php';
require_once $root.'/scripts/lib/source-attestation.php';
$source=nexoraComputeSourceAttestation($root);
$hash=static fn(string $file):?string=>is_file($file)?(hash_file('sha256',$file)?:null):null;
$report=['schema'=>2,'chunk'=>'N1.0-C3','platform_version'=>(string)($platform['version']??'unknown'),'source_tree_sha256'=>$source['tree_sha256'],'checked_at'=>gmdate(DATE_ATOM),'strict'=>$strict,'requested_drivers'=>$drivers,'results'=>$results,'status'=>$failed?'fail':'pass','artifacts'=>['c2_evidence_sha256'=>$hash($root.'/storage/app/nexora/n1-c2/latest.json'),'composer_lock_sha256'=>$hash($root.'/composer.lock'),'package_lock_sha256'=>$hash($root.'/package-lock.json'),'reviewed_locks_sha256'=>$hash($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'),'certified_toolchain_sha256'=>$hash($root.'/storage/app/nexora/certification/toolchain.json')]];
$dir=$root.'/storage/app/nexora/certification';if(!is_dir($dir))@mkdir($dir,0775,true);file_put_contents($dir.'/database-matrix.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
exit($failed?1:0);
