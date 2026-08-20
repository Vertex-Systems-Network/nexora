<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N033EnterpriseArchitectureTest extends TestCase
{
    public function test_enterprise_tenancy_identity_and_ui_boundaries_are_present(): void
    {
        $root=dirname(__DIR__,2);$config=(string)file_get_contents($root.'/config/nexora.php');$migration=(string)file_get_contents($root.'/database/migrations/2026_08_16_002000_add_nexora_enterprise_tenancy.php');$plan=(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');self::assertStringContainsString('EnterpriseModule::class',$config);
        foreach(['nx_enterprise_organizations','nx_enterprise_roles','nx_enterprise_organization_members','nx_enterprise_domains','nx_enterprise_invitations','nx_enterprise_sso_providers','nx_enterprise_scim_tokens','nx_enterprise_impersonation_sessions','nx_enterprise_audit_events','nx_enterprise_settings'] as $table)self::assertStringContainsString($table,$migration);
        self::assertStringNotContainsString('->after(',$migration);self::assertStringContainsString("'tenant_id'",$migration);
        $trait=(string)file_get_contents($root.'/app/Nexora/Enterprise/Support/BelongsToTenant.php');self::assertStringContainsString("addGlobalScope('nexora_tenant'",$trait);
        $provider=(string)file_get_contents($root.'/app/Nexora/Enterprise/Contracts/EnterpriseIdentityProviderContract.php');self::assertStringContainsString('redirectUrl',$provider);self::assertStringContainsString('resolveIdentity',$provider);
        foreach(glob($root.'/resources/js/admin/pages/Admin/Enterprise/*.tsx')?:[] as $file){$source=(string)file_get_contents($file);self::assertStringContainsString('@nexora/admin-ui',$source);self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\\b/',$source);}
        self::assertStringContainsString('| N0.33 | Multisite, tenancy, organizations, SSO and enterprise controls | DONE |',$plan);self::assertStringContainsString('| N0.34 | Cloud/HA/distributed runtime, queues, object storage, operational tooling | DONE |',$plan);
    }
}
