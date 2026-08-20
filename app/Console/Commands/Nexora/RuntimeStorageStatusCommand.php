<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class RuntimeStorageStatusCommand extends Command
{
    protected $signature='nexora:runtime:storage-status {--deep : Run write/read/delete probes and local public-link verification} {--assert-installed : Require current storage fingerprint to match installation lineage}';
    protected $description='Inspect the non-secret Nexora media/object/backup storage data-plane identity and optional deep probes.';

    public function handle(RuntimeStorageDataPlaneIdentity $storage,InstallationState $installation): int
    {
        try{
            $current=$storage->current((bool)$this->option('deep'));$errors=[];
            if($this->option('assert-installed')){
                $metadata=$installation->metadata()??[];$expected=strtolower(trim((string)($metadata['runtime_storage_fingerprint']??'')));
                if($expected===''||!hash_equals($expected,(string)$current['fingerprint']))$errors[]='runtime storage fingerprint does not match installed lineage';
            }
            if(($current['status']??null)!=='pass')$errors[]='one or more deep storage probes failed';
            $this->line(json_encode(['status'=>$errors===[]?'pass':'fail','errors'=>$errors,'storage'=>$current],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
            return $errors===[]?self::SUCCESS:self::FAILURE;
        }catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
    }
}
