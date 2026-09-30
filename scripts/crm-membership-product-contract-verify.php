<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required CRM/Membership source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read CRM/Membership source file: {$relative}";
        return '';
    }
    return $contents;
};

$migration = $read('database/migrations/2026_08_21_000500_scope_crm_membership_identity_to_tenant.php');
$databaseContracts = $read('scripts/lib/database-contracts.php');
$linkModel = $read('app/Models/CrmCommerceLink.php');
$linkService = $read('app/Nexora/Crm/Services/CrmCommerceLinkService.php');
$crmSettings = $read('app/Http/Controllers/Admin/Crm/CrmSettingsController.php');
$organizationController = $read('app/Http/Controllers/Admin/Crm/OrganizationController.php');
$contactController = $read('app/Http/Controllers/Admin/Crm/ContactController.php');
$leadController = $read('app/Http/Controllers/Admin/Crm/LeadController.php');
$opportunityController = $read('app/Http/Controllers/Admin/Crm/OpportunityController.php');
$leadConversion = $read('app/Nexora/Crm/Services/CrmLeadConversionService.php');
$tenantMemberDirectory = $read('app/Nexora/Enterprise/Services/TenantMemberDirectory.php');
$membershipController = $read('app/Http/Controllers/Admin/Membership/MembershipController.php');
$membershipPlanController = $read('app/Http/Controllers/Admin/Membership/MembershipPlanController.php');
$membershipManager = $read('app/Nexora/Membership/Services/MembershipManager.php');
$membershipCommerceSync = $read('app/Nexora/Membership/Services/MembershipCommerceSyncService.php');
$test = $read('tests/Feature/Crm/CrmMembershipTenantIsolationTest.php');

foreach ([
    "uuid('tenant_id')->nullable()->index(self::CRM_LINK_TENANT_INDEX)" => 'CRM Commerce link tenant column',
    "foreign('tenant_id', self::CRM_LINK_TENANT_FOREIGN)" => 'CRM Commerce link tenant foreign key',
    "unique(['tenant_id', 'commerce_customer_id'], self::CRM_LINK_TENANT_CUSTOMER)" => 'tenant-scoped CRM Commerce customer identity',
    "unique(['tenant_id', 'slug'], self::CRM_PIPELINE_TENANT_SLUG)" => 'tenant-scoped CRM pipeline slug',
    "unique(['tenant_id', 'entity_type', 'key'], self::CRM_CUSTOM_FIELD_TENANT_KEY)" => 'tenant-scoped CRM custom-field identity',
    "unique(['tenant_id', 'slug'], self::MEMBERSHIP_PLAN_TENANT_SLUG)" => 'tenant-scoped Membership plan slug',
    "unique(['tenant_id', 'resource_type', 'resource_id'], self::MEMBERSHIP_POLICY_TENANT_RESOURCE)" => 'tenant-scoped Membership access-policy identity',
    'has a cross-tenant {$label} relationship.' => 'fail-closed CRM link backfill consistency check',
] as $needle => $label) {
    if ($migration !== '' && ! str_contains($migration, $needle)) {
        $errors[] = "CRM/Membership tenant migration missing: {$label}.";
    }
}

foreach ([
    'function nexoraMigrationSchemaTableBlocks(' => 'forward tenantization Schema::table parser',
    'function nexoraMigrationForwardTenantizesTable(' => 'forward tenantization classifier',
    '$tenantForwardModels=[]' => 'forward-tenantized model classification',
    "'tenant_forward_models'=>count(\$tenantForwardModels)" => 'forward tenantization metric',
    'nor converted by a later forward tenantization migration' => 'forward-tenantization failure contract',
] as $needle => $label) {
    if ($databaseContracts !== '' && ! str_contains($databaseContracts, $needle)) {
        $errors[] = "Database contract analyzer missing: {$label}.";
    }
}

foreach ([
    'use BelongsToTenant;' => 'CRM Commerce link tenant global scope',
    "protected \$table = 'nx_crm_commerce_links'" => 'CRM Commerce link table declaration',
] as $needle => $label) {
    if ($linkModel !== '' && ! str_contains($linkModel, $needle)) {
        $errors[] = "CRM Commerce link model missing: {$label}.";
    }
}

foreach ([
    'private TenantContext $tenant' => 'CRM Commerce active tenant dependency',
    'private function assertCurrentTenant(' => 'CRM Commerce service tenant assertion',
    'must belong to the current organization.' => 'CRM Commerce cross-tenant rejection',
    "CrmCommerceLink::query()->updateOrCreate(" => 'tenant-scoped CRM Commerce persistence',
] as $needle => $label) {
    if ($linkService !== '' && ! str_contains($linkService, $needle)) {
        $errors[] = "CRM Commerce link service missing: {$label}.";
    }
}

foreach ([
    "Rule::unique('nx_crm_pipelines', 'slug')" => 'tenant-aware CRM pipeline validation',
    "Rule::unique('nx_crm_custom_field_definitions', 'key')" => 'tenant-aware CRM custom-field validation',
    "->where('tenant_id', \$tenantId)" => 'CRM validation tenant predicate',
    'Select an organization before changing CRM settings.' => 'CRM missing-tenant fail-closed validation',
] as $needle => $label) {
    if ($crmSettings !== '' && ! str_contains($crmSettings, $needle)) {
        $errors[] = "CRM Settings controller missing: {$label}.";
    }
}

foreach ([
    'private TenantContext $tenant' => 'shared tenant context dependency',
    "->whereHas('enterpriseMemberships'" => 'tenant-member relationship filter',
    "->where('organization_id', \$tenantId)" => 'tenant-member organization predicate',
    "->where('status', 'active')" => 'active membership/user filter',
    'public function options(bool $includeEmail = false): array' => 'shared owner/member option projection',
] as $needle => $label) {
    if ($tenantMemberDirectory !== '' && ! str_contains($tenantMemberDirectory, $needle)) {
        $errors[] = "Tenant member directory missing: {$label}.";
    }
}

