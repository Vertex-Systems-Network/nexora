<?php

declare(strict_types=1);

namespace App\Nexora\Observability\Services;

use App\Models\AuditLog;
use App\Models\ObservabilityIncident;
use App\Models\RuntimeMetric;
use Illuminate\Support\Facades\Schema;

final class ObservabilityRetentionService
{
    /** @return array{audit_logs:int,incidents:int,runtime_metrics:int} */
    public function prune(): array
    {
        $auditDays = max(30, min(3650, (int) config('nexora_observability.audit_retention_days', 365)));
        $incidentDays = max(7, min(365, (int) config('nexora_observability.incident_retention_days', 30)));
        $metricDays = max(1, min(3650, (int) config('nexora_cloud.metric_retention_days', 30)));

        $audit = Schema::hasTable('nx_audit_logs')
            ? AuditLog::query()->withoutGlobalScope('nexora_audit_tenant')
                ->where('created_at', '<', now()->subDays($auditDays))->delete()
            : 0;
        $incidents = Schema::hasTable('nx_observability_incidents')
            ? ObservabilityIncident::query()->withoutGlobalScope('nexora_observability_tenant')
                ->where('occurred_at', '<', now()->subDays($incidentDays))->delete()
            : 0;
        $metrics = Schema::hasTable('nx_runtime_metrics')
            ? RuntimeMetric::query()->where('observed_at', '<', now()->subDays($metricDays))->delete()
            : 0;

        return [
            'audit_logs' => (int) $audit,
            'incidents' => (int) $incidents,
            'runtime_metrics' => (int) $metrics,
        ];
    }
}
