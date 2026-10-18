<?php

declare(strict_types=1);

namespace App\Nexora\Security\TwoFactor;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/** SECURITY-BASELINE-200 / SYS-MFA-TOTP user-level MFA lifecycle. */
final class TwoFactorManager
{
    public const RECOVERY_CODE_COUNT = 8;

    public const MAX_ACCOUNT_FAILURES = 10;

    public const FAILURE_DECAY_SECONDS = 900;

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

    /** Account-wide failure cap shared by web and API, independent of IP and session (review round 1, finding 1). */
    public function tooManyFailures(User $user): bool
    {
        return RateLimiter::tooManyAttempts($this->failureKey($user), self::MAX_ACCOUNT_FAILURES);
    }

    public function failureRetryAfter(User $user): int
    {
        return max(1, RateLimiter::availableIn($this->failureKey($user)));
    }

    /**
     * Verifies a login code; returns 'totp', 'recovery' or null. The row is re-read under a lock and the
     * TOTP step is advanced with a conditional update, so concurrent requests cannot both accept one code.
     */
    public function verifyForLogin(User $user, string $code): ?string
    {
        if ($this->tooManyFailures($user)) {
            return null;
        }

        $method = DB::transaction(function () use ($user, $code): ?string {
            $fresh = User::query()->whereKey($user->getKey())->lockForUpdate()->first();
            if (! $fresh instanceof User || ! $this->enabled($fresh)) {
                return null;
            }

            $step = $this->totp->verify((string) $fresh->two_factor_secret, $code, $fresh->two_factor_last_used_step);
            if ($step !== null) {
                $claimed = User::query()->whereKey($fresh->getKey())
                    ->where(fn ($query) => $query->whereNull('two_factor_last_used_step')->orWhere('two_factor_last_used_step', '<', $step))
                    ->update(['two_factor_last_used_step' => $step]);

                return $claimed === 1 ? 'totp' : null;
            }

            $hashes = (array) ($fresh->two_factor_recovery_codes ?? []);
            $candidate = $this->hashRecoveryCode($code);
            foreach ($hashes as $index => $hash) {
                if (is_string($hash) && hash_equals($hash, $candidate)) {
                    unset($hashes[$index]);
                    $fresh->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();

                    return 'recovery';
                }
            }

            return null;
        });

        if ($method === null) {
            RateLimiter::hit($this->failureKey($user), self::FAILURE_DECAY_SECONDS);
        } else {
            RateLimiter::clear($this->failureKey($user));
            $user->refresh();
        }

        return $method;
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

    private function failureKey(User $user): string
    {
        return 'nexora-2fa-failures:'.$user->getKey();
    }

    private function hashRecoveryCode(string $code): string
    {
        return hash('sha256', Str::lower(trim($code)));
    }
}
