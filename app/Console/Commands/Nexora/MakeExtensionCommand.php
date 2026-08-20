<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MakeExtensionCommand extends Command
{
    protected $signature = 'nexora:make:extension {identifier} {--name=} {--type=extension}';
    protected $description = 'Create a Forge-compatible Nexora extension source scaffold.';

    public function handle(): int
    {
        $id=strtolower(trim((string)$this->argument('identifier')));
        if (preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)+$/',$id)!==1) { $this->error('Use a namespaced identifier such as vendor.extension.'); return self::FAILURE; }
        $type=(string)$this->option('type'); if (! in_array($type,['extension','app','integration','studio-pack'],true)) { $this->error('Type must be extension, app, integration or studio-pack.'); return self::FAILURE; }
        $name=trim((string)($this->option('name') ?: ucwords(str_replace(['.','-','_'],' ',$id))));
        $root=base_path('extensions/'.$id); if (is_dir($root)) { $this->error('Extension source directory already exists: '.$root); return self::FAILURE; }
        foreach (['src','resources','database/migrations','tests'] as $dir) File::ensureDirectoryExists($root.'/'.$dir,0755,true);
        $manifest=['schema'=>'https://nexora.dev/schemas/package-v1.json','id'=>$id,'name'=>$name,'type'=>$type,'version'=>'0.1.0','description'=>'','requires'=>['nexora'=>'>=0.34 <2.0'],'runtime'=>['mode'=>'declarative'],'capabilities'=>[],'dependencies'=>(object)[],'migrations'=>['policy'=>'none','schema_compatible_rollback'=>false]];
        File::put($root.'/nexora.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
        File::put($root.'/README.md',"# {$name}\n\nForge source package for Nexora. Build/sign the ZIP outside the runtime installation directory, then upload it through Sentinel.\n");
        File::put($root.'/database/migrations/.gitkeep',''); File::put($root.'/tests/.gitkeep','');
        File::put($root.'/composer.json',json_encode(['name'=>str_replace('.','/',$id),'type'=>'nexora-extension','require'=>['php'=>'^8.3']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
        $this->info('Created Forge extension scaffold: '.$root); $this->line('Next: add only requested capabilities to nexora.json, package the directory as ZIP, then send it through Sentinel.');
        return self::SUCCESS;
    }
}
