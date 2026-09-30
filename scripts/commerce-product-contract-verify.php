<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Commerce source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Commerce source file: {$relative}";
        return '';
    }
    return $contents;
};

$routes = $read('routes/web.php');
$billingRoutes = $read('routes/commerce.php');
$bootstrapProviders = $read('bootstrap/providers.php');
$commerceProvider = $read('app/Providers/CommerceServiceProvider.php');
$providerContract = $read('app/Nexora/Commerce/Contracts/PaymentProviderContract.php');
$providerBilling = $read('app/Nexora/Commerce/Services/ProviderBillingService.php');
$refundService = $read('app/Nexora/Commerce/Services/RefundService.php');
$billingController = $read('app/Http/Controllers/Admin/Commerce/BillingController.php');
$billingPage = $read('resources/js/admin/pages/Admin/Commerce/Billing.tsx');
$providerBillingTest = $read('tests/Feature/Commerce/ProviderBillingFlowTest.php');
$currency = $read('app/Nexora/Commerce/Services/CurrencyManager.php');
$taxes = $read('app/Nexora/Commerce/Services/TaxCalculator.php');
$orders = $read('app/Nexora/Commerce/Services/CommerceOrderService.php');
$invoices = $read('app/Nexora/Commerce/Services/InvoiceService.php');
$controller = $read('app/Http/Controllers/Admin/Commerce/OrderController.php');
$productController = $read('app/Http/Controllers/Admin/Commerce/ProductController.php');
$portableUnique = $read('app/Nexora/Foundation/Database/PortableNullableUnique.php');
$tenantIdentityMigration = $read('database/migrations/2026_08_21_000400_scope_commerce_product_identity_to_tenant.php');
$page = $read('resources/js/admin/pages/Admin/Commerce/Orders.tsx');
$test = $read('tests/Feature/Commerce/CommerceAdminFlowTest.php');

foreach ([
    "Route::post('/commerce/orders/{order}/place'" => 'order placement route',
    "permission:commerce.orders.manage" => 'order-management permission',
    "Route::post('/commerce/orders/{order}/invoice'" => 'invoice creation route',
    "permission:commerce.billing.manage" => 'billing-management permission',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) {
        $errors[] = "Commerce route contract missing: {$label}.";
    }
}

foreach ([
    "Route::post('/billing/invoices/{invoice}/payments'" => 'provider payment action route',
    "Route::post('/billing/transactions/{payment}/refunds'" => 'provider refund action route',
    "Route::post('/billing/subscriptions'" => 'provider subscription creation route',
    "Route::post('/billing/subscriptions/{subscription}/cancel'" => 'provider subscription cancellation route',
    "permission:commerce.billing.manage" => 'provider billing permission boundary',
    "EnsureTenantRouteBinding::class" => 'provider billing tenant route binding',
    "throttle:20,1" => 'provider billing action throttle',
] as $needle => $label) {
    if ($billingRoutes !== '' && ! str_contains($billingRoutes, $needle)) {
        $errors[] = "Commerce billing route contract missing: {$label}.";
    }
}

foreach ([
    'CommerceServiceProvider::class' => 'Commerce provider bootstrap registration',
] as $needle => $label) {
    if ($bootstrapProviders !== '' && ! str_contains($bootstrapProviders, $needle)) {
        $errors[] = "Commerce bootstrap contract missing: {$label}.";
    }
}
foreach ([
    'singleton(ProviderBillingService::class)' => 'provider billing singleton',
    "loadRoutesFrom(base_path('routes/commerce.php'))" => 'modular Commerce route loading',
] as $needle => $label) {
    if ($commerceProvider !== '' && ! str_contains($commerceProvider, $needle)) {
        $errors[] = "Commerce service provider missing: {$label}.";
    }
}

foreach ([
    "CAPABILITY_PAYMENTS = 'payments'" => 'payments capability constant',
    "CAPABILITY_REFUNDS = 'refunds'" => 'refunds capability constant',
    "CAPABILITY_SUBSCRIPTIONS = 'subscriptions'" => 'subscriptions capability constant',
    'createSubscription(SubscriptionRequest $request)' => 'provider subscription creation contract',
    'cancelSubscription(string $providerReference' => 'provider subscription cancellation contract',
] as $needle => $label) {
    if ($providerContract !== '' && ! str_contains($providerContract, $needle)) {
        $errors[] = "Commerce payment provider contract missing: {$label}.";
    }
}

