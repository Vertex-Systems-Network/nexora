<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Primary SQL portability source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Primary SQL portability source file: {$relative}";
        return '';
    }
    return $contents;
};

$registry = $read('app/Nexora/Installation/Database/DatabaseDriverRegistry.php');
$versions = $read('app/Nexora/Installation/Database/DatabaseVersionPolicy.php');
$provisioner = $read('app/Nexora/Installation/DatabaseProvisioner.php');
$databaseConfig = $read('config/database.php');
$installer = $read('app/Http/Controllers/Install/InstallerController.php');
$backup = $read('app/Nexora/Installation/DatabaseBackupManager.php');
$identity = $read('app/Nexora/Installation/Database/DatabaseDataPlaneIdentity.php');
$databaseContracts = $read('scripts/lib/database-contracts.php');
$registryTest = $read('tests/Unit/DatabaseDriverRegistryTest.php');
$versionTest = $read('tests/Unit/DatabaseVersionPolicyTest.php');
$provisionerTest = $read('tests/Unit/DatabaseProvisionerConfigurationTest.php');
$roundTripTest = $read('tests/Compatibility/DatabaseRoundTripCompatibilityTest.php');

foreach ([
    "'mysql' =>" => 'MySQL registry definition',
    "'mariadb' =>" => 'MariaDB registry definition',
    "'pgsql' =>" => 'PostgreSQL registry definition',
    "'sqlite' =>" => 'SQLite registry definition',
    "'sqlsrv' =>" => 'SQL Server registry definition',
    "'aws_rds_mysql' =>" => 'RDS MySQL registry definition',
    "'aws_rds_mariadb' =>" => 'RDS MariaDB registry definition',
    "'aws_rds_pgsql' =>" => 'RDS PostgreSQL registry definition',
    "'aws_rds_sqlsrv' =>" => 'RDS SQL Server registry definition',
    "'aws_aurora_mysql' =>" => 'Aurora MySQL registry definition',
    "'aws_aurora_pgsql' =>" => 'Aurora PostgreSQL registry definition',
    "'supports_create' => false" => 'managed-database create prohibition',
    "'managed' => true" => 'managed provider metadata',
] as $needle => $label) {
    if ($registry !== '' && ! str_contains($registry, $needle)) {
        $errors[] = "Database driver registry missing: {$label}.";
    }
}

foreach ([
    "'mysql' => '5.7.0'" => 'MySQL minimum version',
    "'mariadb' => '10.3.0'" => 'MariaDB minimum version',
    "'pgsql' => '10.0.0'" => 'PostgreSQL minimum version',
    "'sqlite' => '3.26.0'" => 'SQLite minimum version',
    "'sqlsrv' => '14.0.0'" => 'SQL Server minimum version',
    "str_contains(\$driver,'mariadb')" => 'managed MariaDB alias normalization',
    "str_contains(\$driver,'mysql')" => 'managed MySQL alias normalization',
    "str_contains(\$driver,'pgsql')||str_contains(\$driver,'postgres')" => 'managed PostgreSQL alias normalization',
    "str_contains(\$driver,'sqlsrv')||str_contains(\$driver,'sqlserver')" => 'managed SQL Server alias normalization',
] as $needle => $label) {
    if ($versions !== '' && ! str_contains($versions, $needle)) {
        $errors[] = "Database version policy missing: {$label}.";
    }
}

foreach ([
    "'mysql', 'mariadb' => \$this->connectMySqlFamily" => 'MySQL/MariaDB test path',
    "'pgsql' => \$this->connectPostgres" => 'PostgreSQL test path',
    "'sqlite' => \$this->connectSqlite" => 'SQLite test path',
    "'sqlsrv' => \$this->connectSqlServer" => 'SQL Server test path',
    "'mysql', 'mariadb' => \$this->wipeMySqlFamily" => 'MySQL/MariaDB wipe path',
    "'pgsql' => \$this->wipePostgres" => 'PostgreSQL wipe path',
    "'sqlite' => \$this->wipeSqlite" => 'SQLite wipe path',
    "'sqlsrv' => \$this->wipeSqlServer" => 'SQL Server wipe path',
    "'foreign_key_constraints' => true" => 'SQLite foreign-key runtime configuration',
    "'search_path' => 'public'" => 'PostgreSQL public search path',
    "'sslmode' => 'prefer'" => 'PostgreSQL SSL-mode baseline',
    "'strategy' => 'native-php'" => 'native MySQL-family backup policy',
    "'strategy' => 'file-copy'" => 'SQLite backup policy',
    "'strategy' => 'external'" => 'external PostgreSQL/SQL Server backup policy',
    'assertDatabaseName' => 'safe network database identifier validation',
    'quotePgIdentifier' => 'PostgreSQL identifier quoting',
    'quoteSqlServerIdentifier' => 'SQL Server identifier quoting',
] as $needle => $label) {
    if ($provisioner !== '' && ! str_contains($provisioner, $needle)) {
        $errors[] = "Database provisioner portability contract missing: {$label}.";
    }
}

foreach (['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'] as $connection) {
    if ($databaseConfig !== '' && ! str_contains($databaseConfig, "'{$connection}' => [")) {
        $errors[] = "config/database.php is missing the {$connection} first-party connection.";
    }
}
foreach ([
    "env('MYSQL_ATTR_SSL_CA')" => 'MySQL/MariaDB TLS CA configuration',
    "env('DB_SSLMODE', 'prefer')" => 'PostgreSQL SSL-mode configuration',
    "'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true)" => 'SQLite foreign-key configuration',
] as $needle => $label) {
    if ($databaseConfig !== '' && ! str_contains($databaseConfig, $needle)) {
        $errors[] = "Database configuration missing: {$label}.";
    }
}

