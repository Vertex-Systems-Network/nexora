<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/deployment-generation.php';
require_once $root.'/scripts/lib/final-release-seal.php';
require_once $root.'/scripts/lib/production-dependency-stage.php';
require_once $root.'/scripts/lib/release-content-manifest.php';
$configPath = $root.'/config/nexora.php';
if (! is_file($configPath)) {
    fwrite(STDERR, "[Nexora Release] config/nexora.php is missing.\n");
    exit(1);
}

/** @var array<string,mixed> $platform */
$platform = require $configPath;
$releasePolicyPath = $root.'/config/nexora-release.php';
if (! is_file($releasePolicyPath)) {
    fwrite(STDERR, "[Nexora Release] config/nexora-release.php is missing.\n");
    exit(1);
}
/** @var array<string,mixed> $releasePolicy */
$releasePolicy = require $releasePolicyPath;
$version = trim((string) ($platform['version'] ?? ''));
if ($version === '' || preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $version) !== 1) {
    fwrite(STDERR, "[Nexora Release] config/nexora.php contains an invalid semantic version [{$version}].\n");
    exit(1);
}

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "[Nexora Release] PHP ext-zip is required.\n");
    exit(1);
}

$certificationPath = $root.'/storage/app/nexora/certification/latest.json';
$performancePath = $root.'/storage/app/nexora/certification/build-assets.json';
$finalEvidencePath = $root.'/storage/app/nexora/certification/final-evidence.json';
$dependencyAuditPath = $root.'/storage/app/nexora/certification/dependency-audit.json';
$dependencyProvenancePath = $root.'/storage/app/nexora/certification/dependency-provenance.json';
$toolchainPath = $root.'/storage/app/nexora/certification/toolchain.json';
$dependencySbomPath = $root.'/storage/app/nexora/certification/dependency-sbom.json';
$productionDependenciesPath = $root.'/storage/app/nexora/certification/production-dependencies.json';
$releaseProvenancePath = $root.'/storage/app/nexora/certification/release-provenance.json';
$required = [
    $root.'/vendor/autoload.php' => 'Composer dependencies',
    $root.'/public/build/manifest.json' => 'production frontend build',
    $root.'/composer.lock' => 'composer.lock',
    $root.'/package-lock.json' => 'package-lock.json',
    $certificationPath => 'N1.0 release certification report',
    $performancePath => 'RC9 build asset performance report',
    $finalEvidencePath => 'RC10 final operator evidence report',
    $dependencyAuditPath => 'RC15 dependency audit evidence',
    $dependencyProvenancePath => 'RC15 dependency provenance evidence',
    $toolchainPath => 'certified PHP/Composer/Node/npm toolchain fingerprint',
    $dependencySbomPath => 'CycloneDX dependency SBOM',
    $productionDependenciesPath => 'verified production no-dev dependency stage',
    $releaseProvenancePath => 'release provenance',
];

$missing = [];
foreach ($required as $path => $label) {
    if (! is_file($path)) {
        $missing[] = $label;
    }
}
if ($missing !== []) {
    fwrite(STDERR, "[Nexora Release] Missing: ".implode(', ', $missing).". Complete release certification first.\n");
    exit(1);
}

try {
    $certification = json_decode((string) file_get_contents($certificationPath), true, 128, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, "[Nexora Release] Certification report is invalid JSON: {$exception->getMessage()}\n");
    exit(1);
}
if (! is_array($certification)
    || ($certification['status'] ?? null) !== 'certification-pass'
    || ($certification['platform_version'] ?? null) !== $version
) {
    fwrite(STDERR, "[Nexora Release] The certification report does not certify platform {$version}. Run scripts/quality-check for this exact source tree.\n");
    exit(1);
}
$currentSourceAttestation = nexoraComputeSourceAttestation($root);
if (($certification['source_tree_sha256'] ?? null) !== $currentSourceAttestation['tree_sha256']) {
    fwrite(STDERR, "[Nexora Release] Certified source-tree SHA-256 no longer matches the current source tree. Re-run exact-version certification.\n");
    exit(1);
}
try {
    $performance = json_decode((string) file_get_contents($performancePath), true, 128, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, "[Nexora Release] Build asset performance report is invalid JSON: {$exception->getMessage()}\n");
    exit(1);
}
if (! is_array($performance)
    || ($performance['status'] ?? null) !== 'pass'
    || ($performance['platform_version'] ?? null) !== $version
) {
    fwrite(STDERR, "[Nexora Release] Build assets have not passed RC9 budgets for platform {$version}. Run scripts/performance-build-verify.php after npm run build.\n");
    exit(1);
}

