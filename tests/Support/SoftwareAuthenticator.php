<?php

declare(strict_types=1);

namespace Tests\Support;

use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * Minimal WebAuthn platform authenticator for tests (ES256, "none" attestation, discoverable).
 * It produces real P-256 keys, CBOR attestation objects and ECDSA signatures, so feature tests
 * exercise the production verification path instead of a mock.
 */
final class SoftwareAuthenticator
{
    public readonly string $credentialId;

    private OpenSSLAsymmetricKey $key;

    private int $counter = 0;

    private readonly string $origin;

    private readonly string $rpId;

    /** Defaults to the application's APP_URL, which is also where Laravel's test client sends requests. */
    public function __construct(?string $origin = null, ?string $rpId = null, ?string $credentialId = null)
    {
        $appUrl = parse_url((string) config('app.url'));
        $host = strtolower((string) ($appUrl['host'] ?? 'localhost'));
        $this->origin = $origin ?? strtolower((string) ($appUrl['scheme'] ?? 'http')).'://'.$host.(isset($appUrl['port']) ? ':'.$appUrl['port'] : '');
        $this->rpId = $rpId ?? $host;
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        if (! $key instanceof OpenSSLAsymmetricKey) {
            throw new RuntimeException('Unable to create a P-256 test key.');
        }
        $this->key = $key;
        $this->credentialId = $credentialId ?? random_bytes(32);
    }

    /** @param array<string,mixed> $publicKey creation options as sent to the browser */
    public function create(array $publicKey, int $flags = 0x45): string
    {
        $details = openssl_pkey_get_details($this->key);
        $cose = self::cborMap([
            [1, 2],                       // kty: EC2
            [3, -7],                      // alg: ES256
            [-1, 1],                      // crv: P-256
            [-2, ['bytes' => str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)]],
            [-3, ['bytes' => str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT)]],
        ]);
        $authData = hash('sha256', $this->rpId, true).chr($flags).pack('N', $this->counter)
            .str_repeat("\0", 16).pack('n', strlen($this->credentialId)).$this->credentialId.$cose;
        $attestationObject = self::cborMap([
            ['fmt', 'none'],
            ['attStmt', ['map' => []]],
            ['authData', ['bytes' => $authData]],
        ]);

        return json_encode([
            'id' => self::b64u($this->credentialId),
            'rawId' => self::b64u($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::b64u($this->clientData('webauthn.create', (string) $publicKey['challenge'])),
                'attestationObject' => self::b64u($attestationObject),
                'transports' => ['internal'],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /** @param array<string,mixed> $publicKey request options as sent to the browser */
    public function get(array $publicKey, string $userHandle, int $flags = 0x05, ?string $origin = null): string
    {
        $this->counter++;
        $authData = hash('sha256', $this->rpId, true).chr($flags).pack('N', $this->counter);
        $clientData = $this->clientData('webauthn.get', (string) $publicKey['challenge'], $origin);
        openssl_sign($authData.hash('sha256', $clientData, true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return json_encode([
            'id' => self::b64u($this->credentialId),
            'rawId' => self::b64u($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::b64u($clientData),
                'authenticatorData' => self::b64u($authData),
                'signature' => self::b64u($signature),
                'userHandle' => self::b64u($userHandle),
            ],
        ], JSON_THROW_ON_ERROR);
    }

    public static function b64u(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function clientData(string $type, string $challenge, ?string $origin = null): string
    {
        return json_encode(['type' => $type, 'challenge' => $challenge, 'origin' => $origin ?? $this->origin, 'crossOrigin' => false], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /** @param list<array{0:int|string,1:mixed}> $pairs */
    private static function cborMap(array $pairs): string
    {
        $out = self::cborHead(5, count($pairs));
        foreach ($pairs as [$key, $value]) {
            $out .= self::cbor($key).self::cbor($value);
        }

        return $out;
    }

    private static function cbor(mixed $value): string
    {
        return match (true) {
            is_int($value) && $value >= 0 => self::cborHead(0, $value),
            is_int($value) => self::cborHead(1, -1 - $value),
            is_string($value) => self::cborHead(3, strlen($value)).$value,
            is_array($value) && isset($value['bytes']) => self::cborHead(2, strlen($value['bytes'])).$value['bytes'],
            is_array($value) && isset($value['map']) => self::cborMap($value['map']),
            default => throw new RuntimeException('Unsupported CBOR value in test authenticator.'),
        };
    }

    private static function cborHead(int $major, int $length): string
    {
        return match (true) {
            $length < 24 => chr(($major << 5) | $length),
            $length < 256 => chr(($major << 5) | 24).chr($length),
            default => chr(($major << 5) | 25).pack('n', $length),
        };
    }
}
