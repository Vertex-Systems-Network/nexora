<?php

declare(strict_types=1);

$get=static function(string $name,mixed $default=null):mixed{
    if(function_exists('env')) return env($name,$default);
    $value=getenv($name);return $value===false||$value===''?$default:$value;
};

return [
    'schema'=>1,
    'require_exact_php_patch'=>filter_var($get('NEXORA_ENGINE_REQUIRE_EXACT_PHP_PATCH',true),FILTER_VALIDATE_BOOL),
    'require_exact_extension_profile'=>filter_var($get('NEXORA_ENGINE_REQUIRE_EXACT_EXTENSIONS',true),FILTER_VALIDATE_BOOL),
    'require_exact_pdo_drivers'=>filter_var($get('NEXORA_ENGINE_REQUIRE_EXACT_PDO_DRIVERS',true),FILTER_VALIDATE_BOOL),
    'required_extensions'=>['fileinfo','mbstring','openssl','pdo','zip'],
    'compatibility_extensions'=>['bcmath','curl','dom','exif','fileinfo','gd','imagick','intl','mbstring','openssl','pdo','redis','simplexml','sodium','xml','zip'],
    'compatibility_ini'=>['default_charset','precision','serialize_precision','zend.assertions'],
    'queue_payload_schema'=>max(13,(int)$get('NEXORA_QUEUE_PAYLOAD_SCHEMA',13)),
    'deep_status_required_for_c4'=>filter_var($get('NEXORA_ENGINE_DEEP_STATUS_REQUIRED',true),FILTER_VALIDATE_BOOL),
];
