<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Nexora\Enterprise\Services\TenantContext;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantRouteBinding
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->tenant->id();

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            $this->assertParameter($parameter, $tenantId);
        }

        return $next($request);
    }

    private function assertParameter(mixed $parameter, ?string $tenantId): void
    {
        if (is_array($parameter)) {
            foreach ($parameter as $value) {
                $this->assertParameter($value, $tenantId);
            }
            return;
        }

        if (! $parameter instanceof Model) {
            return;
        }

        if (! array_key_exists('tenant_id', $parameter->getAttributes())) {
            return;
        }

        $modelTenantId = $parameter->getAttribute('tenant_id');
        abort_if($tenantId === null || $modelTenantId === null || (string) $modelTenantId !== $tenantId, 404);
    }
}
