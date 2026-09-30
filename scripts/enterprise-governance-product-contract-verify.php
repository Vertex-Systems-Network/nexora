<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Enterprise Governance source file missing: {$relative}";
        return '';
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Enterprise Governance source file: {$relative}";
        return '';
    }

    return $contents;
};

$ssoPolicy = $read('app/Nexora/Enterprise/Services/SsoEnforcementPolicy.php');
$auth = $read('app/Http/Controllers/Auth/AuthenticatedSessionController.php');
$loginUi = $read('resources/js/admin/pages/Auth/Login.tsx');
$ssoController = $read('app/Http/Controllers/Enterprise/SsoController.php');
$ssoModel = $read('app/Models/EnterpriseSsoProvider.php');
$scimTokens = $read('app/Nexora/Enterprise/Services/ScimTokenManager.php');
$scimController = $read('app/Http/Controllers/Enterprise/ScimController.php');
$invitations = $read('app/Nexora/Enterprise/Services/InvitationManager.php');
$invitationController = $read('app/Http/Controllers/Enterprise/InvitationController.php');
$impersonation = $read('app/Nexora/Enterprise/Services/ImpersonationManager.php');
$test = $read('tests/Feature/Enterprise/EnterpriseIdentityGovernanceTest.php');
$agents = $read('AGENTS.md');
$progress = $read('NEXORA_PROGRESS.md');

$require = static function (string $source, array $needles, string $scope) use (&$errors): void {
    foreach ($needles as $needle => $label) {
        if ($source !== '' && ! str_contains($source, (string) $needle)) {
            $errors[] = "{$scope} contract missing: {$label}.";
        }
    }
};

$forbid = static function (string $source, array $needles, string $scope) use (&$errors): void {
    foreach ($needles as $needle => $label) {
        if ($source !== '' && str_contains($source, (string) $needle)) {
            $errors[] = "{$scope} still contains forbidden {$label}.";
        }
    }
};

$require($ssoPolicy, [
    "hasRole('super-admin')" => 'Super Admin break-glass local authentication policy',
    "where('status', 'active')" => 'active organization membership requirement',
    "where('enforce_for_members', true)" => 'enforced SSO provider policy',
    "where('enabled', true)" => 'enabled provider requirement',
    "\$adapter !== null && \$adapter->protocol() === \$provider->protocol" => 'login option adapter/protocol compatibility',
], 'SSO enforcement');

$require($auth, [
    'SsoEnforcementPolicy $ssoPolicy' => 'SSO policy injection',
    '$ssoPolicy->requiresSso($user)' => 'local password SSO enforcement',
    "'enterprise-sso-required'" => 'blocked-login audit reason',
    "Auth::logout();" => 'blocked-login logout',
    '$sessions->invalidateCurrentSession($request);' => 'blocked-login session invalidation',
], 'Authenticated session');

$require($loginUi, [
    'sso.providers.map' => 'organization SSO provider options',
    'SSO is required for organization members' => 'enforcement explanation',
    'No compatible SSO adapter is currently available' => 'fail-closed adapter-unavailable UX',
], 'Login UI');

$require($ssoController, [
    "abort_unless(\$organization->status === 'active', 404);" => 'active SSO organization boundary',
    "'organization_id' => \$organization->id" => 'SSO state organization binding',
    "'provider_id' => \$record->id" => 'SSO state provider binding',
    "\$adapter->protocol() === \$record->protocol" => 'adapter protocol revalidation',
    "parse_url(\$redirectUrl, PHP_URL_SCHEME)" => 'adapter redirect scheme validation',
    'FILTER_VALIDATE_EMAIL' => 'provider identity email validation',
    "where('status', 'active')" => 'active SSO membership requirement',
    "'enterprise.sso.login'" => 'successful SSO audit event',
], 'SSO callback');

$require($ssoModel, [
    "'secret_payload' => 'encrypted:array'" => 'encrypted SSO secret payload',
    "'client_secret'" => 'secret-like configuration key fence',
    'assertPublicConfiguration' => 'recursive public configuration validation',
    'Secret credentials must be stored in the encrypted identity-provider secret payload.' => 'safe secret-boundary validation message',
], 'SSO model');

$require($scimTokens, [
    "str_starts_with(\$plain, 'nxscim_')" => 'SCIM token namespace validation',
    "where('enabled', true)" => 'enabled SCIM token requirement',
    "whereNull('revoked_at')" => 'revoked SCIM token rejection',
    "whereHas('organization', fn (\$query) => \$query->where('status', 'active'))" => 'active organization SCIM token requirement',
    "\$record->expires_at->isPast()" => 'SCIM token expiry enforcement',
], 'SCIM token');