try {
    $finalEvidence = json_decode((string) file_get_contents($finalEvidencePath), true, 128, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, "[Nexora Release] Final evidence report is invalid JSON: {$exception->getMessage()}\n");
    exit(1);
}
if (! is_array($finalEvidence)
    || ($finalEvidence['status'] ?? null) !== 'pass'
    || ($finalEvidence['platform_version'] ?? null) !== $version
) {
    fwrite(STDERR, "[Nexora Release] Final N1.0 operator evidence is not PASS for platform {$version}. Run scripts/final-evidence-verify.php after zero-install, upgrade, browser, HTTP/build, five-DB matrix, backup/restore and multi-node HA rehearsals.\n");
    exit(1);
}
if (($finalEvidence['source_tree_sha256'] ?? null) !== $currentSourceAttestation['tree_sha256']) {
    fwrite(STDERR, "[Nexora Release] Final evidence was recorded for a different source-tree digest. Re-run final evidence on the exact certified source.\n");
    exit(1);
}

try {
    $dependencyAudit = json_decode((string) file_get_contents($dependencyAuditPath), true, 128, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, "[Nexora Release] Dependency audit evidence is invalid JSON: {$exception->getMessage()}\n");
    exit(1);
}
if (! is_array($dependencyAudit) || ($dependencyAudit['status'] ?? null) !== 'pass' || ($dependencyAudit['platform_version'] ?? null) !== $version || ($dependencyAudit['composer_lock_sha256'] ?? null) !== hash_file('sha256',$root.'/composer.lock') || ($dependencyAudit['package_lock_sha256'] ?? null) !== hash_file('sha256',$root.'/package-lock.json')) {
    fwrite(STDERR, "[Nexora Release] RC15 dependency audit evidence does not match the current locked dependency graph.\n");
    exit(1);
}

try {
    $dependencyProvenance = json_decode((string) file_get_contents($dependencyProvenancePath), true, 128, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, "[Nexora Release] Dependency provenance evidence is invalid JSON: {$exception->getMessage()}\n");
    exit(1);
}
if (! is_array($dependencyProvenance) || ($dependencyProvenance['status'] ?? null) !== 'pass' || ($dependencyProvenance['platform_version'] ?? null) !== $version || ($dependencyProvenance['composer_lock_sha256'] ?? null) !== hash_file('sha256',$root.'/composer.lock') || ($dependencyProvenance['package_lock_sha256'] ?? null) !== hash_file('sha256',$root.'/package-lock.json')) {
    fwrite(STDERR, "[Nexora Release] RC15 dependency provenance does not match the current locked dependency graph.\n");
    exit(1);
}

require_once $root.'/scripts/lib/n1-certified-toolchain.php';
$toolchainErrors=nexoraValidateCertifiedToolchain($root);
if($toolchainErrors!==[]){fwrite(STDERR,"[Nexora Release] Certified toolchain drift: ".implode('; ',$toolchainErrors)."\n");exit(1);}
$toolchainHash=hash_file('sha256',$toolchainPath);
$releaseTrustPolicyHash=hash_file('sha256',$root.'/config/nexora-release-trust.php');
$productionStageErrors=nexoraValidateProductionDependencyStage($root);if($productionStageErrors!==[]){fwrite(STDERR,"[Nexora Release] Production dependency stage invalid: ".implode('; ',$productionStageErrors)."\n");exit(1);}
try{$releaseProvenance=json_decode((string)file_get_contents($releaseProvenancePath),true,128,JSON_THROW_ON_ERROR);}catch(Throwable $e){$releaseProvenance=null;}if(!is_array($releaseProvenance)||($releaseProvenance['status']??null)!=='prepared'||($releaseProvenance['source_tree_sha256']??null)!==$currentSourceAttestation['tree_sha256']){fwrite(STDERR,"[Nexora Release] Release provenance missing/stale. Run scripts/release-provenance.php.\n");exit(1);}
$releaseAnchor=nexoraReleaseTrustAnchorRead($root);$releaseAnchorErrors=nexoraValidateReleaseTrustAnchor($root,$releaseAnchor);if($releaseAnchorErrors!==[]){fwrite(STDERR,"[Nexora Release] Release trust anchor invalid: ".implode('; ',$releaseAnchorErrors)."\n");exit(1);}$releaseAnchorHash=hash_file('sha256',nexoraReleaseTrustAnchorPath($root));

require $root.'/bootstrap/nexora-runtime-bootstrap.php';

$dist = $root.'/dist';
if (! is_dir($dist) && ! mkdir($dist, 0775, true) && ! is_dir($dist)) {
    fwrite(STDERR, "[Nexora Release] Cannot create dist directory.\n");
    exit(1);
}

$fileVersion = preg_replace('/[^0-9A-Za-z._-]+/', '-', $version) ?: 'unknown';
$target = $dist.'/nexora-'.$fileVersion.'-production.zip';
@unlink($target);
$zip = new ZipArchive();
if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "[Nexora Release] Cannot open output ZIP.\n");
    exit(1);
}

