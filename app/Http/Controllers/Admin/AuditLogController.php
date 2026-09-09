<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ObservabilityIncident;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = mb_substr(trim($request->string('search')->toString()), 0, 100);

        $query = AuditLog::query()->with('user:id,name,email')->latest('id');
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('event', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('request_id', 'like', "%{$search}%");
            });
        }

        $incidents = ObservabilityIncident::query()->latest('occurred_at');
        if ($search !== '') {
            $incidents->where(function ($builder) use ($search): void {
                $builder->where('request_id', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%");
            });
        }

        $recentIncidents = $incidents->limit(25)->get()->map(static fn (ObservabilityIncident $incident): array => [
            'id' => $incident->id,
            'severity' => $incident->severity,
            'category' => $incident->category,
            'code' => $incident->code,
            'requestId' => $incident->request_id,
            'routeName' => $incident->route_name,
            'method' => $incident->method,
            'statusCode' => $incident->status_code,
            'durationMs' => $incident->duration_ms,
            'nodeKey' => $incident->node_key,
            'occurredAt' => $incident->occurred_at?->toIso8601String(),
        ])->values();

        return Inertia::render('Admin/Audit/Index', [
            'filters' => ['search' => $search],
            'incidentSummary' => [
                'last24h' => ObservabilityIncident::query()->where('occurred_at', '>=', now()->subDay())->count(),
                'failures24h' => ObservabilityIncident::query()->where('occurred_at', '>=', now()->subDay())->where('category', 'http_failure')->count(),
                'slow24h' => ObservabilityIncident::query()->where('occurred_at', '>=', now()->subDay())->where('category', 'http_latency')->count(),
            ],
            'incidents' => $recentIncidents,
            'logs' => $query->paginate(25)->withQueryString()->through(static fn (AuditLog $log): array => [
                'id' => $log->id,
                'event' => $log->event,
                'user' => $log->user?->only(['id', 'name', 'email']),
                'subjectType' => $log->subject_type,
                'subjectId' => $log->subject_id,
                'ipAddress' => $log->ip_address,
                'requestId' => $log->request_id,
                'metadata' => $log->metadata,
                'createdAt' => $log->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
