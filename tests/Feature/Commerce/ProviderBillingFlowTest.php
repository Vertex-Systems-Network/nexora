<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use App\Models\CommerceInvoice;
use App\Models\CommerceOrder;
use App\Models\CommercePaymentProviderConfig;
use App\Models\CommercePaymentTransaction;
use App\Models\CommercePrice;
use App\Models\CommerceProduct;
use App\Models\CommerceRefund;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Commerce\Contracts\PaymentProviderContract;
use App\Nexora\Commerce\Data\PaymentRequest;
use App\Nexora\Commerce\Data\ProviderTransactionResult;
use App\Nexora\Commerce\Data\RefundRequest;
use App\Nexora\Commerce\Data\SubscriptionRequest;
use App\Nexora\Commerce\Services\PaymentProviderRegistry;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

final class ProviderBillingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_enabled_provider_collects_and_refunds_with_retry_safe_idempotency(): void
    {
        $provider = new FakeCommercePaymentProvider();
        app(PaymentProviderRegistry::class)->register($provider);
        CommercePaymentProviderConfig::query()->create([
            'provider_key' => $provider->key(),
            'display_name' => $provider->label(),
            'enabled' => true,
            'mode' => 'test',
            'configuration' => [],
            'secret_refs' => [],
            'last_health_status' => 'healthy',
        ]);

        $admin = $this->administrator();
        $price = $this->activePrice();

        $this->actingAs($admin)->post('/admin/commerce/orders', [
            'customer_id' => '',
            'currency' => 'USD',
            'price_id' => $price->id,
            'quantity' => 2,
        ])->assertSessionHas('success');

        $order = CommerceOrder::query()->firstOrFail();
        $this->actingAs($admin)->post('/admin/commerce/orders/'.$order->id.'/place')->assertSessionHas('success');
        $this->actingAs($admin)->post('/admin/commerce/orders/'.$order->id.'/invoice')->assertRedirect('/admin/commerce/billing');
        $invoice = CommerceInvoice::query()->where('order_id', $order->id)->firstOrFail();

        $paymentPayload = ['provider_key' => $provider->key(), 'idempotency_key' => 'provider-payment-retry-1'];
        $this->actingAs($admin)->post('/admin/commerce/billing/invoices/'.$invoice->id.'/payments', $paymentPayload)->assertSessionHas('success');
        $this->actingAs($admin)->post('/admin/commerce/billing/invoices/'.$invoice->id.'/payments', $paymentPayload)->assertSessionHas('success');

        self::assertSame(1, $provider->paymentCalls);
        self::assertSame(1, CommercePaymentTransaction::query()->count());
        self::assertSame('paid', $invoice->fresh()?->status);
        self::assertSame(2500, (int) $invoice->fresh()?->amount_paid_minor);
        self::assertSame('paid', $order->fresh()?->status);

        $payment = CommercePaymentTransaction::query()->firstOrFail();
        $refundPayload = ['amount' => '12.50', 'reason' => 'Partial customer refund', 'idempotency_key' => 'provider-refund-retry-1'];
        $this->actingAs($admin)->post('/admin/commerce/billing/transactions/'.$payment->id.'/refunds', $refundPayload)->assertSessionHas('success');
        $this->actingAs($admin)->post('/admin/commerce/billing/transactions/'.$payment->id.'/refunds', $refundPayload)->assertSessionHas('success');

        self::assertSame(1, $provider->refundCalls);
        self::assertSame(1, CommerceRefund::query()->count());
        self::assertSame(1250, (int) $order->fresh()?->refunded_minor);
        self::assertSame('paid', $order->fresh()?->status);

        $this->actingAs($admin)->post('/admin/commerce/billing/transactions/'.$payment->id.'/refunds', [
            'amount' => '20.00',
            'reason' => 'Too large',
            'idempotency_key' => 'provider-refund-too-large',
        ])->assertSessionHas('error');

        self::assertSame(1, $provider->refundCalls);
        self::assertSame(1, CommerceRefund::query()->count());
    }

    public function test_disabled_provider_fails_before_external_payment_call(): void
    {
        $provider = new FakeCommercePaymentProvider();
        app(PaymentProviderRegistry::class)->register($provider);
        CommercePaymentProviderConfig::query()->create([
            'provider_key' => $provider->key(),
            'display_name' => $provider->label(),
            'enabled' => false,
            'mode' => 'test',
            'configuration' => [],
            'secret_refs' => [],
        ]);

        $admin = $this->administrator();
        $price = $this->activePrice();
        $this->actingAs($admin)->post('/admin/commerce/orders', [
            'customer_id' => '',
            'currency' => 'USD',
            'price_id' => $price->id,
            'quantity' => 1,
        ]);
        $order = CommerceOrder::query()->firstOrFail();
        $this->actingAs($admin)->post('/admin/commerce/orders/'.$order->id.'/place');
        $this->actingAs($admin)->post('/admin/commerce/orders/'.$order->id.'/invoice');
        $invoice = CommerceInvoice::query()->firstOrFail();

        $this->actingAs($admin)->post('/admin/commerce/billing/invoices/'.$invoice->id.'/payments', [
            'provider_key' => $provider->key(),
            'idempotency_key' => 'disabled-provider-payment',
        ])->assertSessionHas('error');

        self::assertSame(0, $provider->paymentCalls);
        self::assertSame(0, CommercePaymentTransaction::query()->count());
    }

    private function activePrice(): CommercePrice
    {
        $product = CommerceProduct::query()->create([
            'name' => 'Provider Billing Product',
            'sku' => 'PROVIDER-BILLING-'.strtoupper(substr(md5(microtime(true)), 0, 8)),
            'slug' => 'provider-billing-'.strtolower(substr(md5(microtime(true)), 0, 8)),
            'type' => 'product',
            'status' => 'active',
            'published_at' => now(),
        ]);

        return CommercePrice::query()->create([
            'product_id' => $product->id,
            'currency' => 'USD',
            'amount_minor' => 1250,
            'active' => true,
        ]);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        return $admin;
    }
}

final class FakeCommercePaymentProvider implements PaymentProviderContract
{
    public int $paymentCalls = 0;
    public int $refundCalls = 0;

    public function key(): string { return 'test-provider'; }
    public function label(): string { return 'Test Provider'; }
    public function capabilities(): array { return [self::CAPABILITY_PAYMENTS, self::CAPABILITY_REFUNDS]; }
    public function health(array $configuration = []): array { return ['ok' => true, 'message' => 'Test provider healthy.']; }

    public function createPayment(PaymentRequest $request): ProviderTransactionResult
    {
        $this->paymentCalls++;
        return new ProviderTransactionResult(true, 'succeeded', 'pay-'.$request->idempotencyKey, 'Payment succeeded.');
    }

    public function refund(RefundRequest $request): ProviderTransactionResult
    {
        $this->refundCalls++;
        return new ProviderTransactionResult(true, 'refunded', 'refund-'.$request->idempotencyKey, 'Refund succeeded.');
    }

    public function createSubscription(SubscriptionRequest $request): ProviderTransactionResult
    {
        throw new LogicException('Subscriptions are outside this acceptance flow.');
    }

    public function cancelSubscription(string $providerReference, array $context = []): ProviderTransactionResult
    {
        throw new LogicException('Subscriptions are outside this acceptance flow.');
    }
}
