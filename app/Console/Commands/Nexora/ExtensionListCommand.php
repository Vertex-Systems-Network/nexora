<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Models\Extension;
use Illuminate\Console\Command;

final class ExtensionListCommand extends Command
{
    protected $signature = 'nexora:extension:list {--json : Emit machine-readable JSON}';
    protected $description = 'List installed Nexora extensions and active versions.';

    public function handle(): int
    {
        $rows=Extension::query()->withCount('versions')->orderBy('identifier')->get()->map(fn(Extension $extension)=>[
            'identifier'=>$extension->identifier,'name'=>$extension->name,'type'=>$extension->type,'status'=>$extension->status,'version'=>$extension->current_version,'versions'=>$extension->versions_count,
        ])->all();
        if ($this->option('json')) { $this->line(json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $this->table(['Identifier','Name','Type','Status','Active version','Versions'],array_map(fn($r)=>array_values($r),$rows));
        return self::SUCCESS;
    }
}
