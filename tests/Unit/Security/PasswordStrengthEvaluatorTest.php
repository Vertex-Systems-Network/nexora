<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Nexora\Security\Password\PasswordStrengthEvaluator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PasswordStrengthEvaluatorTest extends TestCase
{
    #[Test]
    public function strong_password_does_not_require_override_consent(): void
    {
        $result = app(PasswordStrengthEvaluator::class)->evaluate('V7!qR2#pL9@mX4$z');

        self::assertSame('strong', $result['level']);
        self::assertTrue($result['minimum_accepted']);
        self::assertFalse($result['consent_required']);
        self::assertNotContains(false, $result['requirements']);
    }

    #[Test]
    public function valid_but_predictable_password_requires_explicit_consent(): void
    {
        $result = app(PasswordStrengthEvaluator::class)->evaluate('NexoraAdmin1234!Qz');

        self::assertContains($result['level'], ['low', 'medium']);
        self::assertTrue($result['minimum_accepted']);
        self::assertTrue($result['consent_required']);
    }

    #[Test]
    public function weak_password_above_the_hard_floor_can_be_consent_gated(): void
    {
        $result = app(PasswordStrengthEvaluator::class)->evaluate('Abcdefgh12');

        self::assertSame('weak', $result['level']);
        self::assertTrue($result['minimum_accepted']);
        self::assertTrue($result['consent_required']);
        self::assertFalse($result['requirements']['symbol']);
    }

    #[Test]
    public function password_below_the_hard_floor_is_blocked_even_with_consent(): void
    {
        $result = app(PasswordStrengthEvaluator::class)->evaluate('short');

        self::assertSame('blocked', $result['level']);
        self::assertFalse($result['minimum_accepted']);
        self::assertFalse($result['consent_required']);
    }
}
