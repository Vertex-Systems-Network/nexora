<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Nexora\Cloud\Services\NodeIdentity;
use App\Nexora\Cloud\Services\NodeManager;
use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeActivityTracker;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeEngineIdentity;
use App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Cloud\Services\RuntimeServiceDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeVersionGuard;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use App\Nexora\Installation\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class RuntimeNodeHeartbeat
{
    public function __construct(
        private readonly NodeIdentity $identity,
        private readonly NodeManager $nodes,
        private readonly RuntimeActivityTracker $activities,
        private readonly RuntimeVersionGuard $versions,
        private readonly InstallationState $installation,
        private readonly RuntimeDeploymentIdentity $deployment,
        private readonly RuntimeActivationIdentity $activation,
        private readonly RuntimeEngineIdentity $engine,
        private readonly DatabaseDataPlaneIdentity $database,
        private readonly RuntimeStorageDataPlaneIdentity $storage,
        private readonly RuntimeServiceDataPlaneIdentity $services,
        private readonly RuntimeResourceEnvelopeIdentity $resources,
        private readonly RuntimePolicyPlaneIdentity $policyPlane,
        private readonly RuntimeProcessPlane $processPlane,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Health endpoints own their response semantics. Liveness is deliberately
        // minimal, while readiness runs the bounded node/runtime/database/cache
        // probes in HealthProbeService and must be allowed to return structured
        // JSON even when the current node is draining or otherwise not ready.
        if ($request->is('health/live') || $request->is('health/ready')) {
            return $next($request);
        }

        // The public login form must stay available to establish an identity.
        // Deep deployment/cluster fencing is retained for the credential POST
        // and authenticated application requests, but is unnecessary for the
        // read-only guest form and otherwise causes dozens of database probes.
        if ($request->isMethod('GET') && $request->is('login')) {
            return $next($request);
        }

        // Runtime fencing is meaningful only after Nexora has a sealed installation.
        // The installer/bootstrap path may intentionally have no configured database yet,
        // so probing node readiness here would turn a healthy bootstrap into a false 503.
        if (! $this->installation->isInstalled()) {
            return $next($request);
        }

        try {
            $runtime = $this->runtimeHeaders();
        } catch (Throwable $exception) {
            report($exception);

            return response(
                'Nexora could not calculate the runtime deployment identity. '
                .'Run `php artisan nexora:runtime:compatibility-status --deep`.',
                503,
                ['Retry-After' => '30'],
            );
        }

        $this->recordHeartbeatWhenDue();

        $readiness = $this->readinessResponse($runtime);
        if ($readiness !== null) {
            return $readiness;
        }

        $clientFence = $this->staleClientResponse($request, $runtime);
        if ($clientFence !== null) {
            return $clientFence;
        }

        $sessionFence = $this->staleSessionResponse($request, $runtime);
        if ($sessionFence !== null) {
            return $sessionFence;
        }

        return $this->handleTrackedRequest($request, $next, $runtime);
    }

    /** @return array<string, string> */
    private function runtimeHeaders(): array
    {
        $deployment = $this->deployment->current();
        $activation = $this->activation->current();

        return [
            'generation' => (string) $deployment['generation'],
            'asset_version' => $this->deployment->assetVersion(),
            'activation_epoch' => (string) $activation['activation_epoch'],
            'activation_fingerprint' => (string) $activation['activation_fingerprint'],
            'engine' => $this->engine->fingerprintValue(),
            'database' => $this->safeFingerprint(fn (): string => $this->database->fingerprintValue()),
            'storage' => $this->safeFingerprint(fn (): string => $this->storage->fingerprintValue()),
            'service' => $this->safeFingerprint(fn (): string => $this->services->fingerprintValue()),
            'resource' => $this->resources->fingerprintValue(),
            'policy' => $this->policyPlane->fingerprintValue(),
            'process' => $this->processPlane->fingerprintValue(),
        ];
    }

    private function recordHeartbeatWhenDue(): void
    {
        try {
            $key = 'nexora:runtime:web-heartbeat:'.hash('sha256', $this->identity->key());
            $ttl = now()->addSeconds(
                (int) config('nexora-process-runtime.heartbeat_throttle_seconds', 30),
            );

            if (Cache::add($key, 1, $ttl)) {
                $this->processPlane->heartbeat('web');
                $this->nodes->heartbeat();
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @param array<string, string> $runtime */
    private function readinessResponse(array $runtime): ?Response
    {
        try {
            if (! $this->nodes->isReady()) {
                return response(
                    'This Nexora runtime node is draining or in maintenance mode.',
                    503,
                    ['Retry-After' => '30'],
                );
            }

            $assessment = $this->versions->assess();
            if ($assessment['compatible']) {
                return null;
            }

            $mismatches = (array) ($assessment['mismatches'] ?? []);
            $mismatchText = $mismatches === [] ? 'unknown' : implode(',', $mismatches);
            $message = 'Nexora runtime identity mismatch: '.$mismatchText.'. '
                .'Run `php artisan nexora:runtime:compatibility-status --deep`.';

            if (in_array('generation', $mismatches, true)) {
                $message .= ' If dependency locks were intentionally refreshed, review them first and use '
                    .'`php artisan nexora:runtime:dependency-reconcile --operator="<name>" --confirm=RECONCILE` '
                    .'while maintenance mode is enabled.';
            }

            return response($message, 503, [
                'Retry-After' => '30',
                'X-Nexora-Runtime-Version' => (string) $assessment['current_version'],
                'X-Nexora-Compatibility-Mismatch' => $mismatchText,
                'X-Nexora-Deployment-Generation' => $runtime['generation'],
                'X-Nexora-Activation-Epoch' => $runtime['activation_epoch'],
                'X-Nexora-Engine-Fingerprint' => $runtime['engine'],
                'X-Nexora-Database-Fingerprint' => $runtime['database'],
                'X-Nexora-Storage-Fingerprint' => $runtime['storage'],
                'X-Nexora-Service-Fingerprint' => $runtime['service'],
                'X-Nexora-Resource-Fingerprint' => $runtime['resource'],
                'X-Nexora-Policy-Fingerprint' => $runtime['policy'],
                'X-Nexora-Process-Fingerprint' => $runtime['process'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response(
                'Nexora runtime readiness could not be verified. '
                .'Run `php artisan nexora:runtime:compatibility-status --deep`.',
                503,
                ['Retry-After' => '30'],
            );
        }
    }

    /** @param array<string, string> $runtime */
    private function staleClientResponse(Request $request, array $runtime): ?Response
    {
        if (! (bool) config('nexora-upgrade.client_generation_fence_required', true)) {
            return null;
        }

        if (! $request->is('admin/*') || $request->header('X-Inertia') || ! $request->expectsJson()) {
            return null;
        }

        $clientGeneration = strtolower(trim(
            (string) $request->header('X-Nexora-Deployment-Generation', ''),
        ));

        $headerRequired = (bool) config(
            'nexora-upgrade.client_generation_require_json_header',
            true,
        );

        if (! $headerRequired && $clientGeneration === '') {
            return null;
        }

        if ($clientGeneration !== '' && hash_equals($runtime['generation'], $clientGeneration)) {
            return null;
        }

        return response(
            'The admin client belongs to a stale Nexora deployment generation. Reload before retrying.',
            409,
            [
                'X-Nexora-Deployment-Mismatch' => '1',
                'X-Nexora-Deployment-Generation' => $runtime['generation'],
                'X-Nexora-Asset-Version' => $runtime['asset_version'],
            ],
        );
    }

    /** @param array<string, string> $runtime */
    private function staleSessionResponse(Request $request, array $runtime): ?Response
    {
        if (! (bool) config('nexora-runtime.deployment.session_schema_enforced', true)) {
            return null;
        }

        if (! $request->hasSession()) {
            return null;
        }

        $expected = max(1, (int) config('nexora-runtime.deployment.session_schema', 1));
        $stored = $request->session()->get('nexora.runtime_session_schema');

        if ($stored === null) {
            $request->session()->put('nexora.runtime_session_schema', $expected);
            return null;
        }

        if ((int) $stored === $expected) {
            return null;
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response(
            'The Nexora session schema changed. Reload and authenticate again.',
            409,
            [
                'X-Nexora-Session-Schema' => (string) $expected,
                'X-Nexora-Deployment-Generation' => $runtime['generation'],
            ],
        );
    }

    /** @param array<string, string> $runtime */
    private function handleTrackedRequest(
        Request $request,
        Closure $next,
        array $runtime,
    ): Response {
        try {
            $activity = $this->activities->begin(
                'web',
                (string) ($request->attributes->get('request_id') ?? bin2hex(random_bytes(8))),
                [
                    'method' => $request->method(),
                    'path' => substr($request->path(), 0, 180),
                    'deployment_generation' => $runtime['generation'],
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            return response(
                'Nexora runtime admission is temporarily closed for a protected platform cutover.',
                503,
                [
                    'Retry-After' => '30',
                    'X-Nexora-Cutover-Barrier' => 'active',
                    'X-Nexora-Deployment-Generation' => $runtime['generation'],
                ],
            );
        }

        try {
            $response = $next($request);
            $this->attachRuntimeHeaders($response, $runtime);

            return $response;
        } finally {
            try {
                $this->activities->end($activity);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /** @param array<string, string> $runtime */
    private function attachRuntimeHeaders(Response $response, array $runtime): void
    {
        $response->headers->set('X-Nexora-Deployment-Generation', $runtime['generation']);
        $response->headers->set('X-Nexora-Asset-Version', $runtime['asset_version']);
        $response->headers->set(
            'X-Nexora-Session-Schema',
            (string) max(1, (int) config('nexora-runtime.deployment.session_schema', 1)),
        );
        $response->headers->set('X-Nexora-Activation-Epoch', $runtime['activation_epoch']);
        $response->headers->set('X-Nexora-Activation-Fingerprint', $runtime['activation_fingerprint']);
        $response->headers->set('X-Nexora-Engine-Fingerprint', $runtime['engine']);
        $response->headers->set('X-Nexora-Database-Fingerprint', $runtime['database']);
        $response->headers->set('X-Nexora-Storage-Fingerprint', $runtime['storage']);
        $response->headers->set('X-Nexora-Service-Fingerprint', $runtime['service']);
        $response->headers->set('X-Nexora-Resource-Fingerprint', $runtime['resource']);
        $response->headers->set('X-Nexora-Policy-Fingerprint', $runtime['policy']);
        $response->headers->set('X-Nexora-Process-Fingerprint', $runtime['process']);
    }

    private function safeFingerprint(callable $resolver): string
    {
        try {
            $value = trim((string) $resolver());
            return $value !== '' ? $value : 'unavailable';
        } catch (Throwable) {
            return 'unavailable';
        }
    }
}
