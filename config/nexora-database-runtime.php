<?php

declare(strict_types=1);

$get=static function(string $name,mixed $default=null):mixed{
    if(function_exists('env')) return env($name,$default);
    $value=getenv($name);return $value===false||$value===''?$default:$value;
};

return [
    'schema'=>1,
    'require_exact_data_plane'=>filter_var($get('NEXORA_DB_REQUIRE_EXACT_DATA_PLANE',true),FILTER_VALIDATE_BOOL),
    'require_exact_server_version'=>filter_var($get('NEXORA_DB_REQUIRE_EXACT_SERVER_VERSION',true),FILTER_VALIDATE_BOOL),
    'require_exact_session_profile'=>filter_var($get('NEXORA_DB_REQUIRE_EXACT_SESSION_PROFILE',true),FILTER_VALIDATE_BOOL),
    'require_schema_attestation'=>filter_var($get('NEXORA_DB_REQUIRE_SCHEMA_ATTESTATION',true),FILTER_VALIDATE_BOOL),
    'require_backup_schema_binding'=>filter_var($get('NEXORA_DB_REQUIRE_BACKUP_SCHEMA_BINDING',true),FILTER_VALIDATE_BOOL),
    'queue_payload_schema'=>max(13,(int)$get('NEXORA_QUEUE_PAYLOAD_SCHEMA',13)),
    'deep_status_required_for_c2'=>filter_var($get('NEXORA_DB_DEEP_STATUS_REQUIRED_C2',true),FILTER_VALIDATE_BOOL),
    'deep_status_required_for_c4'=>filter_var($get('NEXORA_DB_DEEP_STATUS_REQUIRED_C4',true),FILTER_VALIDATE_BOOL),
    'schema_include_views'=>filter_var($get('NEXORA_DB_SCHEMA_INCLUDE_VIEWS',true),FILTER_VALIDATE_BOOL),
];