$excludedTop = array_values((array) ($releasePolicy['excluded_top'] ?? []));
$excludedFiles = array_values((array) ($releasePolicy['excluded_files'] ?? []));
$excludedPrefixes = array_values((array) ($releasePolicy['excluded_prefixes'] ?? []));
$requiredArchiveEntries = array_values((array) ($releasePolicy['required_archive_entries'] ?? []));
$forbiddenArchiveEntries = array_values((array) ($releasePolicy['forbidden_archive_entries'] ?? []));
$forbiddenArchivePrefixes = array_values((array) ($releasePolicy['forbidden_archive_prefixes'] ?? []));

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY,
);

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $absolute = $file->getPathname();
    $relative = str_replace('\\', '/', substr($absolute, strlen($root) + 1));
    $top = explode('/', $relative, 2)[0];

    if ($top === 'vendor') {
        // Never ship the certification host's dev-capable vendor tree. A separately
        // verified Composer --no-dev stage is added below.
        continue;
    }
    if (in_array($top, $excludedTop, true) || in_array($relative, $excludedFiles, true)) {
        continue;
    }
    if (preg_match('/^(?:NEXORA_PLAN_STATUS_N|Nexora_N).*\.(?:md|zip|sha256)$/', basename($relative)) === 1) {
        continue;
    }
    foreach ($excludedPrefixes as $prefix) {
        if (str_starts_with($relative, $prefix) && ! str_ends_with($relative, '.gitkeep') && ! str_ends_with($relative, '.gitignore')) {
            continue 2;
        }
    }

    $zip->addFile($absolute, $relative);
}

$stageVendor=nexoraProductionDependencyStageDir($root).'/vendor';
$stageIterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stageVendor,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY);
foreach($stageIterator as $file){if(!$file->isFile())continue;$absolute=$file->getPathname();$relative='vendor/'.str_replace('\\','/',substr($absolute,strlen($stageVendor)+1));$zip->addFile($absolute,$relative);}
$sbomRaw=(string)file_get_contents($dependencySbomPath);$provenanceRaw=(string)file_get_contents($releaseProvenancePath);
$zip->addFromString('nexora-sbom.json',$sbomRaw);
$zip->addFromString('nexora-provenance.json',$provenanceRaw);

