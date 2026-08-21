<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Multisite / Organizations source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Multisite / Organizations source file: {$relative}";
        return '';
    }
    return $contents;
};

$routeBinding = $read('app/Http/Middleware/EnsureTenantRouteBinding.php');
$authorization = $read('app/Nexora/Enterprise/Services/TenantAuthorizationService.php');
$controller = $read('app/Http/Controllers/Admin/Enterprise/EnterpriseController.php');
$indexUi = $read('resources/js/admin/pages/Admin/Enterprise/Index.tsx');
$showUi = $read('resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx');
$routes = $read('routes/web.php');
$test = $read('tests/Feature/Enterprise/MultisiteOrganizationIsolationTest.php');
$agents = $read('AGENTS.md');
$progress = $read('NEXORA_PROGRESS.md');

foreach ([
    'use App\\Models\\EnterpriseOrganization;' => 'enterprise tenant-root model import',
    'if ($parameter instanceof EnterpriseOrganization)' => 'enterprise tenant-root route binding',
    '(string) $parameter->getKey() !== $tenantId' => 'active tenant / organization key equality',
] as $needle => $label) {
    if ($routeBinding !== '' && ! str_contains($routeBinding, $needle)) {
        $errors[] = "Tenant route-binding contract missing: {$label}.";
    }
}

foreach ([
    "if(\$user->hasRole('super-admin'))return true;" => 'Super Admin tenant authorization bypass',
    "where('organization_id',\$org->id)" => 'current organization membership resolution',
    "in_array(\$permission,\$permissions,true)" => 'organization role permission restriction',
] as $needle => $label) {
    if ($authorization !== '' && ! str_contains($authorization, $needle)) {
        $errors[] = "Tenant authorization contract missing: {$label}.";
    }
}

foreach ([
    "'organization_id' => ['required', 'uuid']" => 'non-disclosing switch validation shape',
    "->where('status', 'active')" => 'active organization switch resolution',
    'abort_unless($organization !== null && $manager->canAccess($request->user(), $organization), 404);' => 'inaccessible switch fail-closed behavior',
    "$canDirectAddMembers = $canManageMembers && $request->user()->hasRole('super-admin');" => 'Super Admin-only direct identity chooser',
    "'users' => $canDirectAddMembers" => 'conditional platform user directory disclosure',
    "abort_unless($request->user()->hasRole('super-admin'), 403);" => 'server-enforced direct identity attachment boundary',
    "Rule::exists('nx_enterprise_organization_members', 'user_id')" => 'organization-scoped impersonation identity validation',
    "'Identity adapter health check failed.'" => 'generic identity adapter failure diagnostics',
    'private function can(Request $request, TenantAuthorizationService $authorization, string $permission): bool' => 'tenant-aware UI capability helper',
] as $needle => $label) {
    if ($controller !== '' && ! str_contains($controller, $needle)) {
        $errors[] = "Enterprise controller contract missing: {$label}.";
    }
}
foreach ([
    "'exists:nx_enterprise_organizations,id'" => 'platform-wide organization existence validator',
    "\$health['message']" => 'untrusted SSO adapter health message passthrough',
] as $forbidden => $label) {
    if ($controller !== '' && str_contains($controller, $forbidden)) {
        $errors[] = "Enterprise controller still contains forbidden {$label}.";
    }
}

foreach ([
    'const manageOrganization=(organization:Org)=>{' => 'tenant-switch-before-manage UI flow',
    "router.post('/admin/enterprise/switch'" => 'organization switch action',
    'onSuccess:()=>router.visit(`/admin/enterprise/organizations/${organization.id}`)' => 'post-switch organization navigation',
] as $needle => $label) {
    if ($indexUi !== '' && ! str_contains($indexUi, $needle)) {
        $errors[] = "Enterprise index UI contract missing: {$label}.";
    }
}

foreach ([
    'canDirectAddMembers: boolean;' => 'direct identity action capability prop',
    '{props.canDirectAddMembers && (' => 'Super Admin-only direct Add member UI',
    'const impersonationUsers = props.members' => 'member-derived impersonation chooser',
    '...impersonationUsers,' => 'member-only impersonation options',
    'open={memberOpen && props.canDirectAddMembers}' => 'direct-member modal capability fence',
] as $needle => $label) {
    if ($showUi !== '' && ! str_contains($showUi, $needle)) {
        $errors[] = "Organization management UI contract missing: {$label}.";
    }
}

foreach ([
    "Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin', EnsureTenantRouteBinding::class])" => 'Admin-wide tenant route binding',
    "Route::post('/enterprise/switch'" => 'organization switch route',
    "Route::post('/enterprise/organizations/{organization}/members'" => 'organization member route',
    "Route::post('/enterprise/organizations/{organization}/impersonate'" => 'organization impersonation route',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) {
        $errors[] = "Enterprise route contract missing: {$label}.";
    }
}

foreach ([
    'test_current_tenant_permissions_cannot_be_reused_against_another_organization_route' => 'cross-organization confused-deputy regression',
    'test_organization_switch_hides_inaccessible_tenants_and_preserves_current_session' => 'non-disclosing switch regression',
    'test_organization_admin_does_not_receive_platform_user_directory_or_direct_attach_access' => 'platform identity non-disclosure regression',
    'test_organization_admin_can_still_invite_by_email_without_platform_user_enumeration' => 'tenant invitation workflow regression',
    'test_impersonation_validation_is_scoped_to_active_organization_members' => 'member-scoped impersonation regression',
    'test_nested_resource_from_another_organization_fails_before_mutation' => 'nested cross-org mutation regression',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "Multisite acceptance contract missing: {$label}.";
    }
}

foreach ([
    'Read `NEXORA_PROGRESS.md` in full' => 'mandatory progress-dashboard read',
    'update `NEXORA_PROGRESS.md` immediately' => 'mandatory per-apply progress update',
    'Never increase Target Power from source CI alone' => 'evidence-based target progress rule',
] as $needle => $label) {
    if ($agents !== '' && ! str_contains($agents, $needle)) {
        $errors[] = "AI governance contract missing: {$label}.";
    }
}
foreach ([
    '## 2. Weighted Project Power Score' => 'weighted project power dashboard',
    '## 8. Apply Log' => 'per-apply progress history',
    'After **every meaningful apply**' => 'progress-update protocol',
] as $needle => $label) {
    if ($progress !== '' && ! str_contains($progress, $needle)) {
        $errors[] = "Progress dashboard contract missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Multisite / Organizations Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Multisite / Organizations Product Contract] PASS — organization-root routes are bound to the active tenant, tenant-role permissions cannot be replayed against another organization, switching is non-disclosing, platform identity attachment is Super Admin-only while tenant invitation remains available, impersonation is member-scoped, adapter diagnostics are fail-closed, and mandatory weighted progress tracking is repository-governed.'.PHP_EOL,
);
