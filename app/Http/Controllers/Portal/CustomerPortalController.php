<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CommerceCustomer;
use App\Models\CommerceInvoice;
use App\Models\CommerceOrder;
use App\Models\CommerceSubscription;
use App\Models\Membership;
use App\Nexora\Commerce\Services\CurrencyManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerPortalController extends Controller
{
    public function __invoke(Request $request, CurrencyManager $currencies): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $customer = CommerceCustomer::query()
            ->where('user_id', $user->id)
            ->first();

        $memberships = Membership::query()
            ->with(['plan:id,name,slug,status', 'commerceSubscription:id,status,current_period_end'])
            ->where('user_id', $user->id)
            ->latest('started_at')
            ->limit(25)
            ->get()
            ->map(fn (Membership $membership): array => [
                'id' => $membership->id,
                'plan' => $membership->plan?->name ?? 'Membership',
                'plan_slug' => $membership->plan?->slug,
                'status' => $membership->status,
                'effective' => $membership->isEffective(),
                'started_at' => $membership->started_at?->toIso8601String(),
                'trial_ends_at' => $membership->trial_ends_at?->toIso8601String(),
                'ends_at' => $membership->ends_at?->toIso8601String(),
                'subscription_status' => $membership->commerceSubscription?->status,
                'subscription_period_end' => $membership->commerceSubscription?->current_period_end?->toIso8601String(),
            ])
            ->values();

        if ($customer === null) {
            return Inertia::render('Portal/Dashboard', [
                'customer' => null,
                'memberships' => $memberships,
                'orders' => [],
                'invoices' => [],
                'subscriptions' => [],
            ]);
        }

        $orders = CommerceOrder::query()
            ->where('customer_id', $customer->id)
            ->withCount('items')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (CommerceOrder $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status,
                'items_count' => (int) $order->items_count,
                'total' => $currencies->format((int) $order->total_minor, $order->currency),
                'currency' => $order->currency,
                'placed_at' => $order->placed_at?->toIso8601String(),
                'created_at' => $order->created_at?->toIso8601String(),
            ])
            ->values();

        $invoices = CommerceInvoice::query()
            ->where('customer_id', $customer->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (CommerceInvoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'total' => $currencies->format((int) $invoice->total_minor, $invoice->currency),
                'due' => $currencies->format((int) $invoice->amount_due_minor, $invoice->currency),
                'issued_at' => $invoice->issued_at?->toIso8601String(),
                'due_at' => $invoice->due_at?->toIso8601String(),
                'paid_at' => $invoice->paid_at?->toIso8601String(),
            ])
            ->values();

        $subscriptions = CommerceSubscription::query()
            ->with('product:id,name')
            ->where('customer_id', $customer->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (CommerceSubscription $subscription): array => [
                'id' => $subscription->id,
                'product' => $subscription->product?->name ?? 'Subscription',
                'provider' => $subscription->provider_key,
                'status' => $subscription->status,
                'amount' => $currencies->format((int) $subscription->amount_minor, $subscription->currency),
                'interval' => $subscription->billing_interval,
                'current_period_end' => $subscription->current_period_end?->toIso8601String(),
                'cancel_at_period_end' => (bool) $subscription->cancel_at_period_end,
            ])
            ->values();

        return Inertia::render('Portal/Dashboard', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
            'memberships' => $memberships,
            'orders' => $orders,
            'invoices' => $invoices,
            'subscriptions' => $subscriptions,
        ]);
    }
}
