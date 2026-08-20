<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class SearchAnalyticsModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier:'nexora.discovery',
            name:'Nexora Search, Analytics & Crawler',
            version:'0.26.0',
            description:'First-party content search, privacy-aware content analytics and observable SEO crawler/content audit.',
            core:true,
            loadOrder:52,
            capabilities:[
                'search.index.read','search.index.write','search.public.query','analytics.events.write','analytics.metrics.read','analytics.metrics.aggregate',
                'seo.crawler.read','seo.crawler.run','admin.navigation.register','http.outbound',
            ],
            dependencies:[
                new ModuleDependency('nexora.documents','^0.18'),
                new ModuleDependency('nexora.seo','^0.19'),
                new ModuleDependency('nexora.publishing','^0.22'),
            ],
            metadata:['search_index'=>'database','analytics'=>'privacy-aware first-party','crawler'=>'same-host only','synthetic_seo_score'=>false],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id'=>'discovery','label'=>'Search & Analytics','href'=>'/admin/discovery','icon'=>'gauge','order'=>59,'permission'=>'discovery.view',
        ]);
    }

    public function boot(): void {}
}
