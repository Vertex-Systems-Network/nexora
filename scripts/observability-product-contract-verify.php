<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required observability source file missing: {$relative}";
        return '';
    }
    $value = file_get_contents($path);
    if ($value === false) {
        $errors[] = "Unable to read observability source file: {$relative}";
        return '';
    }
    return $value;
};
$require = static function (string $source, array $needles, string $scope) use (&$errors): void {
    foreach ($needles as $needle => $label) {
        if ($source !== '' && ! str_contains($source, (string) $needle)) {
            $errors[] = "{$scope} missing: {$label}.";
        }
    }
};
$forbid = static function (string $source, array $needles, string $scope) use (&$errors): void {
    foreach ($needles as $needle => $label) {
        if ($source !== '' && str_contains($source, (string) $needle)) {
            $errors[] = "{$scope} contains forbidden {$label}.";
        }
    }
};

$migration = $read('database/migrations/2026_08_22_000200_add_observability_governance.php');
$auditModel = $read('app/Models/AuditLog.php');
$incidentModel = $read('app/Models/ObservabilityIncident.php');
$audit = $read('app/Nexora/Security/Audit/AuditManager.php');
$sanitizer = $read('app/Nexora/Observability/Services/TelemetrySanitizer.php');
$recorder = $read('app/Nexora/Observability/Services/ObservabilityRecorder.php');
$middleware = $read('app/Http/Middleware/ObserveRequestOutcome.php');
$bootstrap = $read('bootstrap/app.php');
$provider = $read('app/Providers/ObservabilityServiceProvider.php');
$providers = $read('bootstrap/providers.php');
$retention = $read('app/Nexora/Observability/Services/ObservabilityRetentionService.php');
$pruneCommand = $read('app/Console/Commands/PruneObservability.php');
$console = $read('routes/console.php');
$controller = $read('app/Http/Controllers/Admin/AuditLogController.php');
$ui = $read('resources/js/admin/pages/Admin/Audit/Index.tsx');
$runtimeTracker = $read('app/Nexora/Cloud/Services/RuntimeActivityTracker.php');
$test = $read('tests/Feature/Observability/ObservabilityProductTest.php');
$progress = $read('NEXORA_PROGRESS.md');

$require($migration, [
    "\$table->uuid('tenant_id')->nullable()" => 'nullable tenant identity on legacy audit logs',
    "\$table->index(['tenant_id', 'created_at']" => 'tenant/time audit index',
    "Schema::create('nx_observability_incidents'" => 'durable incident table',
    "\$tenantIds->count() === 1" => 'unambiguous-only audit history backfill',
    "where('status', 'active')" => 'active membership backfill boundary',
    "\$table->index(['tenant_id', 'occurred_at']" => 'tenant/time incident index',
], 'observability migration');
$forbid($migration, [
    'DB::statement(' => 'raw SQL migration statement',
    '->after(' => 'schema-order-specific column placement',
], 'observability migration');

$require($auditModel, [
    "addGlobalScope('nexora_audit_tenant'" => 'tenant audit read scope',
    "qualifyColumn('tenant_id')" => 'qualified tenant filter',
], 'audit model');
$require($incidentModel, [
    "addGlobalScope('nexora_observability_tenant'" => 'tenant incident read scope',
    "qualifyColumn('tenant_id')" => 'qualified incident tenant filter',
], 'incident model');

$require($audit, [
    'TenantContext $tenant' => 'explicit tenant dependency',
    'TelemetrySanitizer $sanitizer' => 'telemetry sanitizer dependency',
    "'tenant_id' => \$this->tenant->id()" => 'explicit tenant audit assignment',
    "'metadata' => \$this->sanitizer->metadata(\$metadata)" => 'sanitized audit metadata',
    "FILTER_VALIDATE_IP" => 'validated audit IP',
], 'audit recorder');
$require($sanitizer, [
    'SENSITIVE_KEY' => 'sensitive-key policy',
    "'[REDACTED]'" => 'redaction marker',
    'if ($depth >= 4)' => 'metadata depth bound',
    'if ($seen >= 50)' => 'metadata entry bound',
    'mb_substr($item, 0, 500)' => 'metadata string bound',
], 'telemetry sanitizer');

$require($recorder, [
    "Schema::hasTable('nx_observability_incidents')" => 'pre-migration fail-open table guard',
    '$statusCode >= 500' => 'HTTP failure admission',
    '$duration >= $slowThreshold' => 'slow-request admission',
    'ApiAccessToken::class' => 'API token tenant correlation',
    "hash('sha256', \$exception::class)" => 'exception class fingerprint only',
    "'route_name'" => 'named route correlation',
    "catch (Throwable \$telemetryFailure)" => 'telemetry persistence fail-open boundary',
], 'incident recorder');
$forbid($recorder, [
    '$request->all(' => 'request body persistence',
    '$request->getContent(' => 'raw request content persistence',
    '$request->query(' => 'query-value persistence',
    '$request->headers->all(' => 'arbitrary header persistence',
    '$exception->getMessage()' => 'raw exception message persistence',
], 'incident recorder');

