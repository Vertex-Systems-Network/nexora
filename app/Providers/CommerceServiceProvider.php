<?php

declare(strict_types=1);

namespace App\Providers;

use App\Nexora\Commerce\Services\ProviderBillingService;
use Illuminate\Support\ServiceProvider;

final class CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProviderBillingService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/commerce.php'));
    }
}
