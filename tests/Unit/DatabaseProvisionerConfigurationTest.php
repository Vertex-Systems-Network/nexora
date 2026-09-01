<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Installation\DatabaseProvisioner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DatabaseProvisionerConfigurationTest extends TestCase
{
    public static function driverMappings(): array
    {
        return [
            ['mysql', 'mysql'],
            ['mariadb', 'mariadb'],
            ['pgsql', 'pgsql'],
            ['sqlite', 'sqlite'],
            ['sqlsrv', 'sqlsrv'],
            ['aws_rds_mysql', 'mysql'],
            ['aws_rds_mariadb', 'mariadb'],
            ['aws_rds_pgsql', 'pgsql'],
            ['aws_rds_sqlsrv', 'sqlsrv'],
            ['aws_aurora_mysql', 'mysql'],
            ['aws_aurora_pgsql', 'pgsql'],
        ];
    }

    #[Test, DataProvider('driverMappings')]
    public function it_normalizes_every_supported_primary_driver(string $submitted, string $logical): void
    {
        self::assertSame($logical, app(DatabaseProvisioner::class)->normalizeDriver($submitted));
    }

    #[Test]
    public function it_builds_portable_laravel_connections_without_opening_a_network_connection(): void
    {
        $provisioner = app(DatabaseProvisioner::class);

        $mysql = $provisioner->laravelConnection($this->payload('mysql', 3306));
        self::assertSame('mysql', $mysql['driver']);
        self::assertSame('utf8mb4', $mysql['charset']);
        self::assertSame('utf8mb4_unicode_ci', $mysql['collation']);
        self::assertTrue($mysql['strict']);

        $mariadb = $provisioner->laravelConnection($this->payload('mariadb', 3306));
        self::assertSame('mariadb', $mariadb['driver']);
        self::assertSame('utf8mb4', $mariadb['charset']);

        $pgsql = $provisioner->laravelConnection($this->payload('pgsql', 5432));
        self::assertSame('pgsql', $pgsql['driver']);
        self::assertSame('public', $pgsql['search_path']);
        self::assertSame('prefer', $pgsql['sslmode']);

        $sqlsrv = $provisioner->laravelConnection($this->payload('sqlsrv', 1433));
        self::assertSame('sqlsrv', $sqlsrv['driver']);
        self::assertSame('utf8', $sqlsrv['charset']);

        $sqlitePath = 'nexora-portability.sqlite';
        $sqlite = $provisioner->laravelConnection([
            'driver' => 'sqlite',
            'database' => $sqlitePath,
        ]);
        self::assertSame('sqlite', $sqlite['driver']);
        self::assertTrue($sqlite['foreign_key_constraints']);
        self::assertStringEndsWith(str_replace('\\', '/', 'database/'.$sqlitePath), str_replace('\\', '/', (string) $sqlite['database']));
    }

    #[Test]
    public function managed_sql_variants_reuse_their_compatible_laravel_driver(): void
    {
        $provisioner = app(DatabaseProvisioner::class);
        foreach (self::driverMappings() as [$submitted, $logical]) {
            if (! str_starts_with($submitted, 'aws_')) continue;
            $connection = $provisioner->laravelConnection($this->payload($submitted, $logical === 'pgsql' ? 5432 : ($logical === 'sqlsrv' ? 1433 : 3306)));
            self::assertSame($logical, $connection['driver'], $submitted.' must map to its compatible Laravel driver.');
        }
    }

    #[Test]
    public function environment_and_backup_policy_remain_driver_correct(): void
    {
        $provisioner = app(DatabaseProvisioner::class);

        $sqlite = $provisioner->environment(['driver' => 'sqlite', 'database' => 'portable.sqlite']);
        self::assertSame('sqlite', $sqlite['DB_CONNECTION']);
        self::assertSame('true', $sqlite['DB_FOREIGN_KEYS']);
        self::assertSame('', $sqlite['DB_HOST']);
        self::assertSame('', $sqlite['DB_USERNAME']);

        foreach ([
            'mysql' => ['mysql', true, 'native-php'],
            'mariadb' => ['mariadb', true, 'native-php'],
            'pgsql' => ['pgsql', false, 'external'],
            'sqlsrv' => ['sqlsrv', false, 'external'],
            'aws_rds_mysql' => ['mysql', true, 'native-php'],
            'aws_rds_mariadb' => ['mariadb', true, 'native-php'],
            'aws_rds_pgsql' => ['pgsql', false, 'external'],
            'aws_rds_sqlsrv' => ['sqlsrv', false, 'external'],
            'aws_aurora_mysql' => ['mysql', true, 'native-php'],
            'aws_aurora_pgsql' => ['pgsql', false, 'external'],
        ] as $driver => [$logical, $backupAvailable, $strategy]) {
            $environment = $provisioner->environment($this->payload($driver, $logical === 'pgsql' ? 5432 : ($logical === 'sqlsrv' ? 1433 : 3306)));
            self::assertSame($logical, $environment['DB_CONNECTION']);
            $backup = $provisioner->backupCapability($driver);
            self::assertSame($backupAvailable, $backup['available']);
            self::assertSame($strategy, $backup['strategy']);
        }
    }

    /** @return array<string,mixed> */
    private function payload(string $driver, int $port): array
    {
        return [
            'driver' => $driver,
            'host' => 'db.internal.example',
            'port' => $port,
            'database' => 'nexora_portable',
            'username' => 'nexora_user',
            'password' => 'example-secret',
        ];
    }
}
