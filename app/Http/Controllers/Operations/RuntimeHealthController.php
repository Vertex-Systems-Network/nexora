<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Nexora\Cloud\Services\HealthProbeService;
use Illuminate\Http\JsonResponse;

final class RuntimeHealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'nexora',
            'version' => (string) config('nexora.version', 'unknown'),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function ready(HealthProbeService $health): JsonResponse
    {
        $result = $health->readiness(false);
        return response()->json([
            'status' => $result['ready'] ? 'ready' : 'not_ready',
            'checks' => array_map(static fn (array $check): array => [
                'name' => $check['name'],
                'status' => $check['status'],
                'duration_ms' => $check['duration_ms'],
            ], $result['checks']),
        ], $result['ready'] ? 200 : 503, ['Cache-Control' => 'no-store']);
    }
}