$require($middleware, [
    '$response = $next($request)' => 'response outcome capture',
    '$this->recorder->captureHttp(' => 'incident recorder invocation',
    'throw $exception;' => 'original exception preservation',
], 'request outcome middleware');
$require($bootstrap, [
    'ObserveRequestOutcome::class' => 'request outcome middleware bootstrap',
    'ResolveEnterpriseOrganization::class,' => 'web tenant resolution before downstream observability',
], 'application middleware bootstrap');

$require($provider, [
    '$this->app->scoped(AuditManager::class)' => 'scoped tenant-aware audit manager lifecycle',
    '$this->app->scoped(ObservabilityRecorder::class)' => 'scoped tenant-aware incident recorder lifecycle',
    "nexora:observability:prune" => 'scheduled observability prune',
    'ClusterLeadership::class' => 'leader-gated pruning',
    '->withoutOverlapping()' => 'non-overlapping pruning',
], 'observability provider');
$require($providers, ['ObservabilityServiceProvider::class' => 'provider bootstrap'], 'provider bootstrap');

$require($retention, [
    "max(30, min(3650" => 'bounded audit retention',
    "max(7, min(365" => 'bounded incident retention',
    "withoutGlobalScope('nexora_audit_tenant')" => 'explicit global retention over audit rows',
    "withoutGlobalScope('nexora_observability_tenant')" => 'explicit global retention over incidents',
], 'observability retention');
$require($pruneCommand, [
    "nexora:observability:prune" => 'prune command signature',
    'Runtime metrics remain owned by the existing nexora:runtime:prune policy.' => 'single-owner runtime metric retention disclosure',
], 'observability prune command');
$require($console, [
    "Artisan::command('nexora:runtime:prune'" => 'existing runtime metric prune owner',
    "metric_retention_days" => 'bounded runtime metric retention setting',
], 'runtime metric retention');

$require($controller, [
    "orWhere('request_id', 'like'" => 'audit request-ID search',
    'ObservabilityIncident::query()' => 'tenant-scoped incident query',
    "'requestId' => \$incident->request_id" => 'incident request-ID payload',
    "'failures24h'" => 'failure summary',
    "'slow24h'" => 'latency summary',
], 'Admin audit controller');
$require($ui, [
    'Audit & Incidents' => 'combined audit/incident surface',
    'Request ID' => 'request-ID display',
    'Request payloads, query values and arbitrary headers are not stored.' => 'privacy disclosure',
    'HTTP failures · 24h' => 'failure summary UI',
    'Slow requests · 24h' => 'slow summary UI',
], 'Admin audit UI');

$require($runtimeTracker, [
    "'error_code'=>'queue_probe_'" => 'generic queue diagnostic error code',
    'Queue backlog could not be inspected. Review server logs for the matching operational incident.' => 'generic queue diagnostic message',
], 'runtime activity diagnostics');
$forbid($runtimeTracker, [
    "'error'=>substr(\$e->getMessage()" => 'raw queue exception diagnostic',
], 'runtime activity diagnostics');

$require($test, [
    'test_audit_logs_are_tenant_scoped_and_sensitive_metadata_is_redacted' => 'audit tenant/redaction acceptance',
    'test_incident_recorder_only_retains_failures_or_slow_requests_without_raw_request_or_exception_content' => 'incident threshold/privacy acceptance',
    'test_incident_reads_are_current_tenant_scoped' => 'incident tenant isolation acceptance',
    'test_retention_prunes_old_audit_and_incident_rows_but_preserves_recent_rows' => 'retention acceptance',
], 'observability acceptance');

if ($progress !== '') {
    if (! str_contains($progress, 'Actions: **DEFERRED BY USER')) {
        $errors[] = 'progress governance missing historical Actions quota deferral state.';
    }
    if (! str_contains($progress, 'current certification has resumed through the user-approved self-hosted runner pool')) {
        $errors[] = 'progress governance missing resumed self-hosted certification state.';
    }
    if (! str_contains($progress, 'TARGET POWER    50.0%')) {
        $errors[] = 'progress governance missing unchanged Target Power evidence boundary.';
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Observability Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(STDOUT, '[Nexora Observability Product Contract] PASS — tenant-scoped audit visibility, privacy-minimal 5xx/slow request correlation, scoped tenant service lifetimes, bounded retention, request-ID incident UX and sanitized operational diagnostics are source-guarded.'.PHP_EOL);
