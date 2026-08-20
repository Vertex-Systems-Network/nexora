<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N032MembershipHelpdeskArchitectureTest extends TestCase
{
    public function test_membership_helpdesk_boundaries_and_shared_ui_are_present(): void
    {
        $root=dirname(__DIR__,2);
        $config=(string)file_get_contents($root.'/config/nexora.php');
        $migration=(string)file_get_contents($root.'/database/migrations/2026_08_16_001900_add_nexora_membership_helpdesk.php');
        $plan=(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        self::assertStringContainsString('MembershipModule::class',$config);
        self::assertStringContainsString('HelpdeskModule::class',$config);
        foreach(['membership.plans.read','membership.members.write','membership.access.evaluate','membership.commerce.sync','helpdesk.tickets.read','helpdesk.tickets.write','helpdesk.messages.write','helpdesk.sla.manage'] as $capability) self::assertStringContainsString($capability,$config);
        foreach(['nx_membership_plans','nx_membership_entitlements','nx_memberships','nx_membership_access_policies','nx_membership_events','nx_helpdesk_sla_policies','nx_helpdesk_tickets','nx_helpdesk_messages','nx_helpdesk_ticket_events'] as $table) self::assertStringContainsString($table,$migration);
        self::assertStringNotContainsString('->after(',$migration);
        $theme=(string)file_get_contents($root.'/app/Http/Controllers/Public/ThemePageController.php');
        self::assertStringContainsString('MembershipAccessContract',$theme);
        foreach(array_merge(glob($root.'/resources/js/admin/pages/Admin/Membership/*.tsx')?:[],glob($root.'/resources/js/admin/pages/Admin/Helpdesk/*.tsx')?:[]) as $file){$source=(string)file_get_contents($file);self::assertStringNotContainsString('<button',$source);self::assertStringNotContainsString('<select',$source);self::assertStringNotContainsString('<input',$source);self::assertStringNotContainsString('<textarea',$source);}
        self::assertStringContainsString('DateTimePicker',(string)file_get_contents($root.'/resources/js/admin/pages/Admin/Membership/Members.tsx'));
        self::assertStringContainsString('| N0.32 | Membership + Helpdesk foundations; LMS/Booking/Projects remain external packages | DONE |',$plan);
        self::assertStringContainsString('| N0.33 | Multisite, tenancy, organizations, SSO and enterprise controls | DONE |',$plan);
        foreach(['EXT-L01','EXT-BK01','EXT-PR01'] as $external) self::assertStringContainsString($external,$plan);
    }
}
