<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use App\Models\CommerceBillingEvent;
use App\Models\CommerceInvoice;
use App\Models\CommerceOrder;
use App\Models\CommercePrice;
use App\Models\CommerceProduct;
use App\Models\EnterpriseOrganization;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantContext;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CommerceAdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_open_commerce_workspaces(): void
    {
        $admin = $this->administrator();
        foreach (['/admin/commerce', '/admin/commerce/products', '/admin/commerce/customers', '/admin/commerce/orders', '/admin/commerce/billing', '/admin/commerce/settings'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_guest_cannot_open_commerce_admin(): void
    {
        $this->get('/admin/commerce')->assertRedirect('/login');
    }

    public function test_archived_product_price_is_not_orderable_or_exposed_as_available(): void
    {
        $admin = $this->administrator();
        [$product, $price] = $this->productPrice('archived');

        $this->actingAs($admin)
            ->get('/admin/commerce/orders')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('prices', 0));

        $this->actingAs($admin)
            ->post('/admin/commerce/orders', [
                'customer_id' => '',
                'currency' => 'USD',
                'price_id' => $price->id,
                'quantity' => 1,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('nx_commerce_orders', ['currency' => 'USD']);
        self::assertSame('archived', $product->fresh()?->status);
    }

    public function test_order_place_and_invoice_transitions_are_idempotent(): void
    {
        $admin = $this->administrator();
        [, $price] = $this->productPrice('active');

        $this->actingAs($admin)
            ->post('/admin/commerce/orders', [
                'customer_id' => '',
                'currency' => 'USD',
                'price_id' => $price->id,
                'quantity' => 2,
            ])
            ->assertSessionHas('success');

        $order = CommerceOrder::query()->firstOrFail();
        self::assertSame('draft', $order->status);
        self::assertSame(2500, (int) $order->total_minor);

        $this->actingAs($admin)
            ->post('/admin/commerce/orders/'.$order->id.'/place')
            ->assertSessionHas('success');
        $this->actingAs($admin)
            ->post('/admin/commerce/orders/'.$order->id.'/place')
            ->assertSessionHas('error');

        self::assertSame('pending_payment', $order->fresh()?->status);
        self::assertSame(1, CommerceBillingEvent::query()->where('event_type', 'commerce.order.placed')->where('resource_id', $order->id)->count());

        $this->actingAs($admin)
            ->post('/admin/commerce/orders/'.$order->id.'/invoice')
            ->assertRedirect('/admin/commerce/billing');
        $this->actingAs($admin)
            ->post('/admin/commerce/orders/'.$order->id.'/invoice')
            ->assertRedirect('/admin/commerce/billing');

        self::assertSame(1, CommerceInvoice::query()->where('order_id', $order->id)->where('status', '!=', 'void')->count());
        self::assertSame(1, CommerceBillingEvent::query()->where('event_type', 'commerce.invoice.created')->count());
    }

    public function test_catalog_rejects_amounts_outside_supported_integer_range(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->post('/admin/commerce/products', [
                'name' => 'Overflow Product',
                'sku' => 'OVERFLOW-1',
                'slug' => 'overflow-product',
                'type' => 'product',
                'status' => 'active',
                'description' => '',
                'tax_code' => '',
                'currency' => 'USD',
                'amount' => '9999999999999999999999999999999999999999',
                'billing_interval' => '',
            ])
            ->assertSessionHasErrors(['amount']);

        $this->assertDatabaseMissing('nx_commerce_products', ['sku' => 'OVERFLOW-1']);
    }

    public function test_product_sku_and_slug_are_unique_per_tenant_not_globally(): void
    {
        $default = EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
        $second = EnterpriseOrganization::query()->create([
            'name' => 'Second Commerce Tenant',
            'slug' => 'second-commerce-tenant',
            'status' => 'active',
            'is_default' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
        $tenants = app(TenantContext::class);

        $firstProduct = $tenants->runWith($default, fn () => CommerceProduct::query()->create([
            'name' => 'Shared Identity A',
            'sku' => 'SHARED-SKU',
            'slug' => 'shared-product',
            'type' => 'product',
            'status' => 'active',
            'published_at' => now(),
        ]));
        $secondProduct = $tenants->runWith($second, fn () => CommerceProduct::query()->create([
            'name' => 'Shared Identity B',
            'sku' => 'SHARED-SKU',
            'slug' => 'shared-product',
            'type' => 'product',
            'status' => 'active',
            'published_at' => now(),
        ]));

        self::assertNotSame($firstProduct->tenant_id, $secondProduct->tenant_id);
        self::assertSame('SHARED-SKU', $secondProduct->sku);

        $this->expectException(QueryException::class);
        $tenants->runWith($default, fn () => CommerceProduct::query()->create([
            'name' => 'Duplicate In Same Tenant',
            'sku' => 'SHARED-SKU',
            'slug' => 'different-slug-same-sku',
            'type' => 'product',
            'status' => 'draft',
        ]));
    }

    /** @return array{0:CommerceProduct,1:CommercePrice} */
    private function productPrice(string $status): array
    {
        $product = CommerceProduct::query()->create([
            'name' => 'Commerce Test Product',
            'sku' => 'COMMERCE-'.strtoupper(substr(md5($status.microtime(true)), 0, 8)),
            'slug' => 'commerce-test-'.strtolower(substr(md5($status.microtime(true)), 0, 8)),
            'type' => 'product',
            'status' => $status,
            'published_at' => $status === 'active' ? now() : null,
        ]);
        $price = CommercePrice::query()->create([
            'product_id' => $product->id,
            'currency' => 'USD',
            'amount_minor' => 1250,
            'active' => true,
        ]);

        return [$product, $price];
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        return $admin;
    }
}
