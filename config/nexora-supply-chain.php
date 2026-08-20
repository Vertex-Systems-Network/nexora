<?php

declare(strict_types=1);

$bool = static function (string $name, bool $default): bool {
    $raw=getenv($name);if(!is_string($raw)||trim($raw)==='')return $default;
    return filter_var($raw,FILTER_VALIDATE_BOOL,FILTER_NULL_ON_FAILURE) ?? $default;
};
$int = static function (string $name, int $default, int $minimum): int {
    $raw=getenv($name);return max($minimum,is_string($raw)&&trim($raw)!==''?(int)$raw:$default);
};
return [
    'sbom' => [
        'required' => $bool('NEXORA_RELEASE_SBOM_REQUIRED', true),
        'format' => 'CycloneDX',
        'spec_version' => '1.5',
        'max_components' => $int('NEXORA_RELEASE_SBOM_MAX_COMPONENTS', 20000, 100),
    ],
    'dependency_candidate' => [
        'require_https' => true,
        'composer_allowed_hosts' => [
            'repo.packagist.org',
            'api.github.com',
            'github.com',
            'codeload.github.com',
            'objects.githubusercontent.com',
        ],
        'npm_allowed_hosts' => ['registry.npmjs.org'],
        'composer_audit_required' => true,
        'npm_audit_required' => true,
        'npm_audit_level' => 'high',
    ],
    'production_dependencies' => [
        'composer_no_dev_required' => true,
        'composer_no_scripts_required' => true,
        'stage_dir' => 'storage/app/nexora/release-stage/php-runtime',
    ],
    'provenance' => [
        'required' => true,
        'schema' => 1,
    ],
    'content_manifest' => [
        'required' => true,
        'algorithm' => 'sha256',
    ],
    'offline_identity' => [
        // A release-bundled public key proves integrity, not identity. Strict verification
        // therefore requires an out-of-band expected fingerprint or trust-anchor JSON.
        'external_anchor_required' => true,
    ],
];
