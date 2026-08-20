<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class HelpdeskModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier:'nexora.helpdesk', name:'Nexora Helpdesk', version:'0.32.0',
            description:'Provider-neutral support tickets, conversations, internal notes, assignments, priorities and SLA foundations.', core:true, loadOrder:65,
            capabilities:['helpdesk.tickets.read','helpdesk.tickets.write','helpdesk.messages.write','helpdesk.sla.manage','helpdesk.assignments.manage','admin.navigation.register'],
            dependencies:[new ModuleDependency('nexora.admin','^0.5'),new ModuleDependency('nexora.automation','^0.27'),new ModuleDependency('nexora.crm','^0.31'),new ModuleDependency('nexora.commerce','^0.30')],
            metadata:['email_provider'=>'extension boundary','sla'=>'first-response and resolution deadlines','projects'=>'external package boundary'],
        );
    }
    public function register(): void { $this->navigation->register(['id'=>'helpdesk','label'=>'Helpdesk','href'=>'/admin/helpdesk','icon'=>'life-buoy','order'=>79,'permission'=>'helpdesk.view']); }
    public function boot(): void {}
}