$certificationHash = hash_file('sha256', $certificationPath);
$performanceReportHash = hash_file('sha256', $performancePath);
$releasePolicyHash = hash_file('sha256', $releasePolicyPath);
$environmentPolicyHash = hash_file('sha256', $root.'/config/nexora-environment.php');
$finalEvidenceHash = hash_file('sha256', $finalEvidencePath);
$dependencyAuditHash = hash_file('sha256', $dependencyAuditPath);
$dependencyProvenanceHash = hash_file('sha256', $dependencyProvenancePath);
$dependencyPolicyHash = hash_file('sha256', $root.'/config/nexora-dependencies.php');
$filesystemPolicyHash = hash_file('sha256', $root.'/config/nexora-filesystem.php');
$transferPolicyHash = hash_file('sha256', $root.'/config/nexora-transfers.php');
$runtimePolicyHash = hash_file('sha256', $root.'/config/nexora-runtime.php');
$concurrencyPolicyHash = hash_file('sha256', $root.'/config/nexora-concurrency.php');
$evidencePolicyHash = hash_file('sha256', $root.'/config/nexora-certification-evidence.php');
$releaseTrustPolicyHash = hash_file('sha256', $root.'/config/nexora-release-trust.php');
$updateTrustPolicyHash = hash_file('sha256', $root.'/config/nexora-update-trust.php');
$upgradePolicyHash = hash_file('sha256', $root.'/config/nexora-upgrade.php');
$activationPolicyHash = hash_file('sha256', $root.'/config/nexora-activation.php');
$enginePolicyHash = hash_file('sha256', $root.'/config/nexora-engine.php');
$databasePolicyHash = hash_file('sha256', $root.'/config/nexora-database-runtime.php');
$storagePolicyHash = hash_file('sha256', $root.'/config/nexora-storage-runtime.php');
$networkPolicyHash = hash_file('sha256', $root.'/config/nexora-network-runtime.php');
$hostPolicyHash = hash_file('sha256', $root.'/config/nexora-host-runtime.php');
$resourcePolicyHash = hash_file('sha256', $root.'/config/nexora-resource-runtime.php');
$policyPlaneHash = hash_file('sha256', $root.'/config/nexora-policy-runtime.php');
$processPolicyHash = hash_file('sha256', $root.'/config/nexora-process-runtime.php');
$frameworkPolicyHash = hash_file('sha256', $root.'/config/nexora-framework.php');
$toolchainHash = hash_file('sha256', $toolchainPath);
$dependencySbomHash = hash_file('sha256', $dependencySbomPath);
$productionDependenciesHash = hash_file('sha256', $productionDependenciesPath);
$releaseProvenanceHash = hash_file('sha256', $releaseProvenancePath);
$productionVendorFingerprint = nexoraDirectoryContentFingerprint($stageVendor);
$releaseInputs = [
    'source_tree_sha256'=>$currentSourceAttestation['tree_sha256'],
    'certification_sha256'=>$certificationHash,
    'performance_sha256'=>$performanceReportHash,
    'final_evidence_sha256'=>$finalEvidenceHash,
    'dependency_audit_sha256'=>$dependencyAuditHash,
    'dependency_provenance_sha256'=>$dependencyProvenanceHash,
    'composer_lock_sha256'=>hash_file('sha256',$root.'/composer.lock'),
    'package_lock_sha256'=>hash_file('sha256',$root.'/package-lock.json'),
    'frontend_manifest_sha256'=>hash_file('sha256',$root.'/public/build/manifest.json'),
    'certified_toolchain_sha256'=>$toolchainHash,
    'release_trust_policy_sha256'=>$releaseTrustPolicyHash,
    'update_trust_policy_sha256'=>$updateTrustPolicyHash,
    'upgrade_policy_sha256'=>$upgradePolicyHash,
    'activation_policy_sha256'=>$activationPolicyHash,
    'engine_policy_sha256'=>$enginePolicyHash,
    'database_policy_sha256'=>$databasePolicyHash,
    'storage_policy_sha256'=>$storagePolicyHash,
    'network_policy_sha256'=>$networkPolicyHash,
    'host_policy_sha256'=>$hostPolicyHash,
    'resource_policy_sha256'=>$resourcePolicyHash,
    'policy_plane_sha256'=>$policyPlaneHash,
    'process_policy_sha256'=>$processPolicyHash,
    'framework_policy_sha256'=>$frameworkPolicyHash,
    'dependency_sbom_sha256'=>$dependencySbomHash,
    'production_dependencies_sha256'=>$productionDependenciesHash,
    'release_provenance_sha256'=>$releaseProvenanceHash,
    'production_vendor_tree_sha256'=>$productionVendorFingerprint['sha256'],
    'release_trust_anchor_sha256'=>$releaseAnchorHash,
];
$runtimeDeploymentMaterials=[
    'platform_version'=>$version,
    'source_tree_sha256'=>$currentSourceAttestation['tree_sha256'],
    'frontend_manifest_sha256'=>hash_file('sha256',$root.'/public/build/manifest.json'),
    'composer_lock_sha256'=>hash_file('sha256',$root.'/composer.lock'),
    'package_lock_sha256'=>hash_file('sha256',$root.'/package-lock.json'),
    'runtime_policy_sha256'=>$runtimePolicyHash,
    'upgrade_policy_sha256'=>$upgradePolicyHash,
    'activation_policy_sha256'=>$activationPolicyHash,
    'engine_policy_sha256'=>$enginePolicyHash,
    'database_policy_sha256'=>$databasePolicyHash,
    'storage_policy_sha256'=>$storagePolicyHash,
    'network_policy_sha256'=>$networkPolicyHash,
    'host_policy_sha256'=>$hostPolicyHash,
    'resource_policy_sha256'=>$resourcePolicyHash,
    'policy_plane_sha256'=>$policyPlaneHash,
    'process_policy_sha256'=>$processPolicyHash,
    'framework_policy_sha256'=>$frameworkPolicyHash,
    'session_schema'=>max(1,(int)(getenv('NEXORA_SESSION_SCHEMA')?:1)),
];
$runtimeDeploymentGeneration=nexoraDeploymentGeneration($runtimeDeploymentMaterials);
$manifest = [
    'schema' => 4,
    'product' => 'Nexora',
    'version' => $version,
    'release_channel' => str_contains($version, '-') ? 'prerelease' : 'stable',
    'type' => 'production-release',
    'runtime_deployment' => ['schema'=>1,'generation'=>$runtimeDeploymentGeneration,'materials'=>$runtimeDeploymentMaterials,'cache_namespace'=>'g'.substr($runtimeDeploymentGeneration,0,16),'session_schema'=>$runtimeDeploymentMaterials['session_schema']],
    'runtime_environment_contract' => ['schema'=>1,'environment_fingerprint_required'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'exact_environment_queue_fence'=>true,'key_rotation_requires_maintenance'=>true,'key_rotation_requires_previous_key'=>true,'runtime_secret_values_embedded'=>false],
    'runtime_activation_contract' => ['schema'=>1,'policy_sha256'=>$activationPolicyHash,'activation_epoch_required'=>true,'framework_cache_fingerprint_required'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'exact_queue_activation_required'=>true,'process_epoch_fence'=>true,'opcache_restart_evidence_if_timestamp_validation_disabled'=>true,'status_command'=>'php artisan nexora:runtime:activation-status --deep','rotate_command'=>'php artisan nexora:runtime:activation-rotate --operator=<name> --confirm=ROTATE','automatic_php_fpm_restart'=>false,'automatic_traffic_restore'=>false],
    'runtime_engine_contract' => ['schema'=>1,'policy_sha256'=>$enginePolicyHash,'exact_php_patch_required'=>true,'exact_extension_profile_required'=>true,'exact_pdo_driver_set_required'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'status_command'=>'php artisan nexora:runtime:engine-status --deep','runtime_engine_fingerprint_embedded'=>false],
    'database_data_plane_contract' => ['schema'=>1,'policy_sha256'=>$databasePolicyHash,'exact_data_plane_required'=>true,'exact_server_version_required'=>true,'exact_session_profile_required'=>true,'structural_schema_attestation_required'=>true,'backup_schema_binding_required'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'status_command'=>'php artisan nexora:database:data-plane-status --deep --assert-installed','database_runtime_fingerprint_embedded'=>false],
    'storage_data_plane_contract' => ['schema'=>1,'policy_sha256'=>$storagePolicyHash,'exact_data_plane_required'=>true,'shared_storage_required_for_ha'=>true,'backup_shared_storage_required_for_ha'=>true,'backup_storage_binding_required'=>true,'media_storage_binding_required'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'status_command'=>'php artisan nexora:runtime:storage-status --deep --assert-installed','storage_runtime_fingerprint_embedded'=>false],
    'service_data_plane_contract' => ['schema'=>1,'policy_sha256'=>$networkPolicyHash,'exact_service_data_plane_required'=>true,'cache_session_queue_endpoint_identity_required'=>true,'tls_ca_proxy_identity_required'=>true,'outbound_dns_pin_required'=>true,'private_reserved_external_blocked'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'status_command'=>'php artisan nexora:runtime:service-status --deep --assert-installed','service_runtime_fingerprint_embedded'=>false],
    'host_clock_contract' => ['schema'=>1,'policy_sha256'=>$hostPolicyHash,'exact_host_profile_required'=>true,'database_clock_anchor_required'=>true,'database_clock_skew_bound_required'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'queue_future_skew_fence'=>true,'status_command'=>'php artisan nexora:runtime:host-status --deep --assert-installed','automatic_ntp_mutation'=>false,'automatic_timezone_mutation'=>false],
    'resource_envelope_contract' => ['schema'=>1,'policy_sha256'=>$resourcePolicyHash,'exact_resource_policy_required'=>true,'deep_upgrade_capacity_required'=>true,'deep_ha_capacity_required'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'status_command'=>'php artisan nexora:runtime:resource-status --deep --assert-installed','automatic_resource_mutation'=>false],
    'runtime_policy_plane_contract' => ['schema'=>1,'policy_sha256'=>$policyPlaneHash,'exact_effective_policy_required'=>true,'production_fail_closed_required'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'status_command'=>'php artisan nexora:runtime:policy-status --deep --assert-installed','automatic_policy_mutation'=>false],
    'runtime_process_plane_contract' => ['schema'=>1,'policy_sha256'=>$processPolicyHash,'exact_process_policy_required'=>true,'role_lease_liveness_required'=>true,'queue_idle_liveness_required'=>true,'queue_indefinite_blocking_rejected_for_ha'=>true,'queue_payload_schema'=>max(13,(int)(getenv('NEXORA_QUEUE_PAYLOAD_SCHEMA')?:13)),'status_command'=>'php artisan nexora:runtime:process-status --assert-installed --assert-live','automatic_process_start_stop'=>false],
    'framework_dependency_contract' => ['schema'=>1,'policy_sha256'=>$frameworkPolicyHash,'laravel_minimum'=>'13.24.0','laravel_maximum_exclusive'=>'14.0.0','composer_constraint'=>'^13.24','reviewed_lock_reconciliation_required'=>true,'status_command'=>'php artisan nexora:runtime:compatibility-status --deep'],
    'created_at' => gmdate(DATE_ATOM),
    'requires_server_package_managers' => false,
    'certification' => [
        'status' => $certification['status'],
        'completed_at' => $certification['completed_at'] ?? null,
        'source_tree_sha256' => $currentSourceAttestation['tree_sha256'],
        'source_file_count' => $currentSourceAttestation['file_count'],
        'report_sha256' => $certificationHash,
        'performance_report_sha256' => $performanceReportHash,
        'final_evidence_report_sha256' => $finalEvidenceHash,
        'dependency_audit_report_sha256' => $dependencyAuditHash,
        'dependency_provenance_report_sha256' => $dependencyProvenanceHash,
        'certified_toolchain_sha256' => $toolchainHash,
        'dependency_sbom_sha256' => $dependencySbomHash,
        'production_dependencies_sha256' => $productionDependenciesHash,
        'release_provenance_sha256' => $releaseProvenanceHash,
        'production_vendor_tree_sha256' => $productionVendorFingerprint['sha256'],
    ],
    'performance' => [
        'status' => $performance['status'] ?? null,
        'totals' => $performance['totals'] ?? [],
        'counts' => $performance['counts'] ?? [],
    ],
    'final_evidence' => ['status' => $finalEvidence['status'] ?? null, 'checked_at' => $finalEvidence['checked_at'] ?? null],
    'upgrade' => [
        'policy_sha256' => $upgradePolicyHash,
        'supported_source' => (string) ((require $root.'/config/nexora-upgrade.php')['supported_source'] ?? ''),
        'verified_backup_required' => (bool) ((require $root.'/config/nexora-upgrade.php')['require_backup'] ?? true),
        'restore_readiness_required' => (bool) ((require $root.'/config/nexora-upgrade.php')['require_restore_readiness'] ?? true),
        'preexisting_maintenance_blocked' => (bool) ((require $root.'/config/nexora-upgrade.php')['block_preexisting_maintenance'] ?? true),
        'post_upgrade_health_gate' => true,
        'migration_ledger_required' => (bool) ((require $root.'/config/nexora-upgrade.php')['require_migration_ledger'] ?? true),
        'cluster_quiescence_required' => (bool) ((require $root.'/config/nexora-upgrade.php')['require_cluster_quiescence'] ?? true),
        'shared_maintenance_required' => (bool) ((require $root.'/config/nexora-upgrade.php')['cluster_require_shared_maintenance'] ?? true),
        'runtime_activity_quiescence_required' => (bool) ((require $root.'/config/nexora-upgrade.php')['cluster_require_runtime_quiescence'] ?? true),
        'empty_queue_required' => (bool) ((require $root.'/config/nexora-upgrade.php')['cluster_require_empty_queue'] ?? true),
        'destructive_pending_migrations_blocked' => (bool) ((require $root.'/config/nexora-upgrade.php')['block_destructive_pending_migrations'] ?? true),
        'distributed_upgrade_lock_command' => 'php artisan nexora:upgrade:cluster-lock',
        'cluster_status_command' => 'php artisan nexora:upgrade:cluster-status',
        'node_drain_command' => 'php artisan nexora:upgrade:node-status draining --confirm=SET',
        'scheduler_release_command' => 'php artisan nexora:upgrade:scheduler-lease --release --confirm=RELEASE',
        'quiescence_command' => 'php artisan nexora:upgrade:quiescence --wait',
        'mixed_version_runtime_fence' => true,
        'atomic_runtime_admission_barrier' => (bool) ((require $root.'/config/nexora-upgrade.php')['runtime_admission_barrier_required'] ?? true),
        'queue_payload_schema' => (int) ((require $root.'/config/nexora-upgrade.php')['queue_payload_schema'] ?? 6),
        'runtime_activation_epoch_required' => true,
        'runtime_activation_cache_fingerprint_required' => true,
        'queue_restart_signal_before_traffic_restore' => true,
        'queue_payload_metadata_required' => (bool) ((require $root.'/config/nexora-upgrade.php')['queue_payload_require_metadata'] ?? true),
        'queue_payload_exact_version_required' => (bool) ((require $root.'/config/nexora-upgrade.php')['queue_payload_require_exact_version'] ?? true),
        'cutover_status_command' => 'php artisan nexora:upgrade:cutover-status',
        'automatic_database_rollback' => false,
        'signed_release_admission_required' => true,
        'recipient_trust_anchor_required' => true,
        'admission_command' => 'php scripts/trusted-update-admit.php --production=<zip> --evidence=<zip> --seal=<json> --signature=<sig> --public-key=<pem>',
        'staging_command' => 'php scripts/trusted-update-stage.php --production=<zip> --destination=<empty-dir>',
        'cleanup_command' => 'php scripts/trusted-update-cleanup.php --target=<managed-stage-dir> --apply --confirm=CLEAN',
        'recovery_status_command' => 'php artisan nexora:upgrade:recovery-status',
        'recovery_decision_command' => 'php artisan nexora:upgrade:recovery-record <decision> --operator=<name> --confirm=RECORD',
        'maintenance_lease_command' => 'php artisan nexora:upgrade:maintenance-lease',
        'lineage_export_command' => 'php artisan nexora:upgrade:lineage --output=<path>',
        'automatic_destructive_recovery' => false,
        'apply_command' => 'php artisan nexora:upgrade:apply --yes',
    ],
    'environment' => [
        'policy_sha256' => $environmentPolicyHash,
        'doctor_command' => 'php artisan nexora:environment:doctor --production',
        'real_environment_packaged' => false,
        'config_cache_must_follow_environment' => true,
    ],
    'filesystem' => [
        'policy_sha256' => $filesystemPolicyHash,
        'doctor_command' => 'php artisan nexora:filesystem:doctor',
        'atomic_state_publication' => true,
        'case_portability_certified' => true,
    ],
    'transfers' => [
        'policy_sha256' => $transferPolicyHash,
        'doctor_command' => 'php artisan nexora:transfer:doctor',
        'bounded_streaming' => true,
        'archive_expansion_budgets' => true,
        'partial_file_publication_blocked' => true,
    ],
    'runtime_safety' => [
        'policy_sha256' => $runtimePolicyHash,
        'doctor_command' => 'php artisan nexora:runtime:doctor',
        'request_body_ceiling_enforced' => true,
        'explicit_trusted_proxies_only' => true,
        'queue_timeout_retry_alignment' => true,
        'graceful_cancellation' => true,
    ],
    'concurrency' => [
        'policy_sha256' => $concurrencyPolicyHash,
        'doctor_command' => 'php artisan nexora:concurrency:doctor',
        'bounded_deadlock_retries' => true,
        'portable_transaction_mutex' => true,
        'external_effect_semantics' => 'at-least-once',
    ],
    'dependencies' => [
        'policy_sha256' => $dependencyPolicyHash,
        'audit_status' => $dependencyAudit['status'] ?? null,
        'provenance_status' => $dependencyProvenance['status'] ?? null,
        'composer_package_count' => $dependencyProvenance['counts']['composer'] ?? null,
        'npm_package_count' => $dependencyProvenance['counts']['npm'] ?? null,
        'deterministic_install' => true,
        'lockfiles_required' => true,
        'production_composer_no_dev' => true,
        'production_vendor_tree_sha256' => $productionVendorFingerprint['sha256'],
        'production_vendor_file_count' => $productionVendorFingerprint['file_count'],
    ],
    'release_policy_sha256' => $releasePolicyHash,
    'certification_evidence_policy_sha256' => $evidencePolicyHash,
    'release_trust_policy_sha256' => $releaseTrustPolicyHash,
    'update_trust_policy_sha256' => $updateTrustPolicyHash,
    'signing' => ['key_id'=>$releaseAnchor['key_id'],'public_key_sha256'=>$releaseAnchor['public_key_sha256'],'trust_anchor_sha256'=>$releaseAnchorHash],
    'supply_chain_policy_sha256' => hash_file('sha256',$root.'/config/nexora-supply-chain.php'),
    'release_inputs' => $releaseInputs,
    'artifacts' => [
        'composer_lock_sha256' => hash_file('sha256', $root.'/composer.lock'),
        'package_lock_sha256' => hash_file('sha256', $root.'/package-lock.json'),
        'frontend_manifest_sha256' => hash_file('sha256', $root.'/public/build/manifest.json'),
        'certified_toolchain_sha256' => $toolchainHash,
        'dependency_sbom_sha256' => $dependencySbomHash,
        'production_dependencies_sha256' => $productionDependenciesHash,
        'release_provenance_sha256' => $releaseProvenanceHash,
        'production_vendor_tree_sha256' => $productionVendorFingerprint['sha256'],
    ],
];
$zip->addFromString('nexora-release.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
$zip->close();

// Add a deterministic per-entry content manifest after all production payload entries exist.
$contentZip=new ZipArchive();if($contentZip->open($target)!==true){@unlink($target);fwrite(STDERR,"[Nexora Release] Unable to reopen archive for content manifest.\n");exit(1);}
$contentEntries=[];for($i=0;$i<$contentZip->numFiles;$i++){$name=(string)$contentZip->getNameIndex($i);$data=$contentZip->getFromIndex($i);if(!is_string($data)){$contentZip->close();@unlink($target);fwrite(STDERR,"[Nexora Release] Unable to read archive entry for content manifest [{$name}].\n");exit(1);}$contentEntries[$name]=['sha256'=>hash('sha256',$data),'size'=>strlen($data)];}ksort($contentEntries,SORT_STRING);$contentManifest=['schema'=>1,'algorithm'=>'sha256','platform_version'=>$version,'source_tree_sha256'=>$currentSourceAttestation['tree_sha256'],'entry_count'=>count($contentEntries),'entries'=>$contentEntries];$contentZip->addFromString('nexora-content-manifest.json',json_encode($contentManifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);$contentZip->close();

// Freeze/revalidate all release inputs after archive construction so concurrent evidence/source drift cannot produce a mixed artifact.
$currentInputs = [
    'source_tree_sha256'=>nexoraComputeSourceAttestation($root)['tree_sha256'],
    'certification_sha256'=>hash_file('sha256',$certificationPath),
    'performance_sha256'=>hash_file('sha256',$performancePath),
    'final_evidence_sha256'=>hash_file('sha256',$finalEvidencePath),
    'dependency_audit_sha256'=>hash_file('sha256',$dependencyAuditPath),
    'dependency_provenance_sha256'=>hash_file('sha256',$dependencyProvenancePath),
    'composer_lock_sha256'=>hash_file('sha256',$root.'/composer.lock'),
    'package_lock_sha256'=>hash_file('sha256',$root.'/package-lock.json'),
    'frontend_manifest_sha256'=>hash_file('sha256',$root.'/public/build/manifest.json'),
    'certified_toolchain_sha256'=>hash_file('sha256',$toolchainPath),
    'release_trust_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-release-trust.php'),
    'update_trust_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-update-trust.php'),
    'upgrade_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-upgrade.php'),
    'activation_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-activation.php'),
    'engine_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-engine.php'),
    'database_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-database-runtime.php'),
    'storage_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-storage-runtime.php'),
    'network_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-network-runtime.php'),
    'host_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-host-runtime.php'),
    'resource_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-resource-runtime.php'),
    'policy_plane_sha256'=>hash_file('sha256',$root.'/config/nexora-policy-runtime.php'),
    'process_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-process-runtime.php'),
    'framework_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-framework.php'),
    'dependency_sbom_sha256'=>hash_file('sha256',$dependencySbomPath),
    'production_dependencies_sha256'=>hash_file('sha256',$productionDependenciesPath),
    'release_provenance_sha256'=>hash_file('sha256',$releaseProvenancePath),
    'production_vendor_tree_sha256'=>nexoraDirectoryContentFingerprint($stageVendor)['sha256'],
    'release_trust_anchor_sha256'=>hash_file('sha256',nexoraReleaseTrustAnchorPath($root)),
];
if ($currentInputs !== $releaseInputs) { @unlink($target); fwrite(STDERR, "[Nexora Release] Release inputs changed while the production archive was being built; artifact discarded. Re-run final certification.\n"); exit(1); }

