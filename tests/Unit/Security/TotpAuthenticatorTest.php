<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Nexora\Security\TwoFactor\TotpAuthenticator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** SECURITY-BASELINE-200 / SYS-MFA-TOTP: RFC 6238 Appendix B SHA-1 vectors, truncated to 6 digits. */
final class TotpAuthenticatorTest extends TestCase
{
    private const RFC_SEED = '12345678901234567890';

    /** @return list<array{int,string}> */
    public static function rfcVectors(): array
    {
        return [[59, '287082'], [1111111109, '081804'], [1111111111, '050471'], [1234567890, '005924'], [2000000000, '279037'], [20000000000, '353130']];
    }

    #[DataProvider('rfcVectors')]
    public function test_matches_rfc_6238_vectors(int $time, string $expected): void
    {
        $totp = new TotpAuthenticator;
        $secret = $totp->base32Encode(self::RFC_SEED);

        self::assertSame($expected, $totp->codeAt($secret, $totp->currentStep($time)));
    }

    public function test_base32_round_trips_generated_secrets(): void
    {
        $totp = new TotpAuthenticator;
        $secret = $totp->generateSecret();

        self::assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        self::assertSame(20, strlen($totp->base32Decode($secret)));
        self::assertSame($secret, $totp->base32Encode($totp->base32Decode($secret)));
    }

    public function test_accepts_one_step_of_drift_but_not_two(): void
    {
        $totp = new TotpAuthenticator;
        $secret = $totp->generateSecret();
        $now = 1_800_000_000;
        $step = $totp->currentStep($now);

        self::assertSame($step - 1, $totp->verify($secret, $totp->codeAt($secret, $step - 1), null, $now));
        self::assertSame($step + 1, $totp->verify($secret, $totp->codeAt($secret, $step + 1), null, $now));
        self::assertNull($totp->verify($secret, $totp->codeAt($secret, $step - 2), null, $now));
        self::assertNull($totp->verify($secret, $totp->codeAt($secret, $step + 2), null, $now));
    }

    public function test_rejects_replay_of_an_already_used_step(): void
    {
        $totp = new TotpAuthenticator;
        $secret = $totp->generateSecret();
        $now = 1_800_000_000;
        $step = $totp->currentStep($now);
        $code = $totp->codeAt($secret, $step);

        self::assertSame($step, $totp->verify($secret, $code, null, $now));
        self::assertNull($totp->verify($secret, $code, $step, $now));
    }

    public function test_rejects_malformed_codes(): void
    {
        $totp = new TotpAuthenticator;
        $secret = $totp->generateSecret();

        foreach (['', '12345', '1234567', 'abcdef', '12 34 5x'] as $bad) {
            self::assertNull($totp->verify($secret, $bad));
        }
    }
}
