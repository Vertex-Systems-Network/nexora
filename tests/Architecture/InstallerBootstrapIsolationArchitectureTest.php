<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallerBootstrapIsolationArchitectureTest extends TestCase
{
    #[Test]
    public function installer_routes_and_global_web_middleware_remain_database_independent_before_installation(): void
    {
        $routes = (string) file_get_contents(base_path('routes/web.php'));
        $tenant = (string) file_get_contents(app_path('Http/Middleware/ResolveEnterpriseOrganization.php'));
        $locale = (string) file_get_contents(app_path('Http/Middleware/SetLocale.php'));
        $headers = (string) file_get_contents(app_path('Http/Middleware/ApplyPerformanceHeaders.php'));
        $inertia = (string) file_get_contents(app_path('Http/Middleware/HandleInertiaRequests.php'));
        $bootstrap = (string) file_get_contents(base_path('bootstrap/app.php'));

        self::assertStringContainsString('withoutMiddleware([RuntimeNodeHeartbeat::class, ResolveEnterpriseOrganization::class, HandleInertiaRequests::class])', $routes);
        self::assertStringContainsString('if (! $this->installation->isInstalled() || $request->routeIs(\'install.*\'))', $tenant);
        self::assertStringContainsString('$userLocale = $this->installation->isInstalled() ? $request->user()?->locale : null', $locale);
        self::assertStringContainsString('if (! $this->installation->isInstalled())', $headers);
        self::assertStringContainsString("'mode' => 'bootstrap'", $inertia);
        self::assertStringNotContainsString("app(RuntimeDeploymentIdentity::class)->current()],", $inertia);
        self::assertStringContainsString("response()->view('install.error'", $bootstrap);
    }
}