foreach ([
    "CommercePaymentTransaction::query()->where('idempotency_key', \$idempotencyKey)->first()" => 'pre-provider payment retry short-circuit',
    "CommerceRefund::query()->where('idempotency_key', \$idempotencyKey)->first()" => 'pre-provider refund retry short-circuit',
    "where('metadata->provider_idempotency_key', \$idempotencyKey)" => 'pre-provider subscription retry short-circuit',
    "(\$metadata['last_cancel_idempotency_key'] ?? null) === \$idempotencyKey" => 'pre-provider subscription cancellation retry short-circuit',
    "CAPABILITY_PAYMENTS" => 'payment capability admission',
    "CAPABILITY_REFUNDS" => 'refund capability admission',
    "CAPABILITY_SUBSCRIPTIONS" => 'subscription capability admission',
    "->where('enabled', true)" => 'enabled-provider admission',
    "\$provider->health((array) \$config->configuration)" => 'live provider health admission',
    "Payment provider returned an inconsistent" => 'provider result consistency guard',
    "Refund amount exceeds the remaining refundable payment balance." => 'pre-provider refund balance guard',
    "Subscriptions require an active recurring product price." => 'recurring active-price subscription guard',
    "commerce.billing.invoice." => 'invoice provider-operation mutex',
    "commerce.billing.refund." => 'refund provider-operation mutex',
    "commerce.billing.subscription." => 'subscription provider-operation mutex',
    "commerce.subscription.cancel_failed" => 'failed cancellation audit event',
] as $needle => $label) {
    if ($providerBilling !== '' && ! str_contains($providerBilling, $needle)) {
        $errors[] = "Commerce provider billing service missing: {$label}.";
    }
}

foreach ([
    "'metadata' => \$metadata" => 'provider refund metadata persistence',
    "whereIn('status', ['succeeded', 'refunded'])" => 'successful-refund aggregate semantics',
] as $needle => $label) {
    if ($refundService !== '' && ! str_contains($refundService, $needle)) {
        $errors[] = "Commerce refund service missing: {$label}.";
    }
}

foreach ([
    "'providers' => \$availableProviders" => 'enabled provider UI projection',
    "'canManage' => \$request->user()?->hasPermission('commerce.billing.manage')" => 'billing-management UI authorization',
    "'subscriptionCustomers' => CommerceCustomer::query()" => 'tenant-scoped subscription customer projection',
    "'subscriptionPrices' => \$subscriptionPrices" => 'active recurring price projection',
    'public function collect(' => 'invoice payment controller action',
    'public function refund(' => 'payment refund controller action',
    'public function subscribe(' => 'subscription creation controller action',
    'public function cancelSubscription(' => 'subscription cancellation controller action',
    "new TenantExists('nx_commerce_customers')" => 'tenant customer validation',
    "TenantExists::through('nx_commerce_prices', 'nx_commerce_products', 'product_id')" => 'tenant recurring-price validation',
    'ProviderBillingService $billing' => 'provider orchestration dependency',
    "data_get(\$transaction->metadata, 'provider_successful'" => 'provider payment result feedback',
    "data_get(\$refund->metadata, 'provider_successful'" => 'provider refund result feedback',
    "data_get(\$subscription->metadata, 'provider_successful'" => 'provider subscription result feedback',
    "last_cancel_provider_result.provider_successful" => 'provider cancellation result feedback',
] as $needle => $label) {
    if ($billingController !== '' && ! str_contains($billingController, $needle)) {
        $errors[] = "Commerce billing controller missing: {$label}.";
    }
}

