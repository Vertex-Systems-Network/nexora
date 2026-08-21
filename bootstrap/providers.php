<?php

use App\Providers\AiServiceProvider;
use App\Providers\ApiServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\CommerceServiceProvider;
use App\Providers\ContentMigrationServiceProvider;
use App\Providers\CustomerPortalServiceProvider;
use App\Providers\FormsServiceProvider;
use App\Providers\MarketplaceServiceProvider;
use App\Providers\NexoraServiceProvider;

return [
    AppServiceProvider::class,
    AiServiceProvider::class,
    ApiServiceProvider::class,
    CommerceServiceProvider::class,
    ContentMigrationServiceProvider::class,
    CustomerPortalServiceProvider::class,
    FormsServiceProvider::class,
    MarketplaceServiceProvider::class,
    NexoraServiceProvider::class,
];
