<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Automation source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Automation source file: {$relative}";
        return '';
    }
    return $contents;
};

$controller = $read('app/Http/Controllers/Admin/Automation/AutomationController.php');
$validator = $read('app/Nexora/Automation/Services/AutomationDefinitionValidator.php');
$eventBus = $read('app/Nexora/Automation/Services/AutomationEventBus.php');
$executor = $read('app/Nexora/Automation/Services/WorkflowActionExecutor.php');
$workflowJob = $read('app/Jobs/ExecuteWorkflowRunJob.php');
$deliveryJob = $read('app/Jobs/DeliverWebhookJob.php');
$deliveryService = $read('app/Nexora/Automation/Services/WebhookDeliveryService.php');
$inbound = $read('app/Http/Controllers/Public/InboundWebhookController.php');
$stepModel = $read('app/Models/WorkflowStepRun.php');
$migration = $read('database/migrations/2026_08_21_000700_scope_automation_identity_to_tenant.php');
$test = $read('tests/Feature/Automation/AutomationTenantIsolationTest.php');

foreach ([
    'private TenantMemberDirectory $tenantMembers' => 'tenant-member chooser dependency',
    "'users'=>\$this->userOptions()" => 'tenant-scoped Automation user options',
    "Rule::unique('nx_workflows','slug')->where('tenant_id',\$this->tenant->id())" => 'tenant-scoped workflow slug validation',
] as $needle => $label) {
    if ($controller !== '' && ! str_contains($controller, $needle)) {
        $errors[] = "Automation Admin contract missing: {$label}.";
    }
}
if ($controller !== '' && str_contains($controller, 'User::query()')) {
    $errors[] = 'Automation Admin still contains platform-wide User::query().';
}

foreach ([
    'private TenantMemberDirectory $tenantMembers' => 'definition tenant-member dependency',
    '$this->tenantMembers->contains($userId)' => 'definition-time notification membership validation',
    "WebhookDestination::query()->whereKey(\$destination)->exists()" => 'tenant-scoped outbound destination validation',
    "WebhookEndpoint::query()->whereKey(\$endpoint)->exists()" => 'tenant-scoped inbound endpoint validation',
] as $needle => $label) {
    if ($validator !== '' && ! str_contains($validator, $needle)) {
        $errors[] = "Automation definition contract missing: {$label}.";
    }
}

foreach ([
    '$this->tenantMembers->contains($userId)' => 'runtime notification membership recheck',
    'WebhookDestination::query()->whereKey' => 'runtime tenant-scoped destination re-resolution',
    "'workflow:'.\$run->uuid" => 'deterministic workflow delivery idempotency',
    'DeliverWebhookJob::dispatch($delivery->id)->afterCommit()' => 'post-commit outbound dispatch',
] as $needle => $label) {
    if ($executor !== '' && ! str_contains($executor, $needle)) {
        $errors[] = "Workflow action execution contract missing: {$label}.";
    }
}

foreach ([
    "withoutGlobalScope('nexora_tenant')" => 'unscoped tenant identity lookup for queued run',
    'TenantExecutionScope $tenantScope' => 'queued workflow tenant execution scope',
    '$tenantScope->runRequired(' => 'required workflow tenant restoration',
    "if (\$existing?->status === 'succeeded')" => 'successful step replay suppression',
    "where('status', 'running')" => 'claimed-state completion/failure updates',
] as $needle => $label) {
    if ($workflowJob !== '' && ! str_contains($workflowJob, $needle)) {
        $errors[] = "Workflow queue contract missing: {$label}.";
    }
}

foreach ([
    'app(TenantExecutionScope::class)->runRequired(' => 'terminal webhook failure tenant restoration',
    'private function tenantId(): ?string' => 'reusable delivery tenant lookup',
    "withoutGlobalScope('nexora_tenant')" => 'delivery tenant identity lookup',
] as $needle => $label) {
    if ($deliveryJob !== '' && ! str_contains($deliveryJob, $needle)) {
        $errors[] = "Webhook queue contract missing: {$label}.";
    }
}

foreach ([
    '$this->policy->assertAllowed($destination->url' => 'outbound URL policy',
    '$this->http->external($destination->url)' => 'approved external HTTP client boundary',
    "'X-Nexora-Signature'" => 'signed outbound payload',
    "'Idempotency-Key'" => 'outbound idempotency header',
] as $needle => $label) {
    if ($deliveryService !== '' && ! str_contains($deliveryService, $needle)) {
        $errors[] = "Webhook delivery contract missing: {$label}.";
    }
}

foreach ([
    'abs(now()->timestamp - (int) $timestamp) > 300' => 'inbound replay window',
    '$signer->verify(' => 'inbound signature verification',
    "'Idempotency-Key'" => 'inbound idempotency key handling',
    "WebhookReceipt::query()" => 'durable inbound receipt',
    "'webhook.inbound'" => 'inbound Automation event bridge',
] as $needle => $label) {
    if ($inbound !== '' && ! str_contains($inbound, $needle)) {
        $errors[] = "Inbound webhook contract missing: {$label}.";
    }
}

foreach ([
    'use BelongsToTenant;' => 'tenant global scope on workflow step records',
    "protected \$table = 'nx_workflow_step_runs'" => 'workflow step table binding',
] as $needle => $label) {
    if ($stepModel !== '' && ! str_contains($stepModel, $needle)) {
        $errors[] = "Workflow step model contract missing: {$label}.";
    }
}

foreach ([
    "private const WORKFLOW_TENANT_SLUG = 'nx_workflow_tenant_slug_uq'" => 'tenant workflow slug identity',
    "private const EVENT_TENANT_IDEMPOTENCY = 'nx_automation_event_tenant_idempotency_uq'" => 'tenant event idempotency identity',
    'PortableNullableUnique::createScoped(' => 'SQL Server-safe nullable tenant idempotency uniqueness',
    '$this->backfillStepTenants()' => 'step-run tenant backfill',
    "throw new RuntimeException(\"Workflow step {\$step->id} has no trustworthy parent-run tenant identity.\")" => 'fail-closed step tenant backfill',
] as $needle => $label) {
    if ($migration !== '' && ! str_contains($migration, $needle)) {
        $errors[] = "Automation tenant migration contract missing: {$label}.";
    }
}

foreach ([
    'test_automation_admin_rejects_notification_target_from_another_tenant' => 'cross-tenant notification target rejection',
    "assertDontSee('other-automation@example.test')" => 'cross-tenant Automation user non-disclosure',
    'test_event_idempotency_and_workflow_slug_are_scoped_per_tenant' => 'tenant identity/idempotency acceptance test',
    "where('idempotency_key', 'shared-event-key')->count()" => 'same-key cross-tenant event assertion',
    'test_notification_action_rechecks_membership_at_execution_time' => 'runtime stale-membership rejection',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "Automation acceptance contract missing: {$label}.";
    }
}

if ($eventBus !== '' && (! str_contains($eventBus, 'lockForUpdate()') || ! str_contains($eventBus, 'afterCommit()'))) {
    $errors[] = 'Automation Event Bus must preserve transactional idempotency and after-commit workflow dispatch.';
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Automation Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Automation Product Contract] PASS — workflow identities/idempotency are tenant-native, notification targets are tenant-member checked at definition and execution time, queue workers restore tenant context, workflow steps are tenant-owned, and inbound/outbound webhooks retain replay/idempotency/network-safety boundaries.'.PHP_EOL,
);
