<?php

use App\Providers\AppServiceProvider;
use App\Providers\CommerceServiceProvider;
use App\Providers\CustomerPortalServiceProvider;
use App\Providers\FormsServiceProvider;
use App\Providers\MarketplaceServiceProvider;
use App\Providers\NexoraServiceProvider;

return [
    AppServiceProvider::class,
    CommerceServiceProvider::class,
    CustomerPortalServiceProvider::class,
    FormsServiceProvider::class,
    MarketplaceServiceProvider::class,
    NexoraServiceProvider::class,
];
