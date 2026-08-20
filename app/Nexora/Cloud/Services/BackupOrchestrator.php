<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeBackupRun;
use App\Nexora\Foundation\Transfers\TransferSafety;
use App\Nexora\Installation\DatabaseBackupManager;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class BackupOrchestrator
{
    public function __construct(
        private DatabaseBackupManager $databaseBackups,
        private TransferSafety $transfers,
        private DatabaseDataPlaneIdentity $databaseIdentity,
        private RuntimeStorageDataPlaneIdentity $storageIdentity,
        private RuntimeResourceEnvelopeIdentity $resources,
    ) {}

    public function createDatabaseBackup(?int $requestedBy = null): RuntimeBackupRun
    {
        $connection = (string) config('database.default');
        $cfg = (array) config("database.connections.{$connection}", []);
        $driver = (string) ($cfg['driver'] ?? $connection);
        $resourceEnvelope=$this->resources->assertBackupScratchCapacity();
        $databaseIdentity=$this->databaseIdentity->current(true);$storageIdentity=$this->storageIdentity->current(false);$backupDisk=(string)config('nexora-storage-runtime.backup_disk',config('nexora_cloud.object_storage_disk',config('filesystems.default','local')));$backupProfile=$this->storageIdentity->diskProfile($backupDisk);

        $run = RuntimeBackupRun::query()->create([
            'id' => (string) Str::uuid(), 'type' => 'database', 'status' => 'running', 'driver' => $driver,
            'storage_disk' => $backupDisk, 'requested_by' => $requestedBy, 'started_at' => now(),
        ]);
        $temporaryToken = null; $target=null;

        try {
            $database = [
                'driver' => $driver, 'host' => $cfg['host'] ?? '127.0.0.1', 'port' => isset($cfg['port']) ? (int) $cfg['port'] : null,
                'database' => (string) ($cfg['database'] ?? ''), 'username' => (string) ($cfg['username'] ?? ''), 'password' => (string) ($cfg['password'] ?? ''),
            ];
            if ($driver === 'sqlite') $database['database'] = (string) ($cfg['database'] ?? database_path('database.sqlite'));

            $session = 'runtime-backup:'.$run->id;
            $metadata = $this->databaseBackups->create($database, $session);
            $temporaryToken = is_string($metadata['token'] ?? null) ? (string) $metadata['token'] : null;
            $file = $this->databaseBackups->file((string) $metadata['token'], $session);
            $source=(string)$file['path'];
            $maximum=(int)config('nexora-transfers.backup.max_bytes',53_687_091_200);
            $bytes=$this->transfers->assertSourceFile($source,$maximum,'Protected database snapshot');
            $sourceChecksum=hash_file('sha256',$source);
            if (! is_string($sourceChecksum)) throw new RuntimeException('Protected database snapshot checksum could not be calculated.');

            $extension = pathinfo((string) $file['name'], PATHINFO_EXTENSION) ?: 'bin';
            $target = 'nexora/runtime-backups/'.$run->id.'/database.'.$extension;
            if(($backupProfile['driver']??null)==='local'){$localRoot=(string)config('filesystems.disks.'.$backupDisk.'.root',storage_path('app/private'));$this->transfers->assertLocalCapacity($localRoot,$bytes,(int)config('nexora-transfers.backup.minimum_free_bytes',268_435_456));}
            $input=@fopen($source,'rb');
            if (! is_resource($input)) throw new RuntimeException('Protected database snapshot could not be opened for streaming.');
            try { $stored=Storage::disk($backupDisk)->put($target,$input); }
            finally { fclose($input); }
            if ($stored !== true || ! Storage::disk($backupDisk)->exists($target)) throw new RuntimeException('Protected runtime backup could not be persisted.');
            if ((int)Storage::disk($backupDisk)->size($target)!==$bytes) throw new RuntimeException('Protected runtime backup byte count does not match the source snapshot.');

            $verifyStream=Storage::disk($backupDisk)->readStream($target);
            if (! is_resource($verifyStream)) throw new RuntimeException('Protected runtime backup could not be reopened for checksum verification.');
            try { $verified=$this->transfers->hashStream($verifyStream,$maximum); }
            finally { fclose($verifyStream); }
            if ($verified['bytes']!==$bytes || ! hash_equals($sourceChecksum,$verified['sha256'])) throw new RuntimeException('Protected runtime backup checksum verification failed after streaming persistence.');

            $run->forceFill([
                'status' => 'completed', 'storage_path' => $target, 'checksum_sha256' => $sourceChecksum, 'bytes' => $bytes,
                'manifest' => [
                    'format' => 'nexora-runtime-backup-v1', 'database_connection' => $connection, 'database_driver' => $driver,
                    'source_strategy' => $metadata['strategy'] ?? null, 'object_count' => $metadata['object_count'] ?? null,
                    'created_at' => now()->toIso8601String(), 'restore_mode' => 'planned-offline-restore', 'stream_verified'=>true,
                    'database_data_plane_fingerprint'=>$databaseIdentity['fingerprint']??null,'database_schema_fingerprint'=>$databaseIdentity['schema_fingerprint']??null,'database_server_version'=>$databaseIdentity['normalized_server_version']??null,
                    'runtime_storage_fingerprint'=>$storageIdentity['fingerprint']??null,'runtime_resource_fingerprint'=>$resourceEnvelope['fingerprint']??null,'resource_deep_probe_sha256'=>$resourceEnvelope['deep']['deep_sha256']??null,'backup_storage_disk'=>$backupDisk,'backup_storage_profile_sha256'=>$backupProfile['profile_sha256']??null,'backup_storage_driver'=>$backupProfile['driver']??null,
                ],
                'completed_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            report($e);
            if (is_string($target) && $target!=='') Storage::disk($backupDisk)->delete($target);
            $run->forceFill([
                'status' => 'failed',
                'error_message' => app()->isProduction() ? 'Database backup failed. Review server logs, disk capacity and driver support.' : $e->getMessage(),
                'completed_at' => now(),
            ])->save();
        } finally {
            if (is_string($temporaryToken) && $temporaryToken !== '') $this->databaseBackups->remove($temporaryToken);
        }

        return $run->refresh();
    }

    /** @return array{ok:bool,message:string,checksum?:string} */
    public function verify(RuntimeBackupRun $run): array
    {
        if ($run->status !== 'completed' || ! is_string($run->storage_path) || $run->storage_path === '') return ['ok' => false, 'message' => 'Backup is not in a completed state.'];
        $disk=(string)($run->storage_disk ?: 'local');
        if (! Storage::disk($disk)->exists($run->storage_path)) return ['ok' => false, 'message' => 'Backup artifact is missing from protected storage.'];
        $maximum=(int)config('nexora-transfers.backup.max_bytes',53_687_091_200);
        $stream=Storage::disk($disk)->readStream($run->storage_path);
        if (! is_resource($stream)) return ['ok'=>false,'message'=>'Backup artifact could not be opened for streaming verification.'];
        try { $hashed=$this->transfers->hashStream($stream,$maximum); }
        catch (\Throwable $e) { return ['ok'=>false,'message'=>$e->getMessage()]; }
        finally { fclose($stream); }
        $sizeMatches=$run->bytes===null || (int)$run->bytes===$hashed['bytes'];
        $ok = $sizeMatches && is_string($run->checksum_sha256) && hash_equals($run->checksum_sha256, $hashed['sha256']);
        return ['ok' => $ok, 'message' => $ok ? 'Backup checksum and byte count verified by streaming read.' : 'Backup checksum or byte count mismatch.', 'checksum' => $hashed['sha256']];
    }
}
