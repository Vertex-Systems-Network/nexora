<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Http\Controllers\Controller;
use App\Models\CommerceCustomer;
use App\Models\CommerceInvoice;
use App\Models\CommercePaymentProviderConfig;
use App\Models\CommercePaymentTransaction;
use App\Models\CommercePrice;
use App\Models\CommerceRefund;
use App\Models\CommerceSubscription;
use App\Nexora\Commerce\Contracts\PaymentProviderContract;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Commerce\Services\PaymentProviderRegistry;
use App\Nexora\Commerce\Services\ProviderBillingService;
use App\Nexora\Enterprise\Validation\TenantExists;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller
{
    public function index(Request $request, CurrencyManager $currencies, PaymentProviderRegistry $providers): Response
    {
        $enabledConfigs = CommercePaymentProviderConfig::query()->where('enabled', true)->get()->keyBy('provider_key');
        $availableProviders = collect($providers->all())
            ->filter(fn ($provider, string $key): bool => $enabledConfigs->has($key))
            ->map(function ($provider, string $key) use ($enabledConfigs): array {
                $config = $enabledConfigs->get($key);
                return [
                    'key' => $key,
                    'label' => $provider->label(),
                    'capabilities' => $provider->capabilities(),
                    'health' => $config?->last_health_status,
                ];
            })
            ->values();
        $providerCapabilities = $availableProviders->keyBy('key');

        $invoices = CommerceInvoice::query()
            ->with('customer:id,name,email')
            ->latest()
            ->paginate(20, ['*'], 'invoices_page')
            ->withQueryString()
            ->through(fn (CommerceInvoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'customer' => $invoice->customer?->name ?? 'Guest customer',
                'total' => $currencies->format((int) $invoice->total_minor, $invoice->currency),
                'due' => $currencies->format((int) $invoice->amount_due_minor, $invoice->currency),
                'currency' => $invoice->currency,
                'can_collect' => ! in_array($invoice->status, ['void', 'paid'], true) && (int) $invoice->amount_due_minor > 0,
                'issued_at' => $invoice->issued_at?->toIso8601String(),
                'due_at' => $invoice->due_at?->toIso8601String(),
            ]);

        $transactions = CommercePaymentTransaction::query()
            ->withSum(['refunds as refunded_minor' => fn ($query) => $query->whereIn('status', ['succeeded', 'refunded'])], 'amount_minor')
            ->latest()->limit(25)->get()
            ->map(function (CommercePaymentTransaction $transaction) use ($currencies, $providerCapabilities): array {
                $refundedMinor = (int) ($transaction->refunded_minor ?? 0);
                $refundableMinor = max(0, (int) $transaction->amount_minor - $refundedMinor);
                $provider = $providerCapabilities->get($transaction->provider_key);
                $supportsRefunds = is_array($provider)
                    && in_array(PaymentProviderContract::CAPABILITY_REFUNDS, $provider['capabilities'] ?? [], true);

                return [
                    'id' => $transaction->id,
                    'provider' => $transaction->provider_key,
                    'type' => $transaction->type,
                    'status' => $transaction->status,
                    'amount' => $currencies->format((int) $transaction->amount_minor, $transaction->currency),
                    'refundable' => $currencies->format($refundableMinor, $transaction->currency),
                    'currency' => $transaction->currency,
                    'reference' => $transaction->provider_reference,
                    'can_refund' => $supportsRefunds
                        && $refundableMinor > 0
                        && $transaction->provider_reference !== null
                        && in_array($transaction->type, ['payment', 'capture'], true)
                        && in_array($transaction->status, ['succeeded', 'paid', 'captured'], true),
                    'processed_at' => $transaction->processed_at?->toIso8601String(),
                ];
            });

        $refunds = CommerceRefund::query()->latest()->limit(25)->get()->map(fn (CommerceRefund $refund): array => [
            'id' => $refund->id,
            'provider' => $refund->provider_key,
            'status' => $refund->status,
            'amount' => $currencies->format((int) $refund->amount_minor, $refund->currency),
            'reason' => $refund->reason,
            'created_at' => $refund->created_at?->toIso8601String(),
        ]);

        $subscriptions = CommerceSubscription::query()
            ->with(['customer:id,name', 'product:id,name'])
            ->latest()->limit(25)->get()
            ->map(function (CommerceSubscription $subscription) use ($currencies, $providerCapabilities): array {
                $provider = $providerCapabilities->get($subscription->provider_key);
                $supportsSubscriptions = is_array($provider)
                    && in_array(PaymentProviderContract::CAPABILITY_SUBSCRIPTIONS, $provider['capabilities'] ?? [], true);
                return [
                    'id' => $subscription->id,
                    'customer' => $subscription->customer?->name,
                    'product' => $subscription->product?->name,
                    'provider' => $subscription->provider_key,
                    'status' => $subscription->status,
                    'amount' => $currencies->format((int) $subscription->amount_minor, $subscription->currency),
                    'interval' => $subscription->billing_interval,
                    'period_end' => $subscription->current_period_end?->toIso8601String(),
                    'can_cancel' => $supportsSubscriptions
                        && $subscription->provider_reference !== null
                        && ! in_array($subscription->status, ['cancelled', 'canceled'], true),
                ];
            });

        $now = now();
        $subscriptionPrices = CommercePrice::query()
            ->with('product:id,name,sku')
            ->where('active', true)
            ->whereNotNull('billing_interval')
            ->whereHas('product', fn ($query) => $query->where('status', 'active'))
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->latest()->limit(200)->get()
            ->map(fn (CommercePrice $price): array => [
                'id' => $price->id,
                'label' => ($price->product?->name ?? 'Product').' — '.$currencies->format((int) $price->amount_minor, $price->currency).' / '.$price->billing_interval,
                'currency' => $price->currency,
            ])->values();

        return Inertia::render('Admin/Commerce/Billing', [
            'invoices' => $invoices,
            'transactions' => $transactions,
            'refunds' => $refunds,
            'subscriptions' => $subscriptions,
            'providers' => $availableProviders,
            'subscriptionCustomers' => CommerceCustomer::query()->orderBy('name')->limit(200)->get(['id', 'name', 'email']),
            'subscriptionPrices' => $subscriptionPrices,
            'canManage' => $request->user()?->hasPermission('commerce.billing.manage') ?? false,
        ]);
    }

    public function collect(Request $request, CommerceInvoice $invoice, ProviderBillingService $billing): RedirectResponse
    {
        $data = $request->validate([
            'provider_key' => ['required', 'string', 'max:160'],
            'idempotency_key' => ['required', 'string', 'max:180'],
        ]);
        try {
            $transaction = $billing->collectInvoice($invoice, $data['provider_key'], $data['idempotency_key']);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $successful = (bool) data_get($transaction->metadata, 'provider_successful', false);
        return back()->with($successful ? 'success' : 'error', 'Payment provider returned '.str_replace(['_', '-'], ' ', $transaction->status).'.');
    }

    public function refund(Request $request, CommercePaymentTransaction $payment, CurrencyManager $currencies, ProviderBillingService $billing): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'string', 'max:40'],
            'reason' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['required', 'string', 'max:180'],
        ]);
        try {
            $amountMinor = $currencies->toMinor($data['amount'], $payment->currency);
            $refund = $billing->refundPayment($payment, $amountMinor, $data['reason'] ?? null, $request->user()?->id, $data['idempotency_key']);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $successful = (bool) data_get($refund->metadata, 'provider_successful', false);
        return back()->with($successful ? 'success' : 'error', 'Refund provider returned '.str_replace(['_', '-'], ' ', $refund->status).'.');
    }

    public function subscribe(Request $request, ProviderBillingService $billing): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'uuid', new TenantExists('nx_commerce_customers')],
            'price_id' => ['required', 'uuid', TenantExists::through('nx_commerce_prices', 'nx_commerce_products', 'product_id')],
            'provider_key' => ['required', 'string', 'max:160'],
            'idempotency_key' => ['required', 'string', 'max:180'],
        ]);
        $customer = CommerceCustomer::query()->findOrFail($data['customer_id']);
        $price = CommercePrice::query()->findOrFail($data['price_id']);
        try {
            $subscription = $billing->createSubscription($customer, $price, $data['provider_key'], $data['idempotency_key']);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $successful = (bool) data_get($subscription->metadata, 'provider_successful', false);
        return back()->with($successful ? 'success' : 'error', 'Subscription provider returned '.str_replace(['_', '-'], ' ', $subscription->status).'.');
    }

    public function cancelSubscription(Request $request, CommerceSubscription $subscription, ProviderBillingService $billing): RedirectResponse
    {
        $data = $request->validate(['idempotency_key' => ['required', 'string', 'max:180']]);
        try {
            $updated = $billing->cancelSubscription($subscription, $data['idempotency_key']);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $successful = in_array($updated->status, ['cancelled', 'canceled'], true)
            || (bool) data_get($updated->metadata, 'last_cancel_provider_result.provider_successful', false);
        return back()->with($successful ? 'success' : 'error', 'Subscription cancellation is '.str_replace(['_', '-'], ' ', $updated->status).'.');
    }
}