foreach ([
    'operationKey=()=>' => 'client billing idempotency key generation',
    'Collect payment' => 'invoice payment action UX',
    'Refund payment' => 'refund action UX',
    'Start subscription' => 'subscription creation UX',
    'Cancel subscription' => 'subscription cancellation UX',
    'provider.capabilities.includes("payments")' => 'payment capability UI filter',
    'provider.capabilities.includes("subscriptions")' => 'subscription capability UI filter',
    '/admin/commerce/billing/invoices/${paymentInvoice.id}/payments' => 'payment endpoint wiring',
    '/admin/commerce/billing/transactions/${refundPayment.id}/refunds' => 'refund endpoint wiring',
    '/admin/commerce/billing/subscriptions' => 'subscription endpoint wiring',
    '/admin/commerce/billing/subscriptions/${subscription.id}/cancel' => 'subscription cancellation endpoint wiring',
] as $needle => $label) {
    if ($billingPage !== '' && ! str_contains($billingPage, $needle)) {
        $errors[] = "Commerce Billing UI missing: {$label}.";
    }
}
if ($billingPage !== '' && preg_match('/<(button|input|select|textarea)\b/', $billingPage) === 1) {
    $errors[] = 'Commerce Billing UI must not bypass shared interactive components.';
}

foreach ([
    'test_enabled_provider_collects_and_refunds_with_retry_safe_idempotency' => 'provider payment/refund acceptance flow',
    'test_provider_subscription_create_and_cancel_are_retry_safe' => 'provider subscription create/cancel acceptance flow',
    'test_failed_subscription_cancel_is_recorded_without_state_corruption_or_duplicate_provider_call' => 'failed subscription cancellation regression',
    'test_disabled_provider_fails_before_external_payment_call' => 'disabled-provider fail-closed regression',
    'self::assertSame(1, $provider->paymentCalls)' => 'payment retry external-call assertion',
    'self::assertSame(1, $provider->refundCalls)' => 'refund retry external-call assertion',
    'self::assertSame(1, $provider->subscriptionCalls)' => 'subscription retry external-call assertion',
    'self::assertSame(1, $provider->cancelCalls)' => 'subscription cancellation retry external-call assertion',
    "'amount' => '20.00'" => 'over-refund rejection case',
    "self::assertSame('active', \$subscription->status)" => 'failed cancellation state preservation',
] as $needle => $label) {
    if ($providerBillingTest !== '' && ! str_contains($providerBillingTest, $needle)) {
        $errors[] = "Commerce provider billing acceptance contract missing: {$label}.";
    }
}

foreach ([
    'Amount exceeds the supported Commerce monetary range.' => 'fail-closed amount range',
    '(string) PHP_INT_MAX' => 'platform integer range comparison',
    "preg_match('/^\\d+(?:\\.\\d+)?$/'" => 'strict decimal parser',
] as $needle => $label) {
    if ($currency !== '' && ! str_contains($currency, $needle)) {
        $errors[] = "Commerce currency contract missing: {$label}.";
    }
}

foreach ([
    '$basisPoints < 0 || $basisPoints > 10_000' => 'bounded tax rate',
    'private function scaledRounded(' => 'overflow-safe scaled tax arithmetic',
    'PHP_INT_MAX - $subtotalMinor' => 'tax total overflow guard',
] as $needle => $label) {
    if ($taxes !== '' && ! str_contains($taxes, $needle)) {
        $errors[] = "Commerce tax contract missing: {$label}.";
    }
}

foreach ([
    "whereHas('product', fn (\$query) => \$query->where('status', 'active'))" => 'active product availability',
    "whereNull('starts_at')->orWhere('starts_at', '<=', \$now)" => 'price start window',
    "whereNull('ends_at')->orWhere('ends_at', '>', \$now)" => 'price end window',
    '$quantity < 1 || $quantity > 999' => 'service-level quantity bound',
    'intdiv(PHP_INT_MAX, $quantity)' => 'line-total overflow guard',
    'lockForUpdate()->firstOrFail()' => 'serialized order state transition',
    "if (\$locked->status !== 'draft')" => 'draft-only placement state machine',
    "if (! \$locked->items()->exists())" => 'empty-order placement guard',
] as $needle => $label) {
    if ($orders !== '' && ! str_contains($orders, $needle)) {
        $errors[] = "Commerce order service missing: {$label}.";
    }
}

foreach ([
    'lockForUpdate()->firstOrFail()' => 'serialized invoice creation',
    "whereNotIn('status', ['void'])" => 'active invoice reuse',
    "if (\$locked->status === 'cancelled')" => 'cancelled-order invoice guard',
] as $needle => $label) {
    if ($invoices !== '' && ! str_contains($invoices, $needle)) {
        $errors[] = "Commerce invoice service missing: {$label}.";
    }
}

