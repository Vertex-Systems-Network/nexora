<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N030CommerceArchitectureTest extends TestCase
{
    public function test_commerce_is_provider_neutral_and_uses_shared_admin_ui(): void
    {
        $root=dirname(__DIR__,2);
        $config=(string)file_get_contents($root.'/config/nexora.php');
        $migration=(string)file_get_contents($root.'/database/migrations/2026_08_16_001700_add_nexora_commerce_billing.php');
        $registry=(string)file_get_contents($root.'/app/Nexora/Commerce/Services/PaymentProviderRegistry.php');
        $contract=(string)file_get_contents($root.'/app/Nexora/Commerce/Contracts/PaymentProviderContract.php');
        $plan=(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        self::assertStringContainsString('CommerceModule::class',$config);
        foreach(['nx_commerce_products','nx_commerce_prices','nx_commerce_customers','nx_commerce_orders','nx_commerce_invoices','nx_commerce_payment_transactions','nx_commerce_refunds','nx_commerce_subscriptions','nx_commerce_billing_events'] as $table) self::assertStringContainsString($table,$migration);
        self::assertStringNotContainsString('->after(',$migration);
        self::assertStringContainsString('PaymentProviderContract',$registry);
        self::assertStringContainsString('createPayment',$contract);
        self::assertStringContainsString('createSubscription',$contract);
        foreach(glob($root.'/resources/js/admin/pages/Admin/Commerce/*.tsx') ?: [] as $file) {
            $source=(string)file_get_contents($file);
            self::assertStringNotContainsString('<button',$source);
            self::assertStringNotContainsString('<select',$source);
            self::assertStringNotContainsString('<input',$source);
            self::assertStringNotContainsString('<textarea',$source);
        }
        self::assertStringContainsString('| N0.30 | Commerce + Billing foundation | DONE |',$plan);
        self::assertStringContainsString('| N0.31 | CRM foundation | DONE |',$plan);
        self::assertStringContainsString('| N0.32 | Membership + Helpdesk foundations; LMS/Booking/Projects remain external packages | DONE |',$plan);
        foreach(['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external) self::assertStringContainsString($external,$plan);
    }
}