// Reopen the artifact so a truncated/corrupt ZIP never gets reported as a release.
$verify = new ZipArchive();
if ($verify->open($target, ZipArchive::RDONLY) !== true) {
    @unlink($target);
    fwrite(STDERR, "[Nexora Release] Created ZIP could not be reopened for integrity verification.\n");
    exit(1);
}
foreach ($requiredArchiveEntries as $requiredEntry) {
    if ($verify->locateName((string) $requiredEntry) === false) {
        $verify->close();
        @unlink($target);
        fwrite(STDERR, "[Nexora Release] Production artifact is missing required entry [{$requiredEntry}].\n");
        exit(1);
    }
}
for ($index = 0; $index < $verify->numFiles; $index++) {
    $entry = (string) $verify->getNameIndex($index);
    if (in_array($entry, $forbiddenArchiveEntries, true)) {
        $verify->close();
        @unlink($target);
        fwrite(STDERR, "[Nexora Release] Production artifact contains forbidden entry [{$entry}].\n");
        exit(1);
    }
    foreach ($forbiddenArchivePrefixes as $prefix) {
        if (str_starts_with($entry, (string) $prefix)) {
            $verify->close();
            @unlink($target);
            fwrite(STDERR, "[Nexora Release] Production artifact contains forbidden prefix [{$prefix}] via [{$entry}].\n");
            exit(1);
        }
    }
}
foreach(nexoraValidateReleaseContentManifest($verify) as $contentError){$verify->close();@unlink($target);fwrite(STDERR,"[Nexora Release] Content manifest failed: {$contentError}\n");exit(1);}
$verify->close();

