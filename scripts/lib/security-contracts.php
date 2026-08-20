<?php

declare(strict_types=1);

/** @return array{errors:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeSecurityContracts(string $root): array
{
    $errors = [];
    $read = static function (string $relative) use ($root, &$errors): string {
        $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (! is_file($path)) {
            $errors[] = 'Missing security artifact: '.$relative;
            return '';
        }
        return (string) file_get_contents($path);
    };

    $routes = $read('routes/web.php');
    $bootstrap = $read('bootstrap/app.php');
    $session = $read('config/session.php');
    $login = $read('app/Http/Controllers/Auth/AuthenticatedSessionController.php');
    $register = $read('app/Http/Controllers/Auth/RegisteredUserController.php');
    $forgot = $read('app/Http/Controllers/Auth/PasswordResetLinkController.php');
    $reset = $read('app/Http/Controllers/Auth/NewPasswordController.php');
    $profile = $read('app/Http/Controllers/Admin/ProfileController.php');
    $sso = $read('app/Http/Controllers/Enterprise/SsoController.php');
    $scim = $read('app/Http/Controllers/Enterprise/ScimController.php');
    $scimTokens = $read('app/Nexora/Enterprise/Services/ScimTokenManager.php');
    $webhook = $read('app/Http/Controllers/Public/InboundWebhookController.php');
    $admin = $read('app/Http/Middleware/EnsureAdminAccess.php');
    $tenantBinding = $read('app/Http/Middleware/EnsureTenantRouteBinding.php');
    $sessionManager = $read('app/Nexora/Security/Session/SessionSecurityManager.php');
    $impersonation = $read('app/Nexora/Enterprise/Services/ImpersonationManager.php');

    foreach ([
        "Route::middleware('guest')->group",
        "Route::middleware('auth')->group",
        "Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin', EnsureTenantRouteBinding::class])",
        "Route::post('/logout'",
        "middleware(['signed', 'throttle:6,1'])",
    ] as $marker) {
        if (! str_contains($routes, $marker)) $errors[] = 'Auth route boundary is missing: '.$marker;
    }

    foreach (['hooks/*', 'scim/*', 'sso/*/*/callback'] as $exception) {
        if (! str_contains($bootstrap, "'{$exception}'")) $errors[] = 'Expected authenticated external-protocol CSRF exception is missing: '.$exception;
    }
    if (preg_match('/preventRequestForgery\s*\(\s*except\s*:\s*\[[^\]]*["\']\*["\']/s', $bootstrap) === 1) {
        $errors[] = 'CSRF protection must not use a global wildcard exception.';
    }

    foreach (["env('SESSION_ENCRYPT', true)", "env('SESSION_HTTP_ONLY', true)", "SESSION_SECURE_COOKIE", "env('SESSION_SAME_SITE', 'lax')"] as $marker) {
        if (! str_contains($session, $marker)) $errors[] = 'Secure session default missing: '.$marker;
    }
    if (! str_contains($session, "'serialization' => 'json'")) $errors[] = 'Session serialization must remain JSON.';

    foreach ([$login, $register, $sso] as $source) {
        if (! str_contains($source, 'rotateAuthenticatedSession')) $errors[] = 'Successful authentication path must rotate the session ID and CSRF token.';
    }
    if (! str_contains($login, "status !== 'active'")) $errors[] = 'Password login must reject every non-active account state.';
    if (! str_contains($login, 'email_hash')) $errors[] = 'Failed-login audit metadata must avoid storing the raw submitted email address.';
    if (! str_contains($forgot, 'Password::RESET_LINK_SENT') || str_contains($forgot, "withErrors(['email'")) $errors[] = 'Forgot-password response must not disclose account existence.';
    foreach ([$reset, $profile] as $source) {
        if (! str_contains($source, 'rotateRememberToken')) $errors[] = 'Password mutation must rotate remember-token credentials.';
    }
    if (! str_contains($reset, 'revokeAllSessions')) $errors[] = 'Password reset must revoke existing database sessions.';
    if (! str_contains($profile, 'revokeOtherSessions')) $errors[] = 'Authenticated password change must revoke other database sessions.';

    if (! str_contains($admin, 'TenantAuthorizationService') || ! str_contains($admin, "allows(\$user, 'admin.access')")) {
        $errors[] = 'Admin entry must enforce platform admin.access and the current organization role restriction.';
    }
    foreach (['tenant_id', 'parameters()', 'abort_if'] as $marker) {
        if (! str_contains($tenantBinding, $marker)) $errors[] = 'Tenant-bound route model assertion is missing: '.$marker;
    }

    foreach (['state_hash', 'expires_at', 'hash_equals', "status==='active'", "where('status','active')", 'rotateAuthenticatedSession'] as $marker) {
        if (! str_contains($sso, $marker)) $errors[] = 'SSO anti-fixation/state/membership boundary missing: '.$marker;
    }
    if (str_contains($sso, 'Auth::login($user,true)')) $errors[] = 'Enterprise SSO must not silently force a persistent remember login.';

    foreach (['bearerToken()', 'Invalid SCIM bearer token'] as $marker) {
        if (! str_contains($scim, $marker)) $errors[] = 'SCIM bearer authentication missing: '.$marker;
    }
    foreach (['token_hash', "hash('sha256',\$token)", "hash('sha256',\$plain)"] as $marker) {
        if (! str_contains($scimTokens, $marker)) $errors[] = 'SCIM token hash-only persistence boundary missing: '.$marker;
    }
    if (preg_match('/\$target->update\s*\(\s*\[[^\]]*["\']status["\']/s', $scim) === 1) {
        $errors[] = 'Organization-scoped SCIM PATCH must not suspend the global user account.';
    }

    foreach (['X-Nexora-Timestamp', 'X-Nexora-Signature', '300', '1_048_576', 'Idempotency-Key', 'allowed_ips', 'payload_hash'] as $marker) {
        if (! str_contains($webhook, $marker)) $errors[] = 'Inbound webhook authentication/replay boundary missing: '.$marker;
    }


    foreach (['rotateAuthenticatedSession', "status !== 'active'", 'Auth::login($target, false)', 'Auth::login($actor, false)'] as $marker) {
        if (! str_contains($impersonation, $marker)) $errors[] = 'Impersonation privilege-boundary hardening missing: '.$marker;
    }

    foreach (['regenerate()', 'regenerateToken()', 'invalidate()', 'revokeAllSessions', 'revokeOtherSessions'] as $marker) {
        if (! str_contains($sessionManager, $marker)) $errors[] = 'Central session security manager is incomplete: '.$marker;
    }


    $tenantValidationTargets = [
        'nx_commerce_customers','nx_commerce_prices','nx_documents','nx_crm_contacts','nx_media_folders',
        'nx_newsletter_lists','nx_membership_plans','nx_commerce_subscriptions','nx_author_profiles',
        'nx_taxonomy_terms','nx_content_series','nx_crm_organizations','nx_crm_pipelines','nx_crm_pipeline_stages',
    ];
    $rawTenantExists = 0;
    $controllerRoot = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Admin';
    if (is_dir($controllerRoot)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllerRoot));
        foreach ($iterator as $file) {
            if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') continue;
            $source = (string) file_get_contents($file->getPathname());
            foreach ($tenantValidationTargets as $table) {
                $count = substr_count($source, 'exists:'.$table.',id');
                if ($count > 0) {
                    $rawTenantExists += $count;
                    $errors[] = 'Tenant-owned request reference must use TenantExists instead of raw exists rule: '.$file->getPathname().' -> '.$table;
                }
            }
        }
    }

    $tenantMemberControllers = [
        'app/Http/Controllers/Admin/Helpdesk/HelpdeskTicketController.php',
        'app/Http/Controllers/Admin/Membership/MembershipController.php',
        'app/Http/Controllers/Admin/Crm/OpportunityController.php',
    ];
    $rawTenantMemberExists = 0;
    foreach ($tenantMemberControllers as $relative) {
        $source = $read($relative);
        $count = substr_count($source, 'exists:users,id');
        if ($count > 0) {
            $rawTenantMemberExists += $count;
            $errors[] = 'Tenant-owned user reference must use TenantMemberExists instead of global users exists rule: '.$relative;
        }
    }

    foreach (['TenantExists','TenantMemberExists'] as $validationRule) {
        $relative='app/Nexora/Enterprise/Validation/'.$validationRule.'.php';
        if (! is_file($root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative))) {
            $errors[]='Missing tenant-aware validation rule: '.$relative;
        }
    }

    $csrfExceptions = 0;
    if (preg_match('/preventRequestForgery\s*\(\s*except\s*:\s*\[([^\]]*)\]/s', $bootstrap, $m) === 1) {
        preg_match_all('/["\'][^"\']+["\']/', $m[1], $hits);
        $csrfExceptions = count($hits[0] ?? []);
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'metrics' => [
            'csrf_exceptions' => $csrfExceptions,
            'session_rotation_paths' => substr_count($login.$register.$sso, 'rotateAuthenticatedSession'),
            'external_auth_boundaries' => 3, // SSO, SCIM, inbound webhook
            'tenant_route_binding_guards' => str_contains($routes, 'EnsureTenantRouteBinding::class') ? 1 : 0,
            'raw_tenant_exists' => $rawTenantExists,
            'raw_tenant_member_exists' => $rawTenantMemberExists,
        ],
    ];
}
