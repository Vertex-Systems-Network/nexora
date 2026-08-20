<?php

declare(strict_types=1);

$get=static function(string $name,mixed $default=null):mixed{
    if(function_exists('env')) return env($name,$default);
    $value=getenv($name); return $value===false||$value===''?$default:$value;
};

return [
    'schema'=>1,
    'require_exact_policy_plane'=>filter_var($get('NEXORA_POLICY_REQUIRE_EXACT_PLANE',true),FILTER_VALIDATE_BOOL),
    'production_fail_closed'=>filter_var($get('NEXORA_POLICY_PRODUCTION_FAIL_CLOSED',true),FILTER_VALIDATE_BOOL),
    'queue_payload_schema'=>max(13,(int)$get('NEXORA_QUEUE_PAYLOAD_SCHEMA',13)),
    'deep_status_required_for_c2'=>filter_var($get('NEXORA_POLICY_DEEP_STATUS_REQUIRED_C2',true),FILTER_VALIDATE_BOOL),
    'deep_status_required_for_c4'=>filter_var($get('NEXORA_POLICY_DEEP_STATUS_REQUIRED_C4',true),FILTER_VALIDATE_BOOL),
    'ha'=>[
        'require_policy_convergence'=>filter_var($get('NEXORA_HA_REQUIRE_POLICY_CONVERGENCE',true),FILTER_VALIDATE_BOOL),
    ],
];
