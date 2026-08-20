<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Admin\Navigation\AdminNavigationRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AdminNavigationRegistryTest extends TestCase
{
    public function test_it_orders_registered_items(): void
    {
        $registry = new AdminNavigationRegistry();
        $registry->register(['id' => 'settings', 'order' => 80]);
        $registry->register(['id' => 'dashboard', 'order' => 10]);

        self::assertSame(['dashboard', 'settings'], array_column($registry->all(), 'id'));
    }

    public function test_it_requires_a_stable_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AdminNavigationRegistry())->register(['label' => 'Invalid']);
    }
}
