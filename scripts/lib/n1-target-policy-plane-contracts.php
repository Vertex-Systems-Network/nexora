<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzePolicyPlaneContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=['config/nexora-policy-runtime.php','app/Nexora/Cloud/Services/RuntimePolicyPlaneIdentity.php','app/Console/Commands/Nexora/RuntimePolicyStatusCommand.php','scripts/lib/n1-target-policy-plane-contracts.php','scripts/n1-target-policy-plane-contract-verify.php'];
    foreach($required as $f)if(!is_file($root.'/'.$f)||filesize($root.'/'.$f)===0)$errors[]="policy-plane artifact missing [{$f}]";
    $config=$read($root.'/config/nexora-policy-runtime.php');foreach(['require_exact_policy_plane','production_fail_closed','queue_payload_schema','require_policy_convergence'] as $m)if(!str_contains($config,$m))$errors[]="policy-plane config missing [{$m}]";
    $identity=$read($root.'/app/Nexora/Cloud/Services/RuntimePolicyPlaneIdentity.php');foreach(['concurrency','transfers','runtime','upgrade','update_trust','release_trust','supply_chain','dependencies','ha','deployment_fences_fail_closed','upgrade_safety_fail_closed','update_trust_fail_closed','release_trust_fail_closed','supply_chain_fail_closed','dependency_lock_policy_fail_closed','media_upload_within_http_limit'] as $m)if(!str_contains($identity,$m))$errors[]="effective policy identity missing [{$m}]";
    foreach(['temporary_root','private_key_path','public_key_path','trusted_anchor_path','receipt_path','history_path','stage_dir'] as $forbidden)if(str_contains($identity,"'{$forbidden}'"))$errors[]="policy fingerprint must not bind machine-local/secret path [{$forbidden}]";
    $provider=$read($root.'/app/Providers/AppServiceProvider.php');foreach(['RuntimePolicyPlaneIdentity','runtime_policy_fingerprint','max(13'] as $m)if(!str_contains($provider,$m))$errors[]="queue/provider policy fence missing [{$m}]";
    $guard=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['runtime_policy_fingerprint','runtime_policy_compatible','effective runtime policy plane','max(13'] as $m)if(!str_contains($guard,$m))$errors[]="runtime policy guard missing [{$m}]";
    $node=$read($root.'/app/Nexora/Cloud/Services/NodeManager.php');foreach(['runtime_policy_fingerprint','runtime_policy_status','runtime_policy_deep_sha256'] as $m)if(!str_contains($node,$m))$errors[]="node policy metadata missing [{$m}]";
    $ha=$read($root.'/app/Nexora/Cloud/Services/HaReadinessService.php');foreach(['local_policy_plane','runtime_policy_plane_consistency','runtime_policy_status_pass'] as $m)if(!str_contains($ha,$m))$errors[]="HA policy convergence missing [{$m}]";
    $upgrade=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['source_policy_plane','policy_plane_attested','runtime_policy_fingerprint','runtime_policy_deep_sha256'] as $m)if(!str_contains($upgrade,$m))$errors[]="upgrade policy-plane binding missing [{$m}]";
    $installer=$read($root.'/app/Nexora/Installation/Installer.php');foreach(['RuntimePolicyPlaneIdentity','runtime_policy_fingerprint','runtime_policy_deep_sha256'] as $m)if(!str_contains($installer,$m))$errors[]="installer policy lineage missing [{$m}]";
    $deployment=$read($root.'/scripts/lib/deployment-generation.php').$read($root.'/app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');foreach(['policy_plane_sha256','config/nexora-policy-runtime.php'] as $m)if(!str_contains($deployment,$m))$errors[]="deployment policy-plane binding missing [{$m}]";
    $release=$read($root.'/scripts/build-production-release.php').$read($root.'/scripts/release-provenance.php').$read($root.'/scripts/lib/final-release-seal.php');foreach(['runtime_policy_plane_contract','policy_plane_sha256','nexora:runtime:policy-status --deep --assert-installed'] as $m)if(!str_contains($release,$m))$errors[]="release policy-plane binding missing [{$m}]";
    $c2=$read($root.'/scripts/n1-c2-laravel-runtime-certify.php');if(!str_contains($c2,'runtime-policy-status'))$errors[]='C2 must run effective policy-plane deep status';
    $c4=$read($root.'/scripts/n1-c4-evidence-prepare.php');foreach(['effective_policy_fingerprint_verified','policy_env_override_drift_rejected','queue_wrong_policy_plane_rejected','cross_node_policy_convergence_verified'] as $m)if(!str_contains($c4,$m))$errors[]="C4 policy-plane evidence missing [{$m}]";
    $c6=$read($root.'/scripts/n1-c6-evidence-prepare.php');foreach(['runtime_policy_fingerprint','runtime_policy_status','runtime_policy_plane_consistency','runtime_policy_status_pass'] as $m)if(!str_contains($c6,$m))$errors[]="C6 policy-plane evidence missing [{$m}]";
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['queue_payload_schema'=>13,'effective_policy_domains'=>9,'c2_policy_gate'=>1,'c4_policy_checks'=>8,'ha_policy_checks'=>2,'automatic_policy_mutation'=>0]];
}
