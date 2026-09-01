<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Http\Controllers\Controller;
use App\Models\CommerceCurrency;
use App\Models\CommercePrice;
use App\Models\CommerceProduct;
use App\Models\EnterpriseOrganization;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use RuntimeException;

final class ProductController extends Controller
{
    public function index(Request $request, CurrencyManager $currencies): Response
    {
        $products = CommerceProduct::query()
            ->with(['prices' => fn ($query) => $query->where('active', true)->orderBy('currency')])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CommerceProduct $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'type' => $product->type,
                'status' => $product->status,
                'prices' => $product->prices->map(fn (CommercePrice $price) => [
                    'id' => $price->id,
                    'currency' => $price->currency,
                    'amount' => $currencies->format((int) $price->amount_minor, $price->currency),
                    'billing_interval' => $price->billing_interval,
                ])->values(),
                'created_at' => $product->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Commerce/Products', [
            'products' => $products,
            'currencies' => CommerceCurrency::query()->where('enabled', true)->orderBy('code')->get(['code', 'name', 'symbol', 'minor_unit']),
            'canManage' => $request->user()?->hasPermission('commerce.catalog.manage') ?? false,
        ]);
    }

    public function store(Request $request, CurrencyManager $currencies): RedirectResponse
    {
        $tenantId = $this->tenantId();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['nullable', 'string', 'max:120', Rule::unique('nx_commerce_products', 'sku')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('nx_commerce_products', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'type' => ['required', Rule::in(['product', 'service', 'digital'])],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'description' => ['nullable', 'string', 'max:10000'],
            'tax_code' => ['nullable', 'string', 'max:80'],
            'currency' => ['required', 'string', 'size:3'],
            'amount' => ['required', 'string', 'max:40'],
            'billing_interval' => ['nullable', Rule::in(['monthly', 'yearly'])],
        ]);

        try {
            $currency = $currencies->ensureEnabled($data['currency'])->code;
            $amount = $currencies->toMinor($data['amount'], $currency);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['amount' => $exception->getMessage()])->withInput();
        }

        DB::transaction(function () use ($data, $currency, $amount): void {
            $product = CommerceProduct::query()->create([
                'name' => $data['name'],
                'sku' => $data['sku'] ?: null,
                'slug' => $data['slug'] ?: Str::slug($data['name']).'-'.strtolower(Str::random(6)),
                'type' => $data['type'],
                'status' => $data['status'],
                'description' => $data['description'] ?: null,
                'tax_code' => $data['tax_code'] ?: null,
                'published_at' => $data['status'] === 'active' ? now() : null,
            ]);
            CommercePrice::query()->create([
                'product_id' => $product->id,
                'currency' => $currency,
                'amount_minor' => $amount,
                'billing_interval' => $data['billing_interval'] ?: null,
                'active' => true,
            ]);
        });

        return back()->with('success', 'Product and initial price created.');
    }

    public function price(Request $request, CommerceProduct $product, CurrencyManager $currencies): RedirectResponse
    {
        $data = $request->validate([
            'currency' => ['required', 'string', 'size:3'],
            'amount' => ['required', 'string', 'max:40'],
            'billing_interval' => ['nullable', Rule::in(['monthly', 'yearly'])],
        ]);

        try {
            $currency = $currencies->ensureEnabled($data['currency'])->code;
            $amount = $currencies->toMinor($data['amount'], $currency);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['amount' => $exception->getMessage()])->withInput();
        }

        CommercePrice::query()->create([
            'product_id' => $product->id,
            'currency' => $currency,
            'amount_minor' => $amount,
            'billing_interval' => $data['billing_interval'] ?: null,
            'active' => true,
        ]);

        return back()->with('success', 'Price added.');
    }

    public function status(Request $request, CommerceProduct $product): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'active', 'archived'])]]);
        $product->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === 'active' ? ($product->published_at ?? now()) : $product->published_at,
        ]);

        return back()->with('success', 'Product status updated.');
    }

    private function tenantId(): string
    {
        $tenantId = app(TenantContext::class)->id()
            ?? EnterpriseOrganization::query()->where('is_default', true)->value('id');

        if (! is_string($tenantId) || $tenantId === '') {
            throw new RuntimeException('Commerce catalog requires an active organization context.');
        }

        return $tenantId;
    }
}
