<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Http\Controllers\Controller;
use App\Models\CommerceCurrency;
use App\Models\CommerceCustomer;
use App\Models\CommerceOrder;
use App\Models\CommercePrice;
use App\Nexora\Commerce\Services\CommerceOrderService;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Commerce\Services\InvoiceService;
use App\Nexora\Enterprise\Validation\TenantExists;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use OverflowException;

final class OrderController extends Controller
{
    public function index(Request $request, CurrencyManager $currencies): Response
    {
        $orders = CommerceOrder::query()
            ->with('customer:id,name,email')
            ->withCount([
                'items',
                'invoices as active_invoices_count' => fn ($query) => $query->whereNotIn('status', ['void']),
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CommerceOrder $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status,
                'customer' => $order->customer?->name ?? 'Guest customer',
                'email' => $order->customer?->email,
                'items_count' => $order->items_count,
                'has_invoice' => (int) $order->active_invoices_count > 0,
                'currency' => $order->currency,
                'total' => $currencies->format((int) $order->total_minor, $order->currency),
                'paid' => $currencies->format((int) $order->paid_minor, $order->currency),
                'created_at' => $order->created_at?->toIso8601String(),
            ]);

        $now = now();
        $prices = CommercePrice::query()
            ->with('product:id,name,sku,status')
            ->where('active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->whereHas('product', fn ($query) => $query->where('status', 'active'))
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (CommercePrice $price) => [
                'id' => $price->id,
                'label' => ($price->product?->name ?? 'Product').' — '.$currencies->format((int) $price->amount_minor, $price->currency).($price->billing_interval ? ' / '.$price->billing_interval : ''),
                'currency' => $price->currency,
            ])
            ->values();

        return Inertia::render('Admin/Commerce/Orders', [
            'orders' => $orders,
            'customers' => CommerceCustomer::query()->orderBy('name')->limit(200)->get(['id', 'name', 'email']),
            'prices' => $prices,
            'currencies' => CommerceCurrency::query()->where('enabled', true)->orderByDesc('is_default')->orderBy('code')->get(['code', 'name']),
            'canManage' => $request->user()?->hasPermission('commerce.orders.manage') ?? false,
            'canBill' => $request->user()?->hasPermission('commerce.billing.manage') ?? false,
        ]);
    }

    public function store(Request $request, CommerceOrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'uuid', new TenantExists('nx_commerce_customers')],
            'currency' => ['required', 'string', 'size:3'],
            'price_id' => ['required', 'uuid', TenantExists::through('nx_commerce_prices', 'nx_commerce_products', 'product_id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);
        $customer = $data['customer_id'] ? CommerceCustomer::query()->findOrFail($data['customer_id']) : null;

        try {
            $order = $orders->createDraft($customer, $data['currency'], [[
                'price_id' => $data['price_id'],
                'quantity' => $data['quantity'],
            ]]);
        } catch (InvalidArgumentException|OverflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Draft order '.$order->number.' created.');
    }

    public function place(CommerceOrder $order, CommerceOrderService $orders): RedirectResponse
    {
        try {
            $orders->place($order);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Order placed and is awaiting payment.');
    }

    public function invoice(CommerceOrder $order, InvoiceService $invoices): RedirectResponse
    {
        try {
            $invoice = $invoices->createFromOrder($order);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.commerce.billing')->with('success', 'Invoice '.$invoice->number.' is ready.');
    }
}
