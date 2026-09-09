<?php

declare(strict_types=1);

namespace App\Providers;

use App\Nexora\Ai\Services\AiGenerationService;
use App\Nexora\Ai\Services\AiProviderRegistry;
use Illuminate\Support\ServiceProvider;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiProviderRegistry::class);
        $this->app->singleton(AiGenerationService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/ai.php'));
    }
}
