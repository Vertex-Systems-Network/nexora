<?php

declare(strict_types=1);

use App\Nexora\Installation\Database\DatabaseDriverRegistry;
use App\Nexora\Installation\Database\DatabaseVersionPolicy;
use App\Nexora\Installation\DatabaseProvisioner;

$root = dirname(__DIR__);
$json = in_array('--json', $argv, true);
$listOnly = in_array('--list', $argv, true);
$writeEvidence = in_array('--evidence', $argv, true);

$fail = static function (string $message, int $code = 1) use ($json): never {
    if ($json) {
        fwrite(STDOUT, json_encode(['status' => 'failed', 'message' => $message], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    } else {
        fwrite(STDERR, '[Nexora Database Target Matrix] '.$message.PHP_EOL);
    }
    exit($code);
};

if (! is_file($root.'/vendor/autoload.php')) {
    $fail('vendor/autoload.php is missing. Run this only from a development/target checkout with Composer dependencies installed.', 2);
}

require_once $root.'/vendor/autoload.php';
require_once $root.'/scripts/lib/target-composer.php';

// Initialize Laravel's application paths/helpers without mutating installation state.
require $root.'/bootstrap/app.php';

$registry = new DatabaseDriverRegistry();
$provisioner = new DatabaseProvisioner($registry, new DatabaseVersionPolicy());
$definitions = $registry->all();

$driverArgument = null;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--drivers=')) {
        $driverArgument = substr($argument, strlen('--drivers='));
        break;
    }
}