foreach ([
    "Rule::in(\$this->databaseDrivers->keys())" => 'installer driver allow-list',
    "preg_match('/^[A-Za-z0-9_]+$/'" => 'installer network database-name validation',
    "\$this->database->test(\$validated" => 'installer database test path',
] as $needle => $label) {
    if ($installer !== '' && ! str_contains($installer, $needle)) {
        $errors[] = "Installer primary database contract missing: {$label}.";
    }
}

foreach ([
    "'mysql', 'mariadb' => \$this->createMySqlBackup" => 'MySQL/MariaDB backup implementation',
    "'sqlite' => \$this->createSqliteBackup" => 'SQLite backup implementation',
    "Nexora does not have an in-app backup strategy" => 'fail-closed unsupported in-app backup path',
] as $needle => $label) {
    if ($backup !== '' && ! str_contains($backup, $needle)) {
        $errors[] = "Database backup manager contract missing: {$label}.";
    }
}

foreach ([
    "'mysql','mariadb'=>\$this->mysqlProfile" => 'MySQL/MariaDB runtime identity profile',
    "'pgsql'=>\$this->pgsqlProfile" => 'PostgreSQL runtime identity profile',
    "'sqlite'=>\$this->sqliteProfile" => 'SQLite runtime identity profile',
    "'sqlsrv'=>\$this->sqlsrvProfile" => 'SQL Server runtime identity profile',
] as $needle => $label) {
    if ($identity !== '' && ! str_contains($identity, $needle)) {
        $errors[] = "Database data-plane identity missing: {$label}.";
    }
}

foreach ([
    "'column placement ->after()'" => 'after() portability rejection',
    "'database enum'" => 'enum portability rejection',
    "'database set'" => 'set portability rejection',
    "'full-text index'" => 'full-text portability rejection',
    "'spatial index'" => 'spatial portability rejection',
    "'generated stored column'" => 'stored generated column portability rejection',
    "'generated virtual column'" => 'virtual generated column portability rejection',
    "'raw DB statement'" => 'raw DB statement portability rejection',
    'PortableNullableUnique::create(' => 'portable nullable unique accounting',
] as $needle => $label) {
    if ($databaseContracts !== '' && ! str_contains($databaseContracts, $needle)) {
        $errors[] = "Database source analyzer missing: {$label}.";
    }
}

foreach ([
    "'aws_rds_mariadb'" => 'RDS MariaDB registry test',
    "'aws_rds_sqlsrv'" => 'RDS SQL Server registry test',
    "self::assertFalse(\$drivers[\$key]['supports_create'])" => 'managed create prohibition test',
] as $needle => $label) {
    if ($registryTest !== '' && ! str_contains($registryTest, $needle)) {
        $errors[] = "Database driver registry test missing: {$label}.";
    }
}

foreach (['aws_rds_mysql', 'aws_rds_mariadb', 'aws_rds_pgsql', 'aws_rds_sqlsrv', 'aws_aurora_mysql', 'aws_aurora_pgsql'] as $alias) {
    if ($versionTest !== '' && ! str_contains($versionTest, "'{$alias}'")) {
        $errors[] = "Database version-policy test is missing managed alias {$alias}.";
    }
    if ($provisionerTest !== '' && ! str_contains($provisionerTest, "'{$alias}'")) {
        $errors[] = "Database provisioner configuration test is missing managed alias {$alias}.";
    }
}

foreach ([
    'it_normalizes_every_supported_primary_driver' => 'driver normalization test',
    'it_builds_portable_laravel_connections_without_opening_a_network_connection' => 'no-network Laravel configuration test',
    'managed_sql_variants_reuse_their_compatible_laravel_driver' => 'managed driver mapping test',
    'environment_and_backup_policy_remain_driver_correct' => 'environment/backup policy test',
] as $needle => $label) {
    if ($provisionerTest !== '' && ! str_contains($provisionerTest, $needle)) {
        $errors[] = "Database provisioner configuration coverage missing: {$label}.";
    }
}

foreach ([
    'tenantAwareModelTables' => 'dynamic tenant-root discovery',
    "assertContains('nx_data_connections'" => 'Data Connections tenant runtime assertion',
    "assertContains('nx_forms'" => 'Forms tenant runtime assertion',
    "assertContains('nx_content_collections'" => 'Collections tenant runtime assertion',
] as $needle => $label) {
    if ($roundTripTest !== '' && ! str_contains($roundTripTest, $needle)) {
        $errors[] = "Database round-trip compatibility coverage missing: {$label}.";
    }
}
if ($roundTripTest !== '' && str_contains($roundTripTest, 'assertCount(51,$tenantTables)')) {
    $errors[] = 'Database round-trip compatibility must not freeze tenant model coverage to the historical 51-root count.';
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Primary SQL Portability Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Primary SQL Portability Contract] PASS — MySQL, MariaDB, PostgreSQL, SQLite, SQL Server and SQL-compatible AWS variants are registry/version/config/provisioning/backup/runtime-identity/migration-test aligned.'.PHP_EOL,
);
