<?php

declare(strict_types=1);

use App\Nexora\Foundation\Database\PortableNullableUnique;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_commerce_currencies', function (Blueprint $table): void {
            $table->char('code', 3)->primary();
            $table->string('name', 120);
            $table->string('symbol', 12)->nullable();
            $table->unsignedTinyInteger('minor_unit')->default(2);
            $table->boolean('enabled')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('nx_commerce_tax_rates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 160);
            $table->char('country_code', 2)->nullable()->index();
            $table->string('region_code', 80)->nullable()->index();
            $table->string('tax_code', 80)->nullable()->index();
            $table->unsignedInteger('rate_basis_points');
            $table->boolean('inclusive')->default(false);
            $table->boolean('active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_commerce_products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('sku', 120)->nullable();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('type', 32)->default('product')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->text('description')->nullable();
            $table->string('tax_code', 80)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        PortableNullableUnique::create('nx_commerce_products', 'sku', 'nx_commerce_products_sku_uq');

        Schema::create('nx_commerce_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->char('currency', 3)->index();
            $table->unsignedBigInteger('amount_minor');
            $table->string('billing_interval', 32)->nullable()->index();
            $table->unsignedInteger('interval_count')->default(1);
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('product_id', 'nx_prices_product_fk')->references('id')->on('nx_commerce_products')->cascadeOnDelete();
            $table->foreign('currency', 'nx_prices_currency_fk')->references('code')->on('nx_commerce_currencies')->restrictOnDelete();
            $table->index(['product_id', 'active'], 'nx_prices_product_active_idx');
        });

        Schema::create('nx_commerce_customers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 255)->index();
            $table->string('name', 200);
            $table->string('phone', 80)->nullable();
            $table->string('tax_id', 160)->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_commerce_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('number', 64)->unique();
            $table->uuid('customer_id')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->char('currency', 3)->index();
            $table->unsignedBigInteger('subtotal_minor')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->unsignedBigInteger('paid_minor')->default(0);
            $table->unsignedBigInteger('refunded_minor')->default(0);
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->foreign('customer_id', 'nx_orders_customer_fk')->references('id')->on('nx_commerce_customers')->nullOnDelete();
            $table->foreign('currency', 'nx_orders_currency_fk')->references('code')->on('nx_commerce_currencies')->restrictOnDelete();
        });

        Schema::create('nx_commerce_order_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('product_id')->nullable();
            $table->uuid('price_id')->nullable();
            $table->string('name', 200);
            $table->string('sku', 120)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_amount_minor');
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('total_minor');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('order_id', 'nx_order_items_order_fk')->references('id')->on('nx_commerce_orders')->cascadeOnDelete();
            $table->foreign('product_id', 'nx_order_items_product_fk')->references('id')->on('nx_commerce_products')->nullOnDelete();
            $table->foreign('price_id', 'nx_order_items_price_fk')->references('id')->on('nx_commerce_prices')->nullOnDelete();
        });

        Schema::create('nx_commerce_invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('number', 64)->unique();
            $table->uuid('order_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->char('currency', 3)->index();
            $table->unsignedBigInteger('subtotal_minor')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->unsignedBigInteger('amount_due_minor')->default(0);
            $table->unsignedBigInteger('amount_paid_minor')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->foreign('order_id', 'nx_invoices_order_fk')->references('id')->on('nx_commerce_orders')->nullOnDelete();
            $table->foreign('customer_id', 'nx_invoices_customer_fk')->references('id')->on('nx_commerce_customers')->nullOnDelete();
            $table->foreign('currency', 'nx_invoices_currency_fk')->references('code')->on('nx_commerce_currencies')->restrictOnDelete();
        });

        Schema::create('nx_commerce_payment_provider_configs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider_key', 160)->unique();
            $table->string('display_name', 180);
            $table->boolean('enabled')->default(false)->index();
            $table->string('mode', 32)->default('live');
            $table->json('configuration')->nullable();
            $table->json('secret_refs')->nullable();
            $table->timestamp('last_health_checked_at')->nullable();
            $table->string('last_health_status', 32)->nullable();
            $table->text('last_health_message')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_commerce_payment_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->string('provider_key', 160)->index();
            $table->string('provider_reference', 255)->nullable()->index();
            $table->string('type', 40)->index();
            $table->string('status', 32)->index();
            $table->char('currency', 3)->index();
            $table->unsignedBigInteger('amount_minor');
            $table->string('idempotency_key', 180)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->foreign('order_id', 'nx_tx_order_fk')->references('id')->on('nx_commerce_orders')->nullOnDelete();
            $table->foreign('invoice_id', 'nx_tx_invoice_fk')->references('id')->on('nx_commerce_invoices')->nullOnDelete();
            $table->foreign('customer_id', 'nx_tx_customer_fk')->references('id')->on('nx_commerce_customers')->nullOnDelete();
            $table->foreign('currency', 'nx_tx_currency_fk')->references('code')->on('nx_commerce_currencies')->restrictOnDelete();
        });

        PortableNullableUnique::create('nx_commerce_payment_transactions', 'idempotency_key', 'nx_commerce_payment_idempotency_uq');

        Schema::create('nx_commerce_refunds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->nullable();
            $table->uuid('payment_transaction_id')->nullable();
            $table->string('provider_key', 160)->nullable()->index();
            $table->string('provider_reference', 255)->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->char('currency', 3)->index();
            $table->unsignedBigInteger('amount_minor');
            $table->string('reason', 255)->nullable();
            $table->string('idempotency_key', 180)->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->foreign('order_id', 'nx_refunds_order_fk')->references('id')->on('nx_commerce_orders')->nullOnDelete();
            $table->foreign('payment_transaction_id', 'nx_refunds_tx_fk')->references('id')->on('nx_commerce_payment_transactions')->nullOnDelete();
            $table->foreign('currency', 'nx_refunds_currency_fk')->references('code')->on('nx_commerce_currencies')->restrictOnDelete();
        });

        PortableNullableUnique::create('nx_commerce_refunds', 'idempotency_key', 'nx_commerce_refund_idempotency_uq');

        Schema::create('nx_commerce_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('product_id')->nullable();
            $table->uuid('price_id')->nullable();
            $table->string('provider_key', 160)->index();
            $table->string('provider_reference', 255)->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->char('currency', 3)->index();
            $table->unsignedBigInteger('amount_minor');
            $table->string('billing_interval', 32);
            $table->unsignedInteger('interval_count')->default(1);
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('customer_id', 'nx_subscriptions_customer_fk')->references('id')->on('nx_commerce_customers')->cascadeOnDelete();
            $table->foreign('product_id', 'nx_subscriptions_product_fk')->references('id')->on('nx_commerce_products')->nullOnDelete();
            $table->foreign('price_id', 'nx_subscriptions_price_fk')->references('id')->on('nx_commerce_prices')->nullOnDelete();
            $table->foreign('currency', 'nx_subscriptions_currency_fk')->references('code')->on('nx_commerce_currencies')->restrictOnDelete();
        });

        Schema::create('nx_commerce_billing_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_type', 180)->index();
            $table->string('resource_type', 80)->nullable()->index();
            $table->uuid('resource_id')->nullable()->index();
            $table->string('provider_key', 160)->nullable()->index();
            $table->string('provider_event_id', 255)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['provider_key', 'provider_event_id'], 'nx_billing_provider_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_commerce_billing_events');
        Schema::dropIfExists('nx_commerce_subscriptions');
        Schema::dropIfExists('nx_commerce_refunds');
        Schema::dropIfExists('nx_commerce_payment_transactions');
        Schema::dropIfExists('nx_commerce_payment_provider_configs');
        Schema::dropIfExists('nx_commerce_invoices');
        Schema::dropIfExists('nx_commerce_order_items');
        Schema::dropIfExists('nx_commerce_orders');
        Schema::dropIfExists('nx_commerce_customers');
        Schema::dropIfExists('nx_commerce_prices');
        Schema::dropIfExists('nx_commerce_products');
        Schema::dropIfExists('nx_commerce_tax_rates');
        Schema::dropIfExists('nx_commerce_currencies');
    }
};
