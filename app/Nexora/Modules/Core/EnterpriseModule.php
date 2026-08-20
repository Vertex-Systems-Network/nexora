<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class EnterpriseModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier:'nexora.enterprise', name:'Nexora Enterprise', version:'0.33.0',
            description:'Organizations, tenant isolation, verified domains, invitations, enterprise identity contracts, SCIM and governed impersonation.', core:true, loadOrder:66,
            capabilities:['enterprise.organizations.read','enterprise.organizations.write','enterprise.members.manage','enterprise.domains.manage','enterprise.identity.manage','enterprise.scim.manage','enterprise.impersonation.manage','enterprise.audit.read','enterprise.tenant.resolve','admin.navigation.register'],
            dependencies:[new ModuleDependency('nexora.admin','^0.5'),new ModuleDependency('nexora.identity-access','^0.5'),new ModuleDependency('nexora.automation','^0.27')],
            metadata:['tenant_column'=>'tenant_id','sso'=>'adapter registry for OIDC/SAML','scim'=>'bearer-token provisioning foundation','impersonation'=>'reason + immutable session/audit trail'],
        );
    }
    public function register(): void { $this->navigation->register(['id'=>'enterprise','label'=>'Enterprise','href'=>'/admin/enterprise','icon'=>'globe','order'=>80,'permission'=>'enterprise.view']); }
    public function boot(): void {}
}