$require($scimController, [
    "whereIn('status', ['active', 'suspended'])" => 'SCIM deactivated-resource visibility',
    'abort_unless($member !== null, 409' => 'existing foreign identity attach denial',
    "in_array(\$member->role, ['owner', 'admin'], true)" => 'privileged membership protection',
    "'status' => 'active'" => 'global identity remains active for tenant-local SCIM lifecycle',
    "'active' => \$user->status === 'active' && \$member->status === 'active'" => 'tenant-local SCIM active response',
    'count($operations) <= 50' => 'bounded SCIM PATCH operations',
    "['replace', 'add']" => 'SCIM PATCH operation allow-list',
    "['email_hash' => hash('sha256', \$email)]" => 'privacy-minimal SCIM provisioning audit',
], 'SCIM controller');

$forbid($scimController, [
    "['email' => \$email]" => 'raw SCIM provisioning email audit metadata',
    "'status' => (\$data['active']" => 'global user status controlled by tenant SCIM active flag',
], 'SCIM controller');

$require($invitations, [
    "->update(['status' => 'superseded'])" => 'stale invitation supersession',
    "where('status', 'pending')" => 'pending invitation requirement',
    "where('status', 'active')" => 'active organization invitation acceptance',
    "in_array(\$member->role, ['owner', 'admin'], true)" => 'privileged invitation role preservation',
    "['email_hash' => hash('sha256', \$email), 'role' => \$role]" => 'hashed invitation audit identity',
], 'Invitation');

$require($invitationController, [
    "session()->put('nexora.enterprise.organization_id', \$member->organization_id)" => 'accepted organization session selection',
    "\$request->user()->canAccessAdmin()" => 'post-acceptance Admin/portal routing boundary',
], 'Invitation controller');

$require($impersonation, [
    "session()->has('nexora.enterprise.impersonation_id')" => 'nested impersonation rejection',
    "\$organization->status !== 'active'" => 'active organization impersonation requirement',
    "\$actor->status !== 'active'" => 'active actor requirement',
    "! \$actor->hasRole('super-admin') && ! \$this->isActiveMember" => 'service-level actor tenant authority',
    "['reason_hash' => hash('sha256', trim(\$reason))]" => 'privacy-minimal impersonation reason audit',
    "(int) \$session->target_user_id !== \$currentUserId" => 'stop-session current-target integrity',
    "'actor_restored' => \$actorCanReturn" => 'audited safe actor restoration result',
], 'Impersonation');

$require($test, [
    'test_enforced_sso_blocks_member_password_login_but_preserves_super_admin_break_glass' => 'password SSO enforcement acceptance',
    'test_sso_state_is_bound_to_the_originating_provider_and_callback_protocol_is_rechecked' => 'SSO state/protocol acceptance',
    'test_sso_public_configuration_rejects_secret_like_keys' => 'SSO secret-config acceptance',
    'test_scim_token_fails_closed_after_organization_is_suspended' => 'inactive-tenant SCIM token acceptance',
    'test_scim_cannot_attach_an_existing_foreign_platform_identity' => 'SCIM foreign identity acceptance',
    'test_scim_active_is_tenant_local_and_privileged_membership_is_not_demoted_or_deactivated' => 'SCIM local lifecycle/privilege acceptance',
    'test_new_invitation_supersedes_old_token_and_acceptance_preserves_privileged_role' => 'invitation replay/privilege acceptance',
    'test_invitation_acceptance_selects_the_accepted_organization' => 'invitation tenant selection acceptance',
    'test_nested_impersonation_is_rejected_before_identity_switch' => 'nested impersonation acceptance',
], 'Enterprise Governance acceptance');

$require($agents, [
    'Read `NEXORA_PROGRESS.md` in full' => 'mandatory progress read governance',
    'update `NEXORA_PROGRESS.md` immediately' => 'mandatory per-apply progress write governance',
], 'AI governance');

$require($progress, [
    '## 2. Weighted Project Power Score' => 'weighted Power dashboard',
    'Target Power' => 'source-vs-target scoring boundary',
], 'Progress dashboard');
if ($progress !== '' && preg_match('/^##\s+\d+\.\s+Apply Log\s*$/m', $progress) !== 1) {
    $errors[] = 'Progress dashboard contract missing: per-apply progress history.';
}

if ($errors !== []) {
    fwrite(
        STDERR,
        "[Nexora SSO / Enterprise Governance Product Contract] FAILED\n - ".
        implode("\n - ", array_values(array_unique($errors)))."\n",
    );
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora SSO / Enterprise Governance Product Contract] PASS — enforced tenant SSO cannot be bypassed by ordinary local password login, Super Admin break-glass remains explicit, SSO state/adapter/config trust boundaries are fail-closed, SCIM lifecycle is tenant-local and privilege-safe, invitation tokens are replay-safe, impersonation is non-nestable and session-bound, and weighted progress tracking remains repository-governed.'.PHP_EOL,
);
