<?php

use App\Providers\AppServiceProvider;
use App\Providers\CommerceServiceProvider;
use App\Providers\FormsServiceProvider;
use App\Providers\MarketplaceServiceProvider;
use App\Providers\NexoraServiceProvider;

return [
    AppServiceProvider::class,
    CommerceServiceProvider::class,
    FormsServiceProvider::class,
    MarketplaceServiceProvider::class,
    NexoraServiceProvider::class,
];
