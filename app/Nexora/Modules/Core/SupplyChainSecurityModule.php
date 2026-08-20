<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class SupplyChainSecurityModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier:'nexora.supply-chain',
            name:'Nexora Supply Chain Security',
            version:'0.28.0',
            description:'SBOM inventory, publisher trust, Ed25519 artifact verification, provenance and execution-policy foundations layered on Sentinel quarantine.',
            core:true,
            loadOrder:51,
            capabilities:[
                'security.supply-chain.read','security.artifacts.verify','security.publishers.manage','security.sandbox.evaluate','admin.navigation.register',
            ],
            dependencies:[
                new ModuleDependency('nexora.sentinel','^0.5'),
                new ModuleDependency('nexora.admin','^0.5'),
            ],
            metadata:[
                'sbom'=>'CycloneDX 1.5 compatible inventory',
                'signature'=>'Ed25519 detached content digest',
                'provenance'=>'subject digest binding',
                'sandbox'=>'policy adapter foundation; no OS isolation claim',
            ],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id'=>'supply-chain','label'=>'Supply Chain','href'=>'/admin/security/supply-chain','icon'=>'package-check','order'=>46,'permission'=>'security.supply-chain.view',
        ]);
    }

    public function boot(): void {}
}
