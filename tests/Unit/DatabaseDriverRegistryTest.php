<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Installation\Database\DatabaseDriverRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DatabaseDriverRegistryTest extends TestCase
{
    #[Test]
    public function it_exposes_all_primary_and_managed_sql_database_drivers(): void
    {
        $drivers = app(DatabaseDriverRegistry::class)->all();

        foreach ([
            'mysql',
            'mariadb',
            'pgsql',
            'sqlite',
            'sqlsrv',
            'aws_rds_mysql',
            'aws_rds_mariadb',
            'aws_rds_pgsql',
            'aws_rds_sqlsrv',
            'aws_aurora_mysql',
            'aws_aurora_pgsql',
        ] as $key) {
            self::assertArrayHasKey($key, $drivers);
        }

        self::assertSame('mysql', $drivers['mysql']['pdo_driver']);
        self::assertSame('mariadb', $drivers['mariadb']['laravel_driver']);
        self::assertSame('mysql', $drivers['mariadb']['pdo_driver']);
        self::assertSame('pgsql', $drivers['pgsql']['laravel_driver']);
        self::assertSame('sqlite', $drivers['sqlite']['laravel_driver']);
        self::assertSame('sqlsrv', $drivers['sqlsrv']['laravel_driver']);

        self::assertSame('Amazon Web Services', $drivers['aws_rds_mysql']['group']);
        self::assertSame('mysql', $drivers['aws_rds_mysql']['laravel_driver']);
        self::assertSame('mariadb', $drivers['aws_rds_mariadb']['laravel_driver']);
        self::assertSame('pgsql', $drivers['aws_rds_pgsql']['laravel_driver']);
        self::assertSame('sqlsrv', $drivers['aws_rds_sqlsrv']['laravel_driver']);
        self::assertSame('mysql', $drivers['aws_aurora_mysql']['laravel_driver']);
        self::assertSame('pgsql', $drivers['aws_aurora_pgsql']['laravel_driver']);

        foreach (['aws_rds_mysql', 'aws_rds_mariadb', 'aws_rds_pgsql', 'aws_rds_sqlsrv', 'aws_aurora_mysql', 'aws_aurora_pgsql'] as $key) {
            self::assertTrue($drivers[$key]['managed']);
            self::assertFalse($drivers[$key]['supports_create']);
        }

        foreach ($drivers as $key => $driver) {
            self::assertSame($key, $driver['key'], 'Registry array key and submitted driver key must never drift.');
            self::assertArrayHasKey('available', $driver);
            self::assertArrayHasKey('availability_message', $driver);
            self::assertArrayHasKey('minimum', $driver);
            self::assertArrayHasKey('pdo_driver', $driver);
            self::assertArrayHasKey('laravel_driver', $driver);
            self::assertArrayHasKey('supports_create', $driver);
        }
    }
}
