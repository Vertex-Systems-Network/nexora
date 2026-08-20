<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()->with('roles:id,name,slug');
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'suspended'], true)) {
            $query->where('status', $status);
        }

        $sortable = ['name', 'email', 'status', 'last_login_at', 'created_at'];
        if (in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction)->orderByDesc('id');
        } else {
            $sort = '';
            $query->latest('id');
        }

        return Inertia::render('Admin/Users/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'users' => $query->paginate(15)->withQueryString()->through(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'verified' => $user->email_verified_at !== null,
                'roles' => $user->roles->map->only(['name', 'slug'])->values(),
                'lastLoginAt' => $user->last_login_at?->toIso8601String(),
                'createdAt' => $user->created_at?->toIso8601String(),
            ]),
            'can' => [
                'create' => $request->user()->hasPermission('users.create'),
                'update' => $request->user()->hasPermission('users.update'),
                'delete' => $request->user()->hasPermission('users.delete'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => null,
            'roles' => $this->roles(),
            'locales' => $this->locales(),
        ]);
    }

    public function store(Request $request, AuditManager $audit): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => $data['status'],
            'timezone' => $data['timezone'],
            'locale' => $data['locale'],
            'email_verified_at' => ($data['verified'] ?? false) ? now() : null,
        ]);

        $user->roles()->sync($data['roles'] ?? []);
        $audit->record('user.created', $user, ['roles' => $data['roles'] ?? []]);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): Response
    {
        $user->load('roles:id');

        return Inertia::render('Admin/Users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'timezone' => $user->timezone,
                'locale' => $user->locale,
                'verified' => $user->email_verified_at !== null,
                'roleIds' => $user->roles->pluck('id')->all(),
            ],
            'roles' => $this->roles(),
            'locales' => $this->locales(),
        ]);
    }

    public function update(Request $request, User $user, AuditManager $audit): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $superAdminRoleId = Role::query()->where('slug', 'super-admin')->value('id');
        $isFinalSuperAdmin = $user->hasRole('super-admin')
            && User::query()
                ->whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))
                ->count() <= 1;

        if (
            $isFinalSuperAdmin
            && $superAdminRoleId !== null
            && ! in_array((int) $superAdminRoleId, array_map('intval', $data['roles'] ?? []), true)
        ) {
            abort(422, 'The final Super Admin cannot be demoted.');
        }

        if ($isFinalSuperAdmin && $data['status'] === 'suspended') {
            abort(422, 'The final Super Admin cannot be suspended.');
        }

        if ($request->user()->is($user) && $data['status'] === 'suspended') {
            abort(422, 'You cannot suspend your own account.');
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
            'timezone' => $data['timezone'],
            'locale' => $data['locale'],
            'email_verified_at' => ($data['verified'] ?? false)
                ? ($user->email_verified_at ?? now())
                : null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->roles()->sync($data['roles'] ?? []);
        $audit->record('user.updated', $user, [
            'roles' => $data['roles'] ?? [],
            'status' => $user->status,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function bulk(Request $request, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:users,id'],
            'action' => ['required', Rule::in(['activate', 'suspend'])],
        ]);

        $users = User::query()->whereIn('id', $data['ids'])->get();
        $targetStatus = $data['action'] === 'activate' ? 'active' : 'suspended';
        $superAdminCount = User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))
            ->count();
        $finalSuperAdminId = $superAdminCount <= 1
            ? User::query()
                ->whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))
                ->value('id')
            : null;

        DB::transaction(function () use ($request, $audit, $users, $targetStatus, $finalSuperAdminId): void {
            foreach ($users as $user) {
                $protected = $targetStatus === 'suspended'
                    && ($request->user()->is($user) || $user->id === $finalSuperAdminId);

                if ($protected) {
                    continue;
                }

                $user->update(['status' => $targetStatus]);
                $audit->record('user.status_changed', $user, ['status' => $targetStatus]);
            }
        });

        return back()->with('success', 'Bulk user status update completed. Protected accounts were skipped.');
    }

    public function destroy(Request $request, User $user, AuditManager $audit): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot delete your own account.');

        $isFinalSuperAdmin = $user->hasRole('super-admin')
            && User::query()
                ->whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))
                ->count() <= 1;

        abort_if($isFinalSuperAdmin, 422, 'The final Super Admin cannot be deleted.');

        $audit->record('user.deleted', $user, ['email' => $user->email]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    /** @return array<int, array{id: int, name: string, slug: string}> */
    private function roles(): array
    {
        return Role::query()
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->toArray();
    }

    /** @return array<int,array{code:string,label:string,flag:string}> */
    private function locales(): array
    {
        $supported = (array) config('localization.supported', ['en' => ['native' => 'English']]);

        return array_map(static fn (array $meta, string $code): array => [
            'code' => $code,
            'label' => trim((string) ($meta['name'] ?? $meta['native'] ?? $code).((string) ($meta['country'] ?? '') !== '' ? ' — '.(string) $meta['country'] : '')),
            'flag' => (string) ($meta['flag'] ?? '🌐'),
            'flagUrl' => (string) ($meta['flag_asset'] ?? ''),
        ], $supported, array_keys($supported));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', 'string', Rule::in(array_keys((array) config('localization.supported', ['en' => []])))],
            'verified' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:nx_roles,id'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ]);
    }
}
