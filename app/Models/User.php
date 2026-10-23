<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    /** @var array<int, string>|null */
    private ?array $resolvedPermissionSlugs = null;

    /** @var array<int, string>|null */
    private ?array $resolvedRoleSlugs = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'timezone',
        'locale',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_last_used_step',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_last_used_step' => 'integer',
        ];
    }

    /** @return HasMany<Passkey, $this> */
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'nx_user_roles')->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        if ($this->resolvedRoleSlugs === null) {
            $this->resolvedRoleSlugs = $this->roles()->pluck('slug')->all();
        }

        return in_array($slug, $this->resolvedRoleSlugs, true);
    }

    public function hasPermission(string $slug): bool
    {
        return $this->hasRole('super-admin') || in_array($slug, $this->permissionSlugs(), true);
    }

    /** @return array<int, string> */
    public function permissionSlugs(): array
    {
        if ($this->resolvedPermissionSlugs !== null) {
            return $this->resolvedPermissionSlugs;
        }

        if ($this->hasRole('super-admin')) {
            return $this->resolvedPermissionSlugs = Permission::query()->orderBy('slug')->pluck('slug')->all();
        }

        return $this->resolvedPermissionSlugs = $this->roles()
            ->with('permissions:id,slug')
            ->get()
            ->flatMap(static fn (Role $role) => $role->permissions->pluck('slug'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function enterpriseMemberships(): HasMany
    {
        return $this->hasMany(EnterpriseOrganizationMember::class, 'user_id');
    }

    public function enterpriseOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(EnterpriseOrganization::class, 'nx_enterprise_organization_members', 'user_id', 'organization_id')
            ->withPivot(['role', 'status', 'joined_at', 'last_active_at'])->withTimestamps();
    }

    public function canAccessAdmin(): bool
    {
        return $this->status !== 'suspended' && $this->hasPermission('admin.access');
    }
}
