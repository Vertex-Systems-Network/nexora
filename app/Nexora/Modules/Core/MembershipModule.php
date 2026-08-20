<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class MembershipModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier:'nexora.membership', name:'Nexora Membership', version:'0.32.0',
            description:'Membership plans, entitlements, protected-content access policies and Commerce subscription linkage.', core:true, loadOrder:64,
            capabilities:['membership.plans.read','membership.plans.write','membership.members.read','membership.members.write','membership.access.evaluate','membership.access.manage','membership.commerce.sync','admin.navigation.register'],
            dependencies:[new ModuleDependency('nexora.admin','^0.5'),new ModuleDependency('nexora.documents','^0.18'),new ModuleDependency('nexora.automation','^0.27'),new ModuleDependency('nexora.commerce','^0.30')],
            metadata:['commerce_link'=>'explicit price/subscription link','content_access'=>'policy evaluated centrally','lms'=>'external package boundary'],
        );
    }
    public function register(): void { $this->navigation->register(['id'=>'membership','label'=>'Membership','href'=>'/admin/membership','icon'=>'badge-check','order'=>78,'permission'=>'membership.view']); }
    public function boot(): void {}
}
