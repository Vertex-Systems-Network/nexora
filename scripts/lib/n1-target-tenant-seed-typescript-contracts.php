<?php

declare(strict_types=1);

function nexoraAnalyzeTenantSeedTypeScriptContracts(string $root): array
{
    $errors = [];
    $warnings = [];

    $read = static function (string $relativePath) use ($root): string {
        $path = $root.'/'.$relativePath;

        return is_file($path) ? (string) file_get_contents($path) : '';
    };

    $requiredFiles = [
        'app/Nexora/Enterprise/Services/TenantContext.php',
        'app/Nexora/Enterprise/Services/TenantExecutionScope.php',
        'app/Nexora/Enterprise/Support/BelongsToTenant.php',
        'database/seeders/Core/NexoraCoreSeeder.php',
        'app/Nexora/Installation/Installer.php',
        'tests/Feature/Enterprise/EnterpriseFlowTest.php',
        'scripts/inertia-frontend-contract-verify.php',
    ];

    foreach ($requiredFiles as $file) {
        if (! is_file($root.'/'.$file)) {
            $errors[] = "Required v4.5 source file is missing [{$file}].";
        }
    }

    $tenantContext = $read('app/Nexora/Enterprise/Services/TenantContext.php');
    foreach (['function clear()', 'function runWith(', 'finally {', '$this->organization = $previous'] as $marker) {
        if (! str_contains($tenantContext, $marker)) {
            $errors[] = "TenantContext lifecycle invariant is missing [{$marker}].";
        }
    }

    $tenantExecution = $read('app/Nexora/Enterprise/Services/TenantExecutionScope.php');
    foreach ([
        'function runRequired(',
        'has no tenant identifier',
        'no longer exists or is not active',
        '$this->context->runWith($organization, $callback)',
    ] as $marker) {
        if (! str_contains($tenantExecution, $marker)) {
            $errors[] = "Tenant execution boundary is missing [{$marker}].";
        }
    }

    $tenantTrait = $read('app/Nexora/Enterprise/Support/BelongsToTenant.php');
    foreach (['tenantIdForWrite', 'assertTenantExists', 'organization that no longer exists', '->whereKey($tenantId)'] as $marker) {
        if (! str_contains($tenantTrait, $marker)) {
            $errors[] = "Tenant write boundary is missing [{$marker}].";
        }
    }

    $installer = $read('app/Nexora/Installation/Installer.php');
    $migratePosition = strpos($installer, "Artisan::call('migrate'");
    $clearPosition = strpos($installer, '$this->tenantContext->clear();');
    $seedPosition = strpos($installer, "Artisan::call('db:seed'");

    if ($migratePosition === false || $clearPosition === false || $seedPosition === false) {
        $errors[] = 'Installer migration / tenant reset / seed sequence is incomplete.';
    } elseif (! ($migratePosition < $clearPosition && $clearPosition < $seedPosition)) {
        $errors[] = 'Installer must clear stale TenantContext after migrations and before database seeding.';
    }

    $seeder = $read('database/seeders/Core/NexoraCoreSeeder.php');
    foreach ([
        'seedTenantDefaults',
        '$tenantContext->clear();',
        '$tenantContext->runWith($defaultOrganization',
        'DB::transaction(function (): void',
        'seedDefaultCrmPipeline',
        'seedDefaultHelpdeskPolicies',
        'seedDefaultNewsletterList',
    ] as $marker) {
        if (! str_contains($seeder, $marker)) {
            $errors[] = "Core tenant seed invariant is missing [{$marker}].";
        }
    }

    $enterpriseTests = $read('tests/Feature/Enterprise/EnterpriseFlowTest.php');
    foreach ([
        'test_core_seeder_discards_stale_tenant_context_after_schema_replacement',
        'test_tenant_scoped_write_rejects_an_active_context_for_a_deleted_organization',
        'test_scoped_tenant_context_restores_previous_context_after_an_exception',
        'test_tenant_execution_scope_uses_fresh_organization_and_restores_previous_context',
        'test_tenant_execution_scope_rejects_missing_or_deleted_tenants',
        'test_tenant_execution_scope_rejects_a_suspended_tenant',
    ] as $testName) {
        if (! str_contains($enterpriseTests, $testName)) {
            $errors[] = "Tenant regression test is missing [{$testName}].";
        }
    }

    $tenantJobs = [
        'app/Jobs/ExecuteWorkflowRunJob.php',
        'app/Jobs/DeliverWebhookJob.php',
        'app/Jobs/SendNewsletterDelivery.php',
        'app/Jobs/RunSeoCrawlJob.php',
    ];

    foreach ($tenantJobs as $jobFile) {
        $job = $read($jobFile);

        if (! str_contains($job, 'TenantExecutionScope')) {
            $errors[] = "Tenant-aware queue job must use TenantExecutionScope [{$jobFile}].";
        }

        if (str_contains($job, 'TenantContext') || str_contains($job, '->set(EnterpriseOrganization')) {
            $errors[] = "Tenant-aware queue job must not set ambient tenant state directly [{$jobFile}].";
        }

        if (! str_contains($job, 'withoutGlobalScope(\'nexora_tenant\')')) {
            $errors[] = "Tenant-aware queue job must resolve its root tenant outside ambient tenant scope [{$jobFile}].";
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
    ] as $marker) {
        if (! str_contains($provider, $marker)) {
            $errors[] = "Tenant lifecycle cleanup hook is missing [{$marker}].";
        }
    }

    if (substr_count($provider, 'app(TenantContext::class)->clear();') < 5) {
        $errors[] = 'Queue/scheduler lifecycle must clear TenantContext on start, completion, failure and idle reuse paths.';
    }

    require_once $root.'/scripts/lib/inertia-frontend-contracts.php';
    $frontend = nexoraAnalyzeInertiaFrontendContracts($root);

    foreach ($frontend['errors'] as $frontendError) {
        $errors[] = 'Inertia frontend contract: '.$frontendError;
    }

    $historicalTargets = [
        'resources/js/admin/pages/Admin/Automation/Form.tsx',
        'resources/js/admin/pages/Admin/Cloud/Index.tsx',
        'resources/js/admin/pages/Admin/Discovery/Index.tsx',
        'resources/js/admin/pages/Admin/Distribution/Index.tsx',
        'resources/js/admin/pages/Admin/Documents/Form.tsx',
        'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx',
        'resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx',
        'resources/js/admin/pages/Admin/Media/Index.tsx',
        'resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx',
        'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx',
        'resources/js/admin/pages/Admin/Studio/Editor.tsx',
    ];

    foreach ($historicalTargets as $file) {
        if (! is_file($root.'/'.$file)) {
            $errors[] = "Historical Laragon TypeScript target is missing [{$file}].";
        }
    }

    $readableTargets = [
        'resources/js/admin/pages/Admin/Automation/Form.tsx',
        'resources/js/admin/pages/Admin/Cloud/Index.tsx',
        'resources/js/admin/pages/Admin/Discovery/Index.tsx',
        'resources/js/admin/pages/Admin/Distribution/Index.tsx',
        'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx',
        'resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx',
        'resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx',
        'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx',
    ];

    foreach ($readableTargets as $file) {
        $lines = file($root.'/'.$file, FILE_IGNORE_NEW_LINES) ?: [];
        $longestLine = 0;

        foreach ($lines as $line) {
            $longestLine = max($longestLine, strlen($line));
        }

        if ($longestLine > 260) {
            $errors[] = "Human-readable frontend target exceeds 260 columns [{$file}: {$longestLine}].";
        }
    }

    return [
        'errors' => $errors,
        'warnings' => $warnings,
        'metrics' => [
            'tenant_context_reset' => 1,
            'tenant_scoped_seed_blocks' => 3,
            'tenant_regression_tests' => 6,
            'tenant_queue_jobs_scoped' => 4,
            'tenant_lifecycle_cleanup_hooks' => 8,
            'tenant_seed_transactional' => 1,
            'historical_typescript_targets' => count($historicalTargets),
            'readability_guarded_frontend_targets' => count($readableTargets),
            'frontend_contract_errors' => count($frontend['errors']),
        ],
    ];
}
