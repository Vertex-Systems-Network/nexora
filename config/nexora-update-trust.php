<?php

declare(strict_types=1);

$get = static function (string $name, mixed $default=null): mixed {
    if (function_exists('env')) return env($name,$default);
    $value=getenv($name); return $value===false||$value===''?$default:$value;
};

return [
    // Recipient-side trust is intentionally independent from the release builder's
    // runtime signing key. A trusted anchor must be imported out-of-band.
    'trusted_anchor_path' => $get('NEXORA_UPDATE_TRUST_ANCHOR_PATH', dirname(__DIR__).'/storage/app/nexora/update-trust/trusted-anchor.json'),
    'trust_history_path' => $get('NEXORA_UPDATE_TRUST_HISTORY_PATH', dirname(__DIR__).'/storage/app/nexora/update-trust/trust-history'),
    'admission_path' => $get('NEXORA_UPDATE_ADMISSION_PATH', dirname(__DIR__).'/storage/app/nexora/update-trust/admission.json'),
    'admission_ttl_minutes' => max(15, (int) $get('NEXORA_UPDATE_ADMISSION_TTL_MINUTES', 180)),
    'managed_staging_root' => $get('NEXORA_UPDATE_MANAGED_STAGING_ROOT', dirname(__DIR__).'/storage/app/nexora/update-trust/staging'),
    'stage_records_path' => $get('NEXORA_UPDATE_STAGE_RECORDS_PATH', dirname(__DIR__).'/storage/app/nexora/update-trust/stage-records'),
    'quarantine_ttl_hours' => max(1, (int) $get('NEXORA_UPDATE_QUARANTINE_TTL_HOURS', 168)),
    'require_signed_release' => filter_var($get('NEXORA_UPDATE_REQUIRE_SIGNED_RELEASE', true), FILTER_VALIDATE_BOOL),
    'require_monotonic_version' => filter_var($get('NEXORA_UPDATE_REQUIRE_MONOTONIC_VERSION', true), FILTER_VALIDATE_BOOL),
    'require_exact_source_after_deploy' => filter_var($get('NEXORA_UPDATE_REQUIRE_EXACT_SOURCE', true), FILTER_VALIDATE_BOOL),
    'allow_reinstall_same_version' => filter_var($get('NEXORA_UPDATE_ALLOW_REINSTALL', false), FILTER_VALIDATE_BOOL),
    'clock_skew_seconds' => max(0, (int) $get('NEXORA_UPDATE_CLOCK_SKEW_SECONDS', 300)),
    // Certification candidates break the C4-before-C6 ordering cycle without creating a production bypass.
    'allow_certification_candidate' => filter_var($get('NEXORA_CERTIFICATION_UPGRADE_REHEARSAL', false), FILTER_VALIDATE_BOOL),
    'certification_candidate_ttl_minutes' => max(15, (int) $get('NEXORA_CERTIFICATION_UPDATE_CANDIDATE_TTL_MINUTES', 180)),
    'certification_safe_database_prefixes' => ['nexora_test','nexora_testing','nexora_cert','nexora_certification'],
];
