<?php

declare(strict_types=1);
$root=dirname(__DIR__);
$required=['mysql'=>'pdo_mysql','mariadb'=>'pdo_mysql','pgsql'=>'pdo_pgsql','sqlite'=>'pdo_sqlite','sqlsrv'=>'pdo_sqlsrv'];
$errors=[];$rows=[];
foreach($required as $driver=>$extension){
    $loaded=extension_loaded($extension);
    $rows[$driver]=['pdo_extension'=>$extension,'loaded'=>$loaded];
    if(!$loaded)$errors[]="{$driver} requires PHP extension {$extension}";
}
if(!is_file($root.'/vendor/autoload.php'))$errors[]='vendor/autoload.php is missing';
$platform=require $root.'/config/nexora.php';
$out=['schema'=>1,'chunk'=>'N1.0-C3','platform_version'=>(string)($platform['version']??'unknown'),'required_drivers'=>array_keys($required),'drivers'=>$rows,'status'=>$errors===[]?'pass':'blocked','errors'=>$errors,'checked_at'=>gmdate(DATE_ATOM)];
fwrite(STDOUT,json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
if($errors){foreach($errors as $e)fwrite(STDERR,"[N1.0-C3 Prerequisite] {$e}\n");exit(1);} 
fwrite(STDOUT,"[N1.0-C3 Prerequisite] PASS — all five driver families have the required PDO extensions.\n");
