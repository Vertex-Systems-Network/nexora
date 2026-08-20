<?php

declare(strict_types=1);

$get=static function(string $name,mixed $default=null):mixed{
    if(function_exists('env')) return env($name,$default);
    $value=getenv($name); return $value===false||$value===''?$default:$value;
};
$csv=static fn(string $value):array=>array_values(array_filter(array_map('trim',explode(',',$value)),static fn(string $v):bool=>$v!==''));

return [
    'require_exact_service_data_plane'=>filter_var($get('NEXORA_SERVICE_REQUIRE_EXACT_DATA_PLANE',true),FILTER_VALIDATE_BOOL),
    'queue_payload_schema'=>max(13,(int)$get('NEXORA_QUEUE_PAYLOAD_SCHEMA',13)),
    'external'=>[
        'require_https'=>filter_var($get('NEXORA_NETWORK_REQUIRE_HTTPS',true),FILTER_VALIDATE_BOOL),
        'block_private_reserved'=>filter_var($get('NEXORA_NETWORK_BLOCK_PRIVATE_RESERVED',true),FILTER_VALIDATE_BOOL),
        'require_dns_resolution'=>filter_var($get('NEXORA_NETWORK_REQUIRE_DNS_RESOLUTION',true),FILTER_VALIDATE_BOOL),
        'require_dns_pin'=>filter_var($get('NEXORA_NETWORK_REQUIRE_DNS_PIN',true),FILTER_VALIDATE_BOOL),
        'allowed_ports'=>array_values(array_unique(array_map('intval',$csv((string)$get('NEXORA_NETWORK_ALLOWED_PORTS','443'))))),
        'blocked_host_suffixes'=>$csv((string)$get('NEXORA_NETWORK_BLOCKED_HOST_SUFFIXES','.localhost,.local,.internal')),
    ],
    'tls'=>[
        'verify_peer'=>filter_var($get('NEXORA_TLS_VERIFY_PEER',true),FILTER_VALIDATE_BOOL),
        'ca_bundle'=>(string)$get('NEXORA_TLS_CA_BUNDLE',''),
    ],
    'deep_probe'=>[
        'cache_roundtrip'=>filter_var($get('NEXORA_SERVICE_PROBE_CACHE',true),FILTER_VALIDATE_BOOL),
        'redis_ping'=>filter_var($get('NEXORA_SERVICE_PROBE_REDIS',true),FILTER_VALIDATE_BOOL),
        'queue_size'=>filter_var($get('NEXORA_SERVICE_PROBE_QUEUE',true),FILTER_VALIDATE_BOOL),
        'mail_dns'=>filter_var($get('NEXORA_SERVICE_PROBE_MAIL_DNS',true),FILTER_VALIDATE_BOOL),
        'mail_tcp'=>filter_var($get('NEXORA_SERVICE_PROBE_MAIL_TCP',false),FILTER_VALIDATE_BOOL),
        'mail_tcp_timeout_seconds'=>max(1,min(15,(int)$get('NEXORA_SERVICE_PROBE_MAIL_TCP_TIMEOUT',3))),
    ],
    'ha'=>[
        'require_service_convergence'=>filter_var($get('NEXORA_HA_REQUIRE_SERVICE_CONVERGENCE',true),FILTER_VALIDATE_BOOL),
    ],
];
