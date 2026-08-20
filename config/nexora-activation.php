<?php

declare(strict_types=1);

$get=static function(string $name,mixed $default=null):mixed{
    if(function_exists('env')) return env($name,$default);
    $value=getenv($name);return $value===false||$value===''?$default:$value;
};

return [
    'schema'=>1,
    'epoch_path'=>$get('NEXORA_ACTIVATION_EPOCH_PATH',dirname(__DIR__).'/storage/app/nexora/runtime/activation-epoch.json'),
    'history_path'=>$get('NEXORA_ACTIVATION_HISTORY_PATH',dirname(__DIR__).'/storage/app/nexora/runtime/activation-history'),
    'require_installed_match'=>filter_var($get('NEXORA_ACTIVATION_REQUIRE_INSTALLED_MATCH',true),FILTER_VALIDATE_BOOL),
    'require_process_epoch_match'=>filter_var($get('NEXORA_ACTIVATION_REQUIRE_PROCESS_EPOCH_MATCH',true),FILTER_VALIDATE_BOOL),
    'queue_payload_schema'=>max(13,(int)$get('NEXORA_QUEUE_PAYLOAD_SCHEMA',13)),
    'require_exact_queue_activation'=>filter_var($get('NEXORA_QUEUE_REQUIRE_EXACT_ACTIVATION',true),FILTER_VALIDATE_BOOL),
    'require_maintenance_for_manual_rotation'=>filter_var($get('NEXORA_ACTIVATION_REQUIRE_MAINTENANCE',true),FILTER_VALIDATE_BOOL),
    'opcache_revalidate_max_seconds'=>max(0,(int)$get('NEXORA_OPCACHE_REVALIDATE_MAX_SECONDS',2)),
    'deep_status_required_for_c4'=>filter_var($get('NEXORA_ACTIVATION_DEEP_STATUS_REQUIRED',true),FILTER_VALIDATE_BOOL),
];
