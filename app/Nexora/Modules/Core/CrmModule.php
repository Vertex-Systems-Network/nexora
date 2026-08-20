<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class CrmModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier:'nexora.crm', name:'Nexora CRM', version:'0.31.0',
            description:'Provider-neutral relationship management for organizations, contacts, leads, opportunities, pipelines, activities, timelines and custom fields.', core:true, loadOrder:63,
            capabilities:[
                'crm.organizations.read','crm.organizations.write','crm.contacts.read','crm.contacts.write','crm.leads.read','crm.leads.write',
                'crm.opportunities.read','crm.opportunities.write','crm.activities.read','crm.activities.write','crm.custom-fields.manage','crm.commerce.link','crm.providers.register','admin.navigation.register',
            ],
            dependencies:[new ModuleDependency('nexora.admin','^0.5'),new ModuleDependency('nexora.automation','^0.27'),new ModuleDependency('nexora.commerce','^0.30')],
            metadata:['email_provider'=>'extension boundary','commerce_identity'=>'explicit link only','money_storage'=>'integer minor units','custom_fields'=>'typed definitions and values'],
        );
    }

    public function register(): void
    {
        $this->navigation->register(['id'=>'crm','label'=>'CRM','href'=>'/admin/crm','icon'=>'contact','order'=>75,'permission'=>'crm.view']);
    }

    public function boot(): void {}
}
