<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Membership;

use App\Http\Controllers\Controller;
use App\Models\CommercePrice;
use App\Models\MembershipEntitlement;
use App\Models\MembershipPlan;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Enterprise\Validation\TenantExists;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class MembershipPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $plans = MembershipPlan::query()
            ->withCount(['memberships', 'entitlements'])
            ->with('commercePrice.product:id,name')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (MembershipPlan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'status' => $plan->status,
                'duration_days' => $plan->duration_days,
                'memberships_count' => $plan->memberships_count,
                'entitlements_count' => $plan->entitlements_count,
                'commerce_price' => $plan->commercePrice?->product?->name
                    ? $plan->commercePrice->product->name.' · '.$plan->commercePrice->billing_interval
                    : null,
            ]);

        return Inertia::render('Admin/Membership/Plans', [
            'plans' => $plans,
            'prices' => CommercePrice::query()->with('product:id,name')->where('active', true)->orderBy('amount_minor')->get()->map(fn (CommercePrice $price): array => [
                'id' => $price->id,
                'name' => ($price->product?->name ?? 'Product').' · '.$price->currency.' '.$price->amount_minor.' minor'.($price->billing_interval ? ' / '.$price->billing_interval : ''),
            ]),
            'canManage' => $request->user()?->hasPermission('membership.plans.manage') ?? false,
        ]);
    }

    public function store(Request $request, TenantContext $tenant): RedirectResponse
    {
        $tenantId = $tenant->id();
        if ($tenantId === null) {
            throw ValidationException::withMessages(['tenant' => 'Select an organization before creating a membership plan.']);
        }

        $slug = trim((string) $request->input('slug', ''));
        if ($slug === '') {
            $slug = Str::slug((string) $request->input('name', '')).'-'.Str::lower(Str::random(5));
        }
        $request->merge(['slug' => $slug]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => [
                'required',
                'string',
                'max:190',
                'regex:/^[a-z0-9][a-z0-9-]*$/',
                Rule::unique('nx_membership_plans', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:active,archived'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'commerce_price_id' => [
                'nullable',
                'uuid',
                TenantExists::through('nx_commerce_prices', 'nx_commerce_products', 'product_id'),
                Rule::unique('nx_membership_plans', 'commerce_price_id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ]);

        MembershipPlan::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'duration_days' => $data['duration_days'] ?? null,
            'commerce_price_id' => $data['commerce_price_id'] ?? null,
            'metadata' => [],
        ]);

        return back()->with('success', 'Membership plan created.');
    }

    public function entitlement(Request $request, MembershipPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9][a-z0-9._-]+$/'],
            'label' => ['required', 'string', 'max:180'],
            'value_type' => ['required', 'in:boolean,integer,string'],
            'value' => ['nullable'],
            'active' => ['required', 'boolean'],
        ]);
        $value = match ($data['value_type']) {
            'boolean' => (bool) $data['value'],
            'integer' => (int) ($data['value'] ?? 0),
            default => (string) ($data['value'] ?? ''),
        };

        MembershipEntitlement::query()->updateOrCreate(
            ['plan_id' => $plan->id, 'key' => $data['key']],
            ['label' => $data['label'], 'value_type' => $data['value_type'], 'value' => $value, 'active' => $data['active']],
        );

        return back()->with('success', 'Entitlement saved.');
    }
}
