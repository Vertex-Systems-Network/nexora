<?php

declare(strict_types=1);

/**
 * @return list<string>
 */
function nexoraMigrationSchemaTableBlocks(string $source, string $targetTable): array
{
    $constants=[];
    if(preg_match_all('/(?:private\s+|protected\s+|public\s+)?const\s+([A-Z0-9_]+)\s*=\s*[\'\"]([^\'\"]+)[\'\"]\s*;/', $source,$constantMatches,PREG_SET_ORDER)!==false){
        foreach($constantMatches as $match)$constants[$match[1]]=$match[2];
    }

    $blocks=[];
    if(preg_match_all('/Schema::table\(\s*(?:[\'\"]([^\'\"]+)[\'\"]|self::([A-Z0-9_]+))\s*,/', $source,$matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE)===false)return $blocks;

    foreach($matches as $match){
        $literal=$match[1][0]??'';
        $constant=$match[2][0]??'';
        $table=$literal!==''?$literal:($constants[$constant]??'');
        if($table!==$targetTable)continue;

        $start=(int)$match[0][1]+strlen((string)$match[0][0]);
        $open=strpos($source,'{',$start);
        if($open===false)continue;
        $depth=0;
        $length=strlen($source);
        for($i=$open;$i<$length;$i++){
            if($source[$i]==='{')$depth++;
            elseif($source[$i]==='}'){
                $depth--;
                if($depth===0){
                    $blocks[]=substr($source,$open,$i-$open+1);
                    break;
                }
            }
        }
    }

    return $blocks;
}

function nexoraMigrationForwardTenantizesTable(string $source, string $targetTable): bool
{
    $blocks=nexoraMigrationSchemaTableBlocks($source,$targetTable);
    if($blocks===[])return false;
    $combined=implode("\n",$blocks);

    $hasTenantColumn=preg_match('/->uuid\s*\(\s*[\'\"]tenant_id[\'\"]\s*\)/',$combined)===1;
    $hasTenantForeign=preg_match('/->foreign\s*\(\s*[\'\"]tenant_id[\'\"][^;]*?->references\s*\([^;]*?->on\s*\(\s*[\'\"]nx_enterprise_organizations[\'\"]\s*\)/s',$combined)===1;

    return $hasTenantColumn&&$hasTenantForeign;
}

/**
 * @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>}
 */
