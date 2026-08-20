<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTenantExecutionContracts(string $root): array
{
    $errors = [];
    $warnings = [];

    $read = static function (string $relativePath) use ($root): string {
        $path = $root.'/'.$relativePath;

        return is_file($path) ? (string) file_get_contents($path) : '';
    };

    $required = [
        'app/Nexora/Enterprise/Services/TenantExecutionScope.php',
        'app/Nexora/Enterprise/Services/TenantContext.php',
        'app/Providers/AppServiceProvider.php',
        'database/seeders/Core/NexoraCoreSeeder.php',
        'tests/Feature/Enterprise/EnterpriseFlowTest.php',
    ];

    foreach ($required as $file) {
        if (! is_file($root.'/'.$file)) {
            $errors[] = "Tenant execution artifact is missing [{$file}].";
        }
    }

    $scope = $read('app/Nexora/Enterprise/Services/TenantExecutionScope.php');
    foreach ([
        'function runRequired(',
        "->where('status', 'active')",
        'has no tenant identifier',
        'no longer exists or is not active',
        '$this->context->runWith($organization, $callback)',
    ] as $marker) {
        if (! str_contains($scope, $marker)) {
            $errors[] = "Tenant execution scope is missing fail-closed behavior [{$marker}].";
        }
    }

    $tenantJobs = [
        'app/Jobs/ExecuteWorkflowRunJob.php',
        'app/Jobs/DeliverWebhookJob.php',
        'app/Jobs/SendNewsletterDelivery.php',
        'app/Jobs/RunSeoCrawlJob.php',
    ];

    foreach ($tenantJobs as $file) {
        $job = $read($file);

        foreach (['TenantExecutionScope', "withoutGlobalScope('nexora_tenant')", 'runRequired('] as $marker) {
            if (! str_contains($job, $marker)) {
                $errors[] = "Tenant-aware queue job is missing execution boundary [{$file}: {$marker}].";
            }
        }

        if (str_contains($job, 'TenantContext') || str_contains($job, 'EnterpriseOrganization::query()->find')) {
            $errors[] = "Tenant-aware queue job still mutates or resolves ambient tenant state directly [{$file}].";
        }
    }

    $provider = $read('app/Providers/AppServiceProvider.php');
    foreach ([
        'Queue::before',
        'Queue::after',
        'Queue::exceptionOccurred',
        'Queue::looping',
        'ScheduledTaskStarting::class',
        'ScheduledTaskFinished::class',
        'ScheduledBackgroundTaskFinished::class',
        'ScheduledTaskFailed::class',
    ] as $hook) {
        if (! str_contains($provider, $hook)) {
            $errors[] = "Queue/scheduler tenant lifecycle hook is missing [{$hook}].";
        }
    }

    if (substr_count($provider, 'app(TenantContext::class)->clear();') < 5) {
        $errors[] = 'Queue/scheduler lifecycle does not clear tenant state on enough start/end/failure/idle paths.';
    }

    $seeder = $read('database/seeders/Core/NexoraCoreSeeder.php');
    foreach ([
        '$tenantContext->clear();',
        '$tenantContext->runWith($defaultOrganization',
        'DB::transaction(function (): void',
        'seedDefaultCrmPipeline',
        'seedDefaultHelpdeskPolicies',
        'seedDefaultNewsletterList',
    ] as $marker) {
        if (! str_contains($seeder, $marker)) {
            $errors[] = "Transactional tenant seed boundary is missing [{$marker}].";
        }
    }

    $tests = $read('tests/Feature/Enterprise/EnterpriseFlowTest.php');
    $requiredTests = [
        'test_core_seeder_discards_stale_tenant_context_after_schema_replacement',
        'test_tenant_scoped_write_rejects_an_active_context_for_a_deleted_organization',
        'test_scoped_tenant_context_restores_previous_context_after_an_exception',
        'test_tenant_execution_scope_uses_fresh_organization_and_restores_previous_context',
        'test_tenant_execution_scope_rejects_missing_or_deleted_tenants',
        'test_tenant_execution_scope_rejects_a_suspended_tenant',
    ];

    foreach ($requiredTests as $test) {
        if (! str_contains($tests, $test)) {
            $errors[] = "Tenant execution regression test is missing [{$test}].";
        }
    }

    $c2 = $read('scripts/n1-c2-laravel-runtime-certify.php');
    if (! str_contains($c2, "'tenant-execution-boundary-test'")) {
        $errors[] = 'C2 must run the dedicated tenant execution boundary regression gate.';
    }

    $c4Markers = [
        'queue_tenant_context_cleared_before_job',
        'queue_tenant_scope_fresh_organization_verified',
        'deleted_queue_tenant_rejected_before_job_logic',
        'suspended_queue_tenant_rejected_before_job_logic',
        'queue_tenant_context_cleared_after_success',
        'queue_tenant_context_cleared_after_exception',
        'scheduler_tenant_context_isolation_verified',
        'tenant_default_seed_transaction_verified',
        'cross_tenant_queue_context_bleed_rejected',
    ];

    foreach (['scripts/n1-c4-evidence-prepare.php', 'scripts/lib/final-evidence.php'] as $file) {
        $source = $read($file);

        foreach ($c4Markers as $marker) {
            if (! str_contains($source, $marker)) {
                $errors[] = "C4 tenant execution observation is missing [{$file}: {$marker}].";
            }
        }
    }

    $examplePath = $root.'/docs/upgrade-rehearsal-evidence.example.json';
    $example = is_file($examplePath)
        ? json_decode((string) file_get_contents($examplePath), true)
        : null;
    $exampleChecks = is_array($example) && is_array($example['checks'] ?? null)
        ? $example['checks']
        : [];

    foreach ($c4Markers as $marker) {
        if (! array_key_exists($marker, $exampleChecks)) {
            $errors[] = "C4 example evidence is missing tenant execution observation [{$marker}].";
        }
    }

    return [
        'errors' => $errors,
        'warnings' => $warnings,
        'metrics' => [
            'tenant_queue_jobs_scoped' => count($tenantJobs),
            'tenant_regression_tests' => count($requiredTests),
            'tenant_lifecycle_hooks' => 8,
            'c2_tenant_execution_gate' => 1,
            'c4_tenant_execution_checks' => count($c4Markers),
            'c4_total_checks' => count($exampleChecks),
            'automatic_cross_tenant_fallback' => 0,
        ],
    ];
}
