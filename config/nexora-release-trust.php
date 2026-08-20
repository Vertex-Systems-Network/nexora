<?php

declare(strict_types=1);

$bool = static function (string $name, bool $default): bool {
    $raw = getenv($name);
    if (! is_string($raw) || trim($raw) === '') return $default;
    return filter_var($raw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
};
$int = static function (string $name, int $default, int $minimum): int {
    $raw = getenv($name);
    return max($minimum, is_string($raw) && trim($raw) !== '' ? (int) $raw : $default);
};

return [
    'signature_required' => $bool('NEXORA_RELEASE_SIGNATURE_REQUIRED', true),
    'signature_algorithm' => 'sha256WithRSA',
    'rsa_bits' => $int('NEXORA_RELEASE_RSA_BITS', 3072, 2048),
    // Runtime-only key material. These paths are excluded from source/customer packages.
    'private_key_path' => getenv('NEXORA_RELEASE_PRIVATE_KEY_FILE') ?: 'storage/app/nexora/release-signing/private.pem',
    'public_key_path' => getenv('NEXORA_RELEASE_PUBLIC_KEY_FILE') ?: 'storage/app/nexora/release-signing/public.pem',
    'trust_anchor_path' => 'storage/app/nexora/release-signing/trust-anchor.json',
    'external_identity_anchor_required' => true,
    'archive' => [
        'max_entries' => $int('NEXORA_RELEASE_MAX_ARCHIVE_ENTRIES', 20000, 100),
        'max_entry_bytes' => $int('NEXORA_RELEASE_MAX_ENTRY_BYTES', 268435456, 1048576),
        'max_total_uncompressed_bytes' => $int('NEXORA_RELEASE_MAX_UNCOMPRESSED_BYTES', 2147483648, 67108864),
        'max_compression_ratio' => $int('NEXORA_RELEASE_MAX_COMPRESSION_RATIO', 250, 10),
        'reject_case_collisions' => true,
        'reject_symlinks' => true,
        'reject_unsafe_paths' => true,
    ],
];
