<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N031CrmArchitectureTest extends TestCase
{
    public function test_crm_boundaries_and_shared_ui_are_present(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root.'/config/nexora.php');
        $migration = (string) file_get_contents($root.'/database/migrations/2026_08_16_001800_add_nexora_crm.php');
        $providerContract = (string) file_get_contents($root.'/app/Nexora/Crm/Contracts/CrmActivityProviderContract.php');
        $commerceLink = (string) file_get_contents($root.'/app/Nexora/Crm/Services/CrmCommerceLinkService.php');
        $plan = (string) file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        self::assertStringContainsString('CrmModule::class', $config);
        foreach (['crm.organizations.read','crm.contacts.write','crm.leads.write','crm.opportunities.write','crm.activities.write','crm.custom-fields.manage','crm.commerce.link','crm.providers.register'] as $capability) {
            self::assertStringContainsString($capability, $config);
        }
        foreach (['nx_crm_organizations','nx_crm_contacts','nx_crm_pipelines','nx_crm_pipeline_stages','nx_crm_opportunities','nx_crm_leads','nx_crm_activities','nx_crm_notes','nx_crm_timeline_events','nx_crm_opportunity_stage_history','nx_crm_custom_field_definitions','nx_crm_custom_field_values','nx_crm_commerce_links'] as $table) {
            self::assertStringContainsString($table, $migration);
        }
        self::assertStringNotContainsString('->after(', $migration);
        self::assertStringContainsString('CrmActivityProviderContract', $providerContract);
        self::assertStringContainsString('commerce_customer_id', $commerceLink);

        $crmCore = '';
        foreach (glob($root.'/app/Nexora/Crm/**/*.php') ?: [] as $file) $crmCore .= (string) file_get_contents($file)."\n";
        self::assertDoesNotMatchRegularExpression('/\b(?:Google\\\\Client|GmailService|MicrosoftGraph|OutlookClient)\b/i', $crmCore);

        foreach (glob($root.'/resources/js/admin/pages/Admin/Crm/*.tsx') ?: [] as $file) {
            $source = (string) file_get_contents($file);
            self::assertStringNotContainsString('<button', $source);
            self::assertStringNotContainsString('<select', $source);
            self::assertStringNotContainsString('<input', $source);
            self::assertStringNotContainsString('<textarea', $source);
        }
        $opportunities = (string) file_get_contents($root.'/resources/js/admin/pages/Admin/Crm/Opportunities.tsx');
        self::assertStringContainsString('DateTimePicker', $opportunities);
        self::assertStringContainsString('| N0.31 | CRM foundation | DONE |', $plan);
        self::assertStringContainsString('| N0.32 | Membership + Helpdesk foundations; LMS/Booking/Projects remain external packages | DONE |', $plan);
        foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external) self::assertStringContainsString($external, $plan);
    }
}
