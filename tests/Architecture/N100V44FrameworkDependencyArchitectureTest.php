<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V44FrameworkDependencyArchitectureTest extends TestCase
{
    #[Test]
    public function reviewed_laravel_updates_use_safe_dependency_reconciliation(): void
    {
        require_once base_path('scripts/lib/n1-target-framework-dependency-contracts.php');

        $result = \nexoraAnalyzeFrameworkDependencyContracts(base_path());

        self::assertSame([], $result['errors'], implode(PHP_EOL, $result['errors']));
        self::assertSame(13, $result['metrics']['laravel_major']);
        self::assertSame(24, $result['metrics']['laravel_minimum_minor']);
        self::assertSame(1, $result['metrics']['dependency_reconcile']);
        self::assertSame(0, $result['metrics']['automatic_framework_update']);
    }
}
