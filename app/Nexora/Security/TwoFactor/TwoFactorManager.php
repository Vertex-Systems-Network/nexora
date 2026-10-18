<?php

declare(strict_types=1);

namespace App\Nexora\Security\TwoFactor;

use App\Models\User;
use Illuminate\Support\Str;

/** SECURITY-BASELINE-200 / SYS-MFA-TOTP user-level MFA lifecycle. */
final class TwoFactorManager
{
    public const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly TotpAuthenticator $totp) {}

    public function enabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null && is_string($user->two_factor_secret) && $user->two_factor_secret !== '';
    }

    /** Starts (or restarts) enrolment with a pending secret; login is not gated until confirmed. */
    public function begin(User $user): string
    {
        $secret = $this->totp->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ])->save();

        return $secret;
    }

    /** @return list<string>|null plaintext recovery codes on success */
    public function confirm(User $user, string $code): ?array
    {
        if ($user->two_factor_confirmed_at !== null || ! is_string($user->two_factor_secret)) {
            return null;
        }
        $step = $this->totp->verify($user->two_factor_secret, $code);
        if ($step === null) {
            return null;
        }
        $codes = $this->freshRecoveryCodes();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_step' => $step,
            'two_factor_recovery_codes' => array_map([$this, 'hashRecoveryCode'], $codes),
        ])->save();

        return $codes;
    }

    /** Verifies a login code; returns 'totp', 'recovery' or null. Consumes recovery codes and records the TOTP step. */
    public function verifyForLogin(User $user, string $code): ?string
    {
        if (! $this->enabled($user)) {
            return null;
        }
        $step = $this->totp->verify((string) $user->two_factor_secret, $code, $user->two_factor_last_used_step);
        if ($step !== null) {
            $user->forceFill(['two_factor_last_used_step' => $step])->save();

            return 'totp';
        }

        $hashes = (array) ($user->two_factor_recovery_codes ?? []);
        $candidate = $this->hashRecoveryCode($code);
        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && hash_equals($hash, $candidate)) {
                unset($hashes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();

                return 'recovery';
            }
        }

        return null;
    }

    /** @return list<string> */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->freshRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => array_map([$this, 'hashRecoveryCode'], $codes)])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ])->save();
    }

    public function remainingRecoveryCodes(User $user): int
    {
        return count((array) ($user->two_factor_recovery_codes ?? []));
    }

    /** @return list<string> */
    private function freshRecoveryCodes(): array
    {
        return array_map(
            static fn (): string => Str::lower(Str::random(5).'-'.Str::random(5)),
            range(1, self::RECOVERY_CODE_COUNT),
        );
    }

    private function hashRecoveryCode(string $code): string
    {
        return hash('sha256', Str::lower(trim($code)));
    }
}
