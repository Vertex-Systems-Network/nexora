<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeDatabaseDataPlaneContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'config/nexora-database-runtime.php',
        'app/Nexora/Installation/Database/DatabaseDataPlaneIdentity.php',
        'app/Console/Commands/Nexora/DatabaseDataPlaneStatusCommand.php',
        'scripts/database-data-plane-certify.php',
        'tests/Architecture/N100V37DatabaseDataPlaneArchitectureTest.php',
    ];
    foreach($required as $file)if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="v3.7 database data-plane artifact missing [{$file}]";
    $config=$read($root.'/config/nexora-database-runtime.php');foreach(['require_exact_data_plane','require_exact_server_version','require_exact_session_profile','require_schema_attestation','require_backup_schema_binding','queue_payload_schema','NEXORA_QUEUE_PAYLOAD_SCHEMA'] as $m)if(!str_contains($config,$m))$errors[]="database runtime config missing [{$m}]";
    $identity=$read($root.'/app/Nexora/Installation/Database/DatabaseDataPlaneIdentity.php');foreach(['database_name_sha256','server_version','session_profile','schemaSnapshot','schema_fingerprint','getColumns','getIndexes','getForeignKeys','mysqlProfile','pgsqlProfile','sqliteProfile','sqlsrvProfile'] as $m)if(!str_contains($identity,$m))$errors[]="database data-plane identity missing [{$m}]";
    $guard=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['current_database_data_plane_fingerprint','installed_database_data_plane_fingerprint','database_data_plane_compatible','queue_payload_schema','runtime_database_fingerprint','different Nexora database data-plane fingerprint'] as $m)if(!str_contains($guard,$m))$errors[]="runtime database fence missing [{$m}]";
    $provider=$read($root.'/app/Providers/AppServiceProvider.php');foreach(['DatabaseDataPlaneIdentity::class',"'payload_schema'=>max(",'runtime_database_fingerprint'] as $m)if(!str_contains($provider,$m))$errors[]="queue database data-plane fence missing [{$m}]";
    $node=$read($root.'/app/Nexora/Cloud/Services/NodeManager.php');foreach(['runtime_database_fingerprint','database_server_version','database_driver'] as $m)if(!str_contains($node,$m))$errors[]="node database advertisement missing [{$m}]";
    $ha=$read($root.'/app/Nexora/Cloud/Services/HaReadinessService.php');foreach(['local_database_data_plane','runtime_database_data_plane_consistency'] as $m)if(!str_contains($ha,$m))$errors[]="HA database data-plane convergence missing [{$m}]";
    $upgrade=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['source_database_data_plane','Database data-plane/schema fingerprint changed','database_schema_attested','last_upgrade_database_schema_before_sha256','last_upgrade_database_schema_after_sha256'] as $m)if(!str_contains($upgrade,$m))$errors[]="upgrade database/schema binding missing [{$m}]";
    $installer=$read($root.'/app/Nexora/Installation/Installer.php');foreach(['DatabaseDataPlaneIdentity','database_data_plane_fingerprint','database_schema_fingerprint','database_server_version','database_session_profile_sha256'] as $m)if(!str_contains($installer,$m))$errors[]="installer database lineage missing [{$m}]";
    $backup=$read($root.'/app/Nexora/Cloud/Services/BackupOrchestrator.php').$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeBackupVerifier.php');foreach(['database_data_plane_fingerprint','database_schema_fingerprint','require_backup_schema_binding','schema 4 PASS'] as $m)if(!str_contains($backup,$m))$errors[]="backup database/schema binding missing [{$m}]";
    $c2=$read($root.'/scripts/n1-c2-laravel-runtime-certify.php');foreach(['database-data-plane-baseline','database-data-plane-rebuild','database-data-plane-status','database-data-plane-certify.php'] as $m)if(!str_contains($c2,$m))$errors[]="C2 database data-plane gate missing [{$m}]";
    $matrix=$read($root.'/scripts/certify-database-matrix.php');foreach(['data-plane-baseline','data-plane-rebuild-compare','database-data-plane-certify.php'] as $m)if(!str_contains($matrix,$m))$errors[]="C3 per-driver schema round-trip gate missing [{$m}]";
    $deployment=$read($root.'/scripts/lib/deployment-generation.php').$read($root.'/app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');foreach(['database_policy_sha256','config/nexora-database-runtime.php'] as $m)if(!str_contains($deployment,$m))$errors[]="deployment generation database-policy binding missing [{$m}]";
    $builder=$read($root.'/scripts/build-production-release.php');foreach(['databasePolicyHash','database_policy_sha256','database_data_plane_contract','exact_data_plane_required','structural_schema_attestation_required','nexora:database:data-plane-status --deep --assert-installed'] as $m)if(!str_contains($builder,$m))$errors[]="production release database data-plane contract missing [{$m}]";
    $middleware=$read($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');if(!str_contains($middleware,'X-Nexora-Database-Fingerprint'))$errors[]='web database data-plane response header missing.';
    $env=$read($root.'/.env.production.example');foreach(['NEXORA_QUEUE_PAYLOAD_SCHEMA=13','NEXORA_DB_REQUIRE_EXACT_DATA_PLANE=true','NEXORA_DB_REQUIRE_SCHEMA_ATTESTATION=true','NEXORA_DB_REQUIRE_BACKUP_SCHEMA_BINDING=true'] as $m)if(!str_contains($env,$m))$errors[]="production database runtime default missing [{$m}]";
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['data_plane_identity'=>1,'schema_attestation'=>1,'queue_payload_schema'=>13,'c2_schema_round_trip'=>1,'c3_driver_schema_round_trip'=>5,'backup_schema_binding'=>1,'ha_database_check'=>1,'automatic_database_mutation'=>0]];
}
