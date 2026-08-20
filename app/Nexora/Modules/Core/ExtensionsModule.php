<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class ExtensionsModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier:'nexora.extensions', name:'Nexora Extensions', version:'0.29.0',
            description:'Verified extension lifecycle, capability grants, dependency resolution, Forge scaffolding and Marketplace catalog foundations.', core:true, loadOrder:58,
            capabilities:['extensions.registry.read','extensions.lifecycle.manage','extensions.capabilities.grant','marketplace.catalog.read','marketplace.catalog.sync','admin.navigation.register'],
            dependencies:[new ModuleDependency('nexora.supply-chain','^0.28'),new ModuleDependency('nexora.runtime','^0.5'),new ModuleDependency('nexora.admin','^0.5')],
            metadata:['install_boundary'=>'Sentinel ALLOW + immutable content digest','marketplace'=>'catalog download always returns to quarantine before install','runtime_modes'=>['declarative','trusted-php']],
        );
    }
    public function register(): void { $this->navigation->register(['id'=>'extensions','label'=>'Extensions','href'=>'/admin/extensions','icon'=>'blocks','order'=>72,'permission'=>'extensions.view']); }
    public function boot(): void {}
}
