<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Database\ConcurrencyGuard;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ConcurrencyGuardTest extends TestCase
{
    #[Test]
    public function portable_mutex_rejects_unsafe_names_before_touching_the_database(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConcurrencyGuard())->mutex('../unsafe', static fn (): bool => true);
    }
}
