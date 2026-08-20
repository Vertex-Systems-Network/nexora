<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class AutomationModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier:'nexora.automation',
            name:'Nexora Automation & Webhooks',
            version:'0.27.0',
            description:'Event-driven workflow runtime with conditions, retry-safe actions, signed outbound webhooks and verified inbound webhook endpoints.',
            core:true,
            loadOrder:55,
            capabilities:[
                'automation.workflows.read','automation.workflows.write','automation.events.emit','automation.runs.execute',
                'webhooks.destinations.manage','webhooks.inbound.receive','webhooks.outbound.send','admin.navigation.register','http.outbound',
            ],
            dependencies:[
                new ModuleDependency('nexora.admin','^0.5'),
                new ModuleDependency('nexora.discovery','^0.26'),
            ],
            metadata:['workflow_runtime'=>'queued','conditions'=>'server evaluated','webhook_signing'=>'hmac-sha256','inbound_replay_window_seconds'=>300,'idempotency'=>true],
        );
    }

    public function register(): void
    {
        $this->navigation->register(['id'=>'automation','label'=>'Automation','href'=>'/admin/automation','icon'=>'zap','order'=>61,'permission'=>'automation.view']);
    }
    public function boot(): void {}
}
