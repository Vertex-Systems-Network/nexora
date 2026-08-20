<?php

declare(strict_types=1);

$get=static function(string $name,mixed $default=null):mixed{
    if(function_exists('env')) return env($name,$default);
    $value=getenv($name); return $value===false||$value===''?$default:$value;
};

return [
    'schema'=>1,
    'require_exact_process_policy'=>filter_var($get('NEXORA_PROCESS_REQUIRE_EXACT_POLICY',true),FILTER_VALIDATE_BOOL),
    'lease_seconds'=>max(60,min(900,(int)$get('NEXORA_PROCESS_LEASE_SECONDS',180))),
    'heartbeat_throttle_seconds'=>max(5,min(120,(int)$get('NEXORA_PROCESS_HEARTBEAT_THROTTLE_SECONDS',30))),
    'minimum_web_nodes'=>max(1,(int)$get('NEXORA_PROCESS_MIN_WEB_NODES',2)),
    'minimum_queue_nodes'=>max(1,(int)$get('NEXORA_PROCESS_MIN_QUEUE_NODES',2)),
    'minimum_scheduler_nodes'=>max(1,(int)$get('NEXORA_PROCESS_MIN_SCHEDULER_NODES',1)),
    'require_web_for_ha'=>filter_var($get('NEXORA_PROCESS_REQUIRE_WEB_HA',true),FILTER_VALIDATE_BOOL),
    'require_queue_for_async'=>filter_var($get('NEXORA_PROCESS_REQUIRE_QUEUE_ASYNC',true),FILTER_VALIDATE_BOOL),
    'require_scheduler_for_ha'=>filter_var($get('NEXORA_PROCESS_REQUIRE_SCHEDULER_HA',true),FILTER_VALIDATE_BOOL),
    'reject_indefinite_queue_blocking_for_ha'=>filter_var($get('NEXORA_PROCESS_REJECT_INDEFINITE_QUEUE_BLOCKING_HA',true),FILTER_VALIDATE_BOOL),
    'queue_max_block_seconds'=>max(1,min(120,(int)$get('NEXORA_PROCESS_QUEUE_MAX_BLOCK_SECONDS',30))),
    'queue_payload_schema'=>max(13,(int)$get('NEXORA_QUEUE_PAYLOAD_SCHEMA',13)),
];