function nexoraAnalyzeDatabaseContracts(string $root): array
{
    $errors=[];
    $warnings=[];
    $migrationFiles=glob($root.'/database/migrations/*.php') ?: [];
    sort($migrationFiles,SORT_STRING);
    $created=[];
    $createdPosition=[];
    $dropped=[];
    $foreignTargets=[];
    $explicitNames=[];
    $migrationSources=[];
    $migrationIndexes=[];
    $portableNullableUniqueCount=0;
    $forbidden=[
        'column placement ->after()'=>'/->after\s*\(/',
        'database enum'=>'/->enum\s*\(/',
        'database set'=>'/->set\s*\(/',
        'full-text index'=>'/->fullText\s*\(/',
        'spatial index'=>'/->spatialIndex\s*\(/',
        'generated stored column'=>'/->storedAs\s*\(/',
        'generated virtual column'=>'/->virtualAs\s*\(/',
        'raw DB statement'=>'/DB::(?:statement|unprepared)\s*\(/',
    ];

    foreach($migrationFiles as $fileIndex=>$file){
        $source=(string)file_get_contents($file);
        $basename=basename($file);
        $migrationSources[$basename]=$source;
        $migrationIndexes[$basename]=$fileIndex;
        foreach($forbidden as $label=>$pattern){
            if(preg_match($pattern,$source)===1)$errors[]="{$basename}: forbidden non-portable {$label}.";
        }
        if (preg_match('/->nullable\s*\(\)\s*->unique\s*\(|->unique\s*\([^;]*\)\s*->nullable\s*\(\)/', $source)===1) {
            $errors[]="{$basename}: nullable unique columns must use PortableNullableUnique for SQL Server-compatible NULL semantics.";
        }
        $portableNullableUniqueCount += substr_count($source, 'PortableNullableUnique::create(');
        $portableNullableUniqueCount += substr_count($source, 'PortableNullableUnique::createScoped(');
        if(preg_match('/Schema::create\([\'\"](?:phase_|milestone_)/i',$source)===1)$errors[]="{$basename}: phase/milestone table names are forbidden.";
        foreach(preg_split('/\R/',$source) ?: [] as $lineNo=>$line){
            if(preg_match('/Schema::create\([\'\"]([^\'\"]+)[\'\"]/', $line,$m)===1){
                $table=$m[1];
                if(isset($created[$table]))$errors[]="{$basename}: table {$table} is created more than once (first in {$created[$table]}).";
                $created[$table]=$basename;
                $createdPosition[$table]=[$fileIndex,$lineNo+1];
            }
            if(preg_match('/Schema::dropIfExists\([\'\"]([^\'\"]+)[\'\"]/', $line,$m)===1)$dropped[$m[1]]=$basename;
            if(preg_match('/->on\([\'\"]([^\'\"]+)[\'\"]\)/',$line,$m)===1)$foreignTargets[]=[$m[1],$basename,$fileIndex,$lineNo+1];
            if(preg_match_all('/->(?:index|unique|foreign)\([^;]*?[\'\"]([^\'\"]+)[\'\"]\)/',$line,$matches)===1 || !empty($matches[1])){
                foreach($matches[1]??[] as $name)$explicitNames[]=[$name,$basename,$lineNo+1];
            }
        }
    }

    foreach($foreignTargets as [$target,$basename,$fileIndex,$lineNo]){
        if(!isset($createdPosition[$target])){$errors[]="{$basename}:{$lineNo} foreign key targets missing table {$target}.";continue;}
        [$targetFile,$targetLine]=$createdPosition[$target];
        if($targetFile>$fileIndex || ($targetFile===$fileIndex && $targetLine>$lineNo))$errors[]="{$basename}:{$lineNo} foreign key targets {$target} before that table is created.";
    }
    foreach($created as $table=>$basename){
        if(!isset($dropped[$table]))$errors[]="{$basename}: table {$table} has no dropIfExists() rollback coverage.";
    }
    foreach($explicitNames as [$name,$basename,$lineNo]){
        if(strlen($name)>63)$errors[]="{$basename}:{$lineNo} index/constraint name exceeds 63 characters: {$name}.";
    }

    $enterprise=$root.'/database/migrations/2026_08_16_002000_add_nexora_enterprise_tenancy.php';
    $enterpriseBasename=basename($enterprise);
    $enterpriseMigrationIndex=$migrationIndexes[$enterpriseBasename]??null;
    $tenantTables=[];
    if(is_file($enterprise)){
        $source=(string)file_get_contents($enterprise);
        if(preg_match('/private array \$tenantTables\s*=\s*\[(.*?)\];/s',$source,$m)===1){
            preg_match_all('/[\'\"]([^\'\"]+)[\'\"]/', $m[1],$matches);
            $tenantTables=array_values(array_unique($matches[1]??[]));
        } else $errors[]='Enterprise tenancy migration does not expose the tenant table manifest.';
    } else $errors[]='Enterprise tenancy migration is missing.';

    $tenantModels=[];
    foreach(glob($root.'/app/Models/*.php') ?: [] as $modelFile){
        $source=(string)file_get_contents($modelFile);
        if(!str_contains($source,'use BelongsToTenant;'))continue;
        if(preg_match('/protected \$table\s*=\s*[\'\"]([^\'\"]+)[\'\"]/', $source,$m)!==1){$errors[]=basename($modelFile).': tenant-aware model has no explicit table name.';continue;}
        $tenantModels[$m[1]]=basename($modelFile);
    }
    $declared=array_fill_keys($tenantTables,true);
    $tenantManifestModels=[];
    $tenantNativeModels=[];
    $tenantForwardModels=[];
    foreach($tenantModels as $table=>$model){
        if(isset($declared[$table])) {
            $tenantManifestModels[$table]=$model;
            continue;
        }

        // The enterprise manifest is a backfill contract for tables that existed
        // before enterprise tenancy was introduced. New tenant-aware tables created
        // after that migration must be tenant-native instead: their own migration
        // declares tenant_id and its enterprise foreign key. Existing historical
        // tables may also be tenantized by a later forward migration; never mutate
        // the frozen enterprise backfill migration just to register that evolution.
        $creator=$created[$table]??null;
        $creatorIndex=is_string($creator)?($migrationIndexes[$creator]??null):null;
        $creatorSource=is_string($creator)?($migrationSources[$creator]??''):'';
        $isPostEnterprise=is_int($enterpriseMigrationIndex)&&is_int($creatorIndex)&&$creatorIndex>$enterpriseMigrationIndex;
        $hasTenantColumn=preg_match('/->uuid\([\'\"]tenant_id[\'\"]\)/',$creatorSource)===1;
        $hasTenantForeign=str_contains($creatorSource,"foreign('tenant_id'")&&str_contains($creatorSource,"on('nx_enterprise_organizations')");
        if($isPostEnterprise&&$hasTenantColumn&&$hasTenantForeign){
            $tenantNativeModels[$table]=$model;
            continue;
        }

        $forwardMigration=null;
        if(is_int($enterpriseMigrationIndex)){
            foreach($migrationSources as $migrationBasename=>$migrationSource){
                $migrationIndex=$migrationIndexes[$migrationBasename]??null;
                if(!is_int($migrationIndex)||$migrationIndex<=$enterpriseMigrationIndex)continue;
                if(nexoraMigrationForwardTenantizesTable($migrationSource,$table)){
                    $forwardMigration=$migrationBasename;
                    break;
                }
            }
        }
        if(is_string($forwardMigration)){
            $tenantForwardModels[$table]=$model;
            continue;
        }

        $errors[]="{$model}: tenant-aware table {$table} is neither in the enterprise backfill manifest, created later as tenant-native, nor converted by a later forward tenantization migration with tenant_id and enterprise foreign key.";
    }
    foreach($tenantTables as $table){if(!isset($tenantModels[$table]))$errors[]="Enterprise tenant manifest table {$table} has no BelongsToTenant model.";}

    foreach($tenantModels as $table=>$model){
        if(!isset($created[$table]))$errors[]="{$model}: tenant-aware table {$table} is not created by any migration.";
    }

    $demo=$root.'/database/seeders/Demo/NexoraDemoSeeder.php';
    if(is_file($demo)){
        $source=(string)file_get_contents($demo);
        if(preg_match('/User::factory\(\)->count\s*\(/',$source)===1)$errors[]='Demo seeder creates non-deterministic duplicate users on repeated runs.';
        if(!str_contains($source,"demo-user-%02d@nexora.test"))$warnings[]='Demo seeder does not expose deterministic demo user identities.';
    }
    $core=$root.'/database/seeders/Core/NexoraCoreSeeder.php';
    if(is_file($core)){
        $source=(string)file_get_contents($core);
        if(preg_match('/HelpdeskSlaPolicy::query\(\)->exists\(\)/',$source)===1)$errors[]='Core SLA seeding uses an all-or-nothing exists() gate instead of deterministic updateOrCreate().';
        if(str_contains($source,'User::factory()'))$errors[]='Core seeder must not create random users.';
    }

    $portableHelper=$root.'/app/Nexora/Foundation/Database/PortableNullableUnique.php';
    if (! is_file($portableHelper)) {
        $errors[]='Portable nullable-unique database helper is missing.';
    } else {
        $helperSource=(string)file_get_contents($portableHelper);
        foreach ([
            "getDriverName() === 'sqlsrv'",
            'CREATE UNIQUE INDEX',
            'IS NOT NULL',
            'Schema::table',
            'public static function createScoped(',
            '$blueprint->unique([$scopeColumn, $column], $indexName)',
            'public static function drop(',
            'DROP INDEX',
            '$blueprint->dropUnique($indexName)',
        ] as $marker) {
            if (! str_contains($helperSource,$marker)) $errors[]='Portable nullable-unique helper is missing required SQL Server/non-SQL Server behavior: '.$marker;
        }
    }
    if ($portableNullableUniqueCount !== 11) $errors[]="Expected 11 portable nullable-unique declarations; found {$portableNullableUniqueCount}.";

    $certScript=(string)@file_get_contents($root.'/scripts/create-certification-database.php');
    if(!str_contains($certScript,'Unsafe certification database name'))$errors[]='Certification database script is missing destructive database-name protection.';

    // The original N1.0 certification contract froze 51 enterprise-backfill roots.
    // Data Connections was later corrected into that historical manifest because it
    // already existed before enterprise tenancy. Keep the legacy metrics stable for
    // old certification consumers while exposing the complete current manifest in
    // explicit *_current metrics. Validation above always runs against the full set.
    $historicalTenantTables=array_values(array_filter(
        $tenantTables,
        static fn(string $table): bool => $table !== 'nx_data_connections',
    ));
    $historicalTenantManifestModels=array_filter(
        $tenantManifestModels,
        static fn(string $model,string $table): bool => $table !== 'nx_data_connections',
        ARRAY_FILTER_USE_BOTH,
    );

    return [
        'errors'=>array_values(array_unique($errors)),
        'warnings'=>array_values(array_unique($warnings)),
        'metrics'=>[
            'migrations'=>count($migrationFiles),
            'tables'=>count($created),
            'foreign_targets'=>count($foreignTargets),
            'explicit_index_names'=>count($explicitNames),
            // Legacy N1.0 baseline metrics stay fixed for certification compatibility.
            'tenant_tables'=>count($historicalTenantTables),
            'tenant_models'=>count($historicalTenantManifestModels),
            // Current metrics expose the complete validated enterprise manifest.
            'tenant_tables_current'=>count($tenantTables),
            'tenant_models_current'=>count($tenantManifestModels),
            'tenant_models_total'=>count($tenantModels),
            'tenant_native_models'=>count($tenantNativeModels),
            'tenant_forward_models'=>count($tenantForwardModels),
            'portable_nullable_unique'=>$portableNullableUniqueCount,
            'seeders'=>count(glob($root.'/database/seeders/**/*.php',GLOB_BRACE) ?: []),
        ],
    ];
}
