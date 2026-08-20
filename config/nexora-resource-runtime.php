<?php

declare(strict_types=1);

$get=static function(string $name,mixed $default=null):mixed{
    if(function_exists('env')) return env($name,$default);
    $value=getenv($name); return $value===false||$value===''?$default:$value;
};

return [
    'require_exact_resource_policy'=>filter_var($get('NEXORA_RESOURCE_REQUIRE_EXACT_POLICY',true),FILTER_VALIDATE_BOOL),
    'require_deep_capacity_for_upgrade'=>filter_var($get('NEXORA_RESOURCE_REQUIRE_DEEP_UPGRADE_CAPACITY',true),FILTER_VALIDATE_BOOL),
    'require_deep_capacity_for_ha'=>filter_var($get('NEXORA_RESOURCE_REQUIRE_DEEP_HA_CAPACITY',true),FILTER_VALIDATE_BOOL),
    'minimum_memory_headroom_bytes'=>max(33_554_432,(int)$get('NEXORA_RESOURCE_MIN_MEMORY_HEADROOM_BYTES',134_217_728)),
    'installation_minimum_memory_headroom_bytes'=>max(16_777_216,(int)$get('NEXORA_INSTALL_RESOURCE_MIN_MEMORY_HEADROOM_BYTES',33_554_432)),
    'installation_minimum_temp_free_bytes'=>max(33_554_432,(int)$get('NEXORA_INSTALL_RESOURCE_MIN_TEMP_FREE_BYTES',67_108_864)),
    'installation_minimum_storage_free_bytes'=>max(67_108_864,(int)$get('NEXORA_INSTALL_RESOURCE_MIN_STORAGE_FREE_BYTES',134_217_728)),
    'installation_minimum_bootstrap_free_bytes'=>max(33_554_432,(int)$get('NEXORA_INSTALL_RESOURCE_MIN_BOOTSTRAP_FREE_BYTES',67_108_864)),
    'minimum_queue_memory_headroom_bytes'=>max(16_777_216,(int)$get('NEXORA_RESOURCE_MIN_QUEUE_MEMORY_HEADROOM_BYTES',67_108_864)),
    'minimum_temp_free_bytes'=>max(67_108_864,(int)$get('NEXORA_RESOURCE_MIN_TEMP_FREE_BYTES',536_870_912)),
    'minimum_storage_free_bytes'=>max(134_217_728,(int)$get('NEXORA_RESOURCE_MIN_STORAGE_FREE_BYTES',1_073_741_824)),
    'minimum_transfer_free_bytes'=>max(134_217_728,(int)$get('NEXORA_RESOURCE_MIN_TRANSFER_FREE_BYTES',1_073_741_824)),
    'minimum_bootstrap_free_bytes'=>max(67_108_864,(int)$get('NEXORA_RESOURCE_MIN_BOOTSTRAP_FREE_BYTES',268_435_456)),
    'minimum_backup_staging_free_bytes'=>max(268_435_456,(int)$get('NEXORA_RESOURCE_MIN_BACKUP_STAGING_FREE_BYTES',1_073_741_824)),
    'minimum_open_files_soft'=>max(256,(int)$get('NEXORA_RESOURCE_MIN_OPEN_FILES_SOFT',1024)),
    'require_open_files_observation_on_posix'=>filter_var($get('NEXORA_RESOURCE_REQUIRE_OPEN_FILES_OBSERVATION_POSIX',false),FILTER_VALIDATE_BOOL),
    'queue_payload_schema'=>max(13,(int)$get('NEXORA_QUEUE_PAYLOAD_SCHEMA',13)),
];
