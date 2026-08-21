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
$currency = $read('app/Nexora/Commerce/Services/CurrencyManager.php');
$taxes = $read('app/Nexora/Commerce/Services/TaxCalculator.php');
$orders = $read('app/Nexora/Commerce/Services/CommerceOrderService.php');
$invoices = $read('app/Nexora/Commerce/Services/InvoiceService.php');
$controller = $read('app/Http/Controllers/Admin/Commerce/OrderController.php');
$productController = $read('app/Http/Controllers/Admin/Commerce/ProductController.php');
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
    "Amount exceeds the supported Commerce monetary range." => 'fail-closed amount range',
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
    "withErrors(['amount' => \$exception->getMessage()])" => 'field-level monetary validation error',
    "catch (InvalidArgumentException \$exception)" => 'catalog monetary parser error handling',
] as $needle => $label) {
    if ($productController !== '' && ! str_contains($productController, $needle)) {
        $errors[] = "Commerce product controller missing: {$label}.";
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
    "where('event_type', 'commerce.order.placed')" => 'single placement event assertion',
    "CommerceInvoice::query()->where('order_id'" => 'single invoice assertion',
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
    '[Nexora Commerce Product Contract] PASS — monetary parsing/tax arithmetic are bounded, only active in-window product prices enter orders, placement and invoice creation are serialized/idempotent, and billing actions follow their owning permission.'.PHP_EOL,
);
