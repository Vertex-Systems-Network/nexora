<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Http\Controllers\Controller;
use App\Models\CommerceInvoice;
use App\Models\CommercePaymentProviderConfig;
use App\Models\CommercePaymentTransaction;
use App\Models\CommerceRefund;
use App\Models\CommerceSubscription;
use App\Nexora\Commerce\Contracts\PaymentProviderContract;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Commerce\Services\PaymentProviderRegistry;
use App\Nexora\Commerce\Services\ProviderBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller
{
    public function index(Request $request, CurrencyManager $currencies, PaymentProviderRegistry $providers): Response
    {
        $enabledConfigs = CommercePaymentProviderConfig::query()
            ->where('enabled', true)
            ->get()
            ->keyBy('provider_key');
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
            ->withSum([
                'refunds as refunded_minor' => fn ($query) => $query->whereIn('status', ['succeeded', 'refunded']),
            ], 'amount_minor')
            ->latest()
            ->limit(25)
            ->get()
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
        $subscriptions = CommerceSubscription::query()->with(['customer:id,name', 'product:id,name'])->latest()->limit(25)->get()->map(fn (CommerceSubscription $subscription): array => [
            'id' => $subscription->id,
            'customer' => $subscription->customer?->name,
            'product' => $subscription->product?->name,
            'provider' => $subscription->provider_key,
            'status' => $subscription->status,
            'amount' => $currencies->format((int) $subscription->amount_minor, $subscription->currency),
            'interval' => $subscription->billing_interval,
            'period_end' => $subscription->current_period_end?->toIso8601String(),
        ]);

        return Inertia::render('Admin/Commerce/Billing', [
            'invoices' => $invoices,
            'transactions' => $transactions,
            'refunds' => $refunds,
            'subscriptions' => $subscriptions,
            'providers' => $availableProviders,
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
        return back()->with(
            $successful ? 'success' : 'error',
            'Payment provider returned '.str_replace(['_', '-'], ' ', $transaction->status).'.',
        );
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
            $refund = $billing->refundPayment(
                $payment,
                $amountMinor,
                $data['reason'] ?? null,
                $request->user()?->id,
                $data['idempotency_key'],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $successful = (bool) data_get($refund->metadata, 'provider_successful', false);
        return back()->with(
            $successful ? 'success' : 'error',
            'Refund provider returned '.str_replace(['_', '-'], ' ', $refund->status).'.',
        );
    }
}