if ($listOnly) {
    $rows = [];
    foreach ($definitions as $key => $definition) {
        $rows[] = [
            'key' => $key,
            'label' => $definition['label'] ?? $key,
            'logical_driver' => $definition['laravel_driver'] ?? $key,
            'pdo_driver' => $definition['pdo_driver'] ?? null,
            'available' => (bool) ($definition['available'] ?? false),
            'managed' => (bool) ($definition['managed'] ?? false),
            'supports_create' => (bool) ($definition['supports_create'] ?? false),
            'env_prefix' => 'NEXORA_MATRIX_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $key) ?? $key).'_',
        ];
    }
    if ($json) {
        fwrite(STDOUT, json_encode(['status' => 'ok', 'drivers' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    } else {
        fwrite(STDOUT, "Nexora Database Target Matrix drivers\n");
        foreach ($rows as $row) {
            fwrite(STDOUT, sprintf(
                "%-20s %-12s available=%s managed=%s create=%s env=%s\n",
                $row['key'],
                $row['logical_driver'],
                $row['available'] ? 'yes' : 'no',
                $row['managed'] ? 'yes' : 'no',
                $row['supports_create'] ? 'yes' : 'no',
                $row['env_prefix'],
            ));
        }
        fwrite(STDOUT, "\nUsage: php scripts/database-target-matrix.php --drivers=sqlite,mysql --evidence\n");
        fwrite(STDOUT, "Network profiles require NEXORA_MATRIX_<DRIVER>_DATABASE names beginning with nexora_matrix_.\n");
        fwrite(STDOUT, "--evidence writes a secret-free JSON result to storage/app/nexora/qa/database-target-matrix.json.\n");
    }
    exit(0);
}

$selected = $driverArgument === null || trim($driverArgument) === ''
    ? ['sqlite']
    : array_values(array_unique(array_filter(array_map('trim', explode(',', $driverArgument)))));

if ($selected === []) {
    $fail('No database drivers were selected.', 2);
}

foreach ($selected as $key) {
    if (! isset($definitions[$key])) {
        $fail('Unknown matrix driver: '.$key.'. Use --list to see supported driver keys.', 2);
    }
}

$getEnv = static function (string $name, ?string $default = null): ?string {
    $value = getenv($name);
    if ($value === false || trim((string) $value) === '') return $default;
    return trim((string) $value);
};

$prefixFor = static fn (string $key): string => 'NEXORA_MATRIX_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $key) ?? $key).'_';
$defaultPort = static function (string $logical): int {
    return match ($logical) {
        'mysql', 'mariadb' => 3306,
        'pgsql' => 5432,
        'sqlsrv' => 1433,
        default => 0,
    };
};

$profiles = [];
foreach ($selected as $key) {
    $definition = $definitions[$key];
    $logical = (string) ($definition['laravel_driver'] ?? $key);
    $prefix = $prefixFor($key);

    if ($logical === 'sqlite') {
        $database = $getEnv($prefix.'DATABASE', 'nexora_matrix_sqlite.sqlite');
        if (! is_string($database) || preg_match('/^nexora_matrix_[A-Za-z0-9_-]+\.sqlite$/', $database) !== 1) {
            $fail($key.' SQLite filename must be a basename matching nexora_matrix_*.sqlite; paths and production filenames are refused.', 2);
        }
        $profiles[$key] = ['driver' => $key, 'database' => $database];
        continue;
    }

    $database = $getEnv($prefix.'DATABASE');
    if (! is_string($database) || preg_match('/^nexora_matrix_[A-Za-z0-9_]+$/', $database) !== 1) {
        $fail($key.' requires '.$prefix.'DATABASE and the database name must begin with nexora_matrix_.', 2);
    }

    $profiles[$key] = [
        'driver' => $key,
        'host' => $getEnv($prefix.'HOST', '127.0.0.1'),
        'port' => (int) $getEnv($prefix.'PORT', (string) $defaultPort($logical)),
        'database' => $database,
        'username' => $getEnv($prefix.'USERNAME', ''),
        'password' => $getEnv($prefix.'PASSWORD', ''),
    ];
}

$results = [];
$overall = true;
foreach ($profiles as $key => $profile) {
    $definition = $definitions[$key];
    $logical = (string) ($definition['laravel_driver'] ?? $key);
    $result = [
        'driver' => $key,
        'logical_driver' => $logical,
        'status' => 'failed',
        'connection' => null,
        'test_exit_code' => null,
        'cleanup' => null,
        'detail' => null,
    ];

    if (! ($definition['available'] ?? false)) {
        $result['detail'] = (string) ($definition['availability_message'] ?? 'Required PDO driver is unavailable.');
        $results[] = $result;
        $overall = false;
        continue;
    }

    $supportsCreate = (bool) ($definition['supports_create'] ?? false);
    $probe = $provisioner->test($profile, $supportsCreate);
    $result['connection'] = [
        'ok' => (bool) ($probe['ok'] ?? false),
        'version' => $probe['version'] ?? null,
        'object_count' => $probe['object_count'] ?? null,
        'message' => $probe['message'] ?? null,
    ];

    if (! ($probe['ok'] ?? false)) {
        $result['detail'] = (string) ($probe['message'] ?? 'Connection probe failed.');
        $results[] = $result;
        $overall = false;
        continue;
    }

    $objectCount = (int) ($probe['object_count'] ?? 0);
    if ($objectCount !== 0) {
        $result['detail'] = 'Safety refusal: selected matrix database is not empty ('.$objectCount.' object(s)). Use a fresh disposable nexora_matrix_* database.';
        $results[] = $result;
        $overall = false;
        continue;
    }

    $databaseEnv = $provisioner->environment($profile);
    $childEnv = NexoraBootstrapProcessEnvironment::build($root, $databaseEnv + [
        'APP_ENV' => 'testing',
        'NEXORA_INSTALLER_BYPASS' => 'true',
        'APP_MAINTENANCE_DRIVER' => 'file',
        'BCRYPT_ROUNDS' => '4',
        'BROADCAST_CONNECTION' => 'null',
        'CACHE_STORE' => 'array',
        'DB_URL' => '',
        'MAIL_MAILER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER' => 'array',
    ]);

    $phpunit = $root.'/vendor/bin/phpunit';
    $test = nexoraRunTargetCommand(
        [PHP_BINARY, $phpunit, '--filter', 'DatabaseRoundTripCompatibilityTest', '--colors=never'],
        $root,
        $childEnv,
    );
    $result['test_exit_code'] = $test['exit_code'];
    $result['detail'] = trim($test['stdout'] !== '' ? $test['stdout'] : $test['stderr']);

    try {
        $provisioner->wipe($profile);
        $result['cleanup'] = 'database objects removed';
        if ($logical === 'sqlite') {
            $path = (string) ($databaseEnv['DB_DATABASE'] ?? '');
            $basename = basename(str_replace('\\', '/', $path));
            if ($path !== '' && preg_match('/^nexora_matrix_[A-Za-z0-9_-]+\.sqlite$/', $basename) === 1 && is_file($path)) {
                @unlink($path);
                $result['cleanup'] = 'SQLite matrix file removed';
            }
        }
    } catch (Throwable $exception) {
        $result['cleanup'] = 'cleanup failed: '.$exception->getMessage();
        $overall = false;
    }

    if ($test['exit_code'] === 0 && ! str_starts_with((string) $result['cleanup'], 'cleanup failed:')) {
        $result['status'] = 'passed';
    } else {
        $overall = false;
    }
    $results[] = $result;
}

$nexoraConfig = require $root.'/config/nexora.php';
$payload = [
    'schema' => 2,
    'status' => $overall ? 'passed' : 'failed',
    'scope' => 'target-database-compatibility-matrix',
    'generated_at' => gmdate(DATE_ATOM),
    'platform_version' => (string) ($nexoraConfig['version'] ?? 'unknown'),
    'source_generation' => DatabaseProvisioner::RUNTIME_SOURCE_GENERATION,
    'php_version' => PHP_VERSION,
    'selected_drivers' => array_keys($profiles),
    'destructive_scope' => 'Only empty databases/files whose names match nexora_matrix_* are accepted; cleanup removes matrix objects only.',
    'results' => $results,
];

if ($writeEvidence) {
    $evidenceDirectory = $root.'/storage/app/nexora/qa';
    if (! is_dir($evidenceDirectory) && ! @mkdir($evidenceDirectory, 0775, true) && ! is_dir($evidenceDirectory)) {
        $fail('Unable to create the target-matrix evidence directory under storage/app/nexora/qa.', 3);
    }
    $evidencePath = $evidenceDirectory.'/database-target-matrix.json';
    $payload['evidence_path'] = 'storage/app/nexora/qa/database-target-matrix.json';
    $encodedEvidence = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    if (@file_put_contents($evidencePath, $encodedEvidence, LOCK_EX) === false) {
        $fail('Unable to write the target-matrix evidence file.', 3);
    }
}

if ($json) {
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
} else {
    fwrite(STDOUT, 'Nexora Database Target Matrix — '.strtoupper($payload['status']).PHP_EOL);
    foreach ($results as $row) {
        fwrite(STDOUT, sprintf("%-20s %-8s test=%s cleanup=%s\n", $row['driver'], strtoupper($row['status']), (string) ($row['test_exit_code'] ?? '-'), (string) ($row['cleanup'] ?? '-')));
        if (($row['detail'] ?? '') !== '') {
            fwrite(STDOUT, '  '.str_replace(["\r", "\n"], ' ', (string) $row['detail']).PHP_EOL);
        }
    }
    if ($writeEvidence) {
        fwrite(STDOUT, 'Evidence: storage/app/nexora/qa/database-target-matrix.json'.PHP_EOL);
    }
}

exit($overall ? 0 : 1);
