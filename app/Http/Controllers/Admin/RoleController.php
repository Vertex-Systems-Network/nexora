<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::query()
                ->withCount(['users', 'permissions'])
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'description', 'is_system']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Roles/Form', $this->formPayload(new Role()));
    }

    public function store(Request $request, AuditManager $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $role = Role::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);
        $role->permissions()->sync($data['permissions'] ?? []);
        $audit->record('role.created', $role, ['permissions' => $data['permissions'] ?? []]);

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions:id');
        return Inertia::render('Admin/Roles/Form', $this->formPayload($role));
    }

    public function update(Request $request, Role $role, AuditManager $audit): RedirectResponse
    {
        $data = $this->validated($request, $role);
        $role->update([
            'name' => $data['name'],
            'slug' => $role->is_system ? $role->slug : $data['slug'],
            'description' => $data['description'] ?? null,
        ]);
        $permissionIds = $role->slug === 'super-admin'
            ? Permission::query()->pluck('id')->all()
            : ($data['permissions'] ?? []);
        $role->permissions()->sync($permissionIds);
        $audit->record('role.updated', $role, ['permissions' => $permissionIds]);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role, AuditManager $audit): RedirectResponse
    {
        abort_if($role->is_system, 422, 'System roles cannot be deleted.');
        abort_if($role->users()->exists(), 422, 'Assign users to another role before deleting this role.');
        $audit->record('role.deleted', $role, ['name' => $role->name, 'slug' => $role->slug]);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }

    /** @return array<string, mixed> */
    private function formPayload(Role $role): array
    {
        return [
            'role' => $role->exists ? [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'isSystem' => $role->is_system,
                'permissionIds' => $role->relationLoaded('permissions') ? $role->permissions->pluck('id')->all() : [],
            ] : null,
            'permissions' => Permission::query()
                ->orderBy('group')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'group', 'description'])
                ->groupBy('group'),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('nx_roles', 'slug')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:nx_permissions,id'],
        ]);
    }
}
