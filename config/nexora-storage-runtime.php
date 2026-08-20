<?php

declare(strict_types=1);

$get=static function(string $name,mixed $default=null):mixed{
    if(function_exists('env')) return env($name,$default);
    $value=getenv($name); return $value===false||$value===''?$default:$value;
};

return [
    'require_exact_data_plane'=>filter_var($get('NEXORA_STORAGE_REQUIRE_EXACT_DATA_PLANE',true),FILTER_VALIDATE_BOOL),
    'require_shared_for_ha'=>filter_var($get('NEXORA_STORAGE_REQUIRE_SHARED_FOR_HA',true),FILTER_VALIDATE_BOOL),
    'require_backup_shared_for_ha'=>filter_var($get('NEXORA_BACKUP_STORAGE_REQUIRE_SHARED_FOR_HA',true),FILTER_VALIDATE_BOOL),
    'require_public_link_if_local'=>filter_var($get('NEXORA_STORAGE_REQUIRE_PUBLIC_LINK_IF_LOCAL',true),FILTER_VALIDATE_BOOL),
    'media_disk'=>(string)$get('NEXORA_MEDIA_STORAGE_DISK','public'),
    'backup_disk'=>(string)$get('NEXORA_BACKUP_STORAGE_DISK',(string)$get('NEXORA_OBJECT_STORAGE_DISK',(string)$get('FILESYSTEM_DISK','local'))),
    'object_disk'=>(string)$get('NEXORA_OBJECT_STORAGE_DISK',(string)$get('FILESYSTEM_DISK','local')),
    'namespace'=>trim((string)$get('NEXORA_STORAGE_NAMESPACE','nexora'),' /\\') ?: 'nexora',
    'cluster_id'=>(string)$get('NEXORA_STORAGE_CLUSTER_ID',''),
    'shared_drivers'=>array_values(array_filter(array_map('trim',explode(',',(string)$get('NEXORA_HA_SHARED_STORAGE_DRIVERS','s3'))))),
    'deep_probe_prefix'=>trim((string)$get('NEXORA_STORAGE_PROBE_PREFIX','nexora/runtime/storage-probes'),' /\\'),
    'queue_payload_schema'=>max(13,(int)$get('NEXORA_QUEUE_PAYLOAD_SCHEMA',13)),
];
