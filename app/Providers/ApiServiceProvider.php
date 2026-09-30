<?php

declare(strict_types=1);

namespace App\Providers;

use App\Nexora\Api\Contracts\PublicApiContract;
use App\Nexora\Api\Services\ApiAbilityRegistry;
use App\Nexora\Api\Services\CorePublicApiContract;
use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use Illuminate\Support\ServiceProvider;

final class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApiAbilityRegistry::class);
        $this->app->singleton(PublicApiContract::class, CorePublicApiContract::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/developer-api.php'));

        if ($this->app->bound(AdminNavigationContract::class)) {
            $this->app->make(AdminNavigationContract::class)->register([
                'id' => 'developer-api',
                'label' => 'API & Integrations',
                'href' => '/admin/developer/api-tokens',
                'icon' => 'code',
                'order' => 92,
                'permission' => 'enterprise.identity.manage',
            ]);
        }
    }
}
