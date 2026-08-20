<?php

declare(strict_types=1);

namespace App\Nexora\Installation\Database;

use PDO;

final class DatabaseDriverRegistry
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    /** @return array<string,array<string,mixed>> */
    public function all(): array
    {
        $availablePdo = array_map('strtolower', PDO::getAvailableDrivers());

        $drivers = [
            'mysql' => $this->definition('mysql', 'MySQL', '5.7+', 'mysql', ['pdo_mysql'], 3306, 'root', 'Core databases'),
            'mariadb' => $this->definition('mariadb', 'MariaDB', '10.3+', 'mysql', ['pdo_mysql'], 3306, 'root', 'Core databases'),
            'pgsql' => $this->definition('pgsql', 'PostgreSQL', '10.0+', 'pgsql', ['pdo_pgsql'], 5432, 'postgres', 'Core databases'),
            'sqlite' => [
                'key' => 'sqlite', 'label' => 'SQLite', 'minimum' => '3.26.0+', 'pdo_driver' => 'sqlite', 'laravel_driver' => 'sqlite',
                'extensions' => ['pdo_sqlite'], 'default_host' => '', 'default_port' => null,
                'default_database' => database_path('database.sqlite'), 'default_username' => '', 'supports_create' => true,
                'backup_strategy' => 'file-copy', 'network' => false, 'group' => 'Core databases', 'provider' => 'local', 'managed' => false,
                'description' => 'Single-file database for lightweight deployments and local development.',
            ],
            'sqlsrv' => $this->definition('sqlsrv', 'Microsoft SQL Server', '2017+', 'sqlsrv', ['pdo_sqlsrv'], 1433, 'sa', 'Core databases'),

            'aws_rds_mysql' => $this->awsDefinition('aws_rds_mysql', 'Amazon RDS for MySQL', 'mysql', ['pdo_mysql'], 3306, 'admin'),
            'aws_rds_mariadb' => $this->awsDefinition('aws_rds_mariadb', 'Amazon RDS for MariaDB', 'mysql', ['pdo_mysql'], 3306, 'admin', 'mariadb'),
            'aws_rds_pgsql' => $this->awsDefinition('aws_rds_pgsql', 'Amazon RDS for PostgreSQL', 'pgsql', ['pdo_pgsql'], 5432, 'postgres'),
            'aws_rds_sqlsrv' => $this->awsDefinition('aws_rds_sqlsrv', 'Amazon RDS for SQL Server', 'sqlsrv', ['pdo_sqlsrv'], 1433, 'admin'),
            'aws_aurora_mysql' => $this->awsDefinition('aws_aurora_mysql', 'Amazon Aurora MySQL', 'mysql', ['pdo_mysql'], 3306, 'admin'),
            'aws_aurora_pgsql' => $this->awsDefinition('aws_aurora_pgsql', 'Amazon Aurora PostgreSQL', 'pgsql', ['pdo_pgsql'], 5432, 'postgres'),
        ];

        foreach ($drivers as &$driver) {
            $missing = [];
            if (! in_array(strtolower((string) $driver['pdo_driver']), $availablePdo, true)) {
                $missing[] = 'PDO driver '.$driver['pdo_driver'];
            }
            foreach ((array) $driver['extensions'] as $extension) {
                if (! extension_loaded((string) $extension)) {
                    $missing[] = 'PHP extension '.$extension;
                }
            }
            $missing = array_values(array_unique($missing));
            $driver['available'] = $missing === [];
            $driver['missing'] = $missing;
            $driver['availability_message'] = $missing === []
                ? $driver['label'].' is available on this server.'
                : 'Unavailable: '.implode(', ', $missing).'.';
        }
        unset($driver);

        return $drivers;
    }

    /** @return array<string,mixed> */
    public function get(string $key): array
    {
        $drivers = $this->all();
        if (! isset($drivers[$key])) {
            throw new \InvalidArgumentException('Unsupported database driver: '.$key.'.');
        }

        return $drivers[$key];
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /** @return array<string,mixed> */
    private function definition(string $key, string $label, string $minimum, string $pdoDriver, array $extensions, int $port, string $username, string $group): array
    {
        return [
            'key' => $key, 'label' => $label, 'minimum' => $minimum, 'pdo_driver' => $pdoDriver, 'laravel_driver' => $key,
            'extensions' => $extensions, 'default_host' => '127.0.0.1', 'default_port' => $port,
            'default_database' => 'nexora', 'default_username' => $username, 'supports_create' => true,
            'backup_strategy' => in_array($key, ['mysql', 'mariadb'], true) ? 'native-php' : ($key === 'sqlite' ? 'file-copy' : 'external'),
            'network' => true, 'group' => $group, 'provider' => 'native', 'managed' => false,
            'description' => 'Direct '.$label.' connection for the Nexora primary application database.',
        ];
    }

    /** @return array<string,mixed> */
    private function awsDefinition(string $key, string $label, string $laravelDriver, array $extensions, int $port, string $username, ?string $logicalDriver = null): array
    {
        return [
            'key' => $key, 'label' => $label, 'minimum' => 'Managed service', 'pdo_driver' => $laravelDriver, 'laravel_driver' => $logicalDriver ?? $laravelDriver,
            'extensions' => $extensions, 'default_host' => '', 'default_port' => $port,
            'default_database' => 'nexora', 'default_username' => $username, 'supports_create' => false,
            'backup_strategy' => in_array($laravelDriver, ['mysql'], true) ? 'native-php' : 'external',
            'network' => true, 'group' => 'Amazon Web Services', 'provider' => 'aws', 'managed' => true,
            'description' => 'AWS managed endpoint using the compatible '.$laravelDriver.' Laravel/PDO driver.',
        ];
    }
}
