<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VersionConstraintMatcherTest extends TestCase
{
    #[DataProvider('constraints')]
    public function test_it_matches_supported_constraints(string $version, string $constraint, bool $expected): void
    {
        self::assertSame($expected, (new VersionConstraintMatcher())->matches($version, $constraint));
    }

    /** @return array<string, array{string,string,bool}> */
    public static function constraints(): array
    {
        return [
            'wildcard' => ['1.2.3', '*', true],
            'exact' => ['1.2.3', '1.2.3', true],
            'caret normal' => ['1.9.0', '^1.2', true],
            'caret major mismatch' => ['2.0.0', '^1.2', false],
            'caret zero minor' => ['0.4.9', '^0.4', true],
            'caret zero next minor' => ['0.5.0', '^0.4', false],
            'tilde' => ['2.3.9', '~2.3', true],
            'comparison' => ['2.0.0', '>=1.5', true],
            'or' => ['2.0.0', '^1.0 || ^2.0', true],
            'rc backward compatibility window' => ['1.0.0-rc.1', '>=0.34 <2.0', true],
            'rc is below stable one' => ['1.0.0-rc.1', '>=1.0', false],
        ];
    }
}
