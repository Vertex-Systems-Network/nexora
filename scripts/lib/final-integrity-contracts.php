<?php

declare(strict_types=1);

/** @return array{errors:list<string>,metrics:array<string,int|string>} */
function nexoraAnalyzeFinalIntegrityContracts(string $root): array
{
    $errors=[];
    $required=[
        'scripts/lib/source-attestation.php','scripts/source-attestation.php','scripts/source-attestation-contract-verify.php',
        'scripts/lib/release-artifact.php','scripts/release-artifact-verify.php',
        'scripts/zero-install-evidence-verify.php','scripts/upgrade-rehearsal-evidence-verify.php',
        'docs/zero-install-evidence.example.json','docs/upgrade-rehearsal-evidence.example.json',
        'app/Nexora/Installation/Database/DatabaseVersionPolicy.php','app/Nexora/Installation/Database/DatabaseRuntimeDoctor.php',
        'tests/Feature/Certification/CertificationDatabaseIsolationTest.php','tests/Compatibility/CertificationDatabaseBindingCompatibilityTest.php',
        '.github/workflows/release-certification.yml',
    ];
    foreach($required as $file) if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="missing RC20 artifact [{$file}]";

    $phpunit=(string)@file_get_contents($root.'/phpunit.xml');
    foreach(['DB_CONNECTION','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD','DB_URL'] as $name){
        if(preg_match('/<env name="'.preg_quote($name,'/').'"[^>]*force="true"/i',$phpunit)===1)$errors[]="phpunit.xml must not force override certification DB env [{$name}]";
    }

    $composer=json_decode((string)@file_get_contents($root.'/composer.json'),true)?:[];
    $package=json_decode((string)@file_get_contents($root.'/package.json'),true)?:[];
    $policy=is_file($root.'/config/nexora-dependencies.php')?require $root.'/config/nexora-dependencies.php':[];
    if(($composer['require']['php']??null)!=='>=8.3 <8.5')$errors[]='composer PHP engine must match certified >=8.3 <8.5 range';
    if(($package['engines']['node']??null)!=='>=22 <25')$errors[]='package Node engine must match certified >=22 <25 range';
    if(($package['engines']['npm']??null)!=='>=10 <11')$errors[]='package npm engine must match certified >=10 <11 range';
    if(($policy['npm']['maximum_major_exclusive']??null)!==11)$errors[]='dependency npm policy must remain <11';

    $runner=(string)@file_get_contents($root.'/scripts/certify-release.php');
    foreach(['NEXORA_CERT_EXPECT_DB_CONNECTION','NEXORA_CERT_EXPECT_DB_DATABASE','source-attestation-contract','source-attestation-final',"['mysql','mariadb','pgsql','sqlite','sqlsrv']",'zero-install-evidence','upgrade-rehearsal-evidence','production-package-verify','nexora:database:doctor'] as $marker) if(!str_contains($runner,$marker))$errors[]="certification runner missing RC20 closure marker [{$marker}]";

    $matrix=(string)@file_get_contents($root.'/scripts/certify-database-matrix.php');
    foreach(['NEXORA_CERT_EXPECT_DB_CONNECTION','nexora:database:doctor','CommerceAdminFlowTest.php','CrmAdminFlowTest.php','AutomationFlowTest.php','EnterpriseFlowTest.php','StudioFlowTest.php','ConcurrencyCertificationTest.php'] as $marker) if(!str_contains($matrix,$marker))$errors[]="database matrix missing RC20 high-risk marker [{$marker}]";

    $closure=(string)@file_get_contents($root.'/scripts/lib/final-closure.php');
    foreach(['database_matrix','zero_install','upgrade_rehearsal','nexoraValidateProductionArtifact'] as $marker) if(!str_contains($closure,$marker))$errors[]="final closure missing RC20 domain [{$marker}]";

    $final=(string)@file_get_contents($root.'/scripts/final-evidence-verify.php');
    foreach(['zero_install','upgrade_rehearsal','database_matrix','source_tree_sha256'] as $marker) if(!str_contains($final,$marker))$errors[]="final evidence aggregator missing RC20 binding [{$marker}]";

    $builder=(string)@file_get_contents($root.'/scripts/build-production-release.php');
    foreach(['nexoraComputeSourceAttestation','source_tree_sha256','Final evidence was recorded for a different source-tree digest'] as $marker) if(!str_contains($builder,$marker))$errors[]="production builder missing source-tree seal [{$marker}]";

    $console=(string)@file_get_contents($root.'/routes/console.php');
    if(!str_contains($console,'nexora:database:doctor'))$errors[]='database minimum-version doctor command missing';

    return ['errors'=>$errors,'metrics'=>[
        'closure_domains'=>11,
        'primary_db_families'=>5,
        'matrix_high_risk_feature_files'=>6,
        'source_attestation'=>'sha256-path-size-content-v1',
    ]];
}
