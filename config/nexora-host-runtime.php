<?php

declare(strict_types=1);

$get=static function(string $name,mixed $default=null):mixed{
    if(function_exists('env')) return env($name,$default);
    $value=getenv($name);return $value===false||$value===''?$default:$value;
};
$csv=static fn(string $value):array=>array_values(array_filter(array_map('trim',explode(',',$value)),static fn(string $v):bool=>$v!==''));

return [
    'schema'=>2,
    'require_exact_host_profile'=>filter_var($get('NEXORA_HOST_REQUIRE_EXACT_PROFILE',true),FILTER_VALIDATE_BOOL),
    'required_timezone'=>(string)$get('NEXORA_HOST_REQUIRED_TIMEZONE','UTC'),
    'require_runtime_timezone_match'=>filter_var($get('NEXORA_HOST_REQUIRE_TIMEZONE_MATCH',true),FILTER_VALIDATE_BOOL),
    'require_intl_locale_match'=>filter_var($get('NEXORA_HOST_REQUIRE_INTL_LOCALE_MATCH',true),FILTER_VALIDATE_BOOL),
    'require_monotonic_clock'=>filter_var($get('NEXORA_HOST_REQUIRE_MONOTONIC_CLOCK',true),FILTER_VALIDATE_BOOL),
    'require_database_clock_anchor'=>filter_var($get('NEXORA_HOST_REQUIRE_DATABASE_CLOCK_ANCHOR',true),FILTER_VALIDATE_BOOL),
    'max_database_clock_skew_ms'=>max(250,min(60000,(int)$get('NEXORA_HOST_MAX_DB_CLOCK_SKEW_MS',5000))),
    'queue_future_skew_seconds'=>max(5,min(3600,(int)$get('NEXORA_QUEUE_MAX_FUTURE_SKEW_SECONDS',300))),
    'queue_payload_schema'=>max(13,(int)$get('NEXORA_QUEUE_PAYLOAD_SCHEMA',13)),
    'allowed_umasks'=>$csv((string)$get('NEXORA_HOST_ALLOWED_UMASKS','0022,0027')),
    'require_temp_writable'=>filter_var($get('NEXORA_HOST_REQUIRE_TEMP_WRITABLE',true),FILTER_VALIDATE_BOOL),
    'require_atomic_rename'=>filter_var($get('NEXORA_HOST_REQUIRE_ATOMIC_RENAME',true),FILTER_VALIDATE_BOOL),
    'require_flock'=>filter_var($get('NEXORA_HOST_REQUIRE_FLOCK',true),FILTER_VALIDATE_BOOL),
    'require_secure_random'=>filter_var($get('NEXORA_HOST_REQUIRE_SECURE_RANDOM',true),FILTER_VALIDATE_BOOL),
    'deep_status_required_for_c2'=>filter_var($get('NEXORA_HOST_DEEP_STATUS_REQUIRED_C2',true),FILTER_VALIDATE_BOOL),
    'deep_status_required_for_c4'=>filter_var($get('NEXORA_HOST_DEEP_STATUS_REQUIRED_C4',true),FILTER_VALIDATE_BOOL),
    'installation'=>[
        // Installation must reject materially unsafe clocks, but it does not
        // require every production/HA portability check to be certification-PASS.
        'require_database_clock_anchor'=>filter_var($get('NEXORA_INSTALL_REQUIRE_DATABASE_CLOCK_ANCHOR',true),FILTER_VALIDATE_BOOL),
        'max_database_clock_skew_ms'=>max(5000,min(300000,(int)$get('NEXORA_INSTALL_MAX_DB_CLOCK_SKEW_MS',60000))),
        'temp_directory'=>(string)$get('NEXORA_INSTALL_TEMP_DIRECTORY',''),
    ],
    'ha'=>[
        'require_host_convergence'=>filter_var($get('NEXORA_HA_REQUIRE_HOST_CONVERGENCE',true),FILTER_VALIDATE_BOOL),
        'require_clock_evidence'=>filter_var($get('NEXORA_HA_REQUIRE_CLOCK_EVIDENCE',true),FILTER_VALIDATE_BOOL),
    ],
];
