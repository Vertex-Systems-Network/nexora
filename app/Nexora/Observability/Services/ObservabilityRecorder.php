<?php

declare(strict_types=1);

namespace App\Nexora\Observability\Services;

use App\Http\Middleware\AssignRequestId;
use App\Models\ApiAccessToken;
use App\Models\ObservabilityIncident;
use App\Nexora\Cloud\Services\NodeIdentity;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final readonly class ObservabilityRecorder
{
    public function __construct(
        private TenantContext $tenant,
        private NodeIdentity $node,
        private TelemetrySanitizer $sanitizer,
    ) {}

    public function captureHttp(
        Request $request,
        int $statusCode,
        float $durationMs,
        ?Throwable $exception = null,
    ): ?ObservabilityIncident {
        try {
            if (! Schema::hasTable('nx_observability_incidents')) {
                return null;
            }

            $slowThreshold = max(250, min(60_000, (int) config('nexora_observability.slow_request_ms', 1500)));
            $duration = max(0, min(86_400_000, (int) round($durationMs)));
            $isFailure = $statusCode >= 500;
            $isSlow = $duration >= $slowThreshold;
            if (! $isFailure && ! $isSlow) {
                return null;
            }

            $requestId = (string) ($request->attributes->get(AssignRequestId::ATTRIBUTE)
                ?: $request->headers->get('X-Request-Id')
                ?: '');
            if (preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $requestId) !== 1) {
                $requestId = (string) Str::uuid();
            }

            $token = $request->attributes->get(ApiAccessToken::class);
            $tenantId = $token instanceof ApiAccessToken
                ? (is_string($token->tenant_id) ? $token->tenant_id : null)
                : $this->tenant->id();
            $routeName = $request->route()?->getName();

            return ObservabilityIncident::query()->create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'user_id' => $request->user()?->id,
                'request_id' => $requestId,
                'category' => $isFailure ? 'http_failure' : 'http_latency',
                'severity' => $isFailure ? 'error' : 'warning',
                'code' => $isFailure ? 'HTTP_'.max(500, min(599, $statusCode)) : 'SLOW_REQUEST',
                'route_name' => is_string($routeName) && $routeName !== '' ? mb_substr($routeName, 0, 180) : null,
                'method' => mb_substr(strtoupper($request->method()), 0, 12),
                'status_code' => max(100, min(599, $statusCode)),
                'duration_ms' => $duration,
                'node_key' => mb_substr($this->node->key(), 0, 190),
                'metadata' => $this->sanitizer->metadata(array_filter([
                    'slow_threshold_ms' => $slowThreshold,
                    'exception_fingerprint' => $exception ? hash('sha256', $exception::class) : null,
                    'api_version' => $request->is('api/v1/*') ? 'v1' : null,
                ], static fn ($value): bool => $value !== null)),
                'occurred_at' => now(),
            ]);
        } catch (Throwable $telemetryFailure) {
            report($telemetryFailure);
            return null;
        }
    }
}