foreach ([
    "'canBill' => \$request->user()?->hasPermission('commerce.billing.manage')" => 'billing-specific UI authorization',
    "'has_invoice' => (int) \$order->active_invoices_count > 0" => 'existing invoice UI state',
    "whereHas('product', fn (\$query) => \$query->where('status', 'active'))" => 'active price chooser filter',
    'catch (InvalidArgumentException|OverflowException $exception)' => 'safe draft-order domain errors',
] as $needle => $label) {
    if ($controller !== '' && ! str_contains($controller, $needle)) {
        $errors[] = "Commerce order controller missing: {$label}.";
    }
}

foreach ([
    "Rule::unique('nx_commerce_products', 'sku')->where" => 'tenant-scoped SKU validation',
    "Rule::unique('nx_commerce_products', 'slug')->where" => 'tenant-scoped slug validation',
    "where('tenant_id', \$tenantId)" => 'tenant identity validation predicate',
    "withErrors(['amount' => \$exception->getMessage()])" => 'field-level monetary validation error',
    'private function tenantId(): string' => 'tenant validation context resolver',
] as $needle => $label) {
    if ($productController !== '' && ! str_contains($productController, $needle)) {
        $errors[] = "Commerce product controller missing: {$label}.";
    }
}

foreach ([
    'public static function createScoped(' => 'portable scoped nullable unique API',
    "DB::connection()->getDriverName() === 'sqlsrv'" => 'SQL Server filtered-index branch',
    'WHERE {$quotedColumn} IS NOT NULL' => 'SQL Server nullable scoped filtering',
    '$blueprint->unique([$scopeColumn, $column], $indexName)' => 'portable composite unique fallback',
] as $needle => $label) {
    if ($portableUnique !== '' && ! str_contains($portableUnique, $needle)) {
        $errors[] = "Portable nullable unique helper missing: {$label}.";
    }
}

foreach ([
    "dropUnique(self::GLOBAL_SKU)" => 'legacy global SKU unique removal',
    "dropUnique(self::GLOBAL_SLUG)" => 'legacy global slug unique removal',
    "unique(['tenant_id', 'slug'], self::TENANT_SLUG)" => 'tenant-scoped slug unique index',
    "PortableNullableUnique::createScoped(self::TABLE, 'tenant_id', 'sku', self::TENANT_SKU)" => 'tenant-scoped nullable SKU unique index',
] as $needle => $label) {
    if ($tenantIdentityMigration !== '' && ! str_contains($tenantIdentityMigration, $needle)) {
        $errors[] = "Commerce tenant identity migration missing: {$label}.";
    }
}

foreach ([
    'canBill: boolean;' => 'billing permission prop',
    'canBill && row.status !== "cancelled"' => 'billing-authorized invoice action',
    'row.has_invoice ? "Billing" : "Invoice"' => 'idempotent invoice UX',
    'Choose an active product price' => 'active-price chooser semantics',
] as $needle => $label) {
    if ($page !== '' && ! str_contains($page, $needle)) {
        $errors[] = "Commerce Orders UI missing: {$label}.";
    }
}
if ($page !== '' && preg_match('/<(button|input|select|textarea)\b/', $page) === 1) {
    $errors[] = 'Commerce Orders UI must not bypass shared interactive components.';
}

foreach ([
    'test_archived_product_price_is_not_orderable_or_exposed_as_available' => 'inactive-product price regression',
    'test_order_place_and_invoice_transitions_are_idempotent' => 'order/invoice lifecycle regression',
    'test_catalog_rejects_amounts_outside_supported_integer_range' => 'monetary overflow regression',
    'test_product_sku_and_slug_are_unique_per_tenant_not_globally' => 'tenant product identity regression',
    "where('event_type', 'commerce.order.placed')" => 'single placement event assertion',
    "CommerceInvoice::query()->where('order_id'" => 'single invoice assertion',
    'expectException(QueryException::class)' => 'same-tenant duplicate DB rejection',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "Commerce acceptance-test contract missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Commerce Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Commerce Product Contract] PASS — monetary and tenant product identity are bounded, order/invoice lifecycles are serialized, and enabled healthy capability-scoped providers execute retry-safe payments, refunds and subscriptions through modular Commerce routes and shared Admin UI.'.PHP_EOL,
);
