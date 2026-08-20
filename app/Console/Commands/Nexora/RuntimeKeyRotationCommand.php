<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeKeyRotationService;
use Illuminate\Console\Command;

final class RuntimeKeyRotationCommand extends Command
{
    protected $signature='nexora:runtime:key-rotation {--record : Authorize the current APP_KEY transition} {--commit : Commit the authorized transition after all nodes converge} {--abort : Remove the authorization receipt without changing APP_KEY} {--operator= : Real operator identity} {--reason= : Non-secret abort note} {--confirm= : ROTATE, COMMIT or ABORT}';
    protected $description='Manage explicit APP_KEY rotation continuity without printing or mutating secret key material.';
    public function handle(RuntimeKeyRotationService $rotation): int
    {
        try{
            if($this->option('record')){if((string)$this->option('confirm')!=='ROTATE')throw new \RuntimeException('Recording APP_KEY rotation requires --confirm=ROTATE.');$out=$rotation->record((string)$this->option('operator'));}
            elseif($this->option('commit')){if((string)$this->option('confirm')!=='COMMIT')throw new \RuntimeException('Committing APP_KEY rotation requires --confirm=COMMIT.');$out=$rotation->commit((string)$this->option('operator'));}
            elseif($this->option('abort')){if((string)$this->option('confirm')!=='ABORT')throw new \RuntimeException('Aborting APP_KEY rotation requires --confirm=ABORT.');$out=$rotation->abort((string)$this->option('reason'));}
            else{$out=['receipt'=>$rotation->read(),'validation'=>$rotation->read()!==null?$rotation->validate():[],'cluster'=>$rotation->clusterStatus(),'mutation_performed'=>false];}
        }catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
        $this->line(json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return self::SUCCESS;
    }
}
