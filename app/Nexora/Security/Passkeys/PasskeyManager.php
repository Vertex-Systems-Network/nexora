<?php

declare(strict_types=1);

namespace App\Nexora\Security\Passkeys;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * SECURITY-BASELINE-200 / SYS-MFA-PASSKEY: WebAuthn registration and passwordless sign-in.
 *
 * Ceremonies are single-use: options live in the session only until the matching response is checked,
 * and are pulled (not read) so a challenge can never be answered twice. Only "none" attestation is
 * accepted and user verification is required, so a passkey is itself a phishing-resistant second factor.
 */
final class PasskeyManager
{
    public const MAX_PER_USER = 10;

    public const TTL_SECONDS = 300;

    private const REGISTRATION_KEY = 'nexora.passkey.registration';

    private const LOGIN_KEY = 'nexora.passkey.login';

    private ?SerializerInterface $serializer = null;

    /** @return array<string,mixed> PublicKeyCredentialCreationOptions for navigator.credentials.create() */
    public function registrationOptions(Request $request, User $user): array
    {
        $options = PublicKeyCredentialCreationOptions::create(
            rp: PublicKeyCredentialRpEntity::create((string) config('app.name', 'Nexora'), $this->relyingPartyId($request)),
            user: PublicKeyCredentialUserEntity::create($user->email, $this->userHandle($user), $user->name ?: $user->email),
            challenge: random_bytes(32),
            pubKeyCredParams: [
                PublicKeyCredentialParameters::createPk(-7),   // ES256
                PublicKeyCredentialParameters::createPk(-257), // RS256
            ],
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $user->passkeys()->pluck('credential_id')
                ->map(fn (string $id): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                    PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                    $this->base64UrlDecode($id),
                ))->all(),
            timeout: self::TTL_SECONDS * 1000,
        );

        return $this->remember($request, self::REGISTRATION_KEY, $options, ['user_id' => $user->id]);
    }

    /** Verifies the attestation for the pending ceremony and stores the credential; null when anything fails. */
    public function register(Request $request, User $user, string $credentialJson, string $name): ?Passkey
    {
        $pending = $this->pull($request, self::REGISTRATION_KEY, PublicKeyCredentialCreationOptions::class);
        if ($pending === null || $pending['context']['user_id'] !== $user->id) {
            return null;
        }

        try {
            $credential = $this->serializer()->deserialize($credentialJson, PublicKeyCredential::class, 'json');
            if (! $credential->response instanceof AuthenticatorAttestationResponse) {
                return null;
            }
            $record = AuthenticatorAttestationResponseValidator::create($this->ceremonies($request)->creationCeremony())
                ->check($credential->response, $pending['options'], $request->getHost());
        } catch (Throwable) {
            return null;
        }

        return DB::transaction(function () use ($user, $record, $name): ?Passkey {
            $credentialId = $this->base64UrlEncode($record->publicKeyCredentialId);
            // Lock the owner row so two concurrent registrations cannot both pass the per-user cap.
            User::query()->whereKey($user->getKey())->lockForUpdate()->first();
            if ($user->passkeys()->count() >= self::MAX_PER_USER || Passkey::query()->where('credential_id', $credentialId)->exists()) {
                return null;
            }

            return $user->passkeys()->create([
                'name' => $name,
                'credential_id' => $credentialId,
                'credential_record' => $this->serializeRecord($record),
            ]);
        });
    }

    /** @return array<string,mixed> PublicKeyCredentialRequestOptions for navigator.credentials.get() (discoverable credentials) */
    public function loginOptions(Request $request): array
    {
        $options = PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: $this->relyingPartyId($request),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: self::TTL_SECONDS * 1000,
        );

        return $this->remember($request, self::LOGIN_KEY, $options);
    }

    /** Verifies an assertion for the pending login ceremony; returns the owning user or null. */
    public function authenticate(Request $request, string $credentialJson): ?User
    {
        $pending = $this->pull($request, self::LOGIN_KEY, PublicKeyCredentialRequestOptions::class);
        if ($pending === null) {
            return null;
        }

        try {
            $credential = $this->serializer()->deserialize($credentialJson, PublicKeyCredential::class, 'json');
            if (! $credential->response instanceof AuthenticatorAssertionResponse) {
                return null;
            }
            $credentialId = $this->base64UrlEncode($credential->rawId);
        } catch (Throwable) {
            return null;
        }

        return DB::transaction(function () use ($request, $credential, $credentialId, $pending): ?User {
            // Serialize concurrent assertions for one credential so the signature counter cannot be raced.
            $passkey = Passkey::query()->where('credential_id', $credentialId)->lockForUpdate()->first();
            $user = $passkey?->user;
            if (! $passkey instanceof Passkey || ! $user instanceof User) {
                return null;
            }

            $response = $credential->response;
            $userHandle = $response->userHandle;
            // Discoverable sign-in: the authenticator must name the account, and it must be this passkey's owner.
            if (! is_string($userHandle) || ! hash_equals($this->userHandle($user), $userHandle)) {
                return null;
            }

            try {
                $record = AuthenticatorAssertionResponseValidator::create($this->ceremonies($request)->requestCeremony())
                    ->check($this->deserializeRecord($passkey->credential_record), $response, $pending['options'], $request->getHost(), $userHandle);
            } catch (Throwable) {
                return null;
            }

            $passkey->forceFill([
                'credential_record' => $this->serializeRecord($record),
                'last_used_at' => now(),
            ])->save();

            return $user;
        });
    }

    /** Stable, opaque WebAuthn user handle: no email or database id is disclosed to authenticators. */
    public function userHandle(User $user): string
    {
        return hash_hmac('sha256', 'nexora-passkey-user:'.$user->getKey(), (string) config('app.key'), true);
    }

    private function relyingPartyId(Request $request): string
    {
        return $request->getHost();
    }

    private function ceremonies(Request $request): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory;
        // Exact scheme + host + port of this request; no subdomains, no other origins.
        $factory->setAllowedOrigins([$request->getSchemeAndHttpHost()]);

        return $factory;
    }

    /**
     * @param  array<string,mixed>  $context
     * @return array<string,mixed>
     */
    private function remember(Request $request, string $key, PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options, array $context = []): array
    {
        $json = $this->serializer()->serialize($options, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
        ]);
        $request->session()->put($key, [
            'options' => $json,
            'context' => $context,
            'expires_at' => now()->addSeconds(self::TTL_SECONDS)->getTimestamp(),
        ]);

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @template T of PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions
     *
     * @param  class-string<T>  $class
     * @return array{options:T,context:array<string,mixed>}|null
     */
    private function pull(Request $request, string $key, string $class): ?array
    {
        $pending = $request->session()->pull($key);
        if (! is_array($pending) || ! is_string($pending['options'] ?? null) || (int) ($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        try {
            $options = $this->serializer()->deserialize($pending['options'], $class, 'json');
        } catch (Throwable) {
            return null;
        }

        return ['options' => $options, 'context' => (array) ($pending['context'] ?? [])];
    }

    private function serializeRecord(CredentialRecord $record): string
    {
        return $this->serializer()->serialize($record, 'json', [JsonEncode::OPTIONS => JSON_THROW_ON_ERROR]);
    }

    private function deserializeRecord(string $json): CredentialRecord
    {
        return $this->serializer()->deserialize($json, CredentialRecord::class, 'json');
    }

    private function serializer(): SerializerInterface
    {
        return $this->serializer ??= (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))->create();
    }

    private function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
