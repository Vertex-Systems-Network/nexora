<?php

declare(strict_types=1);

namespace App\Providers;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Migrations\Services\ContentExportService;
use App\Nexora\Migrations\Services\ContentMigrationManager;
use App\Nexora\Migrations\Services\WordPressContentImporter;
use App\Nexora\Migrations\WordPress\WordPressWxrReader;
use Illuminate\Support\ServiceProvider;

final class ContentMigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WordPressWxrReader::class);
        $this->app->scoped(ContentMigrationManager::class);
        $this->app->scoped(WordPressContentImporter::class);
        $this->app->scoped(ContentExportService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/content-migrations.php'));

        if ($this->app->bound(AdminNavigationContract::class)) {
            $this->app->make(AdminNavigationContract::class)->register([
                'id' => 'content-migrations',
                'label' => 'Import / Export',
                'href' => '/admin/migrations',
                'icon' => 'download',
                'order' => 93,
                'permission' => 'documents.view',
            ]);
        }
    }
}
