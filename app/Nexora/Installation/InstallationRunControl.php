<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Nexora\Installation\Exceptions\InstallationCancelledException;
use RuntimeException;

final class InstallationRunControl
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function __construct(private InstallationResumeIdentity $resumeIdentity)
    {
    }

    public function start(string $runId, string $sessionId): void
    {
        $this->assertRunId($runId);
        $this->recoverInterruptedRuns();
        $this->pruneFinishedRuns();
        $resumeIdentity = $this->resumeIdentity->current();
        $resumeMaterials = (array) ($resumeIdentity['materials'] ?? []);

        $this->writeNew($runId, [
            'run_id' => $runId,
            'session_hash' => hash('sha256', $sessionId),
            'resume_fingerprint' => (string) ($resumeIdentity['fingerprint'] ?? ''),
            'platform_version' => (string) ($resumeMaterials['platform_version'] ?? config('nexora.version', 'unknown')),
            'installer_protocol' => (string) ($resumeMaterials['installer_protocol'] ?? Installer::PROTOCOL),
            'source_generation' => (string) ($resumeMaterials['source_generation'] ?? Installer::SOURCE_GENERATION),
            'source_tree_sha256' => $resumeMaterials['source_tree_sha256'] ?? null,
            'source_tree_file_count' => (int) ($resumeMaterials['source_tree_file_count'] ?? 0),
            'critical_source_manifest_sha256' => $resumeMaterials['critical_source_manifest_sha256'] ?? null,
            'critical_source_file_count' => (int) ($resumeMaterials['critical_source_file_count'] ?? 0),
            'migrations_sha256' => $resumeMaterials['migrations_sha256'] ?? null,
            'core_seeders_sha256' => $resumeMaterials['core_seeders_sha256'] ?? null,
            'composer_lock_sha256' => $resumeMaterials['composer_lock_sha256'] ?? null,
            'package_lock_sha256' => $resumeMaterials['package_lock_sha256'] ?? null,
            'active' => true,
            'cancellable' => true,
            'cancel_requested' => false,
            'protected_started' => false,
            'stage' => 'starting',
            'status' => 'running',
            'started_at' => gmdate(DATE_ATOM),
            'heartbeat_at' => gmdate(DATE_ATOM),
            'heartbeat_epoch' => time(),
        ]);
    }

    /** @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database */
    public function bindDatabaseTarget(string $runId, array $database): void
    {
        $fingerprint = $this->databaseFingerprint($database);
        $this->mutate($runId, static function (array $state) use ($fingerprint, $database): array {
            $state['database_fingerprint'] = $fingerprint;
            $state['database_driver'] = (string) ($database['driver'] ?? '');
            $state['database_name_hash'] = hash('sha256', (string) ($database['database'] ?? ''));
            $state['heartbeat_at'] = gmdate(DATE_ATOM);
            $state['heartbeat_epoch'] = time();
            return $state;
        }, false);
    }

    public function update(string $runId, string $stage, bool $cancellable): void
    {
        $this->mutate($runId, static function (array $state) use ($stage, $cancellable): array {
            $state['stage'] = $stage;
            $state['cancellable'] = $cancellable;
            if (! $cancellable && ! in_array($stage, ['starting', 'preflight', 'database', 'host-clock', 'runtime-readiness', 'backup'], true)) {
                $state['protected_started'] = true;
            }
            $state['heartbeat_at'] = gmdate(DATE_ATOM);
            $state['heartbeat_epoch'] = time();
            return $state;
        }, false);
    }

    /** @return array<string,mixed> */
    public function requestCancel(string $runId, string $sessionId): array
    {
        $this->recoverInterruptedRun($runId);
        $result = ['ok' => true, 'active' => false, 'message' => 'The installation task is no longer active.'];

        $this->mutate($runId, function (array $state) use ($sessionId, &$result): array {
            if (! ($state['active'] ?? false)) {
                return $state;
            }
            if (! hash_equals((string) ($state['session_hash'] ?? ''), hash('sha256', $sessionId))) {
                throw new RuntimeException('This installation run belongs to another installer session.');
            }
            if (! ($state['cancellable'] ?? false)) {
                $result = [
                    'ok' => false,
                    'active' => true,
                    'cancellable' => false,
                    'message' => 'Nexora is in a protected schema-changing stage. Cancellation is disabled until this protected operation finishes.',
                ];
                return $state;
            }

            $state['cancel_requested'] = true;
            $state['cancel_requested_at'] = gmdate(DATE_ATOM);
            $state['heartbeat_at'] = gmdate(DATE_ATOM);
            $state['heartbeat_epoch'] = time();
            $result = [
                'ok' => true,
                'active' => true,
                'cancellable' => true,
                'message' => 'Cancellation requested. Nexora will stop at the next safe checkpoint.',
            ];

            return $state;
        }, false);

        return $result;
    }

    public function throwIfCancelled(string $runId): void
    {
        $state = $this->read($runId);
        if (($state['cancel_requested'] ?? false) === true && ($state['cancellable'] ?? false) === true) {
            throw new InstallationCancelledException('Installation cancelled safely before any protected schema-changing stage.');
        }
    }

    public function finish(
        string $runId,
        string $status = 'completed',
        ?string $failureStage = null,
        ?string $failureMessage = null,
    ): void {
        $safeStage = trim((string) $failureStage);
        $safeMessage = $this->sanitizeFailureMessage($failureMessage);

        $this->mutate($runId, static function (array $state) use (
            $status,
            $safeStage,
            $safeMessage,
        ): array {
            $state['active'] = false;
            $state['cancellable'] = false;
            $state['status'] = $status;
            $state['finished_at'] = gmdate(DATE_ATOM);
            $state['heartbeat_at'] = gmdate(DATE_ATOM);
            $state['heartbeat_epoch'] = time();

            if ($safeStage !== '') {
                $state['failure_stage'] = mb_substr($safeStage, 0, 80);
            }
            if ($safeMessage !== null) {
                $state['failure_message'] = $safeMessage;
            }

            return $state;
        }, false);
    }

    /** @return array<string,mixed> */
    public function status(string $runId, string $sessionId): array
    {
        $this->recoverInterruptedRun($runId);
        $state = $this->read($runId);
        if ($state === null) {
            return ['active' => false, 'run_id' => $runId];
        }
        if (! hash_equals((string) ($state['session_hash'] ?? ''), hash('sha256', $sessionId))) {
            throw new RuntimeException('This installation run belongs to another installer session.');
        }
        unset($state['session_hash'], $state['database_fingerprint'], $state['database_name_hash']);
        return $state;
    }

    /**
     * Return a recoverable protected-stage run for the same database target.
     * This lets a failed/interrupted Nexora-owned partial schema continue with
     * `migrate`/idempotent seed rather than demanding a second destructive wipe.
     *
     * @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database
     * @return array<string,mixed>|null
     */
    public function recoverableForDatabase(array $database): ?array
    {
        $state = $this->recoveryForDatabase($database);
        if ($state === null || ($state['resume_compatible'] ?? false) !== true) {
            return null;
        }
        return $state;
    }

    /**
     * Return the newest interrupted protected run for the database, together
     * with an exact-source resume compatibility decision.
     *
     * @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database
     * @return array<string,mixed>|null
     */
    public function recoveryForDatabase(array $database): ?array
    {
        $this->recoverInterruptedRuns();
        $fingerprint = $this->databaseFingerprint($database);
        $cutoff = time() - max(3600, (int) config('installer.recovery_window_seconds', 86400));
        $candidates = [];

        foreach ($this->stateFiles() as $path) {
            $state = $this->readPath($path);
            if ($state === null || ($state['active'] ?? false) === true) {
                continue;
            }
            if (($state['database_fingerprint'] ?? '') !== $fingerprint) {
                continue;
            }
            if (($state['protected_started'] ?? false) !== true) {
                continue;
            }
            if (in_array((string) ($state['status'] ?? ''), ['completed', 'cancelled'], true)) {
                continue;
            }
            if ($this->stateEpoch($state) < $cutoff) {
                continue;
            }
            $candidates[] = $state;
        }

        usort($candidates, fn (array $left, array $right): int => $this->stateEpoch($right) <=> $this->stateEpoch($left));
        if ($candidates === []) {
            return null;
        }

        $state = $candidates[0];
        $currentFingerprint = $this->resumeIdentity->fingerprint();
        $storedFingerprint = trim((string) ($state['resume_fingerprint'] ?? ''));
        $compatible = $storedFingerprint !== '' && hash_equals($storedFingerprint, $currentFingerprint);

        $state['resume_compatible'] = $compatible;
        $state['resume_reason'] = $this->resumeReason($state, $compatible);
        $state['current_platform_version'] = (string) config('nexora.version', 'unknown');
        $state['current_installer_protocol'] = Installer::PROTOCOL;
        $state['current_source_generation'] = Installer::SOURCE_GENERATION;
        unset($state['session_hash'], $state['database_fingerprint'], $state['database_name_hash']);

        return $state;
    }

    /** @param array<string,mixed> $state */
    private function resumeReason(array $state, bool $compatible): string
    {
        if ($compatible) {
            return 'The interrupted run matches the exact full source tree, critical source manifest, migrations, seeders and dependency provenance.';
        }
        if (trim((string) ($state['resume_fingerprint'] ?? '')) === '') {
            return 'The interrupted run predates resume-provenance protection. Start clean with backup or explicit overwrite consent.';
        }

        $previousVersion = (string) ($state['platform_version'] ?? 'unknown');
        $previousProtocol = (string) ($state['installer_protocol'] ?? 'unknown');
        return 'The interrupted run was created by a different Nexora installation provenance '
            .'('.$previousVersion.' / '.$previousProtocol.'). Start clean instead of mixing installer generations.';
    }

    /** @return array<string,mixed> */
    public function recoverySummary(?string $sessionId = null): array
    {
        $recovered = $this->recoverInterruptedRuns();
        $active = [];
        $interrupted = [];
        foreach ($this->stateFiles() as $path) {
            $state = $this->readPath($path);
            if ($state === null) continue;
            if ($sessionId !== null && isset($state['session_hash']) && ! hash_equals((string) $state['session_hash'], hash('sha256', $sessionId))) continue;
            $safe = $state;
            unset($safe['session_hash'], $safe['database_fingerprint'], $safe['database_name_hash']);
            if (($state['active'] ?? false) === true) $active[] = $safe;
            elseif (in_array((string) ($state['status'] ?? ''), ['interrupted', 'failed'], true)) $interrupted[] = $safe;
        }
        usort($active, fn (array $a, array $b): int => $this->stateEpoch($b) <=> $this->stateEpoch($a));
        usort($interrupted, fn (array $a, array $b): int => $this->stateEpoch($b) <=> $this->stateEpoch($a));
        return [
            'recovered_now' => $recovered,
            'active' => $active[0] ?? null,
            'interrupted' => $interrupted[0] ?? null,
        ];
    }

    /** @return list<string> */
    public function recoverInterruptedRuns(): array
    {
        $recovered = [];
        foreach ($this->stateFiles() as $path) {
            $state = $this->readPath($path);
            if ($state === null || ($state['active'] ?? false) !== true) continue;
            $runId = (string) ($state['run_id'] ?? '');
            if (preg_match('/^[a-f0-9]{24}$/', $runId) !== 1) continue;
            if (! $this->isStateStale($state) || ! $this->installerMutexAvailable()) continue;
            $this->mutate($runId, static function (array $current): array {
                if (($current['active'] ?? false) !== true) return $current;
                $current['active'] = false;
                $current['cancellable'] = false;
                $current['status'] = 'interrupted';
                $current['recovered_at'] = gmdate(DATE_ATOM);
                $current['finished_at'] = gmdate(DATE_ATOM);
                $current['heartbeat_at'] = gmdate(DATE_ATOM);
                $current['heartbeat_epoch'] = time();
                return $current;
            }, false);
            $recovered[] = $runId;
        }
        return $recovered;
    }

    public function pruneFinishedRuns(): void
    {
        $cutoff = time() - max(86400, (int) config('installer.recovery_window_seconds', 86400) * 7);
        foreach ($this->stateFiles() as $path) {
            $state = $this->readPath($path);
            if ($state === null || ($state['active'] ?? false) === true) continue;
            if ($this->stateEpoch($state) < $cutoff) @unlink($path);
        }
    }

    /** @return array<string,mixed>|null */
    private function read(string $runId): ?array
    {
        $this->assertRunId($runId);
        return $this->readPath($this->path($runId));
    }

    /** @return array<string,mixed>|null */
    private function readPath(string $path): ?array
    {
        if (! is_file($path)) return null;
        $handle = @fopen($path, 'rb');
        if (! is_resource($handle)) return null;
        try {
            @flock($handle, LOCK_SH);
            $json = stream_get_contents($handle);
            $decoded = is_string($json) ? json_decode($json, true) : null;
            return is_array($decoded) ? $decoded : null;
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @param callable(array<string,mixed>):array<string,mixed> $callback */
    private function mutate(string $runId, callable $callback, bool $create): void
    {
        $this->assertRunId($runId);
        $path = $this->path($runId);
        if (! $create && ! is_file($path)) return;
        $handle = @fopen($path, 'c+');
        if (! is_resource($handle)) throw new RuntimeException('Unable to open installation control state.');
        try {
            if (! @flock($handle, LOCK_EX)) throw new RuntimeException('Unable to lock installation control state.');
            rewind($handle);
            $raw = stream_get_contents($handle);
            $state = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
            if (! is_array($state)) $state = [];
            $state = $callback($state);
            $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            ftruncate($handle, 0);
            rewind($handle);
            if (fwrite($handle, $json) === false) throw new RuntimeException('Unable to persist installation control state.');
            if (! fflush($handle)) throw new RuntimeException('Unable to flush installation control state.');
            if (function_exists('fsync') && ! @fsync($handle)) throw new RuntimeException('Unable to sync installation control state.');
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @param array<string,mixed> $state */
    private function writeNew(string $runId, array $state): void
    {
        $this->mutate($runId, static fn (array $_): array => $state, true);
    }

    private function path(string $runId): string
    {
        return $this->directory().'/'.$runId.'.json';
    }

    /** @return list<string> */
    private function stateFiles(): array
    {
        $files = glob($this->directory().'/*.json') ?: [];
        return array_values(array_filter($files, 'is_file'));
    }

    private function directory(): string
    {
        $path = base_path('storage/app/nexora/installation-control');
        if (! is_dir($path) && ! @mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException('Unable to prepare installation control storage.');
        }
        return $path;
    }

    /** @param array<string,mixed> $state */
    private function isStateStale(array $state): bool
    {
        $staleSeconds = max(300, (int) config('installer.run_stale_seconds', 1800));
        return $this->stateEpoch($state) <= time() - $staleSeconds;
    }

    /** @param array<string,mixed> $state */
    private function stateEpoch(array $state): int
    {
        if (isset($state['heartbeat_epoch']) && is_numeric($state['heartbeat_epoch'])) return (int) $state['heartbeat_epoch'];
        foreach (['heartbeat_at', 'finished_at', 'started_at'] as $key) {
            $value = strtotime((string) ($state[$key] ?? ''));
            if ($value !== false) return $value;
        }
        return 0;
    }

    private function installerMutexAvailable(): bool
    {
        $path = (string) config('installer.mutex_path', base_path('storage/app/nexora/installing.lock'));
        if (! is_dir(dirname($path)) && ! @mkdir(dirname($path), 0755, true) && ! is_dir(dirname($path))) return false;
        $handle = @fopen($path, 'c+');
        if (! is_resource($handle)) return false;
        try {
            $locked = @flock($handle, LOCK_EX | LOCK_NB);
            if ($locked) @flock($handle, LOCK_UN);
            return (bool) $locked;
        } finally {
            fclose($handle);
        }
    }

    /** @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database */
    private function databaseFingerprint(array $database): string
    {
        $parts = [
            strtolower((string) ($database['driver'] ?? '')),
            strtolower(trim((string) ($database['host'] ?? ''))),
            (string) ((int) ($database['port'] ?? 0)),
            trim((string) ($database['database'] ?? '')),
            strtolower(trim((string) ($database['username'] ?? ''))),
        ];
        return hash('sha256', implode('|', $parts));
    }

    private function recoverInterruptedRun(string $runId): void
    {
        $state = $this->read($runId);
        if ($state === null || ($state['active'] ?? false) !== true) return;
        if (! $this->isStateStale($state) || ! $this->installerMutexAvailable()) return;
        $this->mutate($runId, static function (array $current): array {
            $current['active'] = false;
            $current['cancellable'] = false;
            $current['status'] = 'interrupted';
            $current['recovered_at'] = gmdate(DATE_ATOM);
            $current['finished_at'] = gmdate(DATE_ATOM);
            $current['heartbeat_at'] = gmdate(DATE_ATOM);
            $current['heartbeat_epoch'] = time();
            return $current;
        }, false);
    }

    private function assertRunId(string $runId): void
    {
        if (preg_match('/^[a-f0-9]{24}$/', $runId) !== 1) throw new RuntimeException('Invalid installation run identifier.');
    }
}
