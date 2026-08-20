<?php

declare(strict_types=1);

namespace App\Nexora\Security\SupplyChain\Services;

use App\Models\TrustedPublisher;

final readonly class SignatureVerifier
{
    public function __construct(private PackageJsonReader $json) {}

    /** @return array{status:string,publisher:?TrustedPublisher,error:?string,payload:?array<string,mixed>} */
    public function verify(string $zipPath, string $contentSha256): array
    {
        $payload = $this->json->read($zipPath, 'nexora.signature.json', 262_144);
        if (! is_array($payload)) return ['status'=>'missing','publisher'=>null,'error'=>null,'payload'=>null];

        $algorithm = strtolower((string) ($payload['algorithm'] ?? ''));
        $keyId = trim((string) ($payload['key_id'] ?? ''));
        $declaredDigest = strtolower((string) ($payload['content_sha256'] ?? ''));
        $signature = (string) ($payload['signature'] ?? '');
        if ($algorithm !== 'ed25519' || $keyId === '' || preg_match('/^[a-f0-9]{64}$/', $declaredDigest) !== 1 || $signature === '') {
            return ['status'=>'invalid','publisher'=>null,'error'=>'The detached signature manifest is incomplete or uses an unsupported algorithm.','payload'=>$payload];
        }
        if (! hash_equals($contentSha256, $declaredDigest)) {
            return ['status'=>'invalid','publisher'=>null,'error'=>'The signed content digest does not match the current package contents.','payload'=>$payload];
        }
        $publisher = TrustedPublisher::query()->where('key_id', $keyId)->where('status','active')->first();
        if (! $publisher) return ['status'=>'untrusted','publisher'=>null,'error'=>'No active trusted publisher key matches the package key_id.','payload'=>$payload];
        if (! extension_loaded('sodium') || ! function_exists('sodium_crypto_sign_verify_detached')) {
            return ['status'=>'unavailable','publisher'=>$publisher,'error'=>'PHP Sodium is required to verify Ed25519 package signatures.','payload'=>$payload];
        }
        $publicKey = base64_decode((string) $publisher->public_key, true);
        $signatureBytes = base64_decode($signature, true);
        $digestBytes = hex2bin($contentSha256);
        if (! is_string($publicKey) || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || ! is_string($signatureBytes) || strlen($signatureBytes) !== SODIUM_CRYPTO_SIGN_BYTES || ! is_string($digestBytes)) {
            return ['status'=>'invalid','publisher'=>$publisher,'error'=>'The publisher key or detached signature encoding is invalid.','payload'=>$payload];
        }
        $verified = sodium_crypto_sign_verify_detached($signatureBytes, $digestBytes, $publicKey);
        return $verified
            ? ['status'=>'verified','publisher'=>$publisher,'error'=>null,'payload'=>$payload]
            : ['status'=>'invalid','publisher'=>$publisher,'error'=>'Ed25519 signature verification failed.','payload'=>$payload];
    }
}