foreach ([
    'TenantMemberDirectory $members' => 'shared tenant-member chooser dependency',
    '$members->options(true)' => 'tenant-member user chooser',
    'new TenantMemberExists()' => 'membership write tenant-member validation',
] as $needle => $label) {
    if ($membershipController !== '' && ! str_contains($membershipController, $needle)) {
        $errors[] = "Membership controller missing: {$label}.";
    }
}

foreach ([
    'Organization' => $organizationController,
    'Contact' => $contactController,
    'Lead' => $leadController,
    'Opportunity' => $opportunityController,
] as $controllerLabel => $controllerSource) {
    foreach ([
        'TenantMemberDirectory $members' => 'shared tenant-member owner chooser dependency',
        '$members->options()' => 'tenant-scoped owner options',
        'new TenantMemberExists()' => 'tenant-member owner validation',
    ] as $needle => $label) {
        if ($controllerSource !== '' && ! str_contains($controllerSource, $needle)) {
            $errors[] = "CRM {$controllerLabel} controller missing: {$label}.";
        }
    }
    if ($controllerSource !== '' && str_contains($controllerSource, 'exists:users,id')) {
        $errors[] = "CRM {$controllerLabel} controller still exposes platform-wide owner validation.";
    }
}

foreach ([
    'private function resolvePipeline(' => 'lead conversion pipeline re-resolution',
    '->whereKey($pipeline->id)' => 'current-scope pipeline lookup',
    "->where('active',true)" => 'active pipeline requirement',
    'does not belong to the current organization or is inactive.' => 'cross-tenant pipeline rejection',
    'private function resolveStage(' => 'stage re-resolution against resolved pipeline',
    "CrmPipelineStage::query()->where('pipeline_id',\$pipeline->id)" => 'stage/pipeline consistency re-resolution',
] as $needle => $label) {
    if ($leadConversion !== '' && ! str_contains($leadConversion, $needle)) {
        $errors[] = "CRM lead conversion service missing: {$label}.";
    }
}

foreach ([
    "Rule::unique('nx_membership_plans', 'slug')" => 'tenant-aware Membership plan slug validation',
    "Rule::unique('nx_membership_plans', 'commerce_price_id')" => 'tenant-aware Membership price binding validation',
    "->where('tenant_id', \$tenantId)" => 'Membership plan validation tenant predicate',
    'Select an organization before creating a membership plan.' => 'Membership plan missing-tenant rejection',
] as $needle => $label) {
    if ($membershipPlanController !== '' && ! str_contains($membershipPlanController, $needle)) {
        $errors[] = "Membership plan controller missing: {$label}.";
    }
}

foreach ([
    'private TenantContext $tenant' => 'Membership service tenant dependency',
    'private function assertWritableTenant(' => 'Membership service tenant assertion',
    'private function assertCommerceReferences(' => 'Membership Commerce reference assertion',
    "withoutGlobalScope('nexora_tenant')" => 'explicit cross-scope reference inspection before rejection',
    'Commerce customer must belong to the membership organization.' => 'cross-tenant customer rejection',
    'Commerce subscription must belong to the membership organization.' => 'cross-tenant subscription rejection',
    'belongs to a different customer.' => 'customer/subscription consistency rejection',
] as $needle => $label) {
    if ($membershipManager !== '' && ! str_contains($membershipManager, $needle)) {
        $errors[] = "Membership manager missing: {$label}.";
    }
}

foreach ([
    'private TenantExecutionScope $tenantScope' => 'Membership Commerce tenant execution dependency',
    "runRequired(\$tenantId, 'membership Commerce synchronization'" => 'tenant-scoped Commerce synchronization',
    'CommerceSubscription::query()->find($subscription->id)' => 'subscription re-resolution inside tenant scope',
    'does not belong to the active synchronization tenant.' => 'sync cross-tenant fail-closed rejection',
] as $needle => $label) {
    if ($membershipCommerceSync !== '' && ! str_contains($membershipCommerceSync, $needle)) {
        $errors[] = "Membership Commerce sync missing: {$label}.";
    }
}

foreach ([
    'test_crm_commerce_links_are_tenant_scoped_and_cross_tenant_links_fail_closed' => 'CRM link isolation acceptance test',
    'test_crm_and_membership_identity_keys_can_repeat_across_tenants' => 'cross-tenant identity acceptance test',
    'test_membership_manager_rejects_cross_tenant_commerce_customer' => 'Membership service isolation acceptance test',
    'test_membership_member_picker_excludes_users_from_other_tenants' => 'Membership picker non-disclosure acceptance test',
    'test_crm_owner_directory_and_validation_exclude_cross_tenant_users' => 'CRM owner-directory isolation acceptance test',
    'test_crm_lead_conversion_rejects_pipeline_from_another_tenant' => 'CRM lead-conversion tenant isolation acceptance test',
    "assertDontSee('other-member@example.test')" => 'other-tenant user non-disclosure assertion',
    "withoutGlobalScope('nexora_tenant')" => 'cross-tenant duplicate identity DB assertions',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "CRM/Membership acceptance contract missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora CRM + Membership Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora CRM + Membership Product Contract] PASS — CRM Commerce links, CRM configuration identities, CRM owner assignment, lead conversion, Membership plan/access identities, member selection, service-layer Commerce references and Commerce-to-Membership synchronization are tenant-scoped and fail closed on cross-organization misuse.'.PHP_EOL,
);
