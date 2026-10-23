<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Permission;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/**
 * SECURITY-BASELINE-200 / authorization matrix increment: every admin mutation is
 * permission-gated (or explicitly self-scoped below), every admin route sits behind
 * auth + admin + tenant binding, and every referenced permission is seeded.
 */
final class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** Admin mutations deliberately without a permission gate, with the reason. */
    private const SELF_SCOPED_MUTATIONS = [
        'admin.saved-views.store' => 'creates a view owned by the acting user only',
        'admin.saved-views.destroy' => 'controller aborts 404 unless the view belongs to the acting user',
        'admin.enterprise.impersonation.stop' => 'must remain available to end an active impersonation',
    ];

    public function test_every_admin_mutation_requires_a_permission(): void
    {
        $ungated = [];
        foreach ($this->adminRoutes() as $route) {
            $mutating = array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']) !== [];
            if (! $mutating || $this->permissions($route) !== []) {
                continue;
            }
            if (! array_key_exists((string) $route->getName(), self::SELF_SCOPED_MUTATIONS)) {
                $ungated[] = implode('|', $route->methods()).' '.$route->uri().' ('.$route->getName().')';
            }
        }

        $this->assertSame([], $ungated, 'Admin mutations without permission: middleware.');
    }

    public function test_self_scoped_allowlist_has_no_stale_entries(): void
    {
        $names = array_map(fn (Route $route): string => (string) $route->getName(), $this->adminRoutes());

        foreach (array_keys(self::SELF_SCOPED_MUTATIONS) as $name) {
            $this->assertContains($name, $names, "Stale allowlist entry {$name}.");
        }
    }

    public function test_every_admin_route_is_authenticated_admin_and_tenant_bound(): void
    {
        $missing = [];
        foreach ($this->adminRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            foreach (['auth', 'admin', \App\Http\Middleware\EnsureTenantRouteBinding::class] as $required) {
                if (! in_array($required, $middleware, true)) {
                    $missing[] = $route->uri().' lacks '.$required;
                }
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_every_referenced_permission_is_seeded(): void
    {
        $this->seed(NexoraCoreSeeder::class);

        $referenced = [];
        foreach (Router::getRoutes()->getRoutes() as $route) {
            foreach ($this->permissions($route) as $permission) {
                $referenced[$permission] = true;
            }
        }

        $seeded = Permission::query()->pluck('slug')->all();
        $unknown = array_values(array_diff(array_keys($referenced), $seeded));
        sort($unknown);

        $this->assertNotEmpty($referenced);
        $this->assertSame([], $unknown, 'Routes reference permissions that are never seeded (unreachable for every role).');
    }

    /** @return list<Route> */
    private function adminRoutes(): array
    {
        return array_values(array_filter(
            Router::getRoutes()->getRoutes(),
            fn (Route $route): bool => str_starts_with((string) $route->getName(), 'admin.'),
        ));
    }

    /** @return list<string> */
    private function permissions(Route $route): array
    {
        $found = [];
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'permission:')) {
                $found[] = substr($middleware, strlen('permission:'));
            }
        }

        return $found;
    }
}
