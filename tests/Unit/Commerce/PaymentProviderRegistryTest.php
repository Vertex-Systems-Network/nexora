<?php

declare(strict_types=1);

namespace Tests\Unit\Commerce;

use App\Nexora\Commerce\Contracts\PaymentProviderContract;
use App\Nexora\Commerce\Data\PaymentRequest;
use App\Nexora\Commerce\Data\ProviderTransactionResult;
use App\Nexora\Commerce\Data\RefundRequest;
use App\Nexora\Commerce\Data\SubscriptionRequest;
use App\Nexora\Commerce\Services\PaymentProviderRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PaymentProviderRegistryTest extends TestCase
{
    public function test_provider_registry_is_extension_driven_and_rejects_duplicate_keys(): void
    {
        $registry=new PaymentProviderRegistry();
        $provider=$this->provider('vendor.payments');
        $registry->register($provider);
        self::assertTrue($registry->has('vendor.payments'));
        self::assertSame($provider,$registry->get('vendor.payments'));
        $this->expectException(InvalidArgumentException::class);
        $registry->register($this->provider('vendor.payments'));
    }

    private function provider(string $key): PaymentProviderContract
    {
        return new class($key) implements PaymentProviderContract {
            public function __construct(private string $providerKey) {}
            public function key(): string { return $this->providerKey; }
            public function label(): string { return 'Test Provider'; }
            public function capabilities(): array { return ['payments','refunds']; }
            public function health(array $configuration=[]): array { return ['ok'=>true,'message'=>'Ready']; }
            public function createPayment(PaymentRequest $request): ProviderTransactionResult { return new ProviderTransactionResult(true,'succeeded','pay_1'); }
            public function refund(RefundRequest $request): ProviderTransactionResult { return new ProviderTransactionResult(true,'refunded','ref_1'); }
            public function createSubscription(SubscriptionRequest $request): ProviderTransactionResult { return new ProviderTransactionResult(true,'active','sub_1'); }
            public function cancelSubscription(string $providerReference,array $context=[]): ProviderTransactionResult { return new ProviderTransactionResult(true,'cancelled',$providerReference); }
        };
    }
}