$hygiene=nexoraValidateZipArchiveHygiene($root,$target);if($hygiene!==[]){@unlink($target);fwrite(STDERR,"[Nexora Release] Archive hygiene failed: ".implode('; ',$hygiene)."\n");exit(1);}

$hash = hash_file('sha256', $target);
if (! is_string($hash) || $hash === '') {
    @unlink($target);
    fwrite(STDERR, "[Nexora Release] Unable to hash production artifact.\n");
    exit(1);
}
file_put_contents($target.'.sha256', $hash.'  '.basename($target).PHP_EOL);
$seal=nexoraBuildFinalReleaseSeal($root,$target);if(!$seal['ok']){@unlink($target);@unlink($target.'.sha256');$sealPaths=nexoraFinalReleaseSealPaths($root);foreach($sealPaths as $sealPath)@unlink($sealPath);fwrite(STDERR,"[Nexora Release] Final release seal failed: ".implode('; ',$seal['errors'])."\n");exit(1);}

$sealPaths=nexoraFinalReleaseSealPaths($root);fwrite(STDOUT, "[Nexora Release] Created {$target}\nSHA-256: {$hash}\nEvidence bundle SHA-256: {$seal['bundle_sha256']}\nRelease signature SHA-256: ".($seal['signature_sha256']??'unsigned')."\nFinal release seal: {$sealPaths['seal']}\nRelease signature: {$sealPaths['signature']}\nRelease public key: {$sealPaths['public_key']}\n");
