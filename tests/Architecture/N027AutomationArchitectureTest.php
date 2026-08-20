<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N027AutomationArchitectureTest extends TestCase
{
    public function test_automation_runtime_uses_public_contracts_signed_webhooks_and_ui_library_boundaries(): void
    {
        $root=dirname(__DIR__,2);
        $config=(string)file_get_contents($root.'/config/nexora.php');
        $plan=(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        $migration=(string)file_get_contents($root.'/database/migrations/2026_08_15_001400_add_nexora_automation_workflows.php');
        $index=(string)file_get_contents($root.'/resources/js/admin/pages/Admin/Automation/Index.tsx');
        $form=(string)file_get_contents($root.'/resources/js/admin/pages/Admin/Automation/Form.tsx');
        $delivery=(string)file_get_contents($root.'/app/Nexora/Automation/Services/WebhookDeliveryService.php');
        $inbound=(string)file_get_contents($root.'/app/Http/Controllers/Public/InboundWebhookController.php');
        $bootstrap=(string)file_get_contents($root.'/bootstrap/app.php');

        self::assertStringContainsString('AutomationModule::class',$config);
        foreach(['automation.workflows.read','automation.events.emit','webhooks.inbound.receive','webhooks.outbound.send'] as $capability) self::assertStringContainsString($capability,$config);
        foreach(['nx_workflows','nx_workflow_runs','nx_webhook_endpoints','nx_webhook_deliveries'] as $table) self::assertStringContainsString($table,$migration);
        self::assertStringContainsString('X-Nexora-Signature',$delivery);
        self::assertStringContainsString('withoutRedirecting()',$delivery);
        self::assertStringContainsString('previous_secret_valid_until',$inbound);
        self::assertStringContainsString("preventRequestForgery(except: ['hooks/*'])",$bootstrap);
        foreach([$index,$form] as $source){ self::assertStringContainsString('@nexora/admin-ui',$source); self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\b/',$source); }
        self::assertStringContainsString('| N0.27 | Automation/workflow engine, triggers/conditions/actions/webhooks | DONE |',$plan);
        self::assertStringContainsString('| N0.28 | Sentinel advanced supply-chain controls: SBOM, signing, provenance, sandbox adapters | DONE |',$plan);
        foreach(['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external) self::assertStringContainsString($external,$plan);
    }
}
