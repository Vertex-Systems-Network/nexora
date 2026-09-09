<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Membership;

use App\Http\Controllers\Controller;
use App\Models\CommerceCustomer;
use App\Models\CommerceSubscription;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantMemberDirectory;
use App\Nexora\Enterprise\Validation\TenantExists;
use App\Nexora\Enterprise\Validation\TenantMemberExists;
use App\Nexora\Membership\Contracts\MembershipManagerContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class MembershipController extends Controller
{
    public function index(Request $request, TenantMemberDirectory $members): Response
    {
        $query = Membership::query()
            ->with(['plan:id,name', 'user:id,name,email', 'commerceCustomer:id,name,email', 'commerceSubscription:id,status'])
            ->latest();
        $status = (string) $request->query('status', '');
        if ($status !== '') {
            $query->where('status', $status);
        }

        return Inertia::render('Admin/Membership/Members', [
            'memberships' => $query->paginate(20)->withQueryString()->through(fn (Membership $membership): array => [
                'id' => $membership->id,
                'plan' => $membership->plan?->name,
                'member' => $membership->user?->name ?? $membership->commerceCustomer?->name ?? 'Unlinked',
                'email' => $membership->user?->email ?? $membership->commerceCustomer?->email,
                'status' => $membership->status,
                'commerce_subscription' => $membership->commerceSubscription?->status,
                'started_at' => $membership->started_at?->toIso8601String(),
                'ends_at' => $membership->ends_at?->toIso8601String(),
            ]),
            'plans' => MembershipPlan::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'users' => $members->options(true),
            'customers' => CommerceCustomer::query()->orderBy('name')->get(['id', 'name', 'email'])->map(fn (CommerceCustomer $customer): array => ['id' => $customer->id, 'name' => $customer->name.' · '.$customer->email]),
            'subscriptions' => CommerceSubscription::query()->latest()->limit(200)->get(['id', 'customer_id', 'status'])->map(fn (CommerceSubscription $subscription): array => ['id' => $subscription->id, 'name' => $subscription->status.' · '.substr($subscription->id, 0, 8)]),
            'filters' => ['status' => $status],
            'canManage' => $request->user()?->hasPermission('membership.members.manage') ?? false,
        ]);
    }

    public function store(Request $request, MembershipManagerContract $manager): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'uuid', new TenantExists('nx_membership_plans')],
            'user_id' => ['nullable', 'integer', new TenantMemberExists()],
            'commerce_customer_id' => ['nullable', 'uuid', new TenantExists('nx_commerce_customers')],
            'commerce_subscription_id' => ['nullable', 'uuid', new TenantExists('nx_commerce_subscriptions'), 'unique:nx_memberships,commerce_subscription_id'],
            'status' => ['required', 'in:trial,active,paused'],
            'started_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);
        if (empty($data['user_id']) && empty($data['commerce_customer_id'])) {
            throw ValidationException::withMessages(['user_id' => 'Choose a Nexora user or Commerce customer.']);
        }

        $plan = MembershipPlan::query()->findOrFail($data['plan_id']);
        $customer = ! empty($data['commerce_customer_id']) ? CommerceCustomer::query()->findOrFail($data['commerce_customer_id']) : null;
        $user = ! empty($data['user_id'])
            ? User::query()->findOrFail($data['user_id'])
            : ($customer?->user_id ? User::query()->find($customer->user_id) : null);

        try {
            $manager->grant($plan, $user, $data, $request->user()?->id);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['plan_id' => $exception->getMessage()]);
        }

        return back()->with('success', 'Membership granted.');
    }

    public function status(Request $request, Membership $membership, MembershipManagerContract $manager): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:trial,active,paused,cancelled,expired']]);
        $manager->setStatus($membership, $data['status'], $request->user()?->id);

        return back()->with('success', 'Membership status updated.');
    }
}
