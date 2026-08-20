<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/n1-certification-session.php';
require_once $root.'/scripts/lib/release-trust-anchor.php';
require_once $root.'/scripts/lib/production-dependency-stage.php';

$fail = static function (string $message): never {
    fwrite(STDERR, "[Nexora Release Provenance] FAIL — {$message}\n");
    exit(1);
};

$requiredFiles = [
    'composer.lock',
    'package-lock.json',
    'storage/app/nexora/certification/toolchain.json',
    'storage/app/nexora/certification/dependency-sbom.json',
    'storage/app/nexora/certification/production-dependencies.json',
    'storage/app/nexora/certification/final-evidence.json',
];

foreach ($requiredFiles as $relativePath) {
    if (! is_file($root.'/'.$relativePath)) {
        $fail("missing {$relativePath}");
    }
}

$session = nexoraCertificationSessionRead($root);
if (! is_array($session) || nexoraValidateCertificationSession($root, $session) !== []) {
    $fail('active certification session invalid');
}

$anchor = nexoraReleaseTrustAnchorRead($root);
if (! is_array($anchor) || nexoraValidateReleaseTrustAnchor($root, $anchor) !== []) {
    $fail('release trust anchor invalid');
}

$source = nexoraComputeSourceAttestation($root);
$hash = static function (string $path): ?string {
    if (! is_file($path)) {
        return null;
    }

    $digest = hash_file('sha256', $path);

    return is_string($digest) && $digest !== '' ? $digest : null;
};

$materials = [
    'composer_lock_sha256' => $hash($root.'/composer.lock'),
    'package_lock_sha256' => $hash($root.'/package-lock.json'),
    'reviewed_locks_sha256' => $hash(
        $root.'/storage/app/nexora/dependency-intake/reviewed-locks.json',
    ),
    'dependency_sbom_sha256' => $hash(
        $root.'/storage/app/nexora/certification/dependency-sbom.json',
    ),
    'production_dependencies_sha256' => $hash(
        $root.'/storage/app/nexora/certification/production-dependencies.json',
    ),
    'final_evidence_sha256' => $hash(
        $root.'/storage/app/nexora/certification/final-evidence.json',
    ),
    'network_policy_sha256' => $hash($root.'/config/nexora-network-runtime.php'),
    'host_policy_sha256' => $hash($root.'/config/nexora-host-runtime.php'),
    'resource_policy_sha256' => $hash($root.'/config/nexora-resource-runtime.php'),
    'policy_plane_sha256' => $hash($root.'/config/nexora-policy-runtime.php'),
    'process_policy_sha256' => $hash($root.'/config/nexora-process-runtime.php'),
    'framework_policy_sha256' => $hash($root.'/config/nexora-framework.php'),
];

$platform = require $root.'/config/nexora.php';
$payload = [
    'schema' => 1,
    'status' => 'prepared',
    'platform_version' => (string) ($platform['version'] ?? 'unknown'),
    'source_tree_sha256' => $source['tree_sha256'],
    'certification_session_id' => $session['session_id'],
    'builder' => [
        'certified_toolchain_sha256' => $hash(
            $root.'/storage/app/nexora/certification/toolchain.json',
        ),
    ],
    'materials' => $materials,
    'signer' => [
        'key_id' => $anchor['key_id'],
        'public_key_sha256' => $anchor['public_key_sha256'],
    ],
    'created_at' => gmdate(DATE_ATOM),
];

$path = $root.'/storage/app/nexora/certification/release-provenance.json';
file_put_contents(
    $path,
    json_encode(
        $payload,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ).PHP_EOL,
);

fwrite(STDOUT, "[Nexora Release Provenance] PASS — {$path}\n");
