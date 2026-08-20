<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Foundation\Runtime\RuntimeWritableTempDirectory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RuntimeHostClockIdentity
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    private ?array $memo = null;

    public function __construct(private readonly RuntimeWritableTempDirectory $tempDirectories)
    {
    }

    /** @return array<string,mixed> */
    public function current(bool $deep = false): array
    {
        if (! $deep && is_array($this->memo)) {
            return $this->memo;
        }

        $materials = $this->materials();
        $checks = $this->staticChecks();
        $payload = [
            'schema' => 2,
            'status' => in_array(false, $checks, true) ? 'fail' : 'pass',
            'fingerprint' => $this->hash($materials),
            'materials' => $materials,
            'checks' => $checks,
            'process_profile' => $this->processProfile(),
        ];

        if ($deep) {
            $payload['deep'] = $this->deepProbe();
            if (($payload['deep']['status'] ?? null) !== 'pass') {
                $payload['status'] = 'fail';
            }
        } else {
            $this->memo = $payload;
        }

        return $payload;
    }

    /**
     * Installation uses a bounded safety profile instead of demanding every
     * production/HA certification check before the permanent installer lock.
     * Strict host certification remains available through current(true) and is
     * still required by C2/C6.
     *
     * @return array<string,mixed>
     */
    public function installationAttestation(): array
    {
        $strict = $this->current(true);
        $deep = (array) ($strict['deep'] ?? []);
        $deepChecks = (array) ($deep['checks'] ?? []);
        $details = (array) ($deep['details'] ?? []);
        $clock = (array) ($details['clock'] ?? []);
        $staticChecks = (array) ($strict['checks'] ?? []);

        $databaseRequired = (bool) config(
            'nexora-host-runtime.installation.require_database_clock_anchor',
            config('nexora-host-runtime.require_database_clock_anchor', true),
        );
        $installerMaxSkew = max(
            5000,
            min(
                300000,
                (int) config('nexora-host-runtime.installation.max_database_clock_skew_ms', 60000),
            ),
        );
        $databaseAvailable = ($clock['database_unix_ms'] ?? null) !== null;
        $skew = is_numeric($clock['skew_ms'] ?? null)
            ? (int) $clock['skew_ms']
            : null;
        $filesystem = $this->installationFilesystemProbe();
        $filesystemChecks = (array) ($filesystem['checks'] ?? []);

        $installationChecks = [
            'database_clock_anchor' => ! $databaseRequired || $databaseAvailable,
            'database_clock_skew' => $skew === null
                ? ! $databaseRequired
                : abs($skew) <= $installerMaxSkew,
            'monotonic_clock_available' => (bool) ($staticChecks['monotonic_clock_available'] ?? false),
            'temp_writable' => (bool) ($filesystemChecks['temp_writable'] ?? false),
            'atomic_rename' => (bool) ($filesystemChecks['atomic_rename'] ?? false),
            'flock' => (bool) ($filesystemChecks['flock'] ?? false),
            'secure_random' => (bool) ($filesystemChecks['secure_random'] ?? false),
            'umask_portability' => $this->posixUmaskApplicable()
                ? (bool) ($deepChecks['umask_allowed'] ?? false)
                : true,
        ];

        $warnings = [];
        foreach (['app_timezone_required', 'runtime_timezone_matches_app', 'intl_locale_matches_app'] as $check) {
            if (($staticChecks[$check] ?? true) !== true) {
                $warnings[] = "Strict host certification check is pending [{$check}].";
            }
        }

        $strictMaxSkew = (int) config('nexora-host-runtime.max_database_clock_skew_ms', 5000);
        if ($skew !== null && abs($skew) > $strictMaxSkew && abs($skew) <= $installerMaxSkew) {
            $warnings[] = sprintf(
                'Database clock skew is %d ms. Installation tolerance is %d ms, but strict certification requires %d ms.',
                $skew,
                $installerMaxSkew,
                $strictMaxSkew,
            );
        }

        if (! $this->posixUmaskApplicable()) {
            $warnings[] = 'POSIX umask policy is not applicable on Windows and is excluded from installation blocking checks.';
        }

        $blockingReasons = [];
        foreach ($installationChecks as $check => $passed) {
            if (! $passed) {
                $blockingReasons[] = $this->installationFailureReason($check, $clock, $installerMaxSkew, $filesystem);
            }
        }

        return [
            ...$strict,
            'status' => $blockingReasons === [] ? 'pass' : 'fail',
            'strict_status' => (string) ($strict['status'] ?? 'fail'),
            'installation_status' => $blockingReasons === [] ? 'pass' : 'fail',
            'installation_checks' => $installationChecks,
            'installation_max_database_clock_skew_ms' => $installerMaxSkew,
            'installation_blocking_reasons' => $blockingReasons,
            'installation_warnings' => array_values(array_unique($warnings)),
            'installation_temp' => $filesystem['temp'] ?? [],
            'installation_filesystem_probe' => $filesystem,
        ];
    }

    public function fingerprintValue(): string
    {
        return (string) $this->current(false)['fingerprint'];
    }

    /** @return array<string,mixed> */
    public function clockStatus(): array
    {
        $local = microtime(true);
        $database = null;
        $error = null;

        try {
            $database = $this->databaseEpochSeconds();
        } catch (\Throwable $exception) {
            $error = mb_substr($exception->getMessage(), 0, 300);
        }

        $skew = $database !== null
            ? (int) round(($local - $database) * 1000)
            : null;
        $max = (int) config('nexora-host-runtime.max_database_clock_skew_ms', 5000);
        $required = (bool) config('nexora-host-runtime.require_database_clock_anchor', true);
        $ok = (! $required || $database !== null)
            && ($skew === null || abs($skew) <= $max);

        return [
            'status' => $ok ? 'pass' : 'fail',
            'local_unix_ms' => (int) round($local * 1000),
            'database_unix_ms' => $database !== null
                ? (int) round($database * 1000)
                : null,
            'skew_ms' => $skew,
            'timezone_offset_signature' => $this->timezoneOffsetSignature($skew),
            'max_skew_ms' => $max,
            'database_clock_required' => $required,
            'error' => $error,
        ];
    }

    public function databaseNow(): Carbon
    {
        $epoch = $this->databaseEpochSeconds();
        $now = now();
        $now->setTimestamp((int) floor($epoch));

        return $now;
    }

    public function databaseEpochMilliseconds(): int
    {
        return (int) round($this->databaseEpochSeconds() * 1000);
    }

    /** @param array<string,mixed> $meta @return array{ok:bool,reason:?string,generated_unix_ms:?int,database_unix_ms:?int,skew_ms:?int} */
    public function verifyQueueTimestamp(array $meta): array
    {
        $generated = filter_var($meta['generated_unix_ms'] ?? null, FILTER_VALIDATE_INT);
        if ($generated === false || $generated === null) {
            return [
                'ok' => false,
                'reason' => 'queue payload generation timestamp missing',
                'generated_unix_ms' => null,
                'database_unix_ms' => null,
                'skew_ms' => null,
            ];
        }

        try {
            $database = $this->databaseEpochMilliseconds();
        } catch (\Throwable $exception) {
            if ((bool) config('nexora-host-runtime.require_database_clock_anchor', true)) {
                return [
                    'ok' => false,
                    'reason' => 'database clock anchor unavailable: '.mb_substr($exception->getMessage(), 0, 180),
                    'generated_unix_ms' => (int) $generated,
                    'database_unix_ms' => null,
                    'skew_ms' => null,
                ];
            }
            $database = (int) round(microtime(true) * 1000);
        }

        $skew = (int) $generated - $database;
        $max = max(5, (int) config('nexora-host-runtime.queue_future_skew_seconds', 300)) * 1000;
        if ($skew > $max) {
            return [
                'ok' => false,
                'reason' => 'queue payload timestamp is too far in the future for the shared database clock',
                'generated_unix_ms' => (int) $generated,
                'database_unix_ms' => $database,
                'skew_ms' => $skew,
            ];
        }

        return [
            'ok' => true,
            'reason' => null,
            'generated_unix_ms' => (int) $generated,
            'database_unix_ms' => $database,
            'skew_ms' => $skew,
        ];
    }

    /** @return array<string,mixed> */
    private function materials(): array
    {
        $appTimezone = (string) config('app.timezone', 'UTC');
        $runtimeTimezone = date_default_timezone_get();
        $locale = (string) config('app.locale', 'en');
        $fallback = (string) config('app.fallback_locale', 'en');
        $intl = class_exists(\Locale::class)
            ? (string) \Locale::getDefault()
            : null;

        return [
            'os_family' => PHP_OS_FAMILY,
            'machine_arch' => strtolower(trim((string) php_uname('m'))),
            'directory_separator' => DIRECTORY_SEPARATOR,
            'path_separator' => PATH_SEPARATOR,
            'app_timezone' => $appTimezone,
            'runtime_timezone' => $runtimeTimezone,
            'app_locale' => $locale,
            'fallback_locale' => $fallback,
            'intl_default_locale' => $intl,
            'clock_policy_sha256' => $this->policyHash(),
        ];
    }

    /** @return array<string,bool> */
    private function staticChecks(): array
    {
        $required = (string) config('nexora-host-runtime.required_timezone', 'UTC');
        $runtime = date_default_timezone_get();
        $app = (string) config('app.timezone', 'UTC');
        $locale = (string) config('app.locale', 'en');
        $intl = class_exists(\Locale::class)
            ? (string) \Locale::getDefault()
            : $locale;

        return [
            'app_timezone_required' => ! (bool) config('nexora-host-runtime.require_runtime_timezone_match', true)
                || strcasecmp($app, $required) === 0,
            'runtime_timezone_matches_app' => ! (bool) config('nexora-host-runtime.require_runtime_timezone_match', true)
                || strcasecmp($runtime, $app) === 0,
            'intl_locale_matches_app' => ! (bool) config('nexora-host-runtime.require_intl_locale_match', true)
                || strcasecmp(str_replace('-', '_', $intl), str_replace('-', '_', $locale)) === 0,
            'monotonic_clock_available' => ! (bool) config('nexora-host-runtime.require_monotonic_clock', true)
                || function_exists('hrtime'),
        ];
    }

    /** @return array<string,mixed> */
    private function processProfile(): array
    {
        $mask = umask();

        return [
            'sapi' => PHP_SAPI,
            'pid' => getmypid(),
            'umask' => sprintf('%04o', $mask),
            'umask_applicable' => $this->posixUmaskApplicable(),
            'temp_directory_sha256' => hash('sha256', (string) sys_get_temp_dir()),
            'temp_writable' => is_writable(sys_get_temp_dir()),
        ];
    }

    /** @return array<string,mixed> */
    private function deepProbe(): array
    {
        $checks = [];
        $details = [];

        $clock = $this->clockStatus();
        $checks['database_clock_skew'] = ($clock['status'] ?? null) === 'pass';
        $details['clock'] = $clock;

        $allowedUmasks = (array) config('nexora-host-runtime.allowed_umasks', ['0022', '0027']);
        $mask = sprintf('%04o', umask());
        $umaskApplicable = $this->posixUmaskApplicable();
        $checks['umask_allowed'] = ! $umaskApplicable || in_array($mask, $allowedUmasks, true);
        $details['umask'] = $mask;
        $details['umask_applicable'] = $umaskApplicable;

        $temp = $this->tempDirectories->systemPath();
        $checks['temp_writable'] = ! (bool) config('nexora-host-runtime.require_temp_writable', true)
            || is_writable($temp);

        $probeDirectory = rtrim($temp, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'nexora-host-'.bin2hex(random_bytes(6));
        $atomicRename = false;
        $fileLock = false;
        $secureRandom = false;
        $caseSensitive = null;

        try {
            if (! @mkdir($probeDirectory, 0700, true) && ! is_dir($probeDirectory)) {
                throw new \RuntimeException('unable to create temp probe directory');
            }

            $source = $probeDirectory.DIRECTORY_SEPARATOR.'probe-a';
            $target = $probeDirectory.DIRECTORY_SEPARATOR.'probe-b';
            file_put_contents($source, 'nexora-host-probe', LOCK_EX);
            $atomicRename = @rename($source, $target)
                && is_file($target)
                && file_get_contents($target) === 'nexora-host-probe';

            $handle = @fopen($target, 'c+');
            if (is_resource($handle)) {
                $fileLock = @flock($handle, LOCK_EX | LOCK_NB);
                if ($fileLock) {
                    @flock($handle, LOCK_UN);
                }
                fclose($handle);
            }

            try {
                $secureRandom = strlen(random_bytes(32)) === 32;
            } catch (\Throwable) {
                $secureRandom = false;
            }

            $caseFile = $probeDirectory.DIRECTORY_SEPARATOR.'NxCaseProbe';
            file_put_contents($caseFile, 'x');
            $caseSensitive = ! is_file($probeDirectory.DIRECTORY_SEPARATOR.'nxcaseprobe');
            @unlink($caseFile);
            @unlink($target);
        } catch (\Throwable $exception) {
            $details['filesystem_error'] = mb_substr($exception->getMessage(), 0, 300);
        } finally {
            @rmdir($probeDirectory);
        }

        $checks['atomic_rename'] = ! (bool) config('nexora-host-runtime.require_atomic_rename', true)
            || $atomicRename;
        $checks['flock'] = ! (bool) config('nexora-host-runtime.require_flock', true)
            || $fileLock;
        $checks['secure_random'] = ! (bool) config('nexora-host-runtime.require_secure_random', true)
            || $secureRandom;

        $details['atomic_rename'] = $atomicRename;
        $details['flock'] = $fileLock;
        $details['secure_random'] = $secureRandom;
        $details['case_sensitive_filesystem'] = $caseSensitive;

        $payload = [
            'status' => in_array(false, $checks, true) ? 'fail' : 'pass',
            'checks' => $checks,
            'details' => $details,
        ];
        $payload['deep_sha256'] = $this->hash($payload);

        return $payload;
    }

    private function databaseEpochSeconds(): float
    {
        $connection = DB::connection();
        $driver = strtolower((string) $connection->getDriverName());
        $sql = self::databaseEpochQueryForDriver($driver);

        $row = (array) $connection->selectOne($sql);
        $value = $row['nexora_epoch'] ?? reset($row);

        if (! is_numeric($value)) {
            if (is_string($value) && $value !== '') {
                $timestamp = strtotime($value.' UTC');
                if ($timestamp !== false) {
                    return (float) $timestamp;
                }
            }

            throw new \RuntimeException('Database clock query returned an unsupported value.');
        }

        return (float) $value;
    }

    public static function databaseEpochQueryForDriver(string $driver): string
    {
        return match (strtolower(trim($driver))) {
            // CURRENT_TIMESTAMP and UNIX_TIMESTAMP share the same MySQL/MariaDB
            // session timezone. UTC_TIMESTAMP() is a timezone-less datetime and
            // passing it back into UNIX_TIMESTAMP(datetime) can shift the epoch
            // by the session offset (for example exactly +05:00 / 18,000,000 ms).
            'mysql', 'mariadb' => 'SELECT UNIX_TIMESTAMP(CURRENT_TIMESTAMP(6)) AS nexora_epoch',
            'pgsql' => 'SELECT EXTRACT(EPOCH FROM clock_timestamp()) AS nexora_epoch',
            'sqlite' => "SELECT (CAST(strftime('%s','now') AS INTEGER) + CAST(strftime('%f','now') AS REAL) - CAST(strftime('%S','now') AS INTEGER)) AS nexora_epoch",
            'sqlsrv' => "SELECT CAST(DATEDIFF_BIG(MILLISECOND, '1970-01-01', SYSUTCDATETIME()) AS float) / 1000.0 AS nexora_epoch",
            default => 'SELECT CURRENT_TIMESTAMP AS nexora_now',
        };
    }

    /** @return array<string,mixed> */
    private function installationFilesystemProbe(): array
    {
        $temp = $this->tempDirectories->installation();
        $selected = is_string($temp['selected_path'] ?? null)
            ? (string) $temp['selected_path']
            : '';
        $checks = [
            'temp_writable' => ($temp['status'] ?? 'fail') === 'pass' && $selected !== '',
            'atomic_rename' => false,
            'flock' => false,
            'secure_random' => false,
        ];
        $details = [];

        if ($selected === '') {
            return [
                'status' => 'fail',
                'checks' => $checks,
                'temp' => $temp,
                'details' => ['error' => 'no writable installation temp directory could be resolved'],
            ];
        }

        $probeDirectory = rtrim($selected, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'nexora-install-host-'.bin2hex(random_bytes(6));

        try {
            if (! @mkdir($probeDirectory, 0700, true) && ! is_dir($probeDirectory)) {
                throw new \RuntimeException('unable to create installation temp probe directory');
            }

            $source = $probeDirectory.DIRECTORY_SEPARATOR.'probe-a';
            $target = $probeDirectory.DIRECTORY_SEPARATOR.'probe-b';
            file_put_contents($source, 'nexora-install-host-probe', LOCK_EX);
            $checks['atomic_rename'] = @rename($source, $target)
                && is_file($target)
                && file_get_contents($target) === 'nexora-install-host-probe';

            $handle = @fopen($target, 'c+');
            if (is_resource($handle)) {
                $checks['flock'] = @flock($handle, LOCK_EX | LOCK_NB);
                if ($checks['flock']) {
                    @flock($handle, LOCK_UN);
                }
                fclose($handle);
            }

            $checks['secure_random'] = strlen(random_bytes(32)) === 32;
            @unlink($target);
        } catch (\Throwable $exception) {
            $details['error'] = mb_substr($exception->getMessage(), 0, 300);
        } finally {
            @rmdir($probeDirectory);
        }

        return [
            'status' => in_array(false, $checks, true) ? 'fail' : 'pass',
            'checks' => $checks,
            'temp' => $temp,
            'details' => $details,
        ];
    }

    private function timezoneOffsetSignature(?int $skewMs): ?array
    {
        if ($skewMs === null || abs($skewMs) < 1_800_000) {
            return null;
        }

        $step = 900_000; // 15-minute timezone increments, including :30/:45 zones.
        $nearest = (int) round($skewMs / $step) * $step;
        if (abs($skewMs - $nearest) > 5_000) {
            return null;
        }

        return [
            'detected' => true,
            'nearest_offset_ms' => $nearest,
            'nearest_offset_hours' => round($nearest / 3_600_000, 2),
        ];
    }

    private function posixUmaskApplicable(): bool
    {
        return strcasecmp(PHP_OS_FAMILY, 'Windows') !== 0;
    }

    /** @param array<string,mixed> $clock */
    private function installationFailureReason(
        string $check,
        array $clock,
        int $installerMaxSkew,
        array $filesystem,
    ): string
    {
        return match ($check) {
            'database_clock_anchor' => 'Database UTC clock anchor is unavailable: '
                .((string) ($clock['error'] ?? 'unknown database clock error')),
            'database_clock_skew' => sprintf(
                'Host/database clock skew [%s ms] exceeds the installation safety limit [%d ms].',
                is_numeric($clock['skew_ms'] ?? null) ? (string) $clock['skew_ms'] : 'unknown',
                $installerMaxSkew,
            ),
            'monotonic_clock_available' => 'PHP monotonic clock support (hrtime) is unavailable.',
            'temp_writable' => 'No writable installation temporary directory is available. System temp: '
                .((string) (($filesystem['temp']['system_path'] ?? null) ?: 'unknown'))
                .'. Nexora also tried application-local temp fallbacks.',
            'atomic_rename' => 'Installation temporary-filesystem atomic rename probe failed at '
                .((string) (($filesystem['temp']['selected_path'] ?? null) ?: 'unknown')).'.',
            'flock' => 'Installation temporary-filesystem advisory file-lock probe failed at '
                .((string) (($filesystem['temp']['selected_path'] ?? null) ?: 'unknown')).'.',
            'secure_random' => 'Cryptographically secure random_bytes() probe failed.',
            'umask_portability' => 'POSIX umask does not match the configured host policy.',
            default => "Installation host check failed [{$check}].",
        };
    }

    private function policyHash(): ?string
    {
        $path = config_path('nexora-host-runtime.php');

        return is_file($path) ? (hash_file('sha256', $path) ?: null) : null;
    }

    /** @param array<string,mixed> $payload */
    private function hash(array $payload): string
    {
        return hash('sha256', json_encode(
            $this->canonicalize($payload),
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->canonicalize($item),
                $value,
            );
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
