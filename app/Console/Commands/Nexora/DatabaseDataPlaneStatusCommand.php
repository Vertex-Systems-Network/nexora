<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class DatabaseDataPlaneStatusCommand extends Command
{
    protected $signature='nexora:database:data-plane-status {--deep : Include structural schema attestation} {--assert-installed : Require current fingerprints to match installation lineage}';
    protected $description='Inspect the non-secret database server/session identity and optional structural schema fingerprint.';
    public function handle(DatabaseDataPlaneIdentity $identity,InstallationState $installation): int
    {
        try{$current=$identity->current((bool)$this->option('deep'));$errors=[];if($this->option('assert-installed')){$meta=$installation->metadata()??[];$expected=strtolower((string)($meta['database_data_plane_fingerprint']??''));if($expected===''||!hash_equals($expected,(string)$current['fingerprint']))$errors[]='database data-plane fingerprint does not match installed lineage';if($this->option('deep')){$schema=strtolower((string)($meta['database_schema_fingerprint']??''));if($schema===''||!hash_equals($schema,(string)($current['schema_fingerprint']??'')))$errors[]='database schema fingerprint does not match installed lineage';}}$payload=['status'=>$errors===[]?'pass':'fail','errors'=>$errors,'database'=>$current];$this->line(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return $errors===[]?self::SUCCESS:self::FAILURE;}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
    }
}
