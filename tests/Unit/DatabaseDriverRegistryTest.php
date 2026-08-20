<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Installation\Database\DatabaseDriverRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DatabaseDriverRegistryTest extends TestCase
{
    #[Test]
    public function it_exposes_all_laravel_first_party_database_drivers(): void
    {
        $drivers = app(DatabaseDriverRegistry::class)->all();

        foreach (['mysql', 'mariadb', 'pgsql', 'sqlite', 'sqlsrv', 'aws_rds_mysql', 'aws_rds_pgsql', 'aws_aurora_mysql', 'aws_aurora_pgsql'] as $key) {
            self::assertArrayHasKey($key, $drivers);
        }
        self::assertSame('Amazon Web Services', $drivers['aws_rds_mysql']['group']);
        self::assertFalse($drivers['aws_rds_mysql']['supports_create']);

        foreach ($drivers as $key => $driver) {
            self::assertSame($key, $driver['key'], 'Registry array key and submitted driver key must never drift.');
            self::assertArrayHasKey('available', $driver);
            self::assertArrayHasKey('availability_message', $driver);
            self::assertArrayHasKey('minimum', $driver);
        }
    }
}
