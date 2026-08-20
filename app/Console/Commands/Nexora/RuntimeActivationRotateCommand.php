<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

final class RuntimeActivationRotateCommand extends Command
{
    protected $signature='nexora:runtime:activation-rotate {--operator= : Real operator identity} {--confirm= : Must equal ROTATE}';
    protected $description='Rotate the runtime activation epoch after intentional cache/runtime activation changes; does not leave maintenance mode or restart PHP-FPM automatically.';

    public function handle(RuntimeActivationIdentity $activation,RuntimeDeploymentIdentity $deployment,InstallationState $installation): int
    {
        if((string)$this->option('confirm')!=='ROTATE'){$this->error('Activation rotation requires --confirm=ROTATE.');return self::INVALID;}
        try{
            if(!$installation->isInstalled())throw new \RuntimeException('Nexora must be installed before manual activation rotation.');
            $deep=$deployment->deepVerify();if(!($deep['ok']??false))throw new \RuntimeException('Deep deployment verification failed: '.implode('; ',(array)($deep['errors']??[])));
            $current=$activation->rotate('manual-runtime-activation',(string)$this->option('operator'));
            $installation->updateMetadata(['activation_epoch'=>$current['activation_epoch'],'runtime_activation_fingerprint'=>$current['activation_fingerprint'],'runtime_activation_cache_sha256'=>$current['framework_cache']['snapshot_sha256']??null,'runtime_activated_at'=>now()->toIso8601String()]);
            $queueRestart=Artisan::call('queue:restart');
            $payload=['schema'=>1,'status'=>'rotated','activation'=>$current,'queue_restart_exit'=>$queueRestart,'maintenance_mode_active'=>app()->isDownForMaintenance(),'automatic_php_fpm_restart'=>false,'automatic_traffic_restore'=>false];
            $this->line(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
            if(($current['opcache']['worker_restart_evidence_required']??false)===true)$this->warn('OPCache timestamp validation is disabled. Restart the PHP web worker pool before restoring traffic and prove that restart in operator evidence.');
            return $queueRestart===0?self::SUCCESS:self::FAILURE;
        }catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
    }
}
