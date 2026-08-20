<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Enterprise\Services\TenantAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePermission
{
    public function __construct(private readonly AuditManager $audit, private readonly TenantAuthorizationService $tenantAuthorization) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasPermission($permission) || ! $this->tenantAuthorization->allows($user, $permission)) {
            $this->audit->record('authorization.denied', metadata: [
                'permission' => $permission,
                'path' => $request->path(),
                'method' => $request->method(),
            ]);
            abort(403);
        }

        return $next($request);
    }
}
