<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Models\RuntimeBackupRun;
use App\Nexora\Cloud\Services\BackupOrchestrator;
use App\Nexora\Cloud\Services\BackupRestoreRehearsalService;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use Illuminate\Support\Facades\Schema;

final readonly class UpgradeBackupVerifier
{
    public function __construct(private BackupOrchestrator $backups, private BackupRestoreRehearsalService $restoreReadiness, private DatabaseDataPlaneIdentity $databaseIdentity, private RuntimeStorageDataPlaneIdentity $storageIdentity) {}

    /** @return array{ok:bool,type:string,reference:?string,checksum:?string,detail:string,restore_ready:bool,restore_plan_id:?string,restore_readiness_sha256:?string,database_fingerprint:?string,verified_at:?string} */
    public function verify(?string $backupId, ?string $externalEvidence, string $sourceVersion): array
    {
        if ($backupId !== null && $backupId !== '') {
            if (! Schema::hasTable('nx_runtime_backup_runs')) return $this->fail('runtime',$backupId,'Runtime backup table is unavailable.');
            $run = RuntimeBackupRun::query()->find($backupId);
            if (! $run) return $this->fail('runtime',$backupId,'Runtime backup record not found.');
            $verified = $this->backups->verify($run);
            if(! (bool)($verified['ok']??false)) return $this->fail('runtime',(string)$run->id,(string)($verified['message']??'Runtime backup verification failed.'),$run->checksum_sha256?(string)$run->checksum_sha256:null);
            try{$readiness=$this->restoreReadiness->validate($run);}catch(\Throwable $e){return $this->fail('runtime',(string)$run->id,'Restore-readiness validation failed: '.$e->getMessage(),(string)$run->checksum_sha256);}
            $restoreReady=($readiness['status']??null)==='pass' && ($readiness['automatic_destructive_restore']??true)===false;
            $manifest=is_array($run->manifest)?$run->manifest:[];$currentDatabase=$this->databaseIdentity->current(true);$currentStorage=$this->storageIdentity->current(false);
            $backupDataPlane=strtolower(trim((string)($manifest['database_data_plane_fingerprint']??'')));$backupSchema=strtolower(trim((string)($manifest['database_schema_fingerprint']??'')));
            if((bool)config('nexora-database-runtime.require_backup_schema_binding',true)&&($backupDataPlane===''||$backupSchema===''||!hash_equals((string)$currentDatabase['fingerprint'],$backupDataPlane)||!hash_equals((string)$currentDatabase['schema_fingerprint'],$backupSchema)))return $this->fail('runtime',(string)$run->id,'Backup database data-plane/schema identity does not match the current pre-upgrade database.',(string)$run->checksum_sha256);$backupStorage=strtolower(trim((string)($manifest['runtime_storage_fingerprint']??'')));$backupStorageProfile=strtolower(trim((string)($manifest['backup_storage_profile_sha256']??'')));$currentBackupProfile=(array)($currentStorage['roles']['backup']??[]);if((bool)config('nexora-storage-runtime.require_exact_data_plane',true)&&($backupStorage===''||$backupStorageProfile===''||!hash_equals((string)$currentStorage['fingerprint'],$backupStorage)||!hash_equals((string)($currentBackupProfile['profile_sha256']??''),$backupStorageProfile)))return $this->fail('runtime',(string)$run->id,'Backup persistent storage identity does not match the current pre-upgrade storage data-plane.',(string)$run->checksum_sha256);
            $databaseFingerprint=hash('sha256',json_encode([
                'connection'=>$manifest['database_connection']??null,
                'driver'=>$manifest['database_driver']??$run->driver,
                'backup_id'=>(string)$run->id,
                'checksum'=>(string)$run->checksum_sha256,
            ],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
            $readinessHash=hash('sha256',json_encode([
                'backup_id'=>(string)$run->id,
                'checksum'=>(string)$run->checksum_sha256,
                'restore_plan_id'=>$readiness['restore_plan_id']??null,
                'steps'=>$readiness['steps']??[],
                'automatic_destructive_restore'=>$readiness['automatic_destructive_restore']??null,
                'requires_external_copy'=>$readiness['requires_external_copy']??null,
                'backup_storage_disk'=>$readiness['backup_storage_disk']??null,
                'backup_storage_profile_sha256'=>$readiness['backup_storage_profile_sha256']??null,
            ],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
            return [
                'ok'=>$restoreReady,
                'type'=>'runtime',
                'reference'=>(string)$run->id,
                'checksum'=>(string)$run->checksum_sha256,
                'detail'=>$restoreReady?'Runtime backup checksum and guarded restore-readiness plan verified.':'Runtime backup exists but guarded restore-readiness plan is not PASS.',
                'restore_ready'=>$restoreReady,
                'restore_plan_id'=>isset($readiness['restore_plan_id'])?(string)$readiness['restore_plan_id']:null,
                'restore_readiness_sha256'=>$readinessHash,
                'database_fingerprint'=>$databaseFingerprint,
                'verified_at'=>now()->toIso8601String(),'database_data_plane_fingerprint'=>$currentDatabase['fingerprint']??null,'database_schema_fingerprint'=>$currentDatabase['schema_fingerprint']??null,'storage_data_plane_fingerprint'=>$currentStorage['fingerprint']??null,'backup_storage_profile_sha256'=>$currentBackupProfile['profile_sha256']??null,
            ];
        }

        if ($externalEvidence !== null && $externalEvidence !== '') {
            $path = $this->resolveEvidencePath($externalEvidence);
            if (! is_file($path)) return $this->fail('external',$externalEvidence,'External backup evidence file does not exist.');
            try { $data=json_decode((string)file_get_contents($path),true,64,JSON_THROW_ON_ERROR); }
            catch (\Throwable $e) { return $this->fail('external',$externalEvidence,'External backup evidence is invalid JSON: '.$e->getMessage()); }
            if(!is_array($data)) return $this->fail('external',$externalEvidence,'External backup evidence must be a JSON object.');
            $checksum=trim((string)($data['backup_sha256']??''));$operator=trim((string)($data['operator']??''));$verifiedAt=strtotime((string)($data['verified_at']??''));$ttl=(int)config('nexora-upgrade.backup_evidence_ttl_hours',72);$fresh=$verifiedAt!==false&&$verifiedAt<=time()+300&&$verifiedAt>=time()-$ttl*3600;
            $restore=is_array($data['restore_readiness']??null)?$data['restore_readiness']:[];$restoreHash=trim((string)($restore['plan_sha256']??''));$databaseFingerprint=trim((string)($data['database_fingerprint_sha256']??''));$currentDatabase=$this->databaseIdentity->current(true);$currentStorage=$this->storageIdentity->current(false);$databaseDataPlane=trim((string)($data['database_data_plane_sha256']??''));$databaseSchema=trim((string)($data['database_schema_sha256']??''));$storageDataPlane=trim((string)($data['storage_data_plane_sha256']??''));$backupStorageProfile=trim((string)($data['backup_storage_profile_sha256']??''));$currentBackupProfile=(array)($currentStorage['roles']['backup']??[]);
            $ok=($data['schema']??null)===4
                && ($data['status']??null)==='pass'
                && ($data['source_version']??null)===$sourceVersion
                && preg_match('/^[a-f0-9]{64}$/i',$checksum)===1
                && preg_match('/^[a-f0-9]{64}$/i',$databaseFingerprint)===1
                && preg_match('/^[a-f0-9]{64}$/i',$databaseDataPlane)===1 && hash_equals((string)$currentDatabase['fingerprint'],strtolower($databaseDataPlane))
                && preg_match('/^[a-f0-9]{64}$/i',$databaseSchema)===1 && hash_equals((string)$currentDatabase['schema_fingerprint'],strtolower($databaseSchema))
                && preg_match('/^[a-f0-9]{64}$/i',$storageDataPlane)===1 && hash_equals((string)$currentStorage['fingerprint'],strtolower($storageDataPlane))
                && preg_match('/^[a-f0-9]{64}$/i',$backupStorageProfile)===1 && hash_equals((string)($currentBackupProfile['profile_sha256']??''),strtolower($backupStorageProfile))
                && $operator!=='' && !in_array(strtolower($operator),['operator','operator-name','your name'],true)
                && $fresh
                && ($restore['status']??null)==='pass'
                && preg_match('/^[a-f0-9]{64}$/i',$restoreHash)===1
                && ($restore['automatic_destructive_restore']??true)===false;
            return [
                'ok'=>$ok,
                'type'=>'external',
                'reference'=>$externalEvidence,
                'checksum'=>$checksum!==''?$checksum:null,
                'detail'=>$ok?'Fresh external backup evidence, exact database/storage data-plane identities, and guarded restore-readiness plan accepted.':'External backup evidence must be schema 4 PASS, fresh, match source_version plus current database/schema/storage fingerprints, include a real operator, backup SHA-256, and a PASS non-destructive restore-readiness plan.',
                'restore_ready'=>$ok,
                'restore_plan_id'=>isset($restore['plan_id'])?(string)$restore['plan_id']:null,
                'restore_readiness_sha256'=>$restoreHash!==''?$restoreHash:null,
                'database_fingerprint'=>$databaseFingerprint!==''?$databaseFingerprint:null,
                'verified_at'=>isset($data['verified_at'])?(string)$data['verified_at']:null,'database_data_plane_fingerprint'=>$databaseDataPlane!==''?$databaseDataPlane:null,'database_schema_fingerprint'=>$databaseSchema!==''?$databaseSchema:null,'storage_data_plane_fingerprint'=>$storageDataPlane!==''?$storageDataPlane:null,'backup_storage_profile_sha256'=>$backupStorageProfile!==''?$backupStorageProfile:null,
            ];
        }

        return $this->fail('none',null,'No verified backup evidence supplied.');
    }

    /** @return array{ok:bool,type:string,reference:?string,checksum:?string,detail:string,restore_ready:bool,restore_plan_id:?string,restore_readiness_sha256:?string,database_fingerprint:?string,verified_at:?string} */
    private function fail(string $type,?string $reference,string $detail,?string $checksum=null): array
    {
        return ['ok'=>false,'type'=>$type,'reference'=>$reference,'checksum'=>$checksum,'detail'=>$detail,'restore_ready'=>false,'restore_plan_id'=>null,'restore_readiness_sha256'=>null,'database_fingerprint'=>null,'verified_at'=>null,'database_data_plane_fingerprint'=>null,'database_schema_fingerprint'=>null,'storage_data_plane_fingerprint'=>null,'backup_storage_profile_sha256'=>null];
    }

    private function resolveEvidencePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) return $path;
        return base_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
    }
}
